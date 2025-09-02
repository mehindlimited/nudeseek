import os
from pathlib import Path
from typing import Optional, Dict, Any
import logging

logger = logging.getLogger(__name__)

class Config:
    """Configuration loader for video encoder using .env file"""
    
    def __init__(self, env_file: Optional[str] = None):
        self.env_file = env_file or '.env'
        self.config = {}
        self.load_env_file()
    
    def load_env_file(self):
        """Load environment variables from .env file"""
        env_path = Path(self.env_file)
        
        if not env_path.exists():
            logger.warning(f"Environment file {self.env_file} not found. Using system environment variables only.")
            return
        
        try:
            with open(env_path, 'r') as f:
                for line_num, line in enumerate(f, 1):
                    line = line.strip()
                    
                    # Skip empty lines and comments
                    if not line or line.startswith('#'):
                        continue
                    
                    # Parse KEY=VALUE format
                    if '=' in line:
                        key, value = line.split('=', 1)
                        key = key.strip()
                        value = value.strip()
                        
                        # Remove quotes if present
                        if value.startswith('"') and value.endswith('"'):
                            value = value[1:-1]
                        elif value.startswith("'") and value.endswith("'"):
                            value = value[1:-1]
                        
                        # Set in environment if not already set
                        if key not in os.environ:
                            os.environ[key] = value
                        
                        self.config[key] = value
                    else:
                        logger.warning(f"Invalid line format in {self.env_file}:{line_num}: {line}")
            
            logger.info(f"Loaded {len(self.config)} variables from {self.env_file}")
            
        except Exception as e:
            logger.error(f"Error loading {self.env_file}: {e}")
    
    def get(self, key: str, default: Any = None) -> Any:
        """Get configuration value with fallback to environment variables"""
        # First try environment variables (highest priority)
        value = os.getenv(key)
        if value is not None:
            return self._convert_type(value)
        
        # Then try loaded config
        value = self.config.get(key)
        if value is not None:
            return self._convert_type(value)
        
        # Finally return default
        return default
    
    def get_bool(self, key: str, default: bool = False) -> bool:
        """Get boolean configuration value"""
        value = self.get(key, default)
        if isinstance(value, str):
            return value.lower() in ('true', '1', 'yes', 'on')
        return bool(value)
    
    def get_int(self, key: str, default: int = 0) -> int:
        """Get integer configuration value"""
        value = self.get(key, default)
        try:
            return int(value)
        except (ValueError, TypeError):
            return default
    
    def get_float(self, key: str, default: float = 0.0) -> float:
        """Get float configuration value"""
        value = self.get(key, default)
        try:
            return float(value)
        except (ValueError, TypeError):
            return default
    
    def _convert_type(self, value: str) -> Any:
        """Convert string value to appropriate type"""
        if not isinstance(value, str):
            return value
        
        # Convert numeric strings
        if value.isdigit():
            return int(value)
        
        # Convert float strings
        try:
            if '.' in value:
                return float(value)
        except ValueError:
            pass
        
        # Convert boolean strings
        if value.lower() in ('true', 'false', 'yes', 'no', 'on', 'off', '1', '0'):
            return value.lower() in ('true', 'yes', 'on', '1')
        
        return value
    
    def get_s3_config(self) -> Optional[Dict[str, str]]:
        """Get S3 configuration if available"""
        bucket = self.get('S3_BUCKET')
        if not bucket:
            return None
        
        return {
            'bucket_name': bucket,
            'region_name': self.get('AWS_DEFAULT_REGION', 'us-east-1'),
            'aws_access_key_id': self.get('AWS_ACCESS_KEY_ID'),
            'aws_secret_access_key': self.get('AWS_SECRET_ACCESS_KEY'),
        }
    
    def validate_required(self, required_keys: list) -> bool:
        """Validate that required configuration keys are present"""
        missing_keys = []
        
        for key in required_keys:
            if not self.get(key):
                missing_keys.append(key)
        
        if missing_keys:
            logger.error(f"Missing required configuration keys: {', '.join(missing_keys)}")
            return False
        
        return True
    
    def print_config(self, mask_secrets: bool = True):
        """Print current configuration (for debugging)"""
        secret_keys = ['AWS_SECRET_ACCESS_KEY', 'PASSWORD', 'TOKEN', 'KEY']
        
        print("\nCurrent Configuration:")
        print("=" * 40)
        
        all_config = dict(os.environ)
        all_config.update(self.config)
        
        for key, value in sorted(all_config.items()):
            if mask_secrets and any(secret in key.upper() for secret in secret_keys):
                print(f"{key}: {'*' * len(str(value))}")
            else:
                print(f"{key}: {value}")
        print()

# Global config instance
config = Config()