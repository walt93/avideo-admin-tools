# Social Media Integration Project Map

## Directory Structure
```
/management/
├── metadata.php                 # Main entry point (existing)
├── social/                      # New directory for social media tools
│   ├── analyze_topics.php      # Topic discovery endpoint
│   ├── generate_thread.php     # Thread generation endpoint
│   ├── critique_thread.php     # Thread critique endpoint
│   └── update_thread.php       # Thread update/save endpoint
└── includes/                    # Shared components
    ├── db_config.php           # Database configuration (existing)
    ├── social_prompts.php      # LLM prompt templates
    └── social_utils.php        # Shared utility functions

/videos/                         # Base video directory (existing)
└── [video_id]/                 # Individual video directories
    ├── [video_id].mp4         # Video file
    ├── [video_id].txt         # Transcript
    └── [video_id].vtt         # Subtitles
```

## Integration Points in metadata.php

### 1. Database Connection Section
```php
// After existing database connection code
require_once('includes/social_utils.php');
```

### 2. HTML Table Modification
Add topic indicator column:
```php
<th class="col-actions">Files / Topics / Actions</th>
```

### 3. Row Display Modification
Add topic status and controls:
```php
<td class="col-actions">
    <!-- Existing buttons -->
    <?php if ($video['media_files']['has_txt']): ?>
        <button class="btn btn-sm btn-info" 
                onclick="showTopics(<?= $video['id'] ?>)">
            Topics <?= hasTopics($video['id']) ? '🔥' : '?' ?>
        </button>
    <?php endif; ?>
</td>
```

### 4. Modal Dialogs to Add
```html
<!-- Topics Modal -->
<div class="modal fade" id="topicsModal">
    <!-- Topics list and management -->
</div>

<!-- Thread Editor Modal -->
<div class="modal fade" id="threadModal">
    <!-- Thread editing interface -->
</div>
```

### 5. JavaScript Functions to Add
```javascript
// Topic Management
async function showTopics(videoId)
async function analyzeTopics(videoId)
async function generateThread(topicId)
async function critiqueTopic(threadId)
async function saveThread(threadId)
```

## Required Database Tables

### 1. Create video_topics Table
```sql
CREATE TABLE IF NOT EXISTS video_topics (
    id VARCHAR(36) PRIMARY KEY,
    video_id INT NOT NULL,
    topics_json JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id)
);
```

### 2. Create topic_threads Table
```sql
CREATE TABLE IF NOT EXISTS topic_threads (
    id VARCHAR(36) PRIMARY KEY,
    topic_id VARCHAR(36) NOT NULL,
    content TEXT NOT NULL,
    critique TEXT,
    final_content TEXT,
    status ENUM('draft', 'reviewed', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES video_topics(id)
);
```

## Core PHP Functions Needed in social_utils.php

```php
function hasTopics($videoId): bool
function getVideoTopics($videoId): array
function saveTopics($videoId, array $topics): bool
function generateThreadForTopic($topicId): string
function critiqueThread($threadId): string
function saveThread($threadId, $content, $status): bool
```

## Implementation Order

1. Database Setup
   - Create new tables
   - Add indexes for performance

2. Basic UI Integration
   - Add topic button to metadata.php
   - Create basic topics modal
   - Add topic indicator logic

3. Topic Analysis System
   - Implement analyze_topics.php
   - Create topic discovery logic
   - Add topic storage functions

4. Thread Generation
   - Build thread generation endpoint
   - Implement critique system
   - Create thread storage system

5. UI Enhancement
   - Complete topics modal
   - Add thread editor
   - Implement preview functionality

## Configuration Requirements

1. OpenAI API Configuration
```php
// Add to existing config
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));
define('OPENAI_MODEL', 'gpt-4');
```

2. File Path Configuration
```php
define('TOPICS_CACHE_DIR', '/var/www/cache/topics');
```

## Error Handling

Add to existing error handler:
```php
function handleSocialError($error, $context = []): void {
    error_log("Social Media Tool Error: " . $error);
    if (DEBUG_MODE) {
        error_log(print_r($context, true));
    }
}
```

## Security Considerations

1. Input Validation
```php
// Add to social_utils.php
function validateVideoAccess($videoId): bool
function sanitizeTopicInput($input): array
function validateThreadContent($content): bool
```

2. Rate Limiting
```php
// Add to social_utils.php
function checkRateLimit($type, $id): bool
```

## Testing Plan

1. Unit Tests
   - Topic discovery accuracy
   - Thread generation quality
   - Database operations

2. Integration Tests
   - UI functionality
   - API endpoint responses
   - Error handling

3. Load Testing
   - Multiple simultaneous requests
   - Large transcript handling
   - Database performance
