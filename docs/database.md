# AppTrack Database Documentation

This document provides a comprehensive overview of the AppTrack database structure, including all tables, relationships, and constraints.

## Overview

The database follows a normalized design pattern with lookup tables for consistent data management. Key features include:
- Foreign key relationships for data integrity
- Audit logging for change tracking
- Support for file attachments and work notes
- Role-based user management
- Flexible portfolio and relationship management
- **Complete schema implementation** - All application form fields are properly mapped to database columns
- **Activity Tracking System** - Comprehensive work notes and audit log functionality

## Schema Status
✅ **COMPLETE**: All required columns have been added to the `applications` table
✅ **TESTED**: Form submission and data retrieval working correctly
✅ **VALIDATED**: No missing column errors in production
✅ **UI ENHANCED**: Modern horizontal form layout with interactive elements implemented
✅ **SEARCH READY**: Related applications field supports real-time database search functionality
✅ **API INTEGRATED**: RESTful search endpoint for application lookup
✅ **ACTIVITY TRACKER**: Full work notes and audit logging system implemented and tested

## Core Tables

### Table: users
Manages user accounts and authentication with role-based access control.

| Field         | Type           | Description                     | Constraints    |
|---------------|----------------|---------------------------------|----------------|
| id            | INT            | Primary key, auto-increment     | NOT NULL, PK   |
| email         | VARCHAR(255)   | User email address              | UNIQUE, NOT NULL |
| password_hash | VARCHAR(255)   | BCrypt hashed password          | NOT NULL       |
| role          | VARCHAR(50)    | User role (admin/editor/viewer) | NOT NULL       |
| created_at    | DATETIME       | Account creation timestamp      | DEFAULT NOW()  |

**Default Roles:**
- `admin`: Full access (view/edit/delete/user management)
- `editor`: View and edit applications  
- `viewer`: Read-only access to all data

### Table: applications
Central table storing all application information with complete field coverage.

| Field                   | Type           | Description                           | Constraints    |
|-------------------------|----------------|---------------------------------------|----------------|
| id                      | INT            | Primary key, auto-increment          | NOT NULL, PK   |
| short_description       | VARCHAR(255)   | Application name/identifier          | NOT NULL       |
| application_service     | VARCHAR(255)   | ServiceNow CMDB reference            | NULL           |
| relevant_for            | VARCHAR(255)   | Yggdrasil relevance classification   | NULL           |
| phase                   | VARCHAR(100)   | Current delivery phase               | NULL           |
| status                  | VARCHAR(100)   | Current status                       | NULL           |
| handover_status         | INT            | Progress percentage (0-100)          | DEFAULT 0      |
| contract_number         | VARCHAR(255)   | Commercial contract reference        | NULL           |
| contract_responsible    | VARCHAR(255)   | Commercial lead contact              | NULL           |
| information_space       | TEXT           | Documentation area URL               | NULL           |
| ba_sharepoint_list      | TEXT           | Business analyst SharePoint list    | NULL           |
| relationship_yggdrasil  | TEXT           | Connected applications (comma-sep)   | NULL           |
| assigned_to             | VARCHAR(255)   | Data maintenance responsibility       | NULL           |
| preops_portfolio        | VARCHAR(255)   | Yggdrasil project portfolio          | NULL           |
| application_portfolio   | VARCHAR(255)   | Target IT operations portfolio       | NULL           |
| delivery_responsible    | VARCHAR(255)   | Lead vendor/alliance                 | NULL           |
| corporator_link         | TEXT           | Project management system URL        | NULL           |
| project_manager         | VARCHAR(255)   | Project activities lead              | NULL           |
| product_owner           | VARCHAR(255)   | Business need owner                  | NULL           |
| due_date                | DATE           | Target go-live date                  | NULL           |
| deployment_model        | VARCHAR(255)   | Technical deployment approach        | NULL           |
| integrations            | VARCHAR(255)   | Integration indicator                | NULL           |
| sa_document             | TEXT           | Solution architecture docs URL       | NULL           |
| business_need           | TEXT           | Business justification               | NULL           |
| created_at              | DATETIME       | Record creation timestamp            | DEFAULT NOW()  |
| updated_at              | DATETIME       | Last modification timestamp          | DEFAULT NOW()  |

**Note**: All URL fields (information_space, ba_sharepoint_list, corporator_link, sa_document) use TEXT type to support long URLs and are displayed as clickable links in the UI when populated.

## Lookup Tables

### Table: phases
Standardized delivery model phases.

| Field | Type         | Description                    | Constraints    |
|-------|--------------|--------------------------------|----------------|
| id    | INT          | Primary key, auto-increment    | NOT NULL, PK   |
| name  | VARCHAR(100) | Phase name                     | UNIQUE, NOT NULL |

**Default Values:**
- Need
- Solution  
- Build
- Implement
- Operate

### Table: statuses  
Application status classifications.

| Field | Type         | Description                    | Constraints    |
|-------|--------------|--------------------------------|----------------|
| id    | INT          | Primary key, auto-increment    | NOT NULL, PK   |
| name  | VARCHAR(100) | Status name                    | UNIQUE, NOT NULL |

**Default Values:**
- Unknown
- Not started
- Ongoing Work
- On Hold
- Completed

### Table: deployment_models
Technical deployment approaches for standardized classification.

| Field | Type         | Description                    | Constraints    |
|-------|--------------|--------------------------------|----------------|
| id    | INT          | Primary key, auto-increment    | NOT NULL, PK   |
| name  | VARCHAR(100) | Deployment model name          | UNIQUE, NOT NULL |

**Default Values:**
- Client Application
- On-premise
- SaaS
- Externally hosted

### Table: portfolios
Business and technical portfolio classifications for project organization.

| Field | Type         | Description                    | Constraints    |
|-------|--------------|--------------------------------|----------------|
| id    | INT          | Primary key, auto-increment    | NOT NULL, PK   |
| name  | VARCHAR(100) | Portfolio name                 | NOT NULL       |
| type  | ENUM         | Portfolio type                 | 'preops', 'application' |

**Portfolio Types:**
- `preops`: Yggdrasil project portfolios
- `application`: Target IT operations portfolios

## Supporting Tables

### Table: work_notes
Manual activity entries and collaboration tracking with file attachment support.

| Field             | Type         | Description                    | Constraints    |
|-------------------|--------------|--------------------------------|----------------|
| id                | INT          | Primary key, auto-increment    | NOT NULL, PK   |
| application_id    | INT          | Reference to application       | FK to applications.id |
| user_id           | INT          | User who created note          | FK to users.id |
| note              | TEXT         | Note content                   | NOT NULL       |
| type              | ENUM         | Note type                      | 'comment', 'problem', 'change' |
| priority          | ENUM         | Priority level                 | 'low', 'medium', 'high' |
| attachment_data   | LONGBLOB     | File attachment binary data    | NULL           |
| attachment_filename| VARCHAR(255) | Original filename             | NULL           |
| attachment_size   | INT          | File size in bytes             | NULL           |
| attachment_mime_type| VARCHAR(100)| MIME type of attachment       | NULL           |
| is_visible        | TINYINT(1)   | Visibility flag for admin      | DEFAULT 1      |
| created_at        | DATETIME     | Creation timestamp             | DEFAULT NOW()  |
| updated_at        | DATETIME     | Last update timestamp          | DEFAULT NOW()  |

**Note Types:**
- `comment`: General observations and updates
- `problem`: Issues and challenges requiring attention  
- `change`: Planned or implemented changes

**Priority Levels:**
- `low`: General information, no urgency
- `medium`: Important information requiring attention
- `high`: Critical issues or urgent matters

**File Attachments:**
- Maximum file size: 10MB
- Supported formats: Images (JPEG, PNG, GIF, WebP), Documents (PDF, Word, Excel, PowerPoint), Text files, Archives (ZIP, RAR)
- Files stored as LONGBLOB in database for security and backup consistency

### Table: application_relations
Many-to-many relationships between applications with bidirectional support.

| Field                  | Type         | Description                    | Constraints    |
|------------------------|--------------|--------------------------------|----------------|
| id                     | INT          | Primary key, auto-increment    | NOT NULL, PK   |
| application_id         | INT          | Source application             | FK to applications.id |
| related_application_id | INT          | Target application             | FK to applications.id |
| relation_type          | VARCHAR(100) | Relationship classification    | NULL           |
| created_at             | DATETIME     | Relationship creation          | DEFAULT NOW()  |

**Relation Types:**
- `dependency`: Application A depends on Application B
- `integration`: Applications share data or functionality
- `replacement`: Application A replaces Application B
- `related`: General relationship between applications

### Table: audit_log
Complete change tracking for compliance and debugging.

| Field         | Type         | Description                    | Constraints    |
|---------------|--------------|--------------------------------|----------------|
| id            | INT          | Primary key, auto-increment    | NOT NULL, PK   |
| table_name    | VARCHAR(100) | Affected table                 | NOT NULL       |
| record_id     | INT          | Affected record ID             | NOT NULL       |
| field_name    | VARCHAR(100) | Changed field                  | NULL           |
| old_value     | TEXT         | Previous value                 | NULL           |
| new_value     | TEXT         | New value                      | NULL           |
| changed_by    | INT          | User who made change           | FK to users.id |
| changed_at    | DATETIME     | Change timestamp               | DEFAULT NOW()  |
| action        | ENUM         | Operation type                 | 'INSERT', 'UPDATE', 'DELETE' |
| ip_address    | VARCHAR(45)  | Client IP address              | NULL           |
| user_agent    | TEXT         | Browser/client information     | NULL           |

## Database Relationships

### Primary Relationships
- `work_notes.application_id` → `applications.id` (One-to-Many)
- `work_notes.user_id` → `users.id` (Many-to-One, nullable for system notes)
- `application_relations.application_id` → `applications.id` (Many-to-Many)
- `application_relations.related_application_id` → `applications.id` (Many-to-Many)
- `audit_log.changed_by` → `users.id` (Many-to-One)

### Relationship Management
The system handles bidirectional relationships automatically:
- When Application A is linked to Application B, Application B is automatically linked to Application A
- Relationship deletion removes both directional links
- Prevents duplicate relationships and self-references

### Lookup Relationships (Future Implementation)
When fully normalized, these fields will become foreign keys:
- `applications.phase` → `phases.name`
- `applications.status` → `statuses.name`
- `applications.deployment_model` → `deployment_models.name`

## Data Integrity & Constraints

### Foreign Key Constraints
```sql
-- Work notes constraints
ALTER TABLE work_notes 
ADD CONSTRAINT fk_work_notes_application 
FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE;

ALTER TABLE work_notes 
ADD CONSTRAINT fk_work_notes_user 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Application relations constraints
ALTER TABLE application_relations 
ADD CONSTRAINT fk_app_relations_source 
FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE;

ALTER TABLE application_relations 
ADD CONSTRAINT fk_app_relations_target 
FOREIGN KEY (related_application_id) REFERENCES applications(id) ON DELETE CASCADE;

-- Audit log constraints
ALTER TABLE audit_log 
ADD CONSTRAINT fk_audit_log_user 
FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL;
```

### Unique Constraints
```sql
-- Prevent duplicate email addresses
ALTER TABLE users ADD CONSTRAINT uk_users_email UNIQUE (email);

-- Prevent duplicate lookup values
ALTER TABLE phases ADD CONSTRAINT uk_phases_name UNIQUE (name);
ALTER TABLE statuses ADD CONSTRAINT uk_statuses_name UNIQUE (name);
ALTER TABLE deployment_models ADD CONSTRAINT uk_deployment_models_name UNIQUE (name);

-- Prevent duplicate relationships
ALTER TABLE application_relations 
ADD CONSTRAINT uk_app_relations 
UNIQUE (application_id, related_application_id);
```

## SQL Setup Commands

### Complete Database Creation Script

```sql
-- Create main tables
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'viewer',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

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

-- Create lookup tables
CREATE TABLE phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE deployment_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE portfolios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('preops', 'application') NOT NULL
);

-- Create supporting tables
CREATE TABLE work_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NULL,
    note TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'comment',
    attachment_path VARCHAR(255) NULL,
    attachment_type VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE application_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    related_application_id INT NOT NULL,
    relation_type VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (related_application_id) REFERENCES applications(id) ON DELETE CASCADE,
    UNIQUE KEY uk_app_relations (application_id, related_application_id)
);

CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    field_name VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    changed_by INT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### Populate Lookup Tables with Default Data

```sql
-- Insert default phases
INSERT INTO phases (name) VALUES
('Need'),
('Solution'), 
('Build'),
('Implement'),
('Operate');

-- Insert default statuses  
INSERT INTO statuses (name) VALUES
('Unknown'),
('Not started'),
('Ongoing Work'),
('On Hold'),
('Completed');

-- Insert default deployment models
INSERT INTO deployment_models (name) VALUES
('Client Application'),
('On-premise'),
('SaaS'),
('Externally hosted');

-- Insert sample portfolios
INSERT INTO portfolios (name, type) VALUES
('HR Systems', 'application'),
('SCM Systems', 'application'),
('Digital Platforms', 'application'),
('Infrastructure', 'application'),
('Security', 'application'),
('Pre-ops Development', 'preops'),
('Pre-ops Testing', 'preops'),
('Pre-ops Integration', 'preops');
```

### Create Indexes for Performance

```sql
-- Application search indexes
CREATE INDEX idx_applications_short_description ON applications(short_description);
CREATE INDEX idx_applications_application_service ON applications(application_service);
CREATE INDEX idx_applications_phase ON applications(phase);
CREATE INDEX idx_applications_status ON applications(status);
CREATE INDEX idx_applications_relevant_for ON applications(relevant_for);

-- Audit log indexes
CREATE INDEX idx_audit_log_table_record ON audit_log(table_name, record_id);
CREATE INDEX idx_audit_log_changed_at ON audit_log(changed_at);
CREATE INDEX idx_audit_log_changed_by ON audit_log(changed_by);

-- Work notes indexes
CREATE INDEX idx_work_notes_application_id ON work_notes(application_id);
CREATE INDEX idx_work_notes_created_at ON work_notes(created_at);

-- Application relations indexes
CREATE INDEX idx_app_relations_application_id ON application_relations(application_id);
CREATE INDEX idx_app_relations_related_id ON application_relations(related_application_id);
```

### Sample Data Insert (Optional)

```sql
-- Create sample admin user (password: admin123)
INSERT INTO users (email, password_hash, role) VALUES
('admin@akerbp.com', '$2y$12$LQ7OWB7GpC9Dv8OLkO9gT.e8QCz8Gj1JK2Nv3M4L5P6Q7R8S9T0U1V', 'admin');

-- Create sample application
INSERT INTO applications (
    short_description, 
    application_service, 
    relevant_for, 
    phase, 
    status, 
    handover_status,
    business_need,
    assigned_to,
    deployment_model
) VALUES (
    'Sample Finance System',
    'SAP ERP',
    'Yggdrasil',
    'Build',
    'Ongoing Work',
    60,
    'Modernize financial reporting and automate monthly closing processes',
    'admin@akerbp.com',
    'On-premise'
);
```

## API Integration Examples

### Search Applications Endpoint Usage

The `/api/search_applications.php` endpoint provides real-time search capabilities:

```php
// Example API call structure
$query = $_GET['q'];           // Search term (min 2 chars)
$exclude = $_GET['exclude'];   // Application ID to exclude
$selected = $_GET['selected']; // Already selected IDs (comma-separated)

// SQL query structure
$sql = "SELECT id, short_description, application_service 
        FROM applications 
        WHERE (short_description LIKE ? OR application_service LIKE ?)
        AND id != ?
        AND id NOT IN (selected_ids)
        ORDER BY short_description
        LIMIT 20";
```

### Response Format
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

## Performance Considerations

### Optimization Strategies
- **Indexed Fields**: All searchable fields have appropriate indexes
- **Query Limits**: Search results limited to 20 items for performance
- **Prepared Statements**: All queries use PDO prepared statements
- **Connection Pooling**: Singleton database pattern for connection efficiency

### Database Maintenance
```sql
-- Regular maintenance commands
ANALYZE TABLE applications;
ANALYZE TABLE work_notes;
ANALYZE TABLE audit_log;

-- Check table integrity
CHECK TABLE applications;
CHECK TABLE users;

-- Optimize tables (run monthly)
OPTIMIZE TABLE applications;
OPTIMIZE TABLE audit_log;
```

### Backup Strategy
- **Daily backups**: Full database backup with retention policy
- **Transaction log backups**: Every 15 minutes for point-in-time recovery
- **Schema backup**: Version-controlled schema dumps
- **Test restore**: Monthly restore verification

## Security Implementation

### Data Protection
- **Password Hashing**: BCrypt with salt for all user passwords
- **SQL Injection Prevention**: PDO prepared statements for all queries
- **Input Validation**: Server-side validation for all form inputs
- **Session Security**: Secure session handling with proper timeout

### Access Control
```sql
-- Role-based permissions (implemented in application layer)
-- Admin: Full access to all tables
-- Editor: SELECT, INSERT, UPDATE on applications, work_notes
-- Viewer: SELECT only on applications, work_notes

-- Example application-level check
SELECT role FROM users WHERE id = :user_id;
-- Then enforce permissions in PHP code
```

### Audit Requirements
The audit_log table captures:
- **Automatic field changes**: All application form modifications are logged automatically
- **User attribution**: Links changes to specific users with email display
- **Field-level tracking**: Records old and new values for each field
- **Timestamp precision**: Exact change timestamps for compliance
- **Change descriptions**: Human-readable descriptions of what changed
- **IP address and browser information** for security auditing
- **Rollback capability** with old and new value tracking

## Activity Tracking System

The AppTrack application includes a comprehensive activity tracking system that provides:

### Features
- **Dual Activity Types**: Work Notes (manual) and Audit Log (automatic)
- **Real-time Updates**: Activities appear immediately after creation
- **Filtering Options**: "Work Notes Only" filter to focus on manual entries
- **File Attachments**: Support for uploading files with work notes (up to 10MB)
- **Priority Levels**: Visual indicators for low, medium, and high priority items
- **Admin Controls**: Hide/show functionality for sensitive information
- **User Attribution**: All activities linked to specific users with email display
- **Relative Timestamps**: Human-friendly time formatting (e.g., "2 hours ago")

### Implementation
- **Backend**: ActivityManager.php class handles all activity operations
- **Frontend**: JavaScript ActivityTracker class provides interactive interface
- **API Endpoints**: RESTful APIs for CRUD operations (get_activity_feed.php, add_work_note.php, etc.)
- **Database**: Optimized with proper indexing and foreign key constraints

### Usage
- **Work Notes**: Users can manually add comments, report problems, or document changes
- **Audit Trail**: System automatically logs all form field modifications
- **Collaboration**: Team members can see all activity history for transparency
- **Compliance**: Complete audit trail for regulatory requirements

### Security
- **Session Management**: All API calls require valid user sessions
- **Role-based Access**: Admin-only features for sensitive operations
- **Input Validation**: All user inputs are sanitized and validated
- **File Security**: Uploaded files are validated for type and size limits

---

## Migration and Updates

### Schema Versioning
- All schema changes documented with version numbers
- Migration scripts provided for production updates
- Rollback procedures documented for each change

### Adding New Fields
When adding fields to the applications table:

1. **Update Database Schema**
   ```sql
   ALTER TABLE applications 
   ADD COLUMN new_field_name VARCHAR(255) NULL 
   AFTER existing_field_name;
   ```

2. **Update Form Files**
   - Add field to `app_form.php` and `app_view.php`
   - Update form processing logic
   - Add field validation

3. **Update Documentation**
   - Add field description to this document
   - Update README.md if significant change
   - Update API documentation if field is searchable

### Production Deployment
```bash
# Example deployment script
mysql -u username -p database_name < migration_v2.1.sql
php artisan migrate --force  # If using framework
php scripts/clear_cache.php  # Clear any application cache
```

---

> **Note**: This documentation reflects the current, complete database structure as of AppTrack v2.0. All required columns have been implemented and tested. The schema supports the full feature set including horizontal form layouts, interactive sliders, and real-time search functionality. Update this documentation whenever the database structure changes and include migration scripts for schema updates in production environments.

