#!/usr/bin/env python3
"""
Video Transcript Processing Script using OpenAI Whisper

This script processes video files to generate transcripts using OpenAI's Whisper model.
It supports different processing modes to handle video queuing based on various criteria
such as creation date or video duration.

Features:
- Multiple processing modes (most recent, shortest first, longest first)
- Progress tracking and status updates
- Automatic handling of different video resolutions
- CDN integration for video processing
- Comprehensive logging and error handling

Usage:
    python3 process_videos.py [--mode {most_recent,shortest_first,longest_first}]
    python3 process_videos.py --help

Modes:
    most_recent     Process videos starting with the most recently created
    shortest_first  Process videos starting with the shortest duration (default)
    longest_first   Process videos starting with the longest duration

Requirements:
    - OpenAI Whisper installed and accessible in PATH
    - MySQL database connection
    - Appropriate file system permissions
    - Required Python packages (see imports)

Author: [Your Name]
Last Modified: January 2025
"""

import os
import json
import mysql.connector
import subprocess
import glob
import logging
from datetime import datetime
from pathlib import Path
import re
import argparse
import time

class WhisperProcessor:
    """
    A class to process videos using OpenAI's Whisper model for transcription.
    Supports multiple processing modes and handles video file management,
    database interactions, and progress tracking.
    """

    VALID_MODES = ['most_recent', 'shortest_first', 'longest_first']

    def __init__(self, mode='shortest_first'):
        """
        Initialize the WhisperProcessor with specified processing mode.

        Args:
            mode (str): The processing mode to use. Must be one of:
                       'most_recent', 'shortest_first', or 'longest_first'

        Raises:
            ValueError: If an invalid mode is specified
        """
        if mode not in self.VALID_MODES:
            raise ValueError(f"Invalid mode. Must be one of: {', '.join(self.VALID_MODES)}")

        self.mode = mode
        self.base_dir = "/var/www/html/conspyre.tv/videos"
        self.whisper_dir = "/opt/whisper"
        self.status_file = os.path.join(self.whisper_dir, "status.json")
        self.log_dir = os.path.join(self.whisper_dir, "logs")
        self.processed_log = os.path.join(self.log_dir, "files_processed.json")
        self.cdn_base = "https://b-low.b-cdn.net"
        self.whisper_path = subprocess.check_output(['which', 'whisper']).decode().strip()

        # Ensure required directories exist
        os.makedirs(self.whisper_dir, exist_ok=True)
        os.makedirs(self.log_dir, exist_ok=True)

        # Set up logging
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(levelname)s - %(message)s',
            handlers=[
                logging.StreamHandler()
            ]
        )
        self.logger = logging.getLogger(__name__)

        # Initialize processing stats
        self.total_videos = self.get_total_video_count()
        self.processed_count = 0
        self.processing_times = []
        self.current_start_time = None

        # Add tracking for historical progress
        self.total_historical_processed = 0
        self.initialize_progress_tracking()

    def get_total_video_count(self):
        """
        Get the current total count of eligible videos from database.

        Returns:
            int: Total number of eligible videos
        """
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)

        cursor.execute("""
            SELECT COUNT(*) as total
            FROM videos
            WHERE status = 'a' AND type = 'video'
        """)
        result = cursor.fetchone()
        total = result['total']

        cursor.close()
        conn.close()
        return total

    def initialize_progress_tracking(self):
        """Initialize progress tracking without affecting total count"""
        completed_ids = self.scan_completed_transcripts()
        self.processed_count = len(completed_ids)

        self.logger.info(f"Found {self.processed_count} processed files out of {self.total_videos} total files")
        self.update_status(status="initializing")

    def connect_db(self):
        """
        Establish database connection.

        Returns:
            mysql.connector.connection.MySQLConnection: Database connection object
        """
        return mysql.connector.connect(
            host="localhost",
            database="AVideo_conspyretv",
            user="conspyre",
            password="jvciX85cvOdjfg6Qcvp_vn6T"
        )

    def scan_completed_transcripts(self):
        """
        Scans for completed transcripts and merges with processing history.

        Returns:
            set: Set of completed video IDs
        """
        completed_ids = set()

        # Load processing history from files_processed.json
        if os.path.exists(self.processed_log):
            try:
                with open(self.processed_log, 'r') as f:
                    history = json.load(f)
                    for entry in history.get('processed_files', []):
                        if entry.get('success', False):
                            completed_ids.add(entry['id'])
            except json.JSONDecodeError:
                self.logger.error("Error reading files_processed.json, creating new file")
                history = {"processed_files": []}
        else:
            history = {"processed_files": []}

        # Scan video directories for existing transcripts
        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)

        query = """
        SELECT id, filename
        FROM videos
        WHERE status = 'a' AND type = 'video'
        """

        cursor.execute(query)
        videos = cursor.fetchall()
        cursor.close()
        conn.close()

        for video in videos:
            video_dir = os.path.join(self.base_dir, video['filename'])
            json_output = os.path.join(video_dir, f"{video['filename']}.json")

            if os.path.exists(json_output):
                completed_ids.add(video['id'])

                # Add to processing history if not already there
                if not any(entry['id'] == video['id'] for entry in history['processed_files']):
                    history['processed_files'].append({
                        "filename": video['filename'],
                        "id": video['id'],
                        "timestamp": datetime.fromtimestamp(os.path.getmtime(json_output)).isoformat(),
                        "success": True,
                        "source": "scan"
                    })

        # Update files_processed.json with merged data
        with open(self.processed_log, 'w') as f:
            json.dump(history, f, indent=2)

        self.logger.info(f"Found {len(completed_ids)} completed transcripts")
        return completed_ids

    def get_pending_videos(self):
        """
        Get list of videos that need processing, excluding completed ones.
        Supports different sorting modes: most_recent, shortest_first, longest_first

        Returns:
            list: List of dictionaries containing video information
        """
        completed_ids = self.scan_completed_transcripts()

        conn = self.connect_db()
        cursor = conn.cursor(dictionary=True)

        # Base query with duration field
        query = """
        SELECT id, filename, title, duration_in_seconds
        FROM videos
        WHERE status = 'a' AND type = 'video'
            AND id NOT IN ({})
        """.format(','.join(str(id) for id in completed_ids) if completed_ids else 'NULL')

        # Add sorting based on mode
        if self.mode == 'most_recent':
            query += " ORDER BY created DESC"
        elif self.mode == 'shortest_first':
            query += " ORDER BY duration_in_seconds ASC"
        elif self.mode == 'longest_first':
            query += " ORDER BY duration_in_seconds DESC"

        cursor.execute(query)
        videos = cursor.fetchall()
        cursor.close()
        conn.close()

        self.logger.info(f"Found {len(videos)} videos pending processing (Mode: {self.mode})")
        return videos

    def find_video_file(self, video_dir):
        """
        Find the lowest resolution MP4 file in the video directory.

        Args:
            video_dir (str): Directory containing video files

        Returns:
            str: Path to the lowest resolution video file, or None if not found
        """
        pattern = os.path.join(video_dir, "*_ext.mp4")
        mp4_files = glob.glob(pattern)

        if not mp4_files:
            # Try without _ext suffix
            pattern = os.path.join(video_dir, "*.mp4")
            mp4_files = glob.glob(pattern)

        if not mp4_files:
            return None

        # Extract resolutions and find lowest
        resolution_pattern = re.compile(r'_(\d+)\.mp4$')
        valid_files = []

        for file in mp4_files:
            match = resolution_pattern.search(file)
            if match:
                resolution = int(match.group(1))
                valid_files.append((resolution, file))

        if valid_files:
            return min(valid_files, key=lambda x: x[0])[1]

        return mp4_files[0]  # If no resolution found, return first file

    def calculate_eta(self):
        """
        Calculate estimated time remaining for processing.

        Returns:
            str: Formatted string with estimated time remaining
        """
        if not self.processing_times:
            return "Calculating..."

        avg_time = sum(self.processing_times) / len(self.processing_times)
        remaining_videos = self.total_videos - self.processed_count
        seconds_remaining = avg_time * remaining_videos

        hours = int(seconds_remaining // 3600)
        minutes = int((seconds_remaining % 3600) // 60)

        return f"{hours}h {minutes}m"

    def update_status(self, current_video=None, status="running"):
        """
        Update the status file with current processing information.

        Args:
            current_video (str, optional): Currently processing video filename
            status (str, optional): Current processing status
        """
        now = datetime.now()
        current_total = self.get_total_video_count()

        if self.current_start_time and current_video:
            processing_time = (now - self.current_start_time).total_seconds()
            if processing_time > 0:
                self.processing_times.append(processing_time)

        status_data = {
            "status": status,
            "mode": self.mode,
            "last_updated": now.isoformat(),
            "current_video": current_video,
            "progress": {
                "total_videos": current_total,
                "processed": self.processed_count,
                "remaining": current_total - self.processed_count,
                "percent_complete": round((self.processed_count / current_total * 100), 2) if current_total else 0,
                "estimated_completion_time": self.calculate_eta(),
                "average_processing_time": round(sum(self.processing_times) / len(self.processing_times), 2) if self.processing_times else None
            }
        }

        with open(self.status_file, 'w') as f:
            json.dump(status_data, f, indent=2)

    def process_video(self, video):
        """
        Process a single video using Whisper.

        Args:
            video (dict): Dictionary containing video information

        Returns:
            bool: True if processing successful, False otherwise
        """
        video_dir = os.path.join(self.base_dir, video['filename'])
        base_output = os.path.join(video_dir, f"{video['filename']}")
        json_output = f"{base_output}.json"

        # Skip if already processed
        if os.path.exists(json_output):
            self.logger.info(f"Skipping {video['filename']} - already processed")
            return True

        # Find video file
        video_file = self.find_video_file(video_dir)
        if not video_file:
            self.logger.error(f"No video file found for {video['filename']}")
            return False

        # Extract filename for CDN URL
        cdn_filename = os.path.basename(video_file)
        cdn_url = f"{self.cdn_base}/{cdn_filename}"

        # Run whisper
        try:
            # Set up environment
            env = os.environ.copy()
            env['PATH'] = f"/usr/local/bin:/usr/bin:/bin:{os.path.dirname(self.whisper_path)}"

            command = [
                self.whisper_path,
                cdn_url,
                "--model", "turbo",
                "--output_dir", video_dir,
                "--output_format", "all",
                "--task", "transcribe",
                "--language", "en"
            ]

            result = subprocess.run(command, check=True, capture_output=True, text=True, env=env)
            self.logger.info(f"Successfully processed {video['filename']}")

            # Handle file renaming if necessary
            resolutions = ['240', '360', '480', '540', '720', '1080', '1440', '2160']
            cdn_basename = os.path.splitext(cdn_filename)[0]
            needs_renaming = any(f"_{res}" in cdn_basename for res in resolutions)

            if needs_renaming:
                # Remove resolution suffix from whisper output files
                for ext in ['.txt', '.vtt', '.srt', '.json']:
                    source_file = os.path.join(video_dir, f"{cdn_basename}{ext}")
                    target_file = os.path.join(video_dir, f"{video['filename']}{ext}")
                    if os.path.exists(source_file):
                        os.rename(source_file, target_file)
                        self.logger.info(f"Renamed {source_file} to {target_file}")

            # Update permissions for all output files
            for ext in ['.txt', '.vtt', '.srt', '.json']:
                output_file = os.path.join(video_dir, f"{video['filename']}{ext}")
                if os.path.exists(output_file):
                    os.chmod(output_file, 0o664)
                    subprocess.run(['chown', 'www-data:www-data', output_file])

            return True

        except subprocess.CalledProcessError as e:
            self.logger.error(f"Error processing {video['filename']}: {str(e)}")
            self.logger.error(f"Command output: {e.output}")
            self.logger.error(f"Command stderr: {e.stderr}")
            return False

    def log_processed_file(self, video, success, error=None):
        """
        Log processing results to the processed files log.

        Args:
            video (dict): Dictionary containing video information
            success (bool): Whether processing was successful
            error (str, optional): Error message if processing failed
        """
        if not os.path.exists(self.processed_log):
            processed_data = {"processed_files": []}
        else:
            with open(self.processed_log, 'r') as f:
                processed_data = json.load(f)

        entry = {
            "filename": video['filename'],
            "id": video['id'],
            "timestamp": datetime.now().isoformat(),
            "success": success
        }
        if error:
            entry["error"] = str(error)

        processed_data["processed_files"].append(entry)

        with open(self.processed_log, 'w') as f:
            json.dump(processed_data, f, indent=2)

    def run(self):
        """
        Main processing loop. Continuously polls for and processes pending videos.
        Implements a polling interval to check for new videos regularly.
        """
        self.logger.info(f"Starting whisper processing in {self.mode} mode")
        self.update_status(status="running")

        while True:
            try:
                # Get current pending videos
                videos = self.get_pending_videos()
                current_total = len(videos)

                if current_total == 0:
                    self.logger.info("No pending videos found. Waiting for new videos...")
                    self.update_status(status="waiting")
                    time.sleep(300)  # Wait 5 minutes before next poll
                    continue

                self.logger.info(f"Found {current_total} videos to process")

                for video in videos:
                    self.current_start_time = datetime.now()
                    self.update_status(current_video=video['filename'])
                    self.logger.info(f"Processing {video['filename']}")

                    try:
                        success = self.process_video(video)
                        self.log_processed_file(video, success)
                        self.processed_count += 1
                    except Exception as e:
                        self.logger.error(f"Error processing {video['filename']}: {str(e)}")
                        self.log_processed_file(video, False, error=str(e))

                    self.update_status(current_video=video['filename'])

                    # Poll for updated total after each video
                    new_total = self.get_total_video_count()
                    if new_total != self.total_videos:
                        self.logger.info(f"Video count changed from {self.total_videos} to {new_total}")
                        self.total_videos = new_total

                # Short sleep between polling cycles to prevent excessive database queries
                time.sleep(30)

            except KeyboardInterrupt:
                self.logger.info("Received shutdown signal, completing gracefully...")
                self.update_status(status="shutdown")
                break
            except Exception as e:
                self.logger.error(f"Error in main processing loop: {str(e)}")
                self.update_status(status="error")
                time.sleep(60)  # Wait before retrying

        self.logger.info("Processing completed")


def main():
    """
    Main entry point for the script.
    Handles command line arguments and initializes the WhisperProcessor.
    """
    parser = argparse.ArgumentParser(
        description="""
        Process videos with OpenAI Whisper for transcription.

        This script processes video files to generate transcripts using different
        processing modes to determine the order of processing. Available modes
        determine how videos are queued for processing.
        """,
        formatter_class=argparse.RawDescriptionHelpFormatter
    )

    parser.add_argument(
        '--mode',
        choices=WhisperProcessor.VALID_MODES,
        default='shortest_first',
        help="""
        Processing mode for videos:
        most_recent    - Process newest videos first
        shortest_first - Process shortest videos first (default)
        longest_first  - Process longest videos first
        """
    )

    args = parser.parse_args()
    processor = WhisperProcessor(mode=args.mode)
    processor.run()


if __name__ == "__main__":
    main()