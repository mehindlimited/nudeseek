#!/usr/bin/env python3
"""
Monitoring script for the video encoder
Uses .env configuration for credentials
"""

import requests
import json
import time
import argparse
from datetime import datetime, timedelta
from typing import Dict, Any, List
from config import config

class EncoderMonitor:
    def __init__(self, env_file: str = '.env'):
        from config import Config
        global config
        config = Config(env_file)
        
        self.api_base_url = config.get('LARAVEL_API_URL', 'http://localhost:8000').rstrip('/')
        
        # Validate required configuration
        if not config.validate_required(['LARAVEL_API_URL']):
            print("Warning: LARAVEL_API_URL not found in .env file. Using default or command-line override.")
        
        if self.api_base_url == 'http://localhost:8000':
            print("Warning: Using default API URL. Set LARAVEL_API_URL in .env file for production use.")
        
        # Log the configuration being used
        print(f"Monitor initialized with API: {self.api_base_url}")
        
    def get_queue_stats(self) -> Dict[str, Any]:
        """Get encoding queue statistics from Laravel API"""
        try:
            response = requests.get(f"{self.api_base_url}/api/encoding-queue/stats", timeout=30)
            if response.status_code == 200:
                return response.json()
        except Exception as e:
            print(f"Error fetching queue stats: {e}")
        
        # Fallback empty stats
        return {
            'pending': 0,
            'processing': 0,
            'completed': 0,
            'failed': 0,
            'retryable': 0,
            'total': 0,
        }
    
    def check_stuck_jobs(self) -> int:
        """Check for jobs that might be stuck in processing"""
        try:
            response = requests.post(f"{self.api_base_url}/api/encoding-queue/reset-stuck", timeout=30)
            if response.status_code == 200:
                data = response.json()
                return int(data.get('message', '0').split(' ')[1])  # Extract count from message
        except Exception as e:
            print(f"Error checking stuck jobs: {e}")
        return 0
    
    def get_job_status(self, video_code: str) -> Dict[str, Any]:
        """Get status of a specific job"""
        try:
            response = requests.get(f"{self.api_base_url}/api/encoding-queue/{video_code}/status", timeout=30)
            if response.status_code == 200:
                return response.json()
        except Exception as e:
            print(f"Error getting job status: {e}")
        return {}
    
    def display_dashboard(self):
        """Display a real-time monitoring dashboard"""
        try:
            while True:
                # Clear screen
                print("\033[2J\033[H")
                
                print("=" * 60)
                print("VIDEO ENCODER MONITORING DASHBOARD")
                print("=" * 60)
                print(f"API: {self.api_base_url}")
                print(f"Last Updated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
                print()
                
                # Queue Statistics
                stats = self.get_queue_stats()
                print("QUEUE STATISTICS:")
                print("-" * 30)
                print(f"Pending Jobs:     {stats.get('pending', 0):>6}")
                print(f"Processing:       {stats.get('processing', 0):>6}")
                print(f"Completed:        {stats.get('completed', 0):>6}")
                print(f"Failed:           {stats.get('failed', 0):>6}")
                print(f"Retryable:        {stats.get('retryable', 0):>6}")
                print(f"Total Jobs:       {stats.get('total', 0):>6}")
                print()
                
                # Processing Performance
                if stats.get('avg_processing_time_formatted'):
                    print(f"Avg Processing Time: {stats['avg_processing_time_formatted']}")
                
                if stats.get('last_completed'):
                    last_completed = stats['last_completed']
                    print(f"Last Completed: {last_completed.get('video_code', 'N/A')} at {last_completed.get('completed_at', 'N/A')}")
                    if last_completed.get('processing_time_seconds'):
                        print(f"Processing Time: {last_completed['processing_time_seconds']}s")
                print()
                
                # Check for stuck jobs
                if stats.get('stuck_jobs', 0) > 0:
                    print("STUCK JOBS DETECTED:")
                    print("-" * 30)
                    print(f"Number of stuck jobs: {stats['stuck_jobs']}")
                    print("Run with --reset-stuck to reset them")
                    print()
                
                # Health status indicators
                total_jobs = stats.get('total', 0)
                if total_jobs > 0:
                    success_rate = (stats.get('completed', 0) / total_jobs) * 100
                    print("HEALTH INDICATORS:")
                    print("-" * 30)
                    print(f"Success Rate: {success_rate:.1f}%")
                    
                    pending_ratio = (stats.get('pending', 0) / total_jobs) * 100
                    if pending_ratio > 50:
                        print("WARNING: High queue backlog detected!")
                    
                    failed_ratio = (stats.get('failed', 0) / total_jobs) * 100
                    if failed_ratio > 10:
                        print("WARNING: High failure rate detected!")
                    print()
                
                print("Commands:")
                print("- Ctrl+C to exit")
                print("- Run with --reset-stuck to reset stuck jobs")
                print("- Run with --health-check for detailed health report")
                print("=" * 60)
                
                time.sleep(10)  # Update every 10 seconds
                
        except KeyboardInterrupt:
            print("\nMonitoring stopped.")
    
    def run_health_check(self) -> Dict[str, Any]:
        """Run a comprehensive health check"""
        health_report = {
            'timestamp': datetime.now().isoformat(),
            'overall_status': 'healthy',
            'issues': [],
            'recommendations': []
        }
        
        # Check API connectivity first
        try:
            response = requests.get(f"{self.api_base_url}/api/encoding-queue/next-pending", timeout=10)
            if response.status_code not in [200, 404]:
                health_report['issues'].append('API connectivity issues')
                health_report['overall_status'] = 'unhealthy'
        except Exception as e:
            health_report['issues'].append(f'Cannot connect to API: {e}')
            health_report['overall_status'] = 'unhealthy'
            return health_report
        
        # Check queue stats
        stats = self.get_queue_stats()
        
        # Alert if too many failed jobs
        total_jobs = stats.get('total', 0)
        if total_jobs > 0:
            failed_ratio = stats.get('failed', 0) / total_jobs
            if failed_ratio > 0.1:  # More than 10% failed
                health_report['issues'].append(f"High failure rate: {failed_ratio:.1%}")
                health_report['overall_status'] = 'warning'
                health_report['recommendations'].append("Check encoder logs for common failure patterns")
        
        # Alert if jobs are stuck
        if stats.get('stuck_jobs', 0) > 0:
            health_report['issues'].append(f"{stats['stuck_jobs']} jobs appear to be stuck")
            health_report['recommendations'].append("Run --reset-stuck to reset stuck jobs")
            if health_report['overall_status'] == 'healthy':
                health_report['overall_status'] = 'warning'
        
        # Check if queue is backing up
        pending_jobs = stats.get('pending', 0)
        if pending_jobs > 100:
            health_report['issues'].append(f"Large queue backlog: {pending_jobs} pending jobs")
            health_report['recommendations'].append("Consider scaling up encoder instances")
            if health_report['overall_status'] == 'healthy':
                health_report['overall_status'] = 'warning'
        
        # Check processing performance
        if stats.get('avg_processing_time_seconds', 0) > 3600:  # More than 1 hour average
            health_report['issues'].append("Slow average processing time")
            health_report['recommendations'].append("Check encoder performance and resource allocation")
            if health_report['overall_status'] == 'healthy':
                health_report['overall_status'] = 'warning'
        
        # Add stats to report
        health_report['queue_stats'] = stats
        
        return health_report
    
    def show_stats(self):
        """Show current queue statistics"""
        stats = self.get_queue_stats()
        print(f"\nCURRENT QUEUE STATISTICS (API: {self.api_base_url}):")
        print("=" * 50)
        for key, value in stats.items():
            if key == 'last_completed' and isinstance(value, dict):
                print(f"{key.replace('_', ' ').title()}:")
                for sub_key, sub_value in value.items():
                    print(f"  {sub_key.replace('_', ' ').title()}: {sub_value}")
            else:
                print(f"{key.replace('_', ' ').title()}: {value}")
        print()

def main():
    parser = argparse.ArgumentParser(description='Video Encoder Monitor with .env Configuration')
    parser.add_argument('--env-file', default='.env', 
                       help='Path to environment file (default: .env)')
    parser.add_argument('--dashboard', action='store_true', 
                       help='Show real-time dashboard')
    parser.add_argument('--health-check', action='store_true', 
                       help='Run health check and exit')
    parser.add_argument('--reset-stuck', action='store_true', 
                       help='Reset stuck jobs')
    parser.add_argument('--stats', action='store_true', 
                       help='Show current statistics')
    parser.add_argument('--job-status', 
                       help='Check status of specific job by video code')
    parser.add_argument('--show-config', action='store_true',
                       help='Show current configuration')
    
    # Allow API URL override
    parser.add_argument('--api-url', help='Override LARAVEL_API_URL')
    
    args = parser.parse_args()
    
    # Apply API URL override if provided
    if args.api_url:
        import os
        os.environ['LARAVEL_API_URL'] = args.api_url
    
    monitor = EncoderMonitor(args.env_file)
    
    if args.show_config:
        config.print_config()
        return
    
    if args.health_check:
        health = monitor.run_health_check()
        print(json.dumps(health, indent=2))
        exit(0 if health['overall_status'] == 'healthy' else 1)
    
    elif args.reset_stuck:
        count = monitor.check_stuck_jobs()
        if count > 0:
            print(f"Reset {count} stuck jobs")
        else:
            print("No stuck jobs found")
    
    elif args.stats:
        monitor.show_stats()
    
    elif args.job_status:
        status = monitor.get_job_status(args.job_status)
        if status:
            print(f"\nJob Status for {args.job_status}:")
            print("=" * 40)
            print(json.dumps(status, indent=2))
        else:
            print(f"Job {args.job_status} not found")
    
    elif args.dashboard:
        monitor.display_dashboard()
    
    else:
        print("No action specified. Use --help for options.")
        print(f"\nUsing API: {monitor.api_base_url}")
        print("\nQuick commands:")
        print("  --dashboard     Show real-time monitoring dashboard")
        print("  --stats         Show current queue statistics")
        print("  --health-check  Run comprehensive health check")

if __name__ == "__main__":
    main()