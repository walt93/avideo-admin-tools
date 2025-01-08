# Subtitle Upload Endpoint

## Overview
The subtitle upload endpoint allows for programmatic uploading of video subtitle (`.vtt`) and transcript (`.txt`) files to the CMS. Each file is automatically placed in the correct directory structure matching the video's location.

## Endpoint Details
- **URL**: `/management/upload_subtitle.php`
- **Method**: POST
- **Authentication**: Requires both basic auth and API key

## Headers
- `X-Api-Key`: Your API key (required)
- `Authorization`: Basic auth credentials (required)

## Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `file` | File | Yes | The subtitle/transcript file to upload (.vtt or .txt only) |
| `filename` | String | Yes | The video filename without extension (must match existing video in database) |

## Response Format
### Success Response
```json
{
    "success": true,
    "message": "File uploaded successfully",
    "path": "/var/www/html/conspyre.tv/videos/[filename]/[filename].[extension]"
}
```

### Error Response
```json
{
    "error": "Error message here"
}
```

## Status Codes
- `200`: Success
- `400`: Bad Request (invalid parameters)
- `401`: Unauthorized (invalid API key or basic auth)
- `405`: Method Not Allowed (not POST)
- `500`: Server Error

## Example Usage

### Using cURL
```bash
curl -X POST \
  -u username:password \
  -H "X-Api-Key: your_api_key" \
  -F "file=@/path/to/subtitle.vtt" \
  -F "filename=video123" \
  https://conspyre.tv/management/upload_subtitle.php
```

### Python Example
```python
import requests

url = 'https://conspyre.tv/management/upload_subtitle.php'
headers = {
    'X-Api-Key': 'your_api_key'
}
files = {
    'file': open('path/to/subtitle.vtt', 'rb')
}
data = {
    'filename': 'video123'
}
auth = ('username', 'password')

response = requests.post(url, headers=headers, files=files, data=data, auth=auth)
print(response.json())
```

## Error Messages
- "Unauthorized": Invalid API key or basic auth credentials
- "No file uploaded or upload error": File upload failed or missing
- "Filename is required": Missing filename parameter
- "Invalid file extension": File must be .vtt or .txt
- "Video not found in database": Specified filename doesn't match any video
- "Failed to create directory": Server filesystem error
- "Failed to save file": Server filesystem error

## File Storage
Files are stored in the following structure:
```
/var/www/html/conspyre.tv/videos/[filename]/[filename].[extension]
```
Where:
- `[filename]` is the video's filename without extension
- `[extension]` is either `vtt` for subtitles or `txt` for transcripts

## Security Notes
- API key must be kept secure and not exposed in client-side code
- Basic auth credentials should be transmitted only over HTTPS
- Only .vtt and .txt files are accepted
- Filenames are validated against the database
- Directory traversal attempts are prevented

## Support
For issues or questions, contact the system administrator.
