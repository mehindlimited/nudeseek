import boto3
import logging
from pathlib import Path
from typing import Optional
from botocore.exceptions import ClientError, NoCredentialsError
from botocore.config import Config as BotocoreConfig
from config import config

logger = logging.getLogger(__name__)

class S3Uploader:
    def __init__(self, bucket_name: Optional[str] = None, region_name: Optional[str] = None, 
                 aws_access_key_id: Optional[str] = None, 
                 aws_secret_access_key: Optional[str] = None,
                 endpoint_url: Optional[str] = None):
        # Use provided values or fall back to .env configuration
        self.bucket_name = bucket_name or config.get('AWS_BUCKET') or config.get('S3_BUCKET')
        self.region_name = region_name or config.get('AWS_DEFAULT_REGION', 'us-east-1')
        self.endpoint_url = endpoint_url or config.get('AWS_ENDPOINT') or config.get('AWS_URL')
        
        # Get AWS credentials from .env if not provided
        access_key = aws_access_key_id or config.get('AWS_ACCESS_KEY_ID')
        secret_key = aws_secret_access_key or config.get('AWS_SECRET_ACCESS_KEY')
        
        if not self.bucket_name:
            raise ValueError("S3 bucket name is required. Set AWS_BUCKET or S3_BUCKET in .env file or pass bucket_name parameter.")
        
        # Configure boto3 for S3-compatible services
        session_kwargs = {
            'region_name': self.region_name
        }
        
        # Only set credentials if they're provided (allows IAM roles/environment to work)
        if access_key and secret_key:
            session_kwargs.update({
                'aws_access_key_id': access_key,
                'aws_secret_access_key': secret_key
            })
        
        try:
            session = boto3.Session(**session_kwargs)
            
            # S3 client configuration for custom endpoints
            s3_config = {}
            
            # Handle path-style endpoints (common for S3-compatible services)
            use_path_style = config.get_bool('AWS_USE_PATH_STYLE_ENDPOINT', True if self.endpoint_url else False)
            if use_path_style:
                s3_config['addressing_style'] = 'path'
            
            # Configure signature version if needed
            s3_config['signature_version'] = 's3v4'
            
            client_kwargs = {}
            if self.endpoint_url:
                client_kwargs['endpoint_url'] = self.endpoint_url
                logger.info(f"Using custom S3 endpoint: {self.endpoint_url}")
            
            if s3_config:
                client_kwargs['config'] = BotocoreConfig(**s3_config)
            
            self.s3_client = session.client('s3', **client_kwargs)
            
            # Test connection by checking if bucket is accessible
            try:
                self.s3_client.head_bucket(Bucket=self.bucket_name)
                logger.info(f"S3 uploader initialized for bucket: {self.bucket_name}")
                if self.endpoint_url:
                    logger.info(f"Using endpoint: {self.endpoint_url}")
                logger.info(f"Region: {self.region_name}")
            except ClientError as e:
                error_code = e.response['Error']['Code']
                if error_code == '404':
                    raise ValueError(f"S3 bucket '{self.bucket_name}' does not exist")
                elif error_code == '403':
                    raise ValueError(f"Access denied to S3 bucket '{self.bucket_name}'. Check your credentials and permissions.")
                else:
                    # Log the error but don't fail initialization - bucket might exist but have different permissions
                    logger.warning(f"Could not verify bucket access: {e}")
            
        except NoCredentialsError:
            raise ValueError("AWS credentials not found. Set AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY in .env file.")
        except Exception as e:
            raise ValueError(f"Failed to initialize S3 client: {e}")
    
    @classmethod
    def from_env(cls):
        """Create S3Uploader instance using only .env configuration"""
        return cls()
    
    def upload_file(self, local_file_path: Path, s3_key: str, 
                   content_type: Optional[str] = None) -> bool:
        """Upload a file to S3"""
        try:
            # Determine content type if not provided
            if not content_type:
                if s3_key.endswith('.mp4'):
                    content_type = 'video/mp4'
                elif s3_key.endswith('.jpg') or s3_key.endswith('.jpeg'):
                    content_type = 'image/jpeg'
                else:
                    content_type = 'application/octet-stream'
            
            extra_args = {'ContentType': content_type}
            
            # For video files, add metadata for better streaming
            if content_type == 'video/mp4':
                extra_args['Metadata'] = {
                    'Content-Disposition': 'inline',
                    'Cache-Control': 'max-age=31536000'
                }
            
            # For images, add caching headers
            elif content_type.startswith('image/'):
                extra_args['Metadata'] = {
                    'Cache-Control': 'max-age=31536000'
                }
            
            # Upload file
            self.s3_client.upload_file(
                str(local_file_path),
                self.bucket_name,
                s3_key,
                ExtraArgs=extra_args
            )
            
            logger.info(f"Successfully uploaded {local_file_path.name} to s3://{self.bucket_name}/{s3_key}")
            return True
            
        except FileNotFoundError:
            logger.error(f"Local file not found: {local_file_path}")
            return False
        except NoCredentialsError:
            logger.error("AWS credentials not found")
            return False
        except ClientError as e:
            logger.error(f"S3 upload failed: {e}")
            return False
        except Exception as e:
            logger.error(f"Unexpected error during S3 upload: {e}")
            return False
    
    def upload_with_retry(self, local_file_path: Path, s3_key: str, 
                         max_retries: int = 3, content_type: Optional[str] = None) -> bool:
        """Upload file with retry logic"""
        for attempt in range(max_retries + 1):
            if self.upload_file(local_file_path, s3_key, content_type):
                return True
            
            if attempt < max_retries:
                wait_time = 2 ** attempt  # Exponential backoff
                logger.warning(f"Upload attempt {attempt + 1} failed, retrying in {wait_time}s...")
                import time
                time.sleep(wait_time)
            else:
                logger.error(f"All {max_retries + 1} upload attempts failed for {s3_key}")
        
        return False
    
    def file_exists(self, s3_key: str) -> bool:
        """Check if a file exists in S3"""
        try:
            self.s3_client.head_object(Bucket=self.bucket_name, Key=s3_key)
            return True
        except ClientError as e:
            if e.response['Error']['Code'] == '404':
                return False
            else:
                logger.error(f"Error checking if file exists: {e}")
                return False
    
    def get_file_url(self, s3_key: str, expires_in: int = 3600) -> Optional[str]:
        """Generate a presigned URL for the file"""
        try:
            url = self.s3_client.generate_presigned_url(
                'get_object',
                Params={'Bucket': self.bucket_name, 'Key': s3_key},
                ExpiresIn=expires_in
            )
            return url
        except ClientError as e:
            logger.error(f"Error generating presigned URL: {e}")
            return None
    
    def get_public_url(self, s3_key: str) -> str:
        """Get the public URL for a file"""
        if self.endpoint_url:
            # For custom endpoints, construct URL manually
            base_url = self.endpoint_url.rstrip('/')
            if config.get_bool('AWS_USE_PATH_STYLE_ENDPOINT', True):
                return f"{base_url}/{self.bucket_name}/{s3_key}"
            else:
                return f"https://{self.bucket_name}.{base_url.replace('https://', '').replace('http://', '')}/{s3_key}"
        else:
            # Standard AWS S3 URL
            return f"https://{self.bucket_name}.s3.{self.region_name}.amazonaws.com/{s3_key}"
    
    def delete_file(self, s3_key: str) -> bool:
        """Delete a file from S3"""
        try:
            self.s3_client.delete_object(Bucket=self.bucket_name, Key=s3_key)
            logger.info(f"Successfully deleted s3://{self.bucket_name}/{s3_key}")
            return True
        except ClientError as e:
            logger.error(f"Error deleting file: {e}")
            return False
    
    def list_files(self, prefix: str = "", max_keys: int = 1000) -> list:
        """List files in S3 bucket with optional prefix"""
        try:
            response = self.s3_client.list_objects_v2(
                Bucket=self.bucket_name,
                Prefix=prefix,
                MaxKeys=max_keys
            )
            
            files = []
            for obj in response.get('Contents', []):
                files.append({
                    'key': obj['Key'],
                    'size': obj['Size'],
                    'last_modified': obj['LastModified']
                })
            
            return files
        except ClientError as e:
            logger.error(f"Error listing files: {e}")
            return []
    
    def get_bucket_info(self) -> dict:
        """Get bucket information for debugging"""
        try:
            # Try to get bucket location (may not work with all S3-compatible services)
            try:
                location_response = self.s3_client.get_bucket_location(Bucket=self.bucket_name)
                location = location_response.get('LocationConstraint') or self.region_name
            except:
                location = self.region_name
            
            return {
                'bucket_name': self.bucket_name,
                'region': self.region_name,
                'actual_location': location,
                'endpoint_url': self.endpoint_url,
                'accessible': True
            }
        except Exception as e:
            return {
                'bucket_name': self.bucket_name,
                'region': self.region_name,
                'endpoint_url': self.endpoint_url,
                'error': str(e),
                'accessible': False
            }

# Convenience function to create S3 uploader from environment
def create_s3_uploader() -> Optional[S3Uploader]:
    """Create S3Uploader from environment configuration"""
    try:
        return S3Uploader.from_env()
    except ValueError as e:
        logger.warning(f"Could not create S3 uploader: {e}")
        return None