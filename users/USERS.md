# User Content Management System

The User Content Management System provides content creators with a dedicated interface to manage their media on the Conspyre.tv platform. It offers a streamlined experience for managing videos, transcripts, and playlists that appear on their Roku channel.

## Directory Structure

```
/users/
├── config/
│   └── users.json         # User configuration and mapping
├── includes/
│   ├── DatabaseManager.php # Database operations
│   ├── UserManager.php    # User authentication and access control
│   ├── init.php          # System initialization
│   └── ai_handlers.php    # AI-powered content processing
├── js/
│   ├── modal_manager.js   # Modal and UI interactions
│   └── ai_handlers.js     # Client-side AI functionality
├── templates/
│   ├── nav.php           # Shared navigation component
│   ├── main_content.php  # Video listing template
│   └── modals/           # Modal dialog templates
├── [username]/           # User-specific directories
│   ├── index.php        # Media management interface
│   └── upload.php       # Content upload interface
└── metadata.php         # Core media management logic
```

## Features

### Media Management
- Video listing with pagination
- Filter videos by playlist
- Video play functionality
- Transcript and subtitle viewing
- Inline editing of video metadata
- AI-powered content sanitization
- Video count tracking

### Navigation
- Quick access to media management and upload interfaces
- Direct links to main CMS and encoder
- Responsive dark theme design
- Active state indication

### Access Control
- User-specific content isolation
- Apache authentication integration
- IP-based access restrictions
- Secure file handling

### Content Processing
- AI-powered content sanitization
- Title and description management
- Playlist organization
- Transcript and subtitle handling

## Setup

1. Configure user mapping in `config/users.json`:
```json
{
  "users": {
    "username": {
      "id": "user_id",
      "name": "Display Name",
      "allowed_ips": ["ip_addresses"]
    }
  }
}
```

2. Set up Apache authentication:
- Ensure `.htaccess` configurations are in place
- Configure user credentials in Apache's authentication system

3. Deploy using the provided script:
```bash
./deploy.sh
```

## Dependencies

- Apache with mod_auth_basic
- PHP 7.4+
- MySQL/MariaDB
- OpenAI API access for content processing
- Bootstrap 5.1.3
- Bootstrap Icons

## Integration

The system integrates with:
- Conspyre.tv CMS
- Roku channel content delivery
- Video transcoding system
- OpenAI API for content processing

## Security

The system implements multiple security layers:
- Apache Basic Authentication
- IP restriction capabilities
- User-specific content isolation
- Secure file handling
- Protection against directory traversal
- Content sanitization

## Usage

Users access their content management interface at:
```
https://conspyre.xyz/management/users/[username]/index.php
```

Content creators can:
1. View and manage their video content
2. Edit video metadata
3. Organize content in playlists
4. Access transcripts and subtitles
5. Upload new content
6. Preview content as it will appear on Roku

## Notes

- System playlists ("Favorites", "Watch Later") are handled by the main CMS
- Playlist management here directly affects Roku channel organization
- Changes are reflected immediately in the content delivery system