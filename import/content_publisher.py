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
import random
import re

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

    def generate_video_code(self) -> str:
        """Generate a unique video code EXACTLY matching Laravel's format"""
        # EXACT same characters as Laravel: 'abcdefghijklmnopqrstuvwxyz0123456789'
        characters = 'abcdefghijklmnopqrstuvwxyz0123456789'
        
        # Generate EXACTLY 16-character code like Laravel
        code = ''
        for i in range(16):
            code += characters[random.randint(0, len(characters) - 1)]
        
        logger.info(f"🔍 Generated code: '{code}' (length: {len(code)})")
        logger.info(f"🔍 Code validation - IsLower: {code.islower()}, IsAlnum: {code.isalnum()}")
        
        return code

    def clean_title(self, title: str) -> str:
        """Clean video title by removing unwanted parts"""
        if not title:
            return ""
        
        # Remove "- ThisVid.com" and similar patterns
        patterns_to_remove = [
            r'\s*-\s*ThisVid\.com\s*$',
            r'\s*-\s*thisvid\.com\s*$', 
            r'\s*\|\s*ThisVid\.com\s*$',
            r'\s*\|\s*thisvid\.com\s*$'
        ]
        
        cleaned_title = title.strip()
        
        for pattern in patterns_to_remove:
            cleaned_title = re.sub(pattern, '', cleaned_title, flags=re.IGNORECASE)
        
        return cleaned_title.strip()

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

    def process_video_files(self, video_data: Dict[str, Any], video_code: str) -> Dict[str, str]:
        """Process video files (download from sources, upload to temp)"""
        try:
            file_paths = {}
            
            # Define source and destination paths
            source_video_key = video_data.get('s3_video_key')
            source_thumb_key = video_data.get('s3_thumbnail_key')
            
            # Generate local paths
            video_filename = f"{video_code}.mp4"
            thumb_filename = f"{video_code}.jpg"
            local_video_path = os.path.join(self.local_temp_dir, video_filename)
            local_thumb_path = os.path.join(self.local_temp_dir, thumb_filename)
            
            # Define S3 temp paths
            temp_video_key = f"{self.s3_temp_dir}/{video_filename}"
            temp_thumb_key = f"{self.s3_temp_dir}/{thumb_filename}"
            
            # Download video file
            if source_video_key:
                if self.download_file_from_s3(source_video_key, local_video_path):
                    if self.upload_file_to_s3(local_video_path, temp_video_key):
                        file_paths['video_file'] = temp_video_key
                        self._cleanup_local_file(local_video_path)
                    else:
                        logger.error(f"❌ Failed to upload video to S3 temp: {temp_video_key}")
                        return {}
                else:
                    logger.error(f"❌ Failed to download video: {source_video_key}")
                    return {}
            else:
                logger.error("❌ No video S3 key provided")
                return {}
            
            # Download thumbnail if available
            if source_thumb_key:
                if self.download_file_from_s3(source_thumb_key, local_thumb_path):
                    if self.upload_file_to_s3(local_thumb_path, temp_thumb_key):
                        file_paths['thumbnail_file'] = temp_thumb_key
                    self._cleanup_local_file(local_thumb_path)
                else:
                    logger.warning(f"⚠️ Failed to download thumbnail (continuing anyway): {source_thumb_key}")
            
            return file_paths
            
        except Exception as e:
            logger.error(f"❌ Error processing video files: {e}")
            return {}

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
                             video_code: str, retries: int = 0) -> Optional[Dict[str, Any]]:
        """Publish video via Filament API with retry on code conflict"""
        if retries >= self.max_retries:
            logger.error(f"❌ Max retries ({self.max_retries}) reached for video code conflicts")
            return None
            
        try:
            # Clean and prepare title
            raw_title = video_data.get('title', '').strip()
            clean_title = self.clean_title(raw_title) or f'Video {video_code}'
            
            # Prepare tags
            tags = self.parse_tags(video_data.get('tags', ''))
            
            # Get target_id from category or default to 1
            target_id = category_data['target_id'] if category_data else 1
            
            # Get random user based on target_id
            user_id = self.get_random_user_for_target(target_id)
            if not user_id:
                logger.error(f"❌ Could not find suitable user for target_id {target_id}")
                return None
            
            # Prepare API payload - include the video code!
            api_payload = {
                'code': video_code,
                'title': clean_title,
                'description': video_data.get('description', ''),
                'published_at': datetime.now(timezone.utc).isoformat(),
                'access_type': 'public',
                'user_id': user_id,
                'target_id': target_id,
                'category_id': category_data['id'] if category_data else None,
                'tags': tags
            }
            
            # Remove None values but keep empty arrays/strings
            api_payload = {k: v for k, v in api_payload.items() if v is not None}
            
            headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
            
            logger.info(f"📤 Publishing video via API:")
            logger.info(f"   Title: '{clean_title}' (cleaned from: '{raw_title}')")
            logger.info(f"   Code: {video_code}")
            logger.info(f"   User ID: {user_id}, Target ID: {target_id}")
            logger.info(f"   Category ID: {category_data['id'] if category_data else 'None'}")
            logger.info(f"   Tags: {tags}")
            logger.info(f"📋 Full API Payload: {json.dumps(api_payload, indent=2)}")
            
            response = requests.post(
                f"{self.api_base_url}/videos",
                headers=headers,
                json=api_payload,
                timeout=60
            )
            
            if response.status_code in [200, 201]:
                result = response.json()
                logger.info(f"📄 Full API Response: {result}")
                
                if result.get('success'):
                    video_data_response = result.get('data', result)
                    logger.info(f"✅ Video published successfully: ID {video_data_response.get('id', 'Unknown')}")
                    return video_data_response
                else:
                    logger.error(f"❌ API returned success=false: {result}")
                    return None
            elif response.status_code == 422 and 'code' in response.text.lower():
                logger.warning(f"⚠️ Video code {video_code} already exists, retrying with new code (attempt {retries + 1})")
                return self.publish_video_via_api(video_data, category_data, self.generate_video_code(), retries + 1)
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
            
            # Generate video code - FIXED to match Laravel format exactly
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
            published_video = self.publish_video_via_api(video_data, category_data, video_code, retries=0)
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
            
            connection.close()
            
            return {
                'pending_videos': pending['pending_count'],
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
    parser.add_argument('--delay', type=int, default=60,
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
        elif args.mode == 'status':
            status = robot.get_status()
            print(json.dumps(status, indent=2))
            
    except Exception as e:
        logger.error(f"❌ Fatal error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()