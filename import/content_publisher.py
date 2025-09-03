#!/usr/bin/env python3
"""
Content Publisher Robot
Reads from crawler database and publishes content via Filament API
"""

import os
import sys
import logging
import requests
import pymysql
import boto3
from datetime import datetime, timezone
from typing import Optional, Dict, Any, List
import time
from botocore.exceptions import ClientError, NoCredentialsError
from urllib.parse import urlparse
import json

# Load environment variables from .env file
try:
    from dotenv import load_dotenv
    load_dotenv()
    print("✅ Loaded .env file successfully")
except ImportError:
    print("⚠️ python-dotenv not installed, using system environment variables only")
    print("Install with: pip install python-dotenv")
except Exception as e:
    print(f"⚠️ Warning: Could not load .env file: {e}")

# Configure logging
LOG_LEVEL = os.getenv('LOG_LEVEL', 'INFO').upper()
LOG_FILE = os.getenv('LOG_FILE', 'content_publisher.log')

logging.basicConfig(
    level=getattr(logging, LOG_LEVEL),
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(LOG_FILE),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

class ContentPublisherRobot:
    def __init__(self):
        """Initialize the robot with database and S3 configurations"""
        
        # Database configuration (loaded from .env file)
        self.db_config = {
            'host': os.getenv('DB_HOST', 'localhost'),
            'port': int(os.getenv('DB_PORT', '3306')),
            'user': os.getenv('DB_USER', 'crawler1'),
            'password': os.getenv('DB_PASSWORD', 'Novarano1234&8'),
            'database': os.getenv('DB_NAME', 'crawler1'),
            'charset': 'utf8mb4',
            'autocommit': True
        }
        
        logger.info(f"📊 Database config: host={self.db_config['host']}, port={self.db_config['port']}, user={self.db_config['user']}, database={self.db_config['database']}")
        
        # S3 Configuration (with env override support)
        self.s3_config = {
            'aws_access_key_id': os.getenv('AWS_ACCESS_KEY_ID', '4m7hpdpn1is8muenr76bxnhdo7m42l4b'),
            'aws_secret_access_key': os.getenv('AWS_SECRET_ACCESS_KEY', '2a0x7oml2fsa5rzbg5fjtyippmg42a5g'),
            'region_name': os.getenv('AWS_DEFAULT_REGION', 'us-north-3'),
            'endpoint_url': os.getenv('AWS_ENDPOINT', 'https://us-north-3.mojocloud.com')
        }
        
        self.bucket_name = os.getenv('AWS_BUCKET', 'nudeseek-com')
        self.s3_source_dir = 'sources'
        self.s3_temp_dir = 'temp'
        
        # Use a safer default temp directory
        default_temp = os.path.join(os.getcwd(), 'temp')
        self.local_temp_dir = os.getenv('LOCAL_TEMP_DIR', default_temp)
        
        # API Configuration - No token required
        self.api_base_url = os.getenv('API_BASE_URL', 'https://your-api-domain.com/api')
        
        # Processing configuration
        self.max_retries = int(os.getenv('MAX_RETRIES', '3'))
        
        # Initialize S3 client
        self.s3_client = self._init_s3_client()
        
        # Create local temp directory if it doesn't exist
        os.makedirs(self.local_temp_dir, exist_ok=True)
        
        logger.info("🚀 Content Publisher Robot initialized successfully")
        logger.info(f"🌐 API Base URL: {self.api_base_url}")
        logger.info(f"📁 Local temp directory: {self.local_temp_dir}")

    def _init_s3_client(self):
        """Initialize S3 client with custom endpoint"""
        try:
            client = boto3.client(
                's3',
                aws_access_key_id=self.s3_config['aws_access_key_id'],
                aws_secret_access_key=self.s3_config['aws_secret_access_key'],
                region_name=self.s3_config['region_name'],
                endpoint_url=self.s3_config['endpoint_url']
            )
            
            # Test connection
            client.list_buckets()
            logger.info("☁️ S3 client initialized successfully")
            return client
            
        except Exception as e:
            logger.error(f"❌ Failed to initialize S3 client: {e}")
            raise

    def get_database_connection(self):
        """Get database connection"""
        try:
            connection = pymysql.connect(**self.db_config)
            logger.info("🔗 Database connection established")
            return connection
        except Exception as e:
            logger.error(f"❌ Failed to connect to database: {e}")
            logger.error(f"Connection details: host={self.db_config['host']}, port={self.db_config['port']}, user={self.db_config['user']}")
            raise

    def get_next_video_to_process(self, connection) -> Optional[Dict[str, Any]]:
        """Get the first video that needs to be processed"""
        try:
            with connection.cursor(pymysql.cursors.DictCursor) as cursor:
                query = """
                    SELECT * FROM videos 
                    WHERE (imported_status IS NULL OR imported_status = 'no') 
                    AND download_status = 'completed'
                    ORDER BY crawled_at ASC
                    LIMIT 1
                """
                cursor.execute(query)
                result = cursor.fetchone()
                
                if result:
                    logger.info(f"🎥 Found video to process: {result['video_id']} - {result.get('title', 'No title')}")
                    return result
                else:
                    logger.info("📭 No videos found to process")
                    return None
                    
        except Exception as e:
            logger.error(f"❌ Error fetching video from database: {e}")
            return None

    def get_category_by_legacy(self, legacy_value: str) -> Optional[Dict[str, Any]]:
        """Get category information by legacy value from Filament API"""
        try:
            headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
            
            response = requests.get(
                f"{self.api_base_url}/categories/by-legacy/{legacy_value}",
                headers=headers,
                timeout=30
            )
            
            if response.status_code == 200:
                category_data = response.json()
                logger.info(f"🏷️ Found category for legacy '{legacy_value}': {category_data['id']} (target: {category_data['target_id']})")
                return category_data
            elif response.status_code == 404:
                logger.warning(f"⚠️ No category found for legacy value: {legacy_value}")
                return None
            else:
                logger.error(f"❌ Error fetching category: {response.status_code} - {response.text}")
                return None
                
        except Exception as e:
            logger.error(f"❌ Error fetching category by legacy: {e}")
            return None

    def get_random_user_for_target(self, target_id: Optional[int]) -> Optional[int]:
        """Get a random user based on target_id and sexual_orientation criteria"""
        try:
            headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
            
            # Default to target_id 1 if None provided
            if target_id is None:
                target_id = 1
            
            logger.info(f"👤 Getting random user for target_id: {target_id}")
            
            response = requests.get(
                f"{self.api_base_url}/users/random-for-target/{target_id}",
                headers=headers,
                timeout=30
            )
            
            if response.status_code == 200:
                user_data = response.json()
                user_id = user_data.get('id')
                username = user_data.get('username', 'Unknown')
                logger.info(f"✅ Selected random user {user_id} ({username}) for target {target_id}")
                return user_id
            elif response.status_code == 404:
                logger.warning(f"⚠️ No users found for target {target_id}")
                return None
            else:
                logger.error(f"❌ Error fetching random user: {response.status_code} - {response.text}")
                return None
                
        except Exception as e:
            logger.error(f"❌ Error fetching random user: {e}")
            return None

    def download_file_from_s3(self, s3_key: str, local_path: str) -> bool:
        """Download file from S3 to local path"""
        try:
            logger.info(f"⬇️ Downloading {s3_key} to {local_path}")
            self.s3_client.download_file(self.bucket_name, s3_key, local_path)
            
            # Verify file was downloaded
            if os.path.exists(local_path) and os.path.getsize(local_path) > 0:
                logger.info(f"✅ Successfully downloaded {s3_key} ({os.path.getsize(local_path)} bytes)")
                return True
            else:
                logger.error(f"❌ Downloaded file is empty or missing: {local_path}")
                return False
            
        except ClientError as e:
            error_code = e.response['Error']['Code']
            if error_code == 'NoSuchKey':
                logger.error(f"❌ File not found in S3: {s3_key}")
            elif error_code == 'AccessDenied':
                logger.error(f"❌ Access denied for S3 file: {s3_key}")
            else:
                logger.error(f"❌ S3 download error: {e}")
            return False
        except Exception as e:
            logger.error(f"❌ Error downloading file: {e}")
            return False

    def upload_file_to_s3(self, local_path: str, s3_key: str) -> bool:
        """Upload file from local path to S3"""
        try:
            if not os.path.exists(local_path):
                logger.error(f"❌ Local file not found: {local_path}")
                return False
            
            file_size = os.path.getsize(local_path)
            logger.info(f"⬆️ Uploading {local_path} ({file_size} bytes) to {s3_key}")
            
            self.s3_client.upload_file(local_path, self.bucket_name, s3_key)
            logger.info(f"✅ Successfully uploaded to {s3_key}")
            return True
            
        except Exception as e:
            logger.error(f"❌ Error uploading file: {e}")
            return False

    def generate_video_code(self) -> str:
        """Generate a unique video code"""
        import uuid
        return str(uuid.uuid4())[:12].upper()

    def process_video_files(self, video_data: Dict[str, Any], video_code: str) -> Dict[str, str]:
        """Download video files from S3 sources and upload to temp directory with proper naming"""
        file_paths = {}
        
        try:
            # Process video file
            if video_data.get('video_file'):
                video_extension = self._get_file_extension(video_data['video_file'], 'mp4')
                video_filename = f"{video_code}.{video_extension}"
                source_video_key = f"{self.s3_source_dir}/{video_data['video_file']}"
                temp_video_key = f"{self.s3_temp_dir}/{video_filename}"
                local_video_path = os.path.join(self.local_temp_dir, video_filename)
                
                logger.info(f"🎬 Processing video file: {video_data['video_file']} -> {video_filename}")
                
                # Download from sources directory
                if self.download_file_from_s3(source_video_key, local_video_path):
                    # Upload to temp directory with new name
                    if self.upload_file_to_s3(local_video_path, temp_video_key):
                        file_paths['video_file'] = video_filename
                        logger.info(f"✅ Video file processed successfully: {video_filename}")
                    else:
                        logger.error(f"❌ Failed to upload video file to temp: {video_filename}")
                    
                    # Clean up local file
                    self._cleanup_local_file(local_video_path)
                else:
                    logger.error(f"❌ Failed to download video file: {source_video_key}")
            
            # Process thumbnail file
            if video_data.get('thumbnail'):
                thumb_extension = self._get_file_extension(video_data['thumbnail'], 'jpg')
                thumb_filename = f"{video_code}_thumb.{thumb_extension}"
                source_thumb_key = f"{self.s3_source_dir}/{video_data['thumbnail']}"
                temp_thumb_key = f"{self.s3_temp_dir}/{thumb_filename}"
                local_thumb_path = os.path.join(self.local_temp_dir, thumb_filename)
                
                logger.info(f"🖼️ Processing thumbnail: {video_data['thumbnail']} -> {thumb_filename}")
                
                # Download from sources directory
                if self.download_file_from_s3(source_thumb_key, local_thumb_path):
                    # Upload to temp directory with new name
                    if self.upload_file_to_s3(local_thumb_path, temp_thumb_key):
                        file_paths['thumbnail'] = thumb_filename
                        logger.info(f"✅ Thumbnail processed successfully: {thumb_filename}")
                    else:
                        logger.error(f"❌ Failed to upload thumbnail to temp: {thumb_filename}")
                    
                    # Clean up local file
                    self._cleanup_local_file(local_thumb_path)
                else:
                    logger.warning(f"⚠️ Failed to download thumbnail (continuing anyway): {source_thumb_key}")
            
            return file_paths
            
        except Exception as e:
            logger.error(f"❌ Error processing video files: {e}")
            return {}

    def _get_file_extension(self, filename: str, default: str = 'mp4') -> str:
        """Extract file extension from filename"""
        try:
            if '.' in filename:
                return filename.split('.')[-1].lower()
            return default
        except:
            return default

    def _cleanup_local_file(self, file_path: str):
        """Safely remove local file"""
        try:
            if os.path.exists(file_path):
                os.remove(file_path)
                logger.debug(f"🧹 Cleaned up local file: {file_path}")
        except Exception as e:
            logger.warning(f"⚠️ Failed to cleanup local file {file_path}: {e}")

    def parse_tags(self, tags_string: str) -> List[str]:
        """Parse tags string into list"""
        if not tags_string:
            return []
        
        # Handle different separators
        separators = [',', ';', '|', '\n']
        tags = [tags_string]
        
        for sep in separators:
            new_tags = []
            for tag in tags:
                new_tags.extend(tag.split(sep))
            tags = new_tags
        
        # Clean and filter tags
        tags = [tag.strip() for tag in tags if tag.strip()]
        tags = [tag for tag in tags if len(tag) >= 2 and len(tag) <= 50]
        
        logger.debug(f"🏷️ Parsed tags: {tags}")
        return tags

    def publish_video_via_api(self, video_data: Dict[str, Any], category_data: Optional[Dict[str, Any]], 
                            video_code: str) -> Optional[Dict[str, Any]]:
        """Publish video via Filament API"""
        try:
            # Prepare tags
            tags = self.parse_tags(video_data.get('tags', ''))
            
            # Get target_id from category or default to 1
            target_id = category_data['target_id'] if category_data else 1
            
            # Get random user based on target_id
            user_id = self.get_random_user_for_target(target_id)
            if not user_id:
                logger.error(f"❌ Could not find suitable user for target_id {target_id}")
                return None
            
            # Prepare API payload
            api_payload = {
                'title': video_data.get('title', '').strip() or f'Video {video_code}',
                'description': video_data.get('description', ''),
                'published_at': datetime.now(timezone.utc).isoformat(),
                'access_type': 'public',
                'user_id': user_id,
                'target_id': target_id,
                'category_id': category_data['id'] if category_data else None,
                'tags': tags
            }
            
            # Remove None values
            api_payload = {k: v for k, v in api_payload.items() if v is not None}
            
            headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
            
            logger.info(f"📤 Publishing video via API: '{api_payload['title']}' (code: {video_code})")
            
            response = requests.post(
                f"{self.api_base_url}/videos",
                headers=headers,
                json=api_payload,
                timeout=60
            )
            
            if response.status_code in [200, 201]:
                result = response.json()
                if result.get('success'):
                    video_data_response = result.get('data', result)
                    logger.info(f"✅ Video published successfully: {video_data_response.get('id', 'Unknown ID')}")
                    return video_data_response
                else:
                    logger.error(f"❌ API returned success=false: {result}")
                    return None
            else:
                logger.error(f"❌ API error: {response.status_code} - {response.text}")
                return None
                
        except Exception as e:
            logger.error(f"❌ Error publishing video via API: {e}")
            return None

    def trigger_after_create(self, video_code: str) -> bool:
        """Trigger the afterCreate functionality for video encoding using videoCode"""
        try:
            headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
            
            payload = {
                'video_code': video_code
            }
            
            logger.info(f"🎬 Triggering afterCreate for video {video_code}")
            
            response = requests.post(
                f"{self.api_base_url}/videos/{video_code}/after-create",
                headers=headers,
                json=payload,
                timeout=120
            )
            
            if response.status_code in [200, 201]:
                result = response.json()
                if result.get('success'):
                    logger.info(f"✅ AfterCreate triggered successfully for {video_code}")
                    return True
                else:
                    logger.error(f"❌ AfterCreate returned success=false: {result}")
                    return False
            else:
                logger.error(f"❌ AfterCreate API error: {response.status_code} - {response.text}")
                return False
                
        except Exception as e:
            logger.error(f"❌ Error triggering afterCreate: {e}")
            return False

    def mark_video_as_imported(self, connection, video_id: str) -> bool:
        """Mark video as imported in the database"""
        try:
            with connection.cursor() as cursor:
                query = "UPDATE videos SET imported_status = 'yes' WHERE video_id = %s"
                cursor.execute(query, (video_id,))
                connection.commit()
                logger.info(f"✅ Video {video_id} marked as imported")
                return True
                
        except Exception as e:
            logger.error(f"❌ Error marking video as imported: {e}")
            return False

    def process_single_video(self) -> bool:
        """Process a single video from database to API"""
        connection = None
        
        try:
            # Get database connection
            connection = self.get_database_connection()
            
            # Get next video to process
            video_data = self.get_next_video_to_process(connection)
            if not video_data:
                return False
            
            video_id = video_data['video_id']
            logger.info(f"🎯 Processing video: {video_id} - '{video_data.get('title', 'No title')}'")
            
            # Generate video code
            video_code = self.generate_video_code()
            logger.info(f"🆔 Generated video code: {video_code}")
            
            # Get category information
            category_data = None
            if video_data.get('categories'):
                category_data = self.get_category_by_legacy(video_data['categories'])
                if not category_data:
                    logger.warning(f"⚠️ No category found for legacy '{video_data['categories']}', using default target")
            
            # Process video files (download from sources, upload to temp)
            file_paths = self.process_video_files(video_data, video_code)
            
            if not file_paths.get('video_file'):
                logger.error(f"❌ Failed to process video file for {video_id}")
                return False
            
            # Publish video via API
            published_video = self.publish_video_via_api(video_data, category_data, video_code)
            if published_video:
                # Trigger afterCreate functionality using video_code
                after_create_success = self.trigger_after_create(video_code)
                
                if after_create_success:
                    logger.info(f"✅ AfterCreate triggered successfully for video {video_code}")
                else:
                    logger.warning(f"⚠️ AfterCreate failed for video {video_code}, but video was created")
                
                # Mark as imported regardless of afterCreate success
                if self.mark_video_as_imported(connection, video_id):
                    logger.info(f"🎉 Successfully processed video {video_id}")
                    return True
                else:
                    logger.error(f"❌ Failed to mark video as imported: {video_id}")
                    return False
            else:
                logger.error(f"❌ Failed to publish video: {video_id}")
                return False
                
        except Exception as e:
            logger.error(f"❌ Error processing video: {e}")
            return False
            
        finally:
            if connection:
                connection.close()

    def run_continuous(self, delay_seconds: int = 60):
        """Run the robot continuously"""
        delay_seconds = int(os.getenv('DEFAULT_DELAY', delay_seconds))
        logger.info(f"🔄 Starting Content Publisher Robot in continuous mode (delay: {delay_seconds}s)")
        
        consecutive_failures = 0
        max_consecutive_failures = 5
        
        while True:
            try:
                processed = self.process_single_video()
                
                if processed:
                    logger.info(f"✅ Video processed successfully. Waiting {delay_seconds} seconds...")
                    consecutive_failures = 0
                else:
                    consecutive_failures += 1
                    logger.info(f"📭 No videos processed. Waiting {delay_seconds} seconds... (consecutive failures: {consecutive_failures})")
                
                # If too many consecutive failures, increase delay
                if consecutive_failures >= max_consecutive_failures:
                    extended_delay = delay_seconds * 2
                    logger.warning(f"⚠️ Too many consecutive failures, extending delay to {extended_delay}s")
                    time.sleep(extended_delay)
                    consecutive_failures = 0
                else:
                    time.sleep(delay_seconds)
                
            except KeyboardInterrupt:
                logger.info("🛑 Robot stopped by user")
                break
            except Exception as e:
                logger.error(f"❌ Unexpected error in main loop: {e}")
                consecutive_failures += 1
                time.sleep(delay_seconds)

    def run_batch(self, max_videos: int = 10):
        """Run the robot for a batch of videos"""
        max_videos = int(os.getenv('DEFAULT_BATCH_SIZE', max_videos))
        logger.info(f"📦 Starting Content Publisher Robot in batch mode (max {max_videos} videos)")
        
        processed_count = 0
        failed_count = 0
        
        while processed_count < max_videos:
            try:
                if self.process_single_video():
                    processed_count += 1
                    logger.info(f"📊 Processed {processed_count}/{max_videos} videos")
                else:
                    failed_count += 1
                    logger.info(f"📭 No videos to process (attempt {failed_count})")
                    
                    # If no videos found multiple times, break
                    if failed_count >= 3:
                        logger.info("✨ No more videos to process")
                        break
                    
                    # Short delay between attempts
                    time.sleep(5)
                    
            except Exception as e:
                logger.error(f"❌ Error in batch processing: {e}")
                break
        
        logger.info(f"🎉 Batch processing completed. Processed {processed_count} videos")

    def get_status(self) -> Dict[str, Any]:
        """Get current status of the robot and pending videos"""
        try:
            connection = self.get_database_connection()
            
            with connection.cursor(pymysql.cursors.DictCursor) as cursor:
                # Count pending videos
                cursor.execute("""
                    SELECT COUNT(*) as pending_count
                    FROM videos 
                    WHERE (imported_status IS NULL OR imported_status = 'no') 
                    AND download_status = 'completed'
                """)
                pending = cursor.fetchone()
                
                # Count total completed downloads
                cursor.execute("""
                    SELECT COUNT(*) as completed_count
                    FROM videos 
                    WHERE download_status = 'completed'
                """)
                completed = cursor.fetchone()
                
                # Count imported videos
                cursor.execute("""
                    SELECT COUNT(*) as imported_count
                    FROM videos 
                    WHERE imported_status = 'yes'
                """)
                imported = cursor.fetchone()
            
            connection.close()
            
            return {
                'pending_videos': pending['pending_count'],
                'completed_downloads': completed['completed_count'],
                'imported_videos': imported['imported_count'],
                'api_base_url': self.api_base_url,
                'bucket_name': self.bucket_name,
                'status': 'healthy'
            }
            
        except Exception as e:
            logger.error(f"❌ Error getting status: {e}")
            return {
                'status': 'error',
                'error': str(e)
            }

def main():
    """Main function"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Content Publisher Robot')
    parser.add_argument('--mode', choices=['single', 'batch', 'continuous', 'status'], 
                       default='single', help='Running mode')
    parser.add_argument('--batch-size', type=int, 
                       default=int(os.getenv('DEFAULT_BATCH_SIZE', '10')), 
                       help='Number of videos to process in batch mode')
    parser.add_argument('--delay', type=int, 
                       default=int(os.getenv('DEFAULT_DELAY', '60')), 
                       help='Delay between processing in continuous mode (seconds)')
    
    args = parser.parse_args()
    
    # Initialize robot
    try:
        robot = ContentPublisherRobot()
    except Exception as e:
        logger.error(f"❌ Failed to initialize robot: {e}")
        sys.exit(1)
    
    try:
        if args.mode == 'single':
            success = robot.process_single_video()
            sys.exit(0 if success else 1)
        elif args.mode == 'batch':
            robot.run_batch(args.batch_size)
        elif args.mode == 'continuous':
            robot.run_continuous(args.delay)
        elif args.mode == 'status':
            status = robot.get_status()
            print(json.dumps(status, indent=2))
            
    except Exception as e:
        logger.error(f"❌ Fatal error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()