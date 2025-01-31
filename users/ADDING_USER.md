# Adding a New User

This guide outlines the steps required to add a new content creator to the management system.

## 1. Apache Authentication

Add the user credentials to Apache's authentication file:
```bash
sudo htpasswd /etc/apache2/.htpasswd [username]
```

## 2. User Configuration

### 2.1 Update users.json
Add the user entry to `/management/users/config/users.json`:
```json
{
  "users": {
    "[username]": {
      "id": [avideo_user_id],
      "name": "[Display Name]",
      "profile": {
        "photo": "/videos/userPhoto/photo[id].png",
        "display_name": "[Display Name]",
        "social_handle": "@[handle]"
      },
      "categories": [
        {
          "name": "[Category Name]",
          "categories_id": [category_id],
          "users_id": [avideo_user_id]
        }
        // ... additional categories ...
      ]
    }
  }
}
```

## 3. Directory Setup

### 3.1 Create User Directory
```bash
sudo mkdir -p /var/www/html/conspyre.tv/management/users/[username]
```

### 3.2 Copy Required Files
```bash
# Copy index.php
sudo cp /var/www/html/conspyre.tv/management/users/user_index.php \
    /var/www/html/conspyre.tv/management/users/[username]/index.php

# Copy upload.php
sudo cp /var/www/html/conspyre.tv/management/users/[username]/upload.php \
    /var/www/html/conspyre.tv/management/users/[username]/upload.php
```

### 3.3 Set Permissions
```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/html/conspyre.tv/management/users/[username]

# Set directory permissions
sudo chmod 755 /var/www/html/conspyre.tv/management/users/[username]

# Set file permissions
sudo chmod 644 /var/www/html/conspyre.tv/management/users/[username]/*
```

## 4. Configure Access Control

### 4.1 Add to .htaccess
In `/management/users/.htaccess`, ensure the user directory has proper access rules:
```apache
<DirectoryMatch "^/[username]/">
    AuthType Basic
    AuthName "Restricted Access"
    AuthUserFile /etc/apache2/.htpasswd
    Require user [username] walt
</DirectoryMatch>
```

## 5. Verification Steps

1. Check file structure:
```bash
ls -la /var/www/html/conspyre.tv/management/users/[username]/
```

2. Verify users.json entry:
```bash
cat /var/www/html/conspyre.tv/management/users/config/users.json | grep [username]
```

3. Test authentication:
```bash
curl -u [username]:[password] https://conspyre.tv/management/users/[username]/index.php
```

4. Test access with 'walt' credentials:
```bash
curl -u walt:[password] https://conspyre.tv/management/users/[username]/index.php
```

## Common Issues

1. Access Denied
- Check Apache credentials are properly set
- Verify user exists in users.json
- Confirm directory permissions
- Check .htaccess configuration

2. Blank Page
- Check PHP error logs
- Verify all required files are present
- Confirm file permissions

3. Missing Content
- Verify user ID in users.json matches AVideo CMS
- Check category IDs are correct
- Confirm profile photo path is valid

## Shortcut: Using deploy.sh

You can also add a user by updating users.json and running the deploy script:
```bash
# Add user entry to users.json first, then:
./deploy.sh
```

The deploy script will create the necessary directories and set up files with correct permissions.