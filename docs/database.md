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

## Schema Status
✅ **COMPLETE**: All required columns have been added to the `applications` table
✅ **TESTED**: Form submission and data retrieval working correctly
✅ **VALIDATED**: No missing column errors

## Core Tables

### Table: users
Manages user accounts and authentication.

| Field         | Type           | Description                     | Constraints    |
|---------------|----------------|---------------------------------|----------------|
| id            | INT            | Primary key, auto-increment     | NOT NULL, PK   |
| email         | VARCHAR(255)   | User email address              | UNIQUE, NOT NULL |
| password_hash | VARCHAR(255)   | Bcrypt hashed password          | NOT NULL       |
| role          | VARCHAR(50)    | User role (admin/editor/viewer) | NOT NULL       |
| created_at    | DATETIME       | Account creation timestamp      | DEFAULT NOW()  |

### Table: applications
Central table storing all application information.

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
| ba_sharepoint           | TEXT           | Business analyst SharePoint list    | NULL           |
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
Technical deployment approaches.

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
Business and technical portfolio classifications.

| Field | Type         | Description                    | Constraints    |
|-------|--------------|--------------------------------|----------------|
| id    | INT          | Primary key, auto-increment    | NOT NULL, PK   |
| name  | VARCHAR(100) | Portfolio name                 | NOT NULL       |
| type  | ENUM         | Portfolio type                 | 'preops', 'application' |

## Supporting Tables

### Table: work_notes
Comments, decisions, and activity log.

| Field           | Type         | Description                    | Constraints    |
|-----------------|--------------|--------------------------------|----------------|
| id              | INT          | Primary key, auto-increment    | NOT NULL, PK   |
| application_id  | INT          | Related application            | FK to applications.id |
| user_id         | INT          | Author (nullable for system)   | FK to users.id |
| note            | TEXT         | Note content                   | NOT NULL       |
| type            | VARCHAR(50)  | Note classification            | DEFAULT 'comment' |
| attachment_path | VARCHAR(255) | File attachment location       | NULL           |
| attachment_type | VARCHAR(50)  | File type/extension            | NULL           |
| created_at      | DATETIME     | Creation timestamp             | DEFAULT NOW()  |
| updated_at      | DATETIME     | Modification timestamp         | DEFAULT NOW()  |

### Table: application_relations
Many-to-many relationships between applications.

| Field                  | Type         | Description                    | Constraints    |
|------------------------|--------------|--------------------------------|----------------|
| id                     | INT          | Primary key, auto-increment    | NOT NULL, PK   |
| application_id         | INT          | Source application             | FK to applications.id |
| related_application_id | INT          | Target application             | FK to applications.id |
| relation_type          | VARCHAR(100) | Relationship classification    | NULL           |

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

## Database Relationships

### Primary Relationships
- `work_notes.application_id` → `applications.id` (One-to-Many)
- `work_notes.user_id` → `users.id` (Many-to-One, nullable)
- `application_relations.application_id` → `applications.id` (Many-to-Many)
- `application_relations.related_application_id` → `applications.id` (Many-to-Many)
- `audit_log.changed_by` → `users.id` (Many-to-One)

### Lookup Relationships (Future Implementation)
When fully normalized, these fields will become foreign keys:
- `applications.phase` → `phases.name`
- `applications.status` → `statuses.name`
- `applications.deployment_model` → `deployment_models.name`

## SQL Setup Commands

### Create Lookup Tables and Populate with Default Data

```sql
-- Create and populate phases
CREATE TABLE phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

INSERT INTO phases (name) VALUES
('Need'), ('Solution'), ('Build'), ('Implement'), ('Operate');

-- Create and populate statuses  
CREATE TABLE statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

INSERT INTO statuses (name) VALUES
('Unknown'), ('Not started'), ('Ongoing Work'), ('On Hold'), ('Completed');

-- Create and populate deployment models
CREATE TABLE deployment_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

INSERT INTO deployment_models (name) VALUES
('Client Application'), ('On-premise'), ('SaaS'), ('Externally hosted');
```

### Add Missing Columns (if needed)
If the `applications` table is missing required columns, use this script:

```sql
-- Add missing columns to applications table (run only if needed)
ALTER TABLE applications 
ADD COLUMN contract_number VARCHAR(255) NULL AFTER handover_status,
ADD COLUMN contract_responsible VARCHAR(255) NULL AFTER contract_number;

-- Verify the table structure
DESCRIBE applications;
```

**Note**: The above ALTER TABLE commands should only be run if the columns are missing. Check current table structure first with `DESCRIBE applications;`

## Performance Considerations

### Indexes
- Primary keys (automatic)
- Foreign key indexes (automatic)
- Email uniqueness index on users table
- Composite index on audit_log (table_name, record_id, changed_at)

### Query Optimization
- Use prepared statements for all queries
- Implement pagination for large result sets
- Consider caching for lookup table data
- Regular ANALYZE TABLE for query plan optimization

---

> **Note**: This documentation reflects the current, complete database structure. All required columns have been added to the `applications` table. The provided SQL scripts in `fix_database_schema.sql` can be used to add missing columns if needed in future deployments. Update this documentation whenever the database structure changes. Include migration scripts for schema updates in production environments.

