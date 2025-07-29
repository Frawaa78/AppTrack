# AppTrack v3.3.2 - Technical Architecture Documentation

This document provides a comprehensive overview of the technical architecture and recent improvements in AppTrack v3.3.2, focusing on production optimization, file structure cleanup, and comprehensive documentation updates following the User Stories Management System, Executive Dashboard, and Visual Diagram Editor enhancements.

## Version 3.3.2 Overview (January 2025)

AppTrack v3.3.2 is a production optimization release featuring:
- **Production Hardening**: Removed 19 obsolete development files for deployment readiness
- **File Structure Cleanup**: Comprehensive cleanup of test files, debug scripts, and executed migrations
- **Documentation Updates**: Complete documentation refresh reflecting cleaned codebase
- **DrawFlow Enhancements**: Fixed connection line positioning in visual diagram editor
- **FontAwesome Pro Integration**: Enhanced icon system with fallback mechanisms
- **93 Core PHP Files**: Streamlined production-ready file structure
- **31 API Endpoints**: Complete REST API for all system functions
- **12 CSS Components**: Modular styling architecture
- **5 JavaScript Components**: Client-side functionality modules

## User Stories Management System - New Feature v3.3.0

### System Architecture

#### User Stories Module Structure
```php
// User Stories complete module implementation
├── Database Layer
│   ├── user_stories table (17 fields with constraints)
│   └── user_story_attachments table (9 fields)
├── Model Layer
│   └── src/models/UserStory.php (Complete data access layer)
├── Controller Layer
│   └── src/controllers/UserStoryController.php (Business logic)
├── API Layer (7 dedicated endpoints)
│   ├── get_stories.php (List and filter stories)
│   ├── get_story.php (Individual story details)
│   ├── create_story.php (Create new stories)
│   ├── update_story.php (Update existing stories)
│   ├── delete_story.php (Delete stories)
│   ├── get_form_options.php (Dynamic form options)
│   └── upload_attachment.php (File attachment support)
└── Frontend Layer
    ├── user_stories.php (Main dashboard)
    ├── user_story_form.php (Create/edit form)
    └── user_story_view.php (Detailed view)
```

#### User Stories Database Schema
```sql
-- user_stories table (Complete schema)
CREATE TABLE user_stories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT,
    title VARCHAR(255) NOT NULL,
    user_role VARCHAR(100),
    functionality TEXT,
    benefit TEXT,
    description TEXT,
    priority ENUM('Low', 'Medium', 'High', 'Critical'),
    status ENUM('Backlog', 'In Progress', 'Testing', 'Done'),
    assigned_to INT,
    jira_id VARCHAR(100),
    tags TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- user_story_attachments table (File management)
CREATE TABLE user_story_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_story_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    FOREIGN KEY (user_story_id) REFERENCES user_stories(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);
```

## Dual-View Dashboard Management System

### System Architecture

#### Dashboard Structure
```php
// public/dashboard.php - Main dashboard with dual-view support
├── Session Authentication & Role Validation
├── URL Parameter Handling (show_mine_only, view)
├── Database Connection (PDO singleton pattern)
├── Conditional SQL Filtering
│   ├── User role-based filtering (admin/editor only)
│   ├── Three-tier user matching (assigned_to, project_manager, product_owner)
│   └── Cross-view consistency between table and kanban
├── View State Management
│   ├── Table view with sorting and pagination
│   └── Kanban view with drag-and-drop functionality
└── JavaScript Integration for view switching and state persistence
```

#### Kanban Board Implementation
```javascript
// assets/js/components/kanban-board.js - Interactive kanban functionality
class KanbanBoard {
    constructor(containerId, apiEndpoint) {
        this.container = containerId;
        this.apiEndpoint = apiEndpoint;
        this.draggedElement = null;
    }
    
    // Phase-based data organization
    async loadData(showMineOnly = false) {
        const response = await fetch(`${this.apiEndpoint}?show_mine_only=${showMineOnly}`);
        // Organize applications by phase
    }
    
    // Drag-and-drop functionality with audit logging
    async handleDrop(applicationId, newPhase) {
        await fetch('api/update_phase.php', {
            method: 'POST',
            body: JSON.stringify({ id: applicationId, phase: newPhase })
        });
        // Automatic audit logging on server side
    }
}
```

#### Cross-View Filtering Logic
```php
// Harmonized SQL filtering for consistent results
$whereConditions = [];
$params = [];

if ($showMineOnly && in_array($role, ['admin', 'editor'])) {
    $whereConditions[] = "(a.assigned_to = :user_email 
                          OR a.project_manager = :user_email2 
                          OR a.product_owner = :user_email3)";
    $params['user_email'] = $_SESSION['user_email'];
    $params['user_email2'] = $_SESSION['user_email'];
    $params['user_email3'] = $_SESSION['user_email'];
}
```

### System Architecture

#### Profile Page Structure
```php
// public/profile.php - Main profile management interface
├── Session Authentication (redirect if not logged in)
├── Database Connection (PDO singleton pattern)
├── AJAX Request Handling
│   ├── update_profile action (personal & contact information)
│   └── change_password action (secure password updates)
├── Current User Data Fetching
└── HTML Interface with JavaScript enhancement
```

#### Security Implementation
```php
// Field validation and sanitization
$allowed_fields = ['first_name', 'last_name', 'display_name', 'email', 'phone'];
if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

// Password security requirements
if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
    exit;
}

// Current password verification before change
if (!password_verify($current_password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit;
}
```

#### Automatic Display Name Generation
```javascript
// Auto-generate display name when first_name or last_name changes
document.addEventListener('input', function(e) {
    if (e.target.dataset.field === 'first_name' || e.target.dataset.field === 'last_name') {
        const expectedDisplayName = `${firstNameField.value.trim()} ${lastNameField.value.trim()}`.trim();
        if (!currentDisplayName || currentDisplayName === expectedDisplayName.replace(/\s+/g, ' ')) {
            displayNameField.value = expectedDisplayName;
        }
    }
});
```

#### Real-time Field Updates
```javascript
// Handle profile input changes with automatic saving
document.addEventListener('blur', function(e) {
    if (e.target.classList.contains('profile-input')) {
        const field = e.target.dataset.field;
        const value = e.target.value;
        updateProfile(field, value); // AJAX call to save immediately
    }
}, true);
```

### Database Integration

#### User Profile Data Fetching
```php
$stmt = $db->prepare("SELECT id, email, first_name, last_name, display_name, phone, role, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
```

#### Profile Updates with Auto-generation Logic
```php
// Auto-generate display_name if first_name or last_name is updated
if ($field === 'first_name' || $field === 'last_name') {
    $stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($field === 'first_name') {
        $auto_display_name = trim($value . ' ' . $user['last_name']);
    } else {
        $auto_display_name = trim($user['first_name'] . ' ' . $value);
    }
    
    // Update display_name automatically
    $stmt = $db->prepare("UPDATE users SET display_name = ? WHERE id = ?");
    $stmt->execute([$auto_display_name, $user_id]);
}
```

### User Interface Design

#### Professional Profile Card Interface
- **Profile Header**: Gradient background with auto-generated avatar
- **Role Badge System**: Color-coded badges (admin: red, editor: yellow, viewer: blue)
- **Form Sections**: Organized personal information and contact information sections
- **Floating Labels**: Bootstrap 5 form-floating design for modern UX
- **Loading States**: Visual feedback during AJAX operations
- **Toast Notifications**: Success/error feedback for all operations

#### Navigation Integration
```php
// topbar.php dropdown integration
<li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
```

### Security Features

#### Session-based Authentication
```php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
```

#### Password Security
- **Current Password Verification**: Required before any password change
- **Password Strength Requirements**: Minimum 6 characters
- **Real-time Validation**: Client-side password mismatch detection
- **Secure Hashing**: PHP password_hash() with PASSWORD_DEFAULT

#### Database Security
- **Prepared Statements**: All database queries use prepared statements
- **Input Sanitization**: Server-side validation for all fields
- **Error Handling**: Proper PDO exception handling with user-friendly messages

## Version 3.1.1 Overview - HOTFIX (July 21, 2025)

AppTrack v3.1.1 introduces critical stability fixes:
- **Visual Diagram Editor Bug Fix**: Resolved modal arrow disappearing issue when reopening
- **Enhanced SVG Regeneration**: Improved canvas recreation and marker management  
- **Automatic Arrow Recreation**: Seamless arrow restoration without manual intervention
- **Modal Event Integration**: Enhanced modal lifecycle handling for visual components
- **Production Stability**: Eliminated need for manual console commands

## Version 3.1 Overview

AppTrack v3.1 introduces significant enhancements:
- **Enhanced Activity Tracking**: User display names and internationalized date formats
- **DataMap Visual Architecture**: Interactive diagram system with DrawFlow library replacing Mermaid.js
- **Database Schema Extensions**: New columns for integration diagrams and notes
- **Improved User Experience**: Better readability and professional UI design
- **API Expansion**: New endpoints for integration diagram management
- **Work Notes System**: Manual activity entries with file attachments
- **Audit Log System**: Automatic field change tracking
- **Real-time Updates**: Dynamic activity feed with filtering
- **RESTful API**: Complete API endpoints for activity management
- **Enhanced Security**: Session validation and admin controls

## Visual Diagram Editor - Critical Bug Fix v3.1.1

### Issue Resolution
**Problem**: Visual diagram arrows disappeared when closing and reopening integration modals due to SVG container recreation without proper marker regeneration.

**Solution**: Comprehensive fix with multiple layers of protection:

### Technical Implementation

#### 1. Enhanced loadFromMermaidCode Method
```javascript
// Added at end of loadFromMermaidCode()
if (this.connections.size > 0) {
    console.log('🔄 Recreating all connections and markers after data load...');
    this.recreateAllConnectionsAndMarkers();
}
```

#### 2. New Public Method for Manual Fix
```javascript
// Public method for external arrow recreation
forceRecreateArrows() {
    console.log('🔧 FORCE RECREATE: Public method called to recreate all arrows');
    if (this.connections.size > 0) {
        console.log(`🔧 FORCE RECREATE: Found ${this.connections.size} connections to recreate`);
        this.recreateAllConnectionsAndMarkers();
        console.log('✅ FORCE RECREATE: Complete');
    } else {
        console.log('⚠️ FORCE RECREATE: No connections found to recreate');
    }
}
```

#### 3. Modal Event Integration in app_view.php
```php
// Added safeguard in initializeIntegrationDiagram()
setTimeout(() => {
    // ... existing code ...
    
    // CRITICAL FIX: Force recreation of arrows after modal reopen and data load
    if (typeof visualEditor.forceRecreateArrows === 'function') {
        console.log('🔧 MODAL REOPEN FIX: Force recreating arrows after data load');
        visualEditor.forceRecreateArrows();
    }
}, 1500);
```

### Architecture Flow
```
Modal Reopen Sequence:
1. Modal opens → createCanvas() recreates SVG
2. clearAll() removes existing elements  
3. loadFromMermaidCode() loads saved data
4. NEW: recreateAllConnectionsAndMarkers() called automatically
5. NEW: Additional safeguard call after 1.5s delay
6. Result: Arrows visible without manual intervention
```

### Prevention Strategy
- **Automatic Recreation**: Built into data loading process
- **Fail-safe Method**: Public method available for edge cases
- **Event Integration**: Modal lifecycle properly handles visual components
- **SVG Regeneration**: Improved marker definition recreation

## Activity Tracking Architecture - Enhanced v3.1

### Recent Enhancements (July 2025)
- **User Display Names**: Activity feed now shows user display names instead of email addresses
- **English Date Format**: Standardized date display with English day names (Monday, Tuesday, etc.)
- **Improved Queries**: ActivityManager optimized to fetch user display_name from database
- **Fallback Logic**: Graceful degradation to email when display_name unavailable

### Core Components
```
Activity Tracking System/
├── Backend Services/
│   ├── ActivityManager.php      # Core service layer
│   ├── API Endpoints/
│   │   ├── get_activity_feed.php    # Data retrieval
│   │   ├── add_work_note.php        # Manual entries
│   │   ├── hide_activity.php        # Admin controls
│   │   ├── show_activity.php        # Admin controls
│   │   └── download_attachment.php  # File handling
│   └── Database/
│       ├── work_notes table         # Manual activities
│       └── audit_log table          # Automatic tracking
├── Frontend Components/
│   ├── activity_tracker.php     # UI component
│   ├── activity-tracker.js      # JavaScript controller
│   └── activity-tracker.css     # Styling system
└── Integration/
    └── app_form.php             # Embedded tracker
```

## Integration Architecture System - NEW v3.1

### Overview
The DataMap Visual Architecture System provides interactive diagram creation and management for applications with integrations, using the DrawFlow library for professional interactive diagram editing and AI-powered analysis.

### Architecture Components
```
Integration Architecture/
├── Frontend/
│   ├── Integration Modal/
│   │   ├── Mermaid Container      # Diagram display area
│   │   ├── Code Editor           # Mermaid syntax input
│   │   ├── Notes Editor          # Integration documentation
│   │   └── Template Loader       # Pre-built diagram templates
│   ├── Trigger Button/
│   │   └── Inline with S.A. Document field (38x38px icon)
│   └── JavaScript/
│       ├── Mermaid.js Library    # Diagram rendering engine
│       ├── Modal Management      # Show/hide functionality
│       └── Template System       # Built-in diagram patterns
├── Backend/
│   ├── Database Schema/
│   │   ├── integration_diagram   # TEXT column for Mermaid code
│   │   └── integration_notes     # TEXT column for documentation
│   └── API Endpoints/
│       ├── get_integration_diagram.php  # Retrieve diagram & notes
│       └── save_integration_diagram.php # Save with role validation
└── Templates/
    ├── Basic Integration         # Database + API connections
    ├── Data Pipeline            # ETL process flow
    ├── API Integration          # Gateway + Auth + Business Logic
    └── Microservices           # Load Balancer + Multiple Services
```

### Database Schema Updates
```sql
-- Added to applications table
ALTER TABLE applications ADD COLUMN integration_diagram TEXT DEFAULT NULL;
ALTER TABLE applications ADD COLUMN integration_notes TEXT DEFAULT NULL;
```

### API Endpoints

#### GET /api/get_integration_diagram.php
```php
// Retrieves integration diagram and notes
GET ?id=<application_id>
Response: {
    "success": true,
    "diagram_code": "graph TD\n...",
    "notes": "Integration documentation..."
}
```

#### POST /api/save_integration_diagram.php
```php
// Saves diagram code and notes (Admin/Editor only)
POST {
    "application_id": 123,
    "diagram_code": "graph TD\n...",
    "notes": "Updated documentation"
}
Response: {
    "success": true,
    "message": "Integration data saved successfully"
}
```

### UI/UX Design

#### Button Integration
- **Placement**: Inline with S.A. Document field for optimal workflow
- **Design**: 38x38px icon-only button with bi-diagram-3 Bootstrap icon
- **Visibility**: Only shown when application integrations="Yes"
- **Styling**: `btn-outline-success` for visual consistency

#### Text Overflow Solution
- **Problem**: Long S.A. Document URLs overflow when button is present
- **Solution**: Flex layout with `min-width: 0` and `text-overflow: ellipsis`
- **Implementation**: Wrapper div with `overflow: hidden` around link element

#### Modal Design
- **Size**: Bootstrap `modal-xl` for adequate editing space
- **Layout**: Two-column layout (Code Editor | Notes Editor)
- **Responsive**: Mobile-optimized with proper scaling
- **Accessibility**: Proper ARIA labels and keyboard navigation

### Service Layer Pattern
The `ActivityManager.php` implements a service layer pattern:

```php
class ActivityManager {
    // Core functionality
    public function getActivityFeed($applicationId, $filters = [])
    public function addWorkNote($applicationId, $userId, $message, $priority, $isVisible, $attachment)
    public function logFieldChange($applicationId, $userId, $fieldName, $oldValue, $newValue)
    
    // Admin controls
    public function hideActivity($activityId)
    public function showActivity($activityId)
    
    // File management
    private function validateAttachment($file)
    private function saveAttachment($file)
}
```

## CSS Architecture Revolution

### Enhanced Component System
```
assets/css/
├── main.css                 # Primary import and global styles
├── components/              # Reusable component library
│   ├── activity-tracker.css # Activity system styling
│   ├── forms.css           # Horizontal form layout system
│   ├── buttons.css         # Button states and interactions
│   ├── choices.css         # Multi-select dropdown styling
│   ├── range-slider.css    # Interactive slider components
│   └── user-dropdown.css   # User interface components
└── pages/                  # Page-specific overrides
    ├── app-form.css        # Form page enhancements
    └── app-view.css        # View page styling
```

### Import Strategy
The new CSS architecture uses a centralized import system:

```css
/* main.css - Central orchestration */
@import url('./components/forms.css');
@import url('./components/buttons.css');
@import url('./components/choices.css');
@import url('./components/range-slider.css');

/* Global styles and typography */
body { font-size: 0.9rem; }
/* Additional global styles... */
```

### Key Design Principles
1. **Component Isolation**: Each component has dedicated styling
2. **Consistent Naming**: BEM-like methodology for class names
3. **Performance Focus**: Optimized selectors and minimal reflows
4. **Maintainability**: Clear separation of concerns

## JavaScript Framework Enhancement

### Modular Architecture
```
assets/js/
├── main.js                  # Core initialization and global functions
├── components/              # Reusable JavaScript modules
│   ├── activity-tracker.js # Activity tracking system
│   ├── form-handlers.js    # Form interaction logic
│   └── choices-init.js     # Multi-select component initialization
└── pages/                  # Page-specific JavaScript
    ├── app-form.js         # Form page functionality
    └── app-view.js         # View page enhancements
```

### Activity Tracker JavaScript Architecture
The Activity Tracker uses a class-based approach for better organization:

```javascript
class ActivityTracker {
    constructor(applicationId) {
        this.applicationId = applicationId;
        this.currentFilter = 'all';
        this.init();
    }
    
    async loadActivityFeed(filter = 'all') {
        // API call to get_activity_feed.php
        // Dynamic DOM updates
        // Error handling
    }
    
    async submitWorkNote() {
        // Form validation
        // File upload handling
        // API call to add_work_note.php
        // Real-time feed refresh
    }
    
    setupEventListeners() {
        // Filter button handlers
        // Form submission
        // File upload validation
    }
}

// Initialize on page load
window.ActivityTracker = ActivityTracker;
```

### Global Function Strategy
All critical functions are attached to the window object for maximum reliability:

```javascript
// form-handlers.js
window.clearField = function(fieldId) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.value = '';
    field.focus();
  }
};

window.setPhase = function(value, button) {
  document.getElementById('phase_input').value = value;
  button.parentElement.querySelectorAll('button').forEach(btn => 
    btn.classList.remove('active'));
  button.classList.add('active');
};

window.updateHandoverTooltip = function(slider) {
  // Complex tooltip positioning and progress tracking
  // See full implementation in form-handlers.js
};
```

### Error Handling Pattern
```javascript
document.addEventListener('DOMContentLoaded', function() {
  try {
    // Initialize components with error boundaries
    initializeRelatedApplicationsChoice();
    initializeHandoverSlider();
    initializePopovers();
    
    // Initialize Activity Tracker if present
    const applicationId = document.querySelector('[data-application-id]')?.dataset.applicationId;
    if (applicationId) {
      window.activityTracker = new ActivityTracker(applicationId);
    }
  } catch (error) {
    console.error('Component initialization failed:', error);
    // Graceful degradation to basic functionality
  }
});
```

## API Integration System

### RESTful Search Endpoint
The search API provides real-time application lookup functionality:

**Endpoint**: `GET /api/search_applications.php`

**Parameters**:
- `q`: Search query (minimum 2 characters)
- `exclude`: Application ID to exclude from results
- `selected`: Comma-separated list of already selected IDs

**Response Format**:
```json
[
  {
    "value": "123",
    "label": "Finance System (SAP ERP)",
    "customProperties": {
      "service": "SAP ERP",
      "description": "Finance System"
    }
  }
]
```

### Implementation Details
```php
// api/search_applications.php
$query = trim($_GET['q'] ?? '');
$exclude = (int)($_GET['exclude'] ?? 0);
$selectedIds = $_GET['selected'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// SQL with proper parameter binding
$sql = "SELECT id, short_description, application_service 
        FROM applications 
        WHERE (short_description LIKE ? OR application_service LIKE ?)
        AND id != ?
        ORDER BY short_description
        LIMIT 20";
```

### Client-Side Integration
```javascript
// choices-init.js
relationshipSelect.addEventListener('search', function(e) {
  const query = e.detail.value;
  if (query.length < 2) return;

  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetch(`api/search_applications.php?q=${encodeURIComponent(query)}`)
      .then(response => response.json())
      .then(data => {
        relationshipChoices.clearChoices();
        relationshipChoices.setChoices(data, 'value', 'label', true);
      })
      .catch(error => console.error('Search error:', error));
  }, 300); // Debounced for performance
});
```

## Database Architecture Enhancements

### Complete Schema Implementation
All application form fields are now properly mapped to database columns:

```sql
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    short_description VARCHAR(255) NOT NULL,
    application_service VARCHAR(255) NULL,
    relevant_for VARCHAR(255) NULL,
    phase VARCHAR(100) NULL,
    status VARCHAR(100) NULL,
    handover_status INT DEFAULT 0,
    contract_number VARCHAR(255) NULL,
    contract_responsible VARCHAR(255) NULL,
    information_space TEXT NULL,
    ba_sharepoint_list TEXT NULL,
    relationship_yggdrasil TEXT NULL,
    assigned_to VARCHAR(255) NULL,
    preops_portfolio VARCHAR(255) NULL,
    application_portfolio VARCHAR(255) NULL,
    delivery_responsible VARCHAR(255) NULL,
    corporator_link TEXT NULL,
    project_manager VARCHAR(255) NULL,
    product_owner VARCHAR(255) NULL,
    due_date DATE NULL,
    deployment_model VARCHAR(255) NULL,
    integrations VARCHAR(255) NULL,
    sa_document TEXT NULL,
    business_need TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Performance Indexes
```sql
-- Search optimization indexes
CREATE INDEX idx_applications_short_description ON applications(short_description);
CREATE INDEX idx_applications_application_service ON applications(application_service);
CREATE INDEX idx_applications_phase ON applications(phase);
CREATE INDEX idx_applications_status ON applications(status);
CREATE INDEX idx_applications_relevant_for ON applications(relevant_for);
```

### Lookup Table Integration
```sql
-- Standardized lookup values
INSERT INTO phases (name) VALUES
('Need'), ('Solution'), ('Build'), ('Implement'), ('Operate');

INSERT INTO statuses (name) VALUES
('Unknown'), ('Not started'), ('Ongoing Work'), ('On Hold'), ('Completed');

INSERT INTO deployment_models (name) VALUES
('Client Application'), ('On-premise'), ('SaaS'), ('Externally hosted');
```

## Component Implementation Details

### Horizontal Form Layout
The space-efficient form system reduces vertical scrolling by 50%:

```css
.form-group-horizontal {
  display: flex;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.form-label {
  font-weight: 400;
  color: #6c757d;
  width: 160px;               /* Fixed width for alignment */
  text-align: right;          /* Right-aligned labels */
  padding-right: 10px;        /* Consistent spacing */
  padding-top: 0.375rem;      /* Vertical alignment */
  margin-bottom: 0;
}

.form-group-horizontal .form-control,
.form-group-horizontal .form-select,
.form-group-horizontal .input-group {
  flex: 1;                    /* Fill remaining space */
}
```

### Enhanced Handover Slider
Interactive progress tracking with visual markers:

```css
.range-container {
  position: relative;
  margin-bottom: 20px;
}

.range-markers {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.range-marker {
  width: 2px;
  height: 12px;
  background-color: #dee2e6;
  border-radius: 1px;
  transition: all 0.2s ease;
}

.range-marker.active {
  background-color: #007bff;
}

.tooltip-follow {
  position: absolute;
  top: 27px;                  /* Positioned below slider */
  background: #6C757D;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.69rem;
  white-space: nowrap;
  pointer-events: none;
  z-index: 1000;
  transform: translateX(-50%);
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
```

### Choices.js Multi-Select Component
Advanced dropdown with real-time search and custom styling:

```css
.choices__list--dropdown {
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  max-height: 244px;          /* Exactly 4 items visible */
  overflow-y: scroll;
  background: white;
  z-index: 1050;
}

.choices__item--choice {
  padding: 16px 20px;
  min-height: 60px;
  display: flex;
  align-items: center;
  transition: all 0.2s ease;
  cursor: pointer;
}

.choices__item--choice:hover {
  background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
  color: #1976d2;
  border-left: 4px solid #2196f3;
  transform: translateX(3px) scale(1.02);
  box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
}
```

## Security Implementation

### Input Validation and Sanitization
```php
// Server-side validation
$fields = [
    'short_description', 'application_service', 'relevant_for', 
    'phase', 'status', 'handover_status', 'contract_number', 
    'contract_responsible', 'information_space', 'ba_sharepoint_list',
    // ... all other fields
];

$data = [];
foreach ($fields as $field) {
    $data[$field] = trim($_POST[$field] ?? '');
}

// Validate handover_status is numeric
$data['handover_status'] = is_numeric($data['handover_status']) ? 
    (int)$data['handover_status'] : 0;
```

### SQL Injection Prevention
```php
// PDO prepared statements for all queries
$stmt = $db->prepare('SELECT * FROM applications WHERE id = :id');
$stmt->execute([':id' => $id]);

// Search with proper parameter binding
$stmt = $db->prepare($sql);
$stmt->execute([$searchTerm, $searchTerm, $exclude, ...$selectedArray]);
```

### Session Management
```php
session_start();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Role-based access control
$role = $_SESSION['user_role'] ?? 'viewer';
if ($role !== 'admin' && $role !== 'editor') {
    // Restrict access to read-only
}
```

## Cross-Browser Compatibility

### Supported Browser Matrix
| Browser | Minimum Version | Features Supported |
|---------|----------------|-------------------|
| Chrome  | 90+           | All features      |
| Firefox | 88+           | All features      |
| Safari  | 14+           | All features      |
| Edge    | 90+           | All features      |

### CSS Fallbacks
```css
/* Flexbox with vendor prefixes */
.form-group-horizontal {
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
}

/* Custom properties with fallbacks */
.form-range::-webkit-slider-runnable-track {
  background: linear-gradient(to right, 
    #007bff 0%, 
    #007bff var(--progress, 30%), 
    #f1f3f5 var(--progress, 30%), 
    #f1f3f5 100%);
}
```

### JavaScript Feature Detection
```javascript
// Feature detection before enhancement
if (window.fetch && window.Promise) {
  // Modern fetch API
  fetch(url).then(response => response.json());
} else {
  // XMLHttpRequest fallback
  var xhr = new XMLHttpRequest();
  xhr.open('GET', url);
  xhr.send();
}
```

## Performance Optimizations

### CSS Performance
- **Efficient Selectors**: Avoid over-specific CSS selectors
- **Hardware Acceleration**: Use transform and opacity for animations
- **Critical CSS**: Inline critical styles to prevent render blocking
- **Component Isolation**: Scoped styles prevent cascade conflicts

### JavaScript Performance
- **Event Delegation**: Efficient handling of multiple elements
- **Debounced API Calls**: 300ms delay for search queries
- **DOM Caching**: Store frequently accessed elements
- **Lazy Initialization**: Load components only when needed

### Database Performance
- **Indexed Searches**: All searchable fields have appropriate indexes
- **Query Limits**: Search results limited to 20 items
- **Connection Pooling**: Singleton database pattern
- **Prepared Statements**: Optimized query execution

## Testing and Quality Assurance

### Automated Testing Strategy
- **Unit Tests**: JavaScript functions and PHP classes
- **Integration Tests**: API endpoints and database operations
- **E2E Tests**: Complete user workflows
- **Performance Tests**: Load testing and optimization

### Manual Testing Checklist
- [ ] Cross-browser functionality verification
- [ ] Mobile responsiveness testing
- [ ] Accessibility compliance (WCAG 2.1 AA)
- [ ] Form validation and error handling
- [ ] Search functionality and API integration
- [ ] Database operations and data integrity

### Quality Metrics
- **Code Coverage**: >80% for critical functions
- **Performance**: Core Web Vitals compliance
- **Accessibility**: WCAG 2.1 AA compliance
- **Browser Support**: 95%+ user coverage

## Deployment and Maintenance

### Production Deployment
```bash
# Example deployment script
git pull origin main
composer install --no-dev --optimize-autoloader
npm run build:production
php artisan config:cache
php artisan route:cache
systemctl reload apache2
```

### Maintenance Tasks
- **Daily**: Log file rotation and cleanup
- **Weekly**: Database optimization and index analysis
- **Monthly**: Security updates and dependency updates
- **Quarterly**: Full backup testing and disaster recovery

### Monitoring and Alerts
- **Application Performance**: Response time monitoring
- **Database Performance**: Query execution time tracking
- **Error Tracking**: Centralized error logging
- **Uptime Monitoring**: 24/7 availability checks

## Future Roadmap

### Short-term Enhancements (3-6 months)
- **Dark Mode**: User-selectable theme switching
- **Advanced Search**: Faceted search with filters
- **Mobile App**: Progressive Web App implementation
- **Real-time Notifications**: WebSocket integration

### Medium-term Goals (6-12 months)
- **ServiceNow Integration**: Full CMDB synchronization
- **Workflow Engine**: Automated approval processes
- **Advanced Analytics**: Business intelligence dashboard
- **Multi-language Support**: Internationalization

### Long-term Vision (12+ months)
- **AI Integration**: Intelligent data suggestions
- **Enterprise SSO**: Full Entra ID integration
- **API Ecosystem**: Third-party integration platform
- **Advanced Reporting**: Custom report builder

---

> **Documentation Version**: 2.0.0 (July 2025)  
> **Last Updated**: July 15, 2025  
> **Status**: Current and Complete  
> 
> This document reflects the current technical architecture of AppTrack v2.0. All features described are implemented and tested. Update this documentation when making architectural changes or adding new features.
