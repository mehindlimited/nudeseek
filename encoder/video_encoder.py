#!/usr/bin/env python3
import requests
import subprocess
import time
import os
import logging
from pathlib import Path
from typing import Optional, Dict, Any, List, Tuple
import json
from functools import wraps
import random
import urllib3

# ---- Local config loader (your project provides this) ----
#   - 'config' is an instance-like accessor
#   - 'Config' class allows reloading from a specific .env
from config import config

# Disable SSL warnings for local development
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# Optional S3 uploader helper (your project provides s3_uploader.py)
try:
    from s3_uploader import S3Uploader, create_s3_uploader
    S3_AVAILABLE = True
except ImportError:
    S3Uploader = None
    create_s3_uploader = None
    S3_AVAILABLE = False

# ---------------- Logging ----------------
def setup_logging():
    log_level = getattr(logging, config.get('LOG_LEVEL', 'INFO').upper(), logging.INFO)
    logging.basicConfig(
        level=log_level,
        format='%(asctime)s - %(levelname)s - %(message)s'
    )

setup_logging()
logger = logging.getLogger(__name__)

# --------------- Error classes ---------------
class RetryableError(Exception):
    """Exception that indicates an operation should be retried."""
    pass

class NonRetryableError(Exception):
    """Exception that indicates an operation should not be retried."""
    pass

# --------------- Retry decorator ---------------
def retry_on_exception(max_retries: int = 3, delay: float = 1.0, backoff: float = 2.0):
    """Decorator to retry functions on common transient errors."""
    def decorator(func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            last_exception = None
            for attempt in range(max_retries + 1):
                try:
                    return func(*args, **kwargs)
                except NonRetryableError:
                    raise
                except (RetryableError, requests.exceptions.RequestException, subprocess.TimeoutExpired) as e:
                    last_exception = e
                    if attempt < max_retries:
                        wait_time = delay * (backoff ** attempt) + random.uniform(0, 1)
                        logger.warning(f"{func.__name__}: attempt {attempt + 1} failed: {e}. Retrying in {wait_time:.2f}s...")
                        time.sleep(wait_time)
                    else:
                        logger.error(f"{func.__name__}: all {max_retries + 1} attempts failed.")
                        break
            raise last_exception
        return wrapper
    return decorator

# ======================= VideoEncoder =======================
class VideoEncoder:
    def __init__(self):
        # ---- env-driven config ----
        self.api_base_url = config.get('LARAVEL_API_URL', 'http://localhost:8000').rstrip('/')
        self.temp_dir = Path(config.get('TEMP_DIR', './temp'))
        self.temp_dir.mkdir(parents=True, exist_ok=True)
        self.min_thumbnails_required = config.get_int('MIN_THUMBNAILS', 1)  # default: require at least 1

        self.max_job_retries = config.get_int('MAX_RETRIES', 3)
        self.poll_interval = config.get_int('POLL_INTERVAL', 30)
        self.max_consecutive_errors = config.get_int('MAX_CONSECUTIVE_ERRORS', 5)
        self.min_disk_space_gb = config.get_float('MIN_DISK_SPACE_GB', 2.0)

        # SSL config
        self.verify_ssl = config.get_bool('VERIFY_SSL', True)
        self.ca_bundle = config.get('CA_BUNDLE_PATH')

        # HTTP session
        self.session = requests.Session()
        self.session.headers.update({
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'User-Agent': 'VideoEncoder/1.0'
        })
        if not self.verify_ssl:
            self.session.verify = False
            logger.warning("SSL certificate verification is DISABLED. Use only for local development!")
        elif self.ca_bundle and os.path.exists(self.ca_bundle):
            self.session.verify = self.ca_bundle
            logger.info(f"Using custom CA bundle: {self.ca_bundle}")

        # Optional S3 uploader (for uploads after encoding)
        self.s3_uploader: Optional[S3Uploader] = None
        if S3_AVAILABLE and create_s3_uploader:
            self.s3_uploader = create_s3_uploader()
            if self.s3_uploader:
                logger.info("S3 uploader initialized.")
            else:
                logger.warning("S3 uploader not configured or failed to initialize.")
        else:
            logger.info("S3 uploader not available (no boto3 or helper).")

        if not config.validate_required(['LARAVEL_API_URL']):
            raise ValueError("Missing required configuration (LARAVEL_API_URL). Check your .env.")

        logger.info("VideoEncoder initialized.")
        logger.info(f" API URL: {self.api_base_url}")
        logger.info(f" Temp Dir: {self.temp_dir}")

    # -------------------- API endpoints --------------------
    @retry_on_exception(max_retries=3, delay=2.0)
    def get_next_pending_job(self) -> Optional[Dict[str, Any]]:
        url = f"{self.api_base_url}/api/encoding-queue/next-pending"
        r = self.session.get(url, timeout=30)
        if r.status_code == 404:
            return None
        r.raise_for_status()
        return r.json()

    @retry_on_exception(max_retries=3, delay=1.0)
    def mark_as_processing(self, video_code: str) -> bool:
        url = f"{self.api_base_url}/api/encoding-queue/{video_code}/processing"
        r = self.session.post(url, json={}, timeout=30, allow_redirects=False)
        r.raise_for_status()
        return True

    @retry_on_exception(max_retries=3, delay=1.0)
    def mark_as_completed(self, video_code: str, output_path: str, thumbnail_paths: Optional[List[str]] = None) -> bool:
        url = f"{self.api_base_url}/api/encoding-queue/{video_code}/completed"
        payload: Dict[str, Any] = {"output_file_path": output_path}
        if thumbnail_paths:
            payload["thumbnail_paths"] = thumbnail_paths
        r = self.session.post(url, json=payload, timeout=30, allow_redirects=False)
        r.raise_for_status()
        return True

    @retry_on_exception(max_retries=3, delay=1.0)
    def mark_as_failed(self, video_code: str, error_message: str) -> bool:
        url = f"{self.api_base_url}/api/encoding-queue/{video_code}/failed"
        r = self.session.post(url, json={"error_message": error_message}, timeout=30, allow_redirects=False)
        r.raise_for_status()
        return True

    @retry_on_exception(max_retries=3, delay=1.0)
    def activate_video(self, video_code: str) -> bool:
        """
        POST /api/videos/{videoCode}/activate
        """
        url = f"{self.api_base_url}/api/videos/{video_code}/activate"
        r = self.session.post(url, json={}, timeout=30, allow_redirects=False)
        r.raise_for_status()
        logger.info(f"Video {video_code} activated.")
        return True

    @retry_on_exception(max_retries=3, delay=1.0)
    def update_metadata(self, video_code: str, duration: Optional[int], width: Optional[int], height: Optional[int]) -> bool:
        """
        POST /api/videos/{videoCode}/metadata
        Body: { "duration": int|null, "width": int|null, "height": int|null }
        """
        url = f"{self.api_base_url}/api/videos/{video_code}/metadata"
        payload = {"duration": duration, "width": width, "height": height}
        r = self.session.post(url, json=payload, timeout=30, allow_redirects=False)
        r.raise_for_status()
        logger.info(f"Metadata updated for {video_code}: duration={duration}, {width}x{height}")
        return True

    # ------------------ S3/HTTP download helpers ------------------
    def _parse_s3_url(self, url: str) -> Optional[Dict[str, str]]:
        """
        Parse S3 or S3-compatible URL.
        For custom endpoints (e.g., MojoCloud), we use configured bucket/endpoint.
        """
        import re
        configured_bucket = config.get('AWS_BUCKET') or config.get('S3_BUCKET')
        endpoint_url = config.get('AWS_ENDPOINT') or config.get('AWS_URL')
        if not configured_bucket and 'amazonaws.com' not in url.lower():
            return None

        patterns = [
            r'https://([^.]+)\.s3\.([^.]+)\.amazonaws\.com/(.+)',      # bucket.s3.region.amazonaws.com/key
            r'https://s3\.([^.]+)\.amazonaws\.com/([^/]+)/(.+)',       # s3.region.amazonaws.com/bucket/key
            r'https://[^/]+/(.+)',                                     # custom endpoint: domain/key
        ]
        for p in patterns:
            m = re.match(p, url)
            if not m:
                continue
            if 'amazonaws.com' in url:
                if p.startswith('https://s3.'):
                    return {'bucket': m.group(2), 'key': m.group(3), 'region': m.group(1), 'endpoint_url': None}
                return {'bucket': m.group(1), 'key': m.group(3), 'region': m.group(2), 'endpoint_url': None}
            # custom endpoint
            key = m.group(1)
            return {
                'bucket': configured_bucket,
                'key': key,
                'region': config.get('AWS_DEFAULT_REGION', 'us-east-1'),
                'endpoint_url': endpoint_url,
            }
        return None

    def _download_via_s3(self, s3_info: Dict[str, str], local_path: Path) -> bool:
        """Download via boto3 S3 client."""
        try:
            import boto3
            from botocore.exceptions import ClientError, NoCredentialsError
            from botocore.config import Config as BotoConfig

            access_key = config.get('AWS_ACCESS_KEY_ID')
            secret_key = config.get('AWS_SECRET_ACCESS_KEY')
            region = s3_info.get('region') or config.get('AWS_DEFAULT_REGION') or 'us-east-1'
            endpoint_url = s3_info.get('endpoint_url') or config.get('AWS_ENDPOINT') or config.get('AWS_URL')
            use_path_style = config.get_bool('AWS_USE_PATH_STYLE_ENDPOINT', False)

            if not access_key or not secret_key:
                logger.warning("Missing S3 credentials; cannot use S3 client.")
                return False

            client_kwargs: Dict[str, Any] = {
                'aws_access_key_id': access_key,
                'aws_secret_access_key': secret_key,
                'region_name': region,
                'config': BotoConfig(
                    s3={'addressing_style': 'path' if use_path_style else 'virtual'},
                    signature_version='s3v4',
                )
            }
            if endpoint_url:
                client_kwargs['endpoint_url'] = endpoint_url

            s3 = boto3.client('s3', **client_kwargs)
            logger.info(f"S3 head_object: s3://{s3_info['bucket']}/{s3_info['key']}")
            s3.head_object(Bucket=s3_info['bucket'], Key=s3_info['key'])

            logger.info(f"Downloading S3 -> {local_path}")
            s3.download_file(s3_info['bucket'], s3_info['key'], str(local_path))
            if not local_path.exists() or local_path.stat().st_size == 0:
                raise RetryableError("Downloaded file is empty.")
            return True

        except NoCredentialsError:
            logger.warning("No S3 credentials, fallback to HTTP.")
            return False
        except Exception as e:
            logger.warning(f"S3 download failed ({e}); fallback to HTTP.")
            return False

    @retry_on_exception(max_retries=2, delay=5.0)
    def download_file(self, url: str, local_path: Path) -> bool:
        """Download file via S3 when possible; fallback to HTTP."""
        logger.info(f"Downloading: {url}")

        # Try S3 if applicable
        s3_info = self._parse_s3_url(url)
        if s3_info:
            if self._download_via_s3(s3_info, local_path):
                return True

        # HTTP fallback
        headers: Dict[str, str] = {}
        if 'mojocloud.com' in url.lower():
            token = config.get('MOJOCLOUD_AUTH_TOKEN')
            access_key = config.get('MOJOCLOUD_ACCESS_KEY')
            if token:
                headers['Authorization'] = f"Bearer {token}"
            elif access_key:
                headers['Authorization'] = f"AccessKey {access_key}"

        r = self.session.get(url, stream=True, timeout=300, headers=headers)
        if r.status_code in (401, 403, 404):
            raise NonRetryableError(f"HTTP {r.status_code} for {url}")
        r.raise_for_status()

        # Ensure space
        if not self._check_disk_space(local_path.parent):
            raise RetryableError("Insufficient disk space.")

        with open(local_path, 'wb') as f:
            for chunk in r.iter_content(chunk_size=8192):
                if chunk:
                    f.write(chunk)

        if not local_path.exists() or local_path.stat().st_size == 0:
            raise RetryableError("Downloaded file is empty.")
        return True

    # -------------------- System helpers --------------------
    def _check_disk_space(self, path: Path, min_gb: Optional[float] = None) -> bool:
        min_gb = min_gb if min_gb is not None else self.min_disk_space_gb
        try:
            stat = os.statvfs(path)
            free_gb = (stat.f_bavail * stat.f_frsize) / (1024 ** 3)
            return free_gb >= min_gb
        except Exception:
            return True  # Best effort

    def _validate_video_file(self, video_path: Path) -> bool:
        try:
            cmd = ['ffprobe', '-v', 'quiet', '-print_format', 'json', '-show_format', '-show_streams', str(video_path)]
            res = subprocess.run(cmd, capture_output=True, text=True, timeout=30)
            if res.returncode != 0:
                return False
            data = json.loads(res.stdout)
            return any(s.get('codec_type') == 'video' for s in data.get('streams', []))
        except Exception:
            return False

    def _get_video_dimensions(self, video_path: Path) -> Tuple[Optional[int], Optional[int]]:
        try:
            cmd = ['ffprobe', '-v', 'quiet', '-print_format', 'json', '-show_streams', str(video_path)]
            res = subprocess.run(cmd, capture_output=True, text=True, timeout=30)
            if res.returncode != 0:
                return None, None
            data = json.loads(res.stdout)
            for s in data.get('streams', []):
                if s.get('codec_type') == 'video':
                    return s.get('width'), s.get('height')
            return None, None
        except Exception:
            return None, None

    def _probe_media_info(self, path: Path) -> Tuple[Optional[int], Optional[int], Optional[int]]:
        """
        Return (duration_seconds, width, height) using ffprobe, or (None, None, None) on failure.
        """
        try:
            cmd = ['ffprobe', '-v', 'quiet', '-print_format', 'json', '-show_format', '-show_streams', str(path)]
            res = subprocess.run(cmd, capture_output=True, text=True, timeout=30)
            if res.returncode != 0:
                return None, None, None
            data = json.loads(res.stdout)
            duration = data.get('format', {}).get('duration')
            duration_i = int(float(duration)) if duration else None

            width = height = None
            for s in data.get('streams', []):
                if s.get('codec_type') == 'video':
                    width = s.get('width') or width
                    height = s.get('height') or height
                    if width and height:
                        break
            return duration_i, width, height
        except Exception:
            return None, None, None

    # -------------------- Thumbnails --------------------
    def generate_thumbnails(
        self,
        input_path: Path,
        video_code: str,
        job_data: Dict[str, Any],
        num_thumbnails: int = 5,
    ) -> List[Path]:
        """
        Generate N thumbnails:
          * If 'uploaded_thumbnail_url' is given, make it thumb #1 (processed to match style)
          * Extract remaining from video at even intervals (10%..90%)
          * For vertical content, use blurred background composition
        """
        thumbnails: List[Path] = []
        try:
            # Probe video
            probe_cmd = ['ffprobe', '-v', 'quiet', '-print_format', 'json', '-show_format', '-show_streams', str(input_path)]
            probe = subprocess.run(probe_cmd, capture_output=True, text=True, timeout=30)
            if probe.returncode != 0:
                logger.warning("ffprobe failed; cannot generate thumbnails.")
                return thumbnails
            meta = json.loads(probe.stdout)
            duration = float(meta.get('format', {}).get('duration', 0) or 0)
            v_stream = next((s for s in meta.get('streams', []) if s.get('codec_type') == 'video'), None)
            if not duration or not v_stream:
                logger.warning("Missing duration or video stream; cannot generate thumbnails.")
                return thumbnails
            vw = v_stream.get('width')
            vh = v_stream.get('height')
            if not vw or not vh:
                logger.warning("Missing video dimensions; cannot generate thumbnails.")
                return thumbnails

            is_vertical = vh > vw

            # Filters
            fit_640x360_vf = (
                "scale=640:360:force_original_aspect_ratio=decrease,"
                "pad=640:360:(ow-iw)/2:(oh-ih)/2:black"
            )
            vertical_fc = (
                "[0:v]scale=640:360:force_original_aspect_ratio=increase,"
                "crop=640:360,boxblur=20:1[bg];"
                "[0:v]scale=-2:360:force_original_aspect_ratio=decrease[fg];"
                "[bg][fg]overlay=(W-w)/2:(H-h)/2[outv]"
            )

            # Uploaded thumbnail as #1 (processed to match style)
            uploaded_url = job_data.get('uploaded_thumbnail_url')
            if uploaded_url:
                try:
                    dl_src = self.temp_dir / f"{video_code}_uploaded_src.jpg"
                    if self.download_file(uploaded_url, dl_src):
                        first_thumb = self.temp_dir / f"{video_code}_thumb_1.jpg"
                        if is_vertical:
                            cmd = [
                                "ffmpeg", "-v", "error",
                                "-i", str(dl_src),
                                "-filter_complex", vertical_fc,
                                "-map", "[outv]",
                                "-frames:v", "1",
                                "-q:v", "2",
                                "-y", str(first_thumb),
                            ]
                        else:
                            cmd = [
                                "ffmpeg", "-v", "error",
                                "-i", str(dl_src),
                                "-vf", fit_640x360_vf,
                                "-frames:v", "1",
                                "-q:v", "2",
                                "-y", str(first_thumb),
                            ]
                        res = subprocess.run(cmd, capture_output=True, text=True, timeout=30)
                        if res.returncode == 0 and first_thumb.exists() and first_thumb.stat().st_size > 0:
                            thumbnails.append(first_thumb)
                            logger.info("Uploaded thumbnail processed as #1.")
                        else:
                            logger.warning(f"Failed to process uploaded thumbnail: {res.stderr}")
                    # cleanup
                    try:
                        dl_src.unlink(missing_ok=True)
                    except Exception:
                        pass
                except Exception as e:
                    logger.warning(f"Could not use uploaded thumbnail: {e}")

            # Remaining from video
            remaining = max(0, num_thumbnails - len(thumbnails))
            if remaining == 0:
                return thumbnails

            def time_at(idx: int, count: int) -> float:
                return duration * 0.5 if count == 1 else duration * (0.1 + 0.8 * (idx / (count - 1)))

            for i in range(remaining):
                t = time_at(i, remaining)
                index = len(thumbnails) + 1
                out_path = self.temp_dir / f"{video_code}_thumb_{index}.jpg"

                if is_vertical:
                    cmd = [
                        "ffmpeg", "-v", "error",
                        "-ss", f"{t:.3f}", "-i", str(input_path),
                        "-frames:v", "1",
                        "-filter_complex", vertical_fc,
                        "-map", "[outv]",
                        "-q:v", "2",
                        "-y", str(out_path),
                    ]
                else:
                    cmd = [
                        "ffmpeg", "-v", "error",
                        "-ss", f"{t:.3f}", "-i", str(input_path),
                        "-vframes", "1",
                        "-vf", fit_640x360_vf,
                        "-q:v", "2",
                        "-y", str(out_path),
                    ]

                res = subprocess.run(cmd, capture_output=True, text=True, timeout=60)
                if res.returncode == 0 and out_path.exists() and out_path.stat().st_size > 0:
                    thumbnails.append(out_path)
                    logger.info(f"Generated thumbnail #{index} at {t:.1f}s "
                                f"({'vertical blur' if is_vertical else 'letterbox'})")
                else:
                    logger.warning(f"Failed to generate thumbnail #{index}: {res.stderr}")

            return thumbnails

        except Exception as e:
            logger.error(f"Thumbnail generation failed: {e}")
            return thumbnails

    # -------------------- Encoding --------------------
    def encode_video(self, input_path: Path, output_path: Path, encoding_options: Dict[str, Any]) -> bool:
        """Encode video using ffmpeg (CRF, streaming-friendly)."""
        if not self._validate_video_file(input_path):
            raise NonRetryableError("Invalid or corrupt video file.")

        orig_w, orig_h = self._get_video_dimensions(input_path)
        if not orig_w or not orig_h:
            orig_w, orig_h = 1920, 1080
            logger.warning("Could not detect dimensions; defaulting to 1920x1080.")

        # Build command
        codec = encoding_options.get('codec', 'libx264')
        resolution = encoding_options.get('resolution', '1080p')
        max_w = 1920 if resolution == '1080p' else 1280 if resolution == '720p' else 854

        aspect = orig_w / max(1, orig_h)
        is_vertical = orig_h > orig_w

        if is_vertical:
            if orig_h > max_w:
                new_h = max_w
                new_w = int(new_h * aspect)
            else:
                new_w, new_h = orig_w, orig_h
        else:
            if orig_w > max_w:
                new_w = max_w
                new_h = int(new_w / aspect)
            else:
                new_w, new_h = orig_w, orig_h

        # ensure even
        new_w = new_w - (new_w % 2)
        new_h = new_h - (new_h % 2)

        crf = str(encoding_options.get('crf', 25))
        bitrate = encoding_options.get('bitrate', '2000k')
        bufsize_k = str(int(bitrate[:-1]) * 2) + 'k' if bitrate.endswith('k') else '4000k'

        cmd = [
            'ffmpeg', '-i', str(input_path), '-y',
            '-c:v', codec, '-preset', 'medium', '-crf', crf,
            '-vf', f'scale={new_w}:{new_h}',
            '-c:a', 'aac', '-b:a', '128k',
            '-maxrate', bitrate, '-bufsize', bufsize_k,
            '-movflags', '+faststart',
            str(output_path)
        ]

        logger.info(f"Encoding with: {' '.join(cmd)}")
        res = subprocess.run(cmd, capture_output=True, text=True, timeout=7200)
        if res.returncode != 0:
            err = res.stderr.lower()
            if 'no space left' in err or 'disk full' in err:
                raise RetryableError(res.stderr)
            if any(k in err for k in ['invalid data', 'corrupt', 'unsupported']):
                raise NonRetryableError(res.stderr)
            raise RetryableError(res.stderr)

        if not output_path.exists() or output_path.stat().st_size == 0:
            raise RetryableError("Output file missing or empty.")
        return True

    # -------------------- Misc helpers --------------------
    def cleanup_files(self, *file_paths: Path) -> None:
        for p in file_paths:
            try:
                if p and p.exists():
                    p.unlink()
                    logger.debug(f"Removed temp file: {p}")
            except Exception as e:
                logger.warning(f"Failed to remove {p}: {e}")

    def _categorize_error(self, error: Exception) -> str:
        s = str(error).lower()
        if isinstance(error, (requests.exceptions.ConnectionError, requests.exceptions.Timeout)):
            return 'network_error'
        if 'no space left' in s or 'disk full' in s:
            return 'temporary_disk_full'
        if 'timeout' in s:
            return 'ffmpeg_timeout'
        if 'memory' in s:
            return 'memory_error'
        if 'invalid' in s or 'corrupt' in s:
            return 'invalid_video_format'
        if 'unsupported' in s:
            return 'unsupported_codec'
        if 'download' in s:
            return 'download_failed'
        if 'upload' in s:
            return 'upload_failed'
        return 'unknown_error'

    # -------------------- Job processing --------------------
    def process_job(self, job: Dict[str, Any]) -> bool:
        """Download -> thumbnails -> encode -> metadata -> upload -> mark status -> activate."""
        video_code = job['video_code']
        retry_count = job.get('retry_count', 0)
        logger.info(f"Processing job {video_code} (attempt {retry_count + 1})")

        encoding_options = job.get('encoding_options', {}) or {}
        storage_config = encoding_options.get('storage_config', {}) or {}
        videos_path = storage_config.get('videos', 'videos')
        thumbnails_path = storage_config.get('thumbnails', 'thumbnails')
        origin_path = storage_config.get('origin', 'origin')
        first_char = storage_config.get('first_char', video_code[0].lower())

        input_file = self.temp_dir / f"{video_code}.mp4"
        output_file = self.temp_dir / f"{video_code}_encoded.mp4"
        thumbnail_files: List[Path] = []

        try:
            self.mark_as_processing(video_code)

            # 1) Download input
            input_url = job.get('input_file_url')
            if not input_url:
                raise NonRetryableError("Job missing input_file_url.")
            self.download_file(input_url, input_file)

            # 2) Thumbnails
            thumbnail_files = self.generate_thumbnails(input_file, video_code, job, num_thumbnails=5)

            # Enforce thumbnails presence
            if not thumbnail_files or len(thumbnail_files) < getattr(self, 'min_thumbnails_required', 1):
                need = getattr(self, 'min_thumbnails_required', 1)
                have = len(thumbnail_files)
                raise RetryableError(
                    f"Thumbnail generation failed or incomplete: required {need}, generated {have}."
                )

            # 3) Encode
            self.encode_video(input_file, output_file, encoding_options)

            # 4) Update metadata (duration/width/height from encoded file)
            try:
                meta_duration, meta_w, meta_h = self._probe_media_info(output_file)
                self.update_metadata(video_code, meta_duration, meta_w, meta_h)
            except Exception as e:
                # Non-blocking; log only. Make it blocking by raising RetryableError if desired.
                logger.warning(f"Failed to update metadata for {video_code}: {e}")

            # 5) Upload results (if S3 uploader available)
            encoded_s3_path = f"{videos_path}/{first_char}/{video_code}.mp4"
            origin_s3_path = f"{origin_path}/{first_char}/{video_code}.mp4"
            uploaded_thumb_paths: List[str] = []

            if self.s3_uploader:
                # Upload original (best-effort)
                self.s3_uploader.upload_with_retry(input_file, origin_s3_path, max_retries=2)

                # Upload encoded (required)
                if not self.s3_uploader.upload_with_retry(output_file, encoded_s3_path, max_retries=2):
                    raise RetryableError("Failed to upload encoded video.")

                # Upload thumbs
                for idx, thumb in enumerate(thumbnail_files):
                    dest = f"{thumbnails_path}/{first_char}/{video_code}_thumb_{idx + 1}.jpg"
                    if self.s3_uploader.upload_with_retry(thumb, dest, max_retries=2):
                        uploaded_thumb_paths.append(dest)
                    else:
                        logger.warning(f"Failed to upload thumbnail {thumb}")
            else:
                logger.warning("S3 uploader not configured; skipping uploads.")

            # 6) Mark completed
            self.mark_as_completed(video_code, encoded_s3_path, uploaded_thumb_paths)

            # 7) Activate the video
            try:
                self.activate_video(video_code)
            except Exception as e:
                # Optional: not fatal
                logger.warning(f"Activation failed for {video_code}: {e}")

            logger.info(f"Job {video_code} completed.")
            return True

        except NonRetryableError as e:
            logger.error(f"Job {video_code} failed (non-retryable): {e}")
            self.mark_as_failed(video_code, f"Non-retryable error: {e}")
            return False
        except Exception as e:
            logger.error(f"Job {video_code} failed: {e}")
            err_type = self._categorize_error(e)
            if retry_count < job.get('max_retries', self.max_job_retries) and err_type not in ['invalid_video_format', 'unsupported_codec']:
                self.mark_as_failed(video_code, f"Retryable error: {e}")
            else:
                self.mark_as_failed(video_code, f"Final failure: {e}")
            return False
        finally:
            self.cleanup_files(*([input_file, output_file] + thumbnail_files))

    # -------------------- Runner & health --------------------
    def run_continuously(self, poll_interval: Optional[int] = None):
        poll_interval = poll_interval or self.poll_interval
        logger.info("Encoder started.")
        consecutive_errors = 0
        while True:
            try:
                job = self.get_next_pending_job()
                if job:
                    ok = self.process_job(job)
                    consecutive_errors = 0 if ok else (consecutive_errors + 1)
                else:
                    logger.info("No pending jobs. Sleeping...")
                    consecutive_errors = 0
                    time.sleep(poll_interval)

                if consecutive_errors >= self.max_consecutive_errors:
                    backoff = min(300, poll_interval * (2 ** min(consecutive_errors, 5)))
                    logger.warning(f"Too many consecutive errors ({consecutive_errors}). Backing off {backoff}s.")
                    time.sleep(backoff)
                    consecutive_errors = 0
            except KeyboardInterrupt:
                logger.info("Encoder stopped by user.")
                break
            except Exception as e:
                logger.error(f"Main loop error: {e}")
                consecutive_errors += 1
                time.sleep(min(60, poll_interval * consecutive_errors))

    def health_check(self) -> Dict[str, Any]:
        status = {'status': 'healthy', 'issues': [], 'checks': {}}
        try:
            if not self._check_disk_space(self.temp_dir):
                status['issues'].append('Low disk space')
                status['status'] = 'warning'
            status['checks']['disk_space'] = 'ok'
        except Exception as e:
            status['issues'].append(f"Disk check error: {e}")
            status['checks']['disk_space'] = 'error'
        try:
            res = subprocess.run(['ffmpeg', '-version'], capture_output=True, text=True, timeout=10)
            status['checks']['ffmpeg'] = 'ok' if res.returncode == 0 else 'error'
        except Exception as e:
            status['checks']['ffmpeg'] = 'error'
            status['issues'].append(f"FFmpeg not available: {e}")
            status['status'] = 'unhealthy'
        try:
            r = self.session.get(f"{self.api_base_url}/api/encoding-queue/next-pending", timeout=10)
            status['checks']['api_connectivity'] = 'ok' if r.status_code in (200, 404) else 'warning'
        except Exception as e:
            status['checks']['api_connectivity'] = 'error'
            status['issues'].append(f"API unreachable: {e}")
            status['status'] = 'unhealthy'
        return status

# ======================= CLI entrypoint =======================
def main():
    import argparse
    parser = argparse.ArgumentParser(description='Video Encoder with .env configuration')
    parser.add_argument('--env-file', default='.env', help='Path to .env (default: ./.env)')
    parser.add_argument('--health-check', action='store_true', help='Run health check and exit')
    parser.add_argument('--show-config', action='store_true', help='Print current config and exit')
    parser.add_argument('--validate-config', action='store_true', help='Validate config and exit')
    parser.add_argument('--api-url', help='Override LARAVEL_API_URL')
    parser.add_argument('--temp-dir', help='Override TEMP_DIR')
    parser.add_argument('--poll-interval', type=int, help='Override POLL_INTERVAL')
    parser.add_argument('--max-retries', type=int, help='Override MAX_RETRIES')
    parser.add_argument('--log-level', choices=['DEBUG','INFO','WARNING','ERROR'], help='Override LOG_LEVEL')
    args = parser.parse_args()

    # Reload config from given env file
    from config import Config
    global config
    config = Config(args.env_file)

    # CLI overrides
    if args.api_url: os.environ['LARAVEL_API_URL'] = args.api_url
    if args.temp_dir: os.environ['TEMP_DIR'] = args.temp_dir
    if args.poll_interval is not None: os.environ['POLL_INTERVAL'] = str(args.poll_interval)
    if args.max_retries is not None: os.environ['MAX_RETRIES'] = str(args.max_retries)
    if args.log_level: os.environ['LOG_LEVEL'] = args.log_level
    if args.log_level:
        setup_logging()  # reconfigure log level immediately

    try:
        encoder = VideoEncoder()

        if args.show_config:
            config.print_config()
            return

        if args.validate_config:
            ok = config.validate_required(['LARAVEL_API_URL'])
            if ok:
                print("✅ Configuration is valid")
                if config.get('S3_BUCKET') or config.get('AWS_BUCKET'):
                    print("✅ S3 configuration found")
                else:
                    print("⚠️ No S3 configuration found (uploads will be skipped)")
                return
            else:
                print("❌ Configuration validation failed")
                raise SystemExit(1)

        if args.health_check:
            health = encoder.health_check()
            print(json.dumps(health, indent=2))
            raise SystemExit(0 if health['status'] == 'healthy' else 1)

        # Run worker loop
        encoder.run_continuously()

    except KeyboardInterrupt:
        logger.info("Encoder stopped gracefully")
    except Exception as e:
        logger.error(f"Fatal error: {e}")
        raise SystemExit(1)

if __name__ == "__main__":
    main()