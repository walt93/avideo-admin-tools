# AVideo Bulk Metadata Editor

A fast, efficient tool for AVideo administrators to bulk edit video metadata across categories and playlists. This tool provides a streamlined interface for managing video titles and descriptions without navigating through the main AVideo CMS interface.

## Features

- Edit video titles and descriptions quickly through a modal interface
- Filter videos by category (with nested subcategory support)
- Filter by playlist
- Paginated results (25 videos per page)
- Responsive Bootstrap 5 interface
- Direct database access with proper error handling
- Category tree navigation (up to 3 levels deep)

## Prerequisites

- AVideo CMS installation
- PHP 7.0+
- MySQL/MariaDB
- Apache2 with mod_rewrite enabled
- Database access credentials

## Installation

1. Create a secure directory for the editor (e.g., `/management` or similar)

2. Set up basic authentication:
```bash
# Install Apache utilities if needed
sudo apt-get install apache2-utils

# Create password file (outside web root)
sudo htpasswd -c /etc/apache2/.htpasswd yourusername
```

3. Create `.htaccess` in your editor directory:
```apache
AuthType Basic
AuthName "Management Console"
AuthUserFile /etc/apache2/.htpasswd
Require valid-user
```

4. Copy `metadata.php` to your secure directory

5. Set up your database password as an environment variables:
```bash
# Add to your Apache environment or .env file
AVIDEO_DATABASE_PW=your_password_here
AVIDEO_DATABASE_NAME=your_database_name_here
AVIDEO_DATABASE_USER=your_database_user_here
AVIDEO_DATABASE_HOST=your_database_host_here e.g. localhost
OPENAI_API_KEY=your open AI api key goes here
AVIDEO_ENDPOINT_API_KEY=make up some secure secret and use it on both sides
```

## Security Considerations

- The tool requires direct database access
- Place in a secure directory with authentication
- Use environment variables for sensitive credentials
- Consider IP restrictions in .htaccess if needed
- Keep backups before making bulk changes

## Usage

1. Access the editor through your secure URL
2. Use the category dropdowns to filter by content area
3. Or select a playlist to filter videos
4. Click "Edit" on any video to modify its metadata
5. Changes are saved immediately to the database

## Database Schema

The tool interacts with these AVideo tables:
- `videos`: Core video metadata
- `categories`: Category hierarchy
- `playlists`: Playlist information
- `playlists_has_videos`: Playlist-video relationships

## Subtitle and Transcript Management

The subtitle management system provides tools for handling video subtitles (VTT) and transcripts (TXT) within your AVideo installation. This system includes:

### Features
- Secure API endpoint for uploading subtitle and transcript files
- Automatic file organization matching video structure
- Support for both VTT (subtitles) and TXT (transcript) formats
- Database-validated file management

### Upload Endpoint
The system provides a secure endpoint for uploading subtitle and transcript files:
```bash
POST /management/upload_subtitle.php
```

Required authentication:
- Basic HTTP Authentication
- API Key via X-Api-Key header

Files are automatically stored in the correct video directory:
```
/videos/[video_filename]/[video_filename].[vtt|txt]
```

For detailed endpoint documentation, see `docs/upload_subtitle.md`.

### Environment Configuration
Required environment variables:
```bash
AVIDEO_DATABASE_PW=your_db_password
AVIDEO_DATABASE_NAME=your_db_name
AVIDEO_DATABASE_USER=your_db_user
AVIDEO_DATABASE_HOST=your_db_host
AVIDEO_ENDPOINT_API_KEY=your_api_key
```

### Security
- API key authentication required
- Basic HTTP authentication required
- Extension validation (.vtt and .txt only)
- Database validation of video filenames
- Secure file handling and storage

## Contributing

Issues and pull requests are welcome. When contributing, please:
- Test changes thoroughly
- Maintain the existing code style
- Add comments for complex functionality
- Update documentation as needed

## License

MIT License - See LICENSE file for details
