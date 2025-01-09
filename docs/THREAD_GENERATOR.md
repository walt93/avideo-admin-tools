# Social Media Thread Generator - Technical Specification

## Overview
A system to automate the discovery of compelling social media content from video transcripts and assist in generating high-quality thread content for Truth Tide TV's social media presence.

## System Architecture

### 1. Topic Discovery System

#### Data Structure
```json
{
  "video_id": "string",
  "filename": "string",
  "analysis_date": "timestamp",
  "prompt_context": "string",  // Saved LLM conversation context
  "topics": [
    {
      "id": "string",
      "title": "string",
      "heat_score": number,    // 1-10 relevance rating
      "key_segments": [
        {
          "timestamp": "string",
          "transcript": "string",
          "duration": "number"
        }
      ],
      "related_topics": ["topic_ids"],
      "threads": [
        {
          "id": "string",
          "created_at": "timestamp",
          "original_content": "string",
          "critique": "string",
          "final_content": "string",
          "status": "draft|reviewed|published"
        }
      ]
    }
  ]
}
```

#### Topic Discovery Process
1. Initial Analysis
   - Input: Full video transcript
   - Process: LLM analyzes for key topics aligned with Truth Tide TV's editorial stance
   - Output: Initial topic list with heat scores

2. Iterative Refinement
   - System presents topics to LLM for expansion
   - Each iteration adds depth and connections
   - Process continues until no new significant topics found

3. Context Preservation
   - Store complete conversation context as template
   - Include key prompting strategies and editorial guidelines
   - Maintain for reuse in thread generation

### 2. User Interface Components

#### Topics Panel
1. Main Video List Enhancement
   - Add topics indicator (🔥) with count
   - Show only for videos with analyzed content

2. Topics Modal
   - Triggered from main list
   - Display topics sorted by heat score
   - Each topic card shows:
     * Title and relevance
     * Key transcript segments
     * Generate/Edit Thread button
     * Thread status indicator

3. Thread Editor
   - Rich text editor for thread content
   - Preview mode with social media formatting
   - Thread critique display
   - Save/Publish controls

### 3. Thread Generation System

#### Generation Process
1. Initial Generation
   - Use stored prompt context
   - Include relevant transcript segments
   - Apply Truth Tide TV's editorial guidelines

2. Auto-Critique
   - Submit generated thread for critique
   - Areas to analyze:
     * Message clarity
     * Emotional impact
     * Call to action effectiveness
     * Alignment with brand voice

3. Storage
   - Save both original and critiqued versions
   - Track revision history
   - Maintain publishing status

### 4. Database Schema Additions

```sql
CREATE TABLE video_topics (
    id VARCHAR(36) PRIMARY KEY,
    video_id INT NOT NULL,
    topics_json JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id)
);

CREATE TABLE topic_threads (
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

## Implementation Phases

### Phase 1: Topic Discovery
- Implement basic topic extraction
- Create topics JSON structure
- Add database tables
- Basic UI indicators

### Phase 2: Thread Generation
- Implement thread generation
- Add critique system
- Basic thread editor
- Storage system

### Phase 3: UI Enhancement
- Full topics modal
- Rich thread editor
- Preview functionality
- Publishing workflow

### Phase 4: Optimization
- Refine prompts
- Enhance topic discovery
- Add analytics
- Thread scheduling

## API Endpoints

### Topic Management
```
POST /api/videos/{id}/analyze-topics
GET /api/videos/{id}/topics
PUT /api/topics/{id}
DELETE /api/topics/{id}
```

### Thread Management
```
POST /api/topics/{id}/generate-thread
PUT /api/threads/{id}
POST /api/threads/{id}/critique
PUT /api/threads/{id}/publish
```

## Security Considerations
- Rate limiting on LLM API calls
- Access control for thread publishing
- Input sanitization for all user content
- API key management
- Backup strategy for topics/threads

## Performance Considerations
- Cache frequently accessed topics
- Batch LLM requests where possible
- Optimize large transcript handling
- Efficient JSON storage/retrieval

## Future Enhancements
- Multi-platform posting
- Engagement analytics
- A/B testing framework
- Automated scheduling
- Content calendar integration
