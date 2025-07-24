# AppTrack Database Documentation

This document provides a comprehensive overview of the AppTrack database structure, including all tables, relationships, and AI integration capabilities.

## Overview

The database follows an enterprise-grade normalized design pattern with comprehensive AI analytics integration and User Stories management. Key architectural features include:

- **25 Core Tables** with optimized relationships and constraints (based on SQL schema analysis)
- **AI Analysis Engine** with intelligent caching and change detection  
- **Comprehensive Audit System** with full change tracking and timestamps
- **Rich Work Notes** with attachment support and priority management
- **User Stories Management** with Agile methodology support and application integration
- **Handover Management System** with 15-step wizard and document generation
- **Role-Based Access Control** with granular permission mapping
- **Data Integrity** through foreign key constraints and ACID transactions
- **Production Optimization** with strategic indexing and performance tuning
- **Kanban Integration** with phase-based workflow and audit logging

## Current Schema Status ✅

✅ **PRODUCTION READY**: All 25 application tables properly mapped and validated (confirmed by SQL schema)
✅ **AI INTEGRATED**: Complete OpenAI analysis system with multilingual support (4 dedicated tables)  
✅ **USER STORIES MODULE**: Complete Agile User Stories management (2 dedicated tables with attachment support)
✅ **HANDOVER SYSTEM**: Complete 15-step handover management (4 dedicated tables)
✅ **OPTIMIZED PERFORMANCE**: Smart caching with configurable expiration policies (6-48 hours)  
✅ **AUDIT COMPLETE**: Full change tracking with user attribution and visibility controls  
✅ **FILE MANAGEMENT**: Attachment system with BLOB storage and comprehensive metadata  
✅ **SEARCH ENABLED**: Real-time application search with relationship mapping and indexing
✅ **SECURITY HARDENED**: Data privacy controls with sensitive field exclusion for AI processing
✅ **HANDOVER SYSTEM**: Complete 15-step handover management with participant tracking
✅ **KANBAN WORKFLOW**: Phase-based kanban system with drag-and-drop functionality
✅ **USER STORIES MODULE**: Complete Agile User Stories management with application integration

## Database Architecture Summary

| Category | Tables | Purpose | Key Features |
|----------|--------|---------|--------------|
| **Core Application** | applications, work_notes, audit_log | Primary business data | Full lifecycle tracking |
| **User Stories** | user_stories, user_story_attachments | Agile story management | Jira integration, CRUD operations |
| **AI Analysis** | ai_analysis, ai_configurations, ai_usage_log, data_snapshots | AI insights & caching | Smart change detection |
| **User Management** | users, application_user_relations | Authentication & authorization | Role-based access |
| **Reference Data** | phases, statuses, deployment_models, portfolios, project_managers, product_owners | Controlled vocabularies | Data consistency |
| **Relationships** | application_relations | Dependency mapping | Bidirectional links |
| **Handover Management** | handover_documents, handover_data, handover_participants, handover_signatures | Document workflow | 15-step process |

## Core Application Tables

### Table: applications
Primary entity storing all application lifecycle information with complete handover integration.

| Field                   | Type           | Description                           | Constraints      |
|-------------------------|----------------|---------------------------------------|------------------|
| id                      | int(11)        | Primary key, auto-increment          | NOT NULL, PK     |
| short_description       | varchar(255)   | Application name/identifier          | NOT NULL         |
| application_service     | varchar(255)   | ServiceNow CMDB reference            | NULL             |
| relevant_for            | varchar(255)   | Yggdrasil relevance classification   | NULL             |
| phase                   | varchar(100)   | Current delivery phase               | NULL             |
| status                  | varchar(100)   | Current status                       | NULL             |
| handover_status         | int(11)        | Handover progress percentage (0-100) | DEFAULT 0        |
| contract_number         | varchar(255)   | Commercial contract reference        | NULL             |
| contract_responsible    | varchar(255)   | Commercial lead contact              | NULL             |
| information_space       | text           | Documentation area URL               | NULL             |
| ba_sharepoint_list      | text           | Business analyst SharePoint list    | NULL             |
| relationship_yggdrasil  | text           | Connected applications (comma-sep)   | NULL             |
| assigned_to             | varchar(100)   | Data maintenance responsibility       | NULL             |
| preops_portfolio        | varchar(100)   | Yggdrasil project portfolio          | NULL             |
| application_portfolio   | varchar(100)   | Target IT operations portfolio       | NULL             |
| delivery_responsible    | varchar(100)   | Lead vendor/alliance                 | NULL             |
| corporator_link         | text           | Project management system URL        | NULL             |
| corporater_link         | varchar(255)   | Alternative project management URL   | NULL             |
| project_manager         | varchar(100)   | Project activities lead              | NULL             |
| product_owner           | varchar(100)   | Business need owner                  | NULL             |
| due_date                | date           | Target go-live date                  | NULL             |
| deployment_model        | varchar(255)   | Technical deployment approach        | NULL             |
| integrations            | varchar(255)   | Integration indicator                | NULL             |
| sa_document             | text           | Solution architecture docs URL       | NULL             |
| business_need           | varchar(350)   | Business justification               | NULL             |
| created_at              | timestamp      | Record creation timestamp            | DEFAULT CURRENT  |
| updated_at              | timestamp      | Last modification timestamp          | ON UPDATE        |
| integration_diagram     | text           | Mermaid diagram code for integration | NULL, COMMENT    |
| integration_notes       | text           | Notes about integration architecture | NULL, COMMENT    |
| handover_document_id    | int(11)        | FK to handover_documents table       | NULL, MUL        |

**Key Features:**
- **Kanban Integration**: `phase` field drives kanban board organization (Need, Solution, Build, Implement, Operate)
- **User Assignment**: Three-tier assignment system (`assigned_to`, `project_manager`, `product_owner`) for "Show mine only" filtering
- **Handover Integration**: Direct relationship to handover management system via `handover_document_id`
- **Integration Architecture**: Built-in support for visual integration diagrams and technical notes

## AI Analysis Tables

### Table: ai_analysis
Stores AI-generated analysis results with intelligent caching and change detection.

| Field                | Type                                                                                              | Description                           | Constraints      |
|----------------------|---------------------------------------------------------------------------------------------------|---------------------------------------|------------------|
| id                   | int(11)                                                                                          | Primary key, auto-increment          | NOT NULL, PK     |
| application_id       | int(11)                                                                                          | FK to applications table              | NOT NULL, MUL    |
| analysis_type        | enum('summary','timeline','risk_assessment','relationship_analysis','trend_analysis')           | Type of analysis performed            | NOT NULL         |
| ai_model             | varchar(100)                                                                                     | OpenAI model used                     | DEFAULT 'gpt-3.5-turbo' |
| prompt_version       | varchar(50)                                                                                      | Prompt template version               | DEFAULT 'v1.0'   |
| input_data_hash      | varchar(64)                                                                                      | SHA-256 hash of input data           | NOT NULL, MUL    |
| analysis_result      | longtext                                                                                         | JSON-formatted analysis result        | NOT NULL         |
| confidence_score     | decimal(3,2)                                                                                     | AI confidence in result (0.00-1.00)  | NULL             |
| processing_time_ms   | int(11)                                                                                          | Analysis processing time              | NULL             |
| token_count          | int(11)                                                                                          | OpenAI tokens consumed               | NULL             |
| created_at           | datetime                                                                                         | Analysis creation timestamp           | DEFAULT CURRENT  |
| expires_at           | datetime                                                                                         | Cache expiration time                 | NULL, MUL        |
| created_by           | int(11)                                                                                          | FK to users table                     | NULL, MUL        |

**Features:**
- **Smart Caching**: Prevents duplicate analysis requests using input data hashing
- **Configurable Expiration**: Different cache durations per analysis type
- **Performance Tracking**: Processing time and token usage monitoring
- **Change Detection**: Automatic cache invalidation when source data changes

### Table: ai_configurations
Manages AI analysis prompts and model parameters for different analysis types.

| Field            | Type            | Description                           | Constraints           |
|------------------|-----------------|---------------------------------------|-----------------------|
| id               | int(11)         | Primary key, auto-increment          | NOT NULL, PK          |
| analysis_type    | varchar(100)    | Analysis type identifier              | NOT NULL, MUL         |
| prompt_template  | text            | AI prompt template with placeholders | NOT NULL              |
| prompt_version   | varchar(50)     | Template version for tracking         | NOT NULL, MUL         |
| model_name       | varchar(100)    | OpenAI model to use                   | DEFAULT 'gpt-3.5-turbo' |
| model_parameters | longtext        | JSON model configuration              | NULL                  |
| max_tokens       | int(11)         | Maximum response tokens               | DEFAULT 2000          |
| temperature      | decimal(3,2)    | AI creativity setting (0.00-1.00)    | DEFAULT 0.70          |
| is_active        | tinyint(1)      | Enable/disable this configuration    | DEFAULT 1             |
| created_at       | datetime        | Configuration creation time           | DEFAULT CURRENT       |
| updated_at       | datetime        | Last modification time                | ON UPDATE CURRENT     |
| created_by       | int(11)         | FK to users table                     | NULL, MUL             |

### Table: ai_usage_log
Comprehensive logging of all AI API interactions for monitoring and cost tracking.

| Field              | Type                              | Description                           | Constraints      |
|--------------------|-----------------------------------|---------------------------------------|------------------|
| id                 | int(11)                          | Primary key, auto-increment          | NOT NULL, PK     |
| user_id            | int(11)                          | FK to users table                     | NULL, MUL        |
| application_id     | int(11)                          | FK to applications table              | NULL, MUL        |
| analysis_type      | varchar(100)                     | Type of analysis requested            | NOT NULL         |
| model_used         | varchar(100)                     | OpenAI model used                     | NOT NULL         |
| tokens_used        | int(11)                          | Total tokens consumed                 | NOT NULL         |
| cost_estimate      | decimal(10,6)                    | Estimated cost in USD                 | NULL             |
| processing_time_ms | int(11)                          | Total processing time                 | NOT NULL         |
| status             | enum('success','error','timeout') | Request outcome                       | NOT NULL, MUL    |
| error_message      | text                             | Error details if failed               | NULL             |
| created_at         | datetime                         | Request timestamp                     | DEFAULT CURRENT  |

## User Management Tables

### Table: deployment_models
Reference table defining standardized deployment models for application architecture tracking.

| Field            | Type         | Description                    | Constraints    |
|------------------|--------------|--------------------------------|----------------|
| id               | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| deployment_model | varchar(100) | Deployment model name (unique) | UNIQUE, NOT NULL |

**Default Values:**
- Monolithic
- Microservices
- Serverless
- Container-based
- Hybrid
- Other

### Table: programming_languages
Reference table defining standardized programming languages for technology tracking.

| Field              | Type         | Description                    | Constraints    |
|--------------------|--------------|--------------------------------|----------------|
| id                 | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| programming_language | varchar(100) | Programming language name (unique) | UNIQUE, NOT NULL |

**Default Values:**
- PHP
- JavaScript
- Python
- Java
- C#
- Go
- Ruby
- Other

### Table: frameworks
Reference table defining standardized frameworks for technology stack tracking.

| Field     | Type         | Description                    | Constraints    |
|-----------|--------------|--------------------------------|----------------|
| id        | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| framework | varchar(100) | Framework name (unique)        | UNIQUE, NOT NULL |

**Default Values:**
- Laravel
- React
- Vue.js
- Angular
- Express.js
- Django
- Spring Boot
- Other

### Table: users
System users with role-based access control for the AppTrack application.

| Field               | Type         | Description                    | Constraints    |
|---------------------|--------------|--------------------------------|----------------|
| id                  | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| email               | varchar(255) | User email (unique)            | UNIQUE, NOT NULL |
| password            | varchar(255) | Hashed password                | NOT NULL       |
| full_name           | varchar(255) | User's full name               |                |
| role                | enum         | User role (viewer/editor/admin)| NOT NULL       |
| created_at          | timestamp    | Account creation timestamp     | DEFAULT CURRENT_TIMESTAMP |
| updated_at          | timestamp    | Last update timestamp          | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

**User Roles:**
- **viewer**: Read-only access to applications
- **editor**: Can create and edit applications
- **admin**: Full access including user management

### Table: activity_log
Comprehensive audit trail for all system activities and changes.

| Field        | Type         | Description                    | Constraints    |
|--------------|--------------|--------------------------------|----------------|
| id           | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| user_id      | int(11)      | User who performed the action  | FK to users.id |
| action       | varchar(255) | Action performed               | NOT NULL       |
| target_type  | varchar(100) | Type of target (application, user, etc.) | NOT NULL |
| target_id    | int(11)      | ID of the target entity        |                |
| details      | text         | JSON formatted change details  |                |
| timestamp    | timestamp    | When the action occurred       | DEFAULT CURRENT_TIMESTAMP |

**Common Actions:**
- create_application, update_application, delete_application
- kanban_move, phase_change, status_change
- create_user, update_user, delete_user
- login, logout

## Database Relationships

### Foreign Key Constraints
The applications table maintains foreign key relationships with reference tables:
- `phase_id` → `application_phases.id`
- `status_id` → `application_statuses.id`
- `application_type_id` → `application_types.id`
- `environment_id` → `environments.id`
- `database_type_id` → `database_types.id`
- `hosting_model_id` → `hosting_models.id`
- `deployment_model_id` → `deployment_models.id`
- `programming_language_id` → `programming_languages.id`
- `framework_id` → `frameworks.id`
- `handover_document_id` → `handover_documents.id`

### Data Integrity
- All reference tables use auto-incrementing IDs as primary keys
- Unique constraints on name/type fields prevent duplicates
- NULL values allowed in applications table for optional relationships
- Cascade options preserve data integrity during deletions

## Indexes and Performance

### Optimized Queries
- Primary keys provide clustered index performance
- Unique constraints create automatic indexes for reference lookups
- Foreign key relationships optimized for JOIN operations
- Activity log indexed by timestamp and user_id for audit queries

### Query Patterns
- Dashboard filtering uses JOINs across user-related fields
- Kanban board groups by phase with status counts
- Search functionality leverages indexed text fields
- Audit trails query by timestamp ranges and target types
Comprehensive user management with role-based access control.

| Field         | Type                              | Description                     | Constraints           |
|---------------|-----------------------------------|---------------------------------|-----------------------|
| id            | int(11)                          | Primary key, auto-increment     | NOT NULL, PK          |
| email         | varchar(100)                     | User email address              | UNIQUE, NOT NULL      |
| first_name    | varchar(100)                     | User first name                 | DEFAULT ''            |
| last_name     | varchar(100)                     | User last name                  | DEFAULT ''            |
| display_name  | varchar(200)                     | Full display name               | DEFAULT ''            |
| phone         | varchar(20)                      | Contact phone number            | NULL                  |
| is_active     | tinyint(1)                       | Account status                  | DEFAULT 1             |
| password_hash | varchar(255)                     | BCrypt hashed password          | NOT NULL              |
| role          | enum('admin','editor','viewer')  | User access level               | DEFAULT 'viewer'      |
| created_at    | timestamp                        | Account creation timestamp      | DEFAULT CURRENT       |

**Access Levels:**
- **admin**: Full system access including user management
- **editor**: Create, edit, view applications and work notes
- **viewer**: Read-only access to all application data

### Table: application_user_relations
Maps users to applications with specific role assignments.

| Field          | Type                                                     | Description                  | Constraints      |
|----------------|----------------------------------------------------------|------------------------------|------------------|
| application_id | int(11)                                                 | FK to applications table     | NOT NULL, PK     |
| user_id        | int(11)                                                 | FK to users table            | NOT NULL, PK     |
| role           | enum('assigned_to','project_manager','product_owner')  | User role for application    | NOT NULL, PK     |

### Table: project_managers
Standardized project manager names for consistent data entry.

| Field | Type         | Description                    | Constraints    |
|-------|--------------|--------------------------------|----------------|
| id    | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| name  | varchar(100) | Project manager name           | UNIQUE, NOT NULL |

### Table: product_owners
Standardized product owner names for consistent data entry.

| Field | Type         | Description                    | Constraints    |
|-------|--------------|--------------------------------|----------------|
| id    | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| name  | varchar(100) | Product owner name             | UNIQUE, NOT NULL |

### Table: application_phases
Reference table defining standardized application phases for consistent categorization.

| Field | Type         | Description                    | Constraints    |
|-------|--------------|--------------------------------|----------------|
| id    | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| phase | varchar(50)  | Phase name (unique)            | UNIQUE, NOT NULL |

**Default Values:**
- Portfolio
- Project
- System
- Retirement

### Table: environments
Reference table defining standardized environments for deployment tracking.

| Field       | Type        | Description                    | Constraints    |
|-------------|-------------|--------------------------------|----------------|
| id          | int(11)     | Primary key, auto-increment    | NOT NULL, PK   |
| environment | varchar(50) | Environment name (unique)      | UNIQUE, NOT NULL |

**Default Values:**
- Production
- Staging
- Development
- Test

### Table: application_statuses
Reference table defining standardized application statuses for consistent categorization.

| Field  | Type         | Description                    | Constraints    |
|--------|--------------|--------------------------------|----------------|
| id     | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| status | varchar(100) | Status name (unique)           | UNIQUE, NOT NULL |

**Default Values:**
- Unknown
- Not started
- Ongoing Work
- On Hold
- Completed

### Table: application_types
Reference table defining standardized application types for categorization.

| Field | Type         | Description                    | Constraints    |
|-------|--------------|--------------------------------|----------------|
| id    | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| type  | varchar(100) | Application type name (unique) | UNIQUE, NOT NULL |

**Default Values:**
- Web Application
- Mobile Application
- Desktop Application
- API/Service
- Database
- Infrastructure
- Other

### Table: database_types
Reference table defining standardized database types for infrastructure tracking.

| Field         | Type         | Description                    | Constraints    |
|---------------|--------------|--------------------------------|----------------|
| id            | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| database_type | varchar(100) | Database type name (unique)    | UNIQUE, NOT NULL |

**Default Values:**
- MySQL
- PostgreSQL
- Oracle
- SQL Server
- MongoDB
- Redis
- Other

### Table: hosting_models
Reference table defining standardized hosting models for infrastructure categorization.

| Field         | Type         | Description                    | Constraints    |
|---------------|--------------|--------------------------------|----------------|
| id            | int(11)      | Primary key, auto-increment    | NOT NULL, PK   |
| hosting_model | varchar(100) | Hosting model name (unique)    | UNIQUE, NOT NULL |

**Default Values:**
- On-Premise
- Cloud (AWS)
- Cloud (Azure)
- Cloud (Google)
- Hybrid
- Other
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

## User Stories Management Tables

### Table: user_stories
Complete Agile User Stories management with application integration and Jira support.

| Field                | Type                                                | Description                           | Constraints      |
|----------------------|----------------------------------------------------|------------------------------------- |------------------|
| id                   | int(11)                                            | Primary key, auto-increment          | NOT NULL, PK     |
| title                | varchar(255)                                       | User story title/summary              | NOT NULL         |
| role                 | varchar(255)                                       | User role: "As a [role]"              | NOT NULL         |
| want_to              | text                                               | Functionality: "I want to [action]"  | NOT NULL         |
| so_that              | text                                               | Benefit: "So that [value]"            | NOT NULL         |
| priority             | enum('Low','Medium','High','Critical')            | Story priority level                  | DEFAULT 'Medium' |
| status               | enum('backlog','in_progress','review','done','cancelled') | Current story status          | DEFAULT 'backlog'|
| application_id       | int(11)                                            | FK to applications table              | NULL, MUL        |
| jira_id              | varchar(50)                                        | External Jira issue key               | NULL             |
| jira_url             | text                                               | Link to Jira issue                    | NULL             |
| sharepoint_url       | text                                               | SharePoint document link              | NULL             |
| category             | varchar(100)                                       | Story category/theme                  | NULL             |
| tags                 | text                                               | Comma-separated tags                  | NULL             |
| source               | enum('manual','jira_import','template')          | Story creation source                 | DEFAULT 'manual' |
| created_by           | int(11)                                            | FK to users table                     | NOT NULL, MUL    |
| created_at           | timestamp                                          | Story creation timestamp              | DEFAULT CURRENT  |
| updated_at           | timestamp                                          | Last modification timestamp           | ON UPDATE        |

**Features:**
- **Agile Compliance**: Native support for standard User Story format with role, functionality, and benefit
- **Application Integration**: Optional linking to applications for integrated project management
- **Jira Integration**: Built-in fields for external project management tool integration
- **Status Tracking**: Complete workflow from backlog through done with cancelled option
- **Flexible Categorization**: Tags and categories for organization

**Foreign Key Constraints:**
```sql
CONSTRAINT `fk_user_stories_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_user_stories_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
```

### Table: user_story_attachments
File attachment support for User Stories with comprehensive metadata tracking.

| Field             | Type         | Description                           | Constraints      |
|-------------------|--------------|---------------------------------------|------------------|
| id                | int(11)      | Primary key, auto-increment           | NOT NULL, PK     |
| user_story_id     | int(11)      | FK to user_stories table              | NOT NULL, MUL    |
| filename          | varchar(255) | Original filename                     | NOT NULL         |
| file_path         | varchar(500) | Relative path to stored file          | NOT NULL         |
| file_size         | int(11)      | File size in bytes                    | NOT NULL         |
| mime_type         | varchar(100) | MIME type of file                     | NOT NULL         |
| uploaded_by       | int(11)      | FK to users table                     | NOT NULL, MUL    |
| uploaded_at       | timestamp    | File upload timestamp                 | DEFAULT CURRENT  |

**Features:**
- **File Storage**: Secure file storage with path-based organization
- **Metadata Tracking**: Complete file information including size and MIME type
- **User Attribution**: Track who uploaded each file with timestamp
- **Multiple Attachments**: Support for multiple files per User Story

**Foreign Key Constraints:**
```sql
CONSTRAINT `fk_attachments_user_story` FOREIGN KEY (`user_story_id`) REFERENCES `user_stories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `fk_attachments_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
```

**Supported File Types:**
- Documents: PDF, Word (.docx), Excel (.xlsx), PowerPoint (.pptx)
- Images: JPEG, PNG, GIF, WebP, SVG
- Text: TXT, CSV, JSON, XML
- Archives: ZIP, RAR, 7Z
- Maximum file size: 10MB per attachment

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

## Handover Management Tables

### Table: handover_documents
Central registry for application handover documents with progress tracking.

| Field                   | Type           | Description                           | Constraints      |
|-------------------------|----------------|---------------------------------------|------------------|
| id                      | int(11)        | Primary key, auto-increment          | NOT NULL, PK     |
| application_id          | int(11)        | FK to applications table              | NOT NULL, MUL    |
| created_by              | int(11)        | FK to users table (document creator) | NOT NULL, MUL    |
| created_at              | timestamp      | Document creation timestamp           | DEFAULT CURRENT  |
| updated_at              | timestamp      | Last modification timestamp           | ON UPDATE        |
| status                  | enum           | Document status                       | DEFAULT 'draft'  |
| current_step            | int(11)        | Current wizard step (1-15)           | DEFAULT 1        |
| completion_percentage   | decimal(5,2)   | Progress completion (0.00-100.00)    | DEFAULT 0.00     |

**Status Values:**
- `draft`: Initial document state
- `in_progress`: Active handover process
- `review`: Under review phase
- `completed`: Handover completed

### Table: handover_data
Flexible key-value storage for handover step data with JSON support.

| Field                   | Type           | Description                           | Constraints      |
|-------------------------|----------------|---------------------------------------|------------------|
| id                      | int(11)        | Primary key, auto-increment          | NOT NULL, PK     |
| handover_document_id    | int(11)        | FK to handover_documents table        | NOT NULL, MUL    |
| section_name            | varchar(100)   | Handover section identifier           | NOT NULL         |
| field_name              | varchar(100)   | Field identifier within section       | NOT NULL         |
| field_value             | text           | Field data (supports JSON arrays)    | NULL             |
| field_type              | enum           | Data type indicator                   | DEFAULT 'text'   |
| sort_order              | int(11)        | Display order within section          | DEFAULT 0        |
| created_at              | timestamp      | Field creation timestamp              | DEFAULT CURRENT  |
| updated_at              | timestamp      | Last modification timestamp           | ON UPDATE        |

**Field Types:**
- `text`: Standard text fields
- `table`: JSON array data for dynamic tables
- `file`: File attachment metadata

### Table: handover_participants
Dynamic participant management for handover processes.

| Field                   | Type           | Description                           | Constraints      |
|-------------------------|----------------|---------------------------------------|------------------|
| id                      | int(11)        | Primary key, auto-increment          | NOT NULL, PK     |
| handover_document_id    | int(11)        | FK to handover_documents table        | NOT NULL, MUL    |
| role                    | varchar(100)   | Participant role/function             | NOT NULL         |
| name                    | varchar(255)   | Participant full name                 | NULL             |
| organization            | varchar(255)   | Participant organization              | NULL             |
| contact_info            | varchar(255)   | Contact details                       | NULL             |
| created_at              | timestamp      | Participant addition timestamp        | DEFAULT CURRENT  |

### Table: handover_signatures
Digital signature and approval tracking for handover completion.

| Field                   | Type           | Description                           | Constraints      |
|-------------------------|----------------|---------------------------------------|------------------|
| id                      | int(11)        | Primary key, auto-increment          | NOT NULL, PK     |
| handover_document_id    | int(11)        | FK to handover_documents table        | NOT NULL, MUL    |
| role                    | varchar(100)   | Signing role/authority                | NOT NULL         |
| signed_by               | int(11)        | FK to users table (optional)         | NULL, MUL        |
| signed_at               | timestamp      | Signature timestamp                   | NULL             |
| signature_data          | text           | Digital signature data                | NULL             |
| created_at              | timestamp      | Signature record creation             | DEFAULT CURRENT  |

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

