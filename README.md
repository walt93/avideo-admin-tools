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

## Contributing

Issues and pull requests are welcome. When contributing, please:
- Test changes thoroughly
- Maintain the existing code style
- Add comments for complex functionality
- Update documentation as needed

## License

MIT License - See LICENSE file for details
