# AppTrack Technical Architecture v3.3.2

## System Overview

AppTrack is a production-ready web application built with a modern, scalable architecture that integrates AI-powered analysis capabilities with traditional application lifecycle management. Version 3.3.2 represents a production optimization release with comprehensive file cleanup and documentation updates following the comprehensive User Stories management and AI integration features.

## Architecture Stack

### Backend
- **Language**: PHP 8+
- **Database**: MySQL 8.0 with InnoDB engine (25 tables)
- **AI Integration**: OpenAI GPT-3.5-turbo API
- **Authentication**: Session-based with BCrypt password hashing
- **API Design**: RESTful endpoints with JSON responses (31 active endpoints)
- **File Structure**: 93 production PHP files in streamlined architecture

### Frontend
- **Framework**: Bootstrap 5.3 for responsive design
- **JavaScript**: Vanilla ES6+ with modular components (5 core JS components)
- **CSS Architecture**: Modular component-based styling (12 CSS components)
- **UI Components**: Choices.js for enhanced multi-select functionality
- **Visual Editor**: DrawFlow integration for diagram creation with connection fixes
- **Icons**: FontAwesome Pro with Bootstrap Icons fallback system
- **Modal System**: Bootstrap modals for AI analysis and integration diagrams

## Core System Components

### 1. Application Management Core
```
src/
├── models/
│   ├── Application.php      # Core application entity
│   ├── User.php            # User management
│   └── UserStory.php       # User Stories data access
├── controllers/
│   ├── ApplicationController.php
│   ├── UserController.php
│   ├── AuthController.php
│   └── UserStoryController.php  # User Stories business logic
└── managers/
    └── ActivityManager.php  # Work notes & audit logging
```

**Responsibilities:**
- CRUD operations for applications and User Stories
- User authentication and authorization
- Activity tracking and audit logging
- Data validation and sanitization
- Kanban board data management
- Cross-view filtering consistency
- User Stories and application relationship management

### 2. User Stories Management System (v3.3.0 NEW)
```
public/
├── user_stories.php           # Main User Stories dashboard
├── user_story_form.php        # Create/edit User Stories
├── user_story_view.php        # Detailed story view
└── api/user_stories/
    ├── get_stories.php         # List and filter stories
    ├── get_story.php           # Individual story details
    ├── create_story.php        # Create new stories
    ├── update_story.php        # Update existing stories
    ├── delete_story.php        # Delete stories
    ├── get_form_options.php    # Dynamic form options
    └── upload_attachment.php   # File attachment support
```

**Features:**
- **Agile Methodology**: Native User Story format support ("As a [role], I want [functionality], so that [benefit]")
- **Application Integration**: Seamless linking between User Stories and Applications
- **Advanced Filtering**: Filter by application, priority, status, and personal stories
- **Statistics Dashboard**: Real-time statistics cards showing story distribution
- **Jira Integration**: Built-in Jira ID field for external project management
- **File Attachments**: Complete file management system for story documentation
- **Consistent UI/UX**: Unified design language matching app_view.php patterns

### 3. Dashboard Management System (v3.2.1)
```
public/
├── dashboard.php           # Dual-view dashboard (table/kanban)
└── api/
    ├── kanban_data.php     # Kanban board data endpoint
    ├── update_phase.php    # Phase updates with audit logging
    └── update_status.php   # Status updates with change tracking
```

**Features:**
- **Dual-View Interface**: Seamless switching between table and kanban views
- **Advanced Filtering**: "Show mine only" toggle with cross-view consistency
- **Kanban Board**: Interactive drag-and-drop with 5-phase workflow
- **State Persistence**: URL parameter-based filter state management
- **Audit Logging**: Comprehensive change tracking for all kanban operations

### 4. Handover Management System (v3.3.0)
```
public/handover/
├── index.php              # Handover overview dashboard
├── wizard.php             # Comprehensive handover wizard
├── preview.php            # Document preview and export
└── sections/              # Modular handover sections
```

**Features:**
- **Application-Specific Isolation**: Documents tied to specific applications
- **Flexible Data Storage**: JSON-enabled storage for complex participant tables
- **Progress Tracking**: Step-by-step completion with visual indicators
- **Export Functionality**: Document generation and printing capabilities

### 4. AI Analysis Engine
```
src/services/
├── AIService.php           # OpenAI integration
└── DataAggregator.php      # Context preparation
```

**Features:**
- **Smart Data Aggregation**: Collects application data, work notes, and relationships
- **Multilingual Processing**: Handles Norwegian/English content with context preservation
- **Intelligent Caching**: SHA-256 hash-based change detection to prevent duplicate analyses
- **Configurable Models**: Support for different AI models and parameters per analysis type
- **Usage Tracking**: Comprehensive logging for cost monitoring and performance optimization

### 5. Database Layer
```
src/
├── db/
│   └── db.php              # PDO connection management
└── config/
    ├── config.php          # Application configuration
    └── database.php        # Database-specific settings
```

**Design Principles:**
- **25-Table Architecture**: Normalized schema with proper foreign key relationships
- **Reference Data Management**: Standardized lookup tables for consistent data entry
- **Handover Management**: Dedicated tables for application handover documentation
- **Audit Capabilities**: Comprehensive change tracking for compliance requirements
- **Security**: Prepared statements for SQL injection prevention
- **Performance**: Strategic indexing and transaction support for data integrity

## Kanban Board Workflow (v3.4.0 NEW)

### 1. Phase-Based Organization
```
Need → Solution → Build → Implement → Operate
```

**Phase Management:**
- Applications organized across 5 standardized delivery phases
- Real-time statistics with dynamic phase counters
- Visual progress tracking with total application counts
- Professional #F6F7FB background styling with 1px borders

### 2. Drag-and-Drop Functionality
```javascript
// Kanban board interaction workflow
dragStart(applicationId)
  ↓
dragOver(targetPhase)
  ↓
drop(applicationId, newPhase)
  ↓
// API call to update_phase.php
updatePhaseAPI(applicationId, newPhase)
  ↓
// Automatic audit logging
logKanbanChange(userId, applicationId, oldPhase, newPhase)
```

### 3. Cross-View Filtering
```php
// Consistent filtering logic across table and kanban views
$showMineOnly = isset($_GET['show_mine_only']) && $_GET['show_mine_only'] === '1';

if ($showMineOnly && in_array($role, ['admin', 'editor'])) {
    $whereConditions[] = "(a.assigned_to = :user_email 
                          OR a.project_manager = :user_email2 
                          OR a.product_owner = :user_email3)";
}
```

## AI Analysis Workflow

### 1. Request Initiation
```javascript
// Frontend triggers analysis request
generateAnalysis(analysisType, forceRefresh)
  ↓
// Check if analysis exists and is current
checkGenerateButtonState()
  ↓
// API call to ai_analysis.php
fetch('api/ai_analysis.php', { method: 'POST', ... })
```

### 2. Backend Processing
```php
// 1. Validate request and user permissions
// 2. Check for existing cached analysis
$existingAnalysis = checkCachedAnalysis($applicationId, $analysisType);

// 3. If no valid cache, gather context
$aggregator = new DataAggregator();
$context = $aggregator->gatherApplicationContext($applicationId);

// 4. Generate data hash for change detection
$dataHash = generateContextHash($context);

// 5. Call OpenAI API if needed
$aiService = new AIService();
$result = $aiService->generateAnalysis($context, $analysisType);

// 6. Store result with expiration
storeAnalysisResult($result, $dataHash, $expirationTime);
```

### 3. Intelligent Caching
- **Change Detection**: SHA-256 hash of input data (application + work notes + relationships)
- **Cache Expiration**: Configurable per analysis type (6-48 hours)
- **Automatic Invalidation**: Cache cleared when source data changes
- **Token Optimization**: Prevents unnecessary OpenAI API calls

## Security Architecture

### Authentication & Authorization
- **Session Management**: Secure PHP sessions with regeneration
- **Password Security**: BCrypt hashing with appropriate cost factor
- **Role-Based Access**: Three-tier permission system (admin/editor/viewer)
- **Input Validation**: Comprehensive sanitization and validation

### Data Protection
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: HTML entity encoding for output
- **CSRF Protection**: Token-based request validation
- **Sensitive Data**: Configuration-based field exclusion from AI analysis

### API Security
- **Rate Limiting**: Configurable per-user request limits
- **Domain Validation**: Allowed domains list for CORS
- **Error Handling**: Sanitized error messages to prevent information leakage

## Performance Optimizations

### Database
- **Indexes**: Strategic indexing on frequently queried columns
- **Connection Pooling**: Singleton pattern for database connections
- **Query Optimization**: Efficient JOINs and selective field retrieval

### AI Integration
- **Smart Caching**: Reduces API calls by 70-80% in typical usage
- **Async Processing**: Non-blocking UI during analysis generation
- **Token Management**: Configurable limits and usage tracking
- **Model Selection**: Appropriate model choice per analysis type

### Frontend
- **Lazy Loading**: Components loaded as needed
- **Minimal Dependencies**: Lightweight JavaScript footprint
- **Responsive Design**: Mobile-first Bootstrap implementation
- **Caching**: Browser-side caching for static assets

## Deployment Architecture

### Production Environment
```
Web Server (Apache/Nginx)
├── /public/                 # Document root
│   ├── *.php               # Public-facing pages
│   └── api/                # REST API endpoints
├── /src/                   # Application logic (secured)
├── /assets/                # CSS, JS, images
└── /docs/                  # Documentation
```

### Database Schema
- **25 Tables**: Normalized design with proper relationships and reference data
- **Handover Management**: 2 dedicated tables for application handover documentation
- **AI Integration**: 4 dedicated tables for analysis system
- **Audit System**: Comprehensive change tracking including kanban operations
- **File Storage**: BLOB-based attachment system

### Configuration Management
- **Environment Variables**: Sensitive data (API keys, DB credentials)
- **Config Files**: Application settings and AI parameters
- **Feature Flags**: Enable/disable functionality per environment

## Monitoring & Maintenance

### Logging
- **Kanban Audit Logs**: All drag-and-drop operations and phase changes tracked
- **AI Usage Logs**: Token consumption, processing time, success rates
- **Audit Logs**: All data changes with user attribution
- **Error Logs**: Comprehensive error tracking and reporting
- **Performance Metrics**: Response times and resource usage

### Maintenance Tasks
- **Cache Cleanup**: Automatic removal of expired AI analysis results
- **Log Rotation**: Configurable log retention policies
- **Database Optimization**: Regular index maintenance and cleanup
- **Security Updates**: Dependency and framework updates

## Future Enhancements

### Planned Integrations
- **ServiceNow CMDB**: Bi-directional application data sync
- **Microsoft Entra ID**: Enterprise authentication
- **Advanced Analytics**: Predictive modeling and trend analysis
- **Workflow Automation**: Approval processes and notifications

### Scalability Improvements
- **Microservices**: Component separation for horizontal scaling
- **API Gateway**: Centralized request routing and rate limiting
- **Caching Layer**: Redis/Memcached for distributed caching
- **Load Balancing**: Multi-server deployment support
