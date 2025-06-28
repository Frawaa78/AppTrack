# AppTrack Database Structure

## Table: users
| Field         | Type           | Description                |
|-------------- |---------------|----------------------------|
| id            | INT, PK        | User ID                    |
| email         | VARCHAR(255)   | User email (unique)        |
| password_hash | VARCHAR(255)   | Hashed password            |
| role          | VARCHAR(50)    | User role (admin/user)     |
| created_at    | DATETIME       | Created timestamp          |

## Table: applications
| Field                  | Type           | Description                        |
|------------------------|----------------|------------------------------------|
| id                     | INT, PK        | Application ID                     |
| short_description      | VARCHAR(255)   | Short description                  |
| application_service    | VARCHAR(255)   | Application service                |
| relevant_for           | VARCHAR(255)   | Relevant for                       |
| phase_id               | INT, FK        | Phase (references phases.id)       |
| status_id              | INT, FK        | Status (references statuses.id)    |
| handover_status        | VARCHAR(255)   | Handover status                    |
| information_space      | VARCHAR(255)   | Information space                  |
| ba_sharepoint_list     | VARCHAR(255)   | BA SharePoint list                 |
| relationship_yggdrasil | VARCHAR(255)   | Relationship Yggdrasil             |
| assigned_to_id         | INT, FK        | Assigned to (references users.id)  |
| preops_portfolio_id    | INT, FK        | Pre-ops portfolio (portfolios.id)  |
| application_portfolio_id| INT, FK       | Application portfolio (portfolios.id)|
| delivery_responsible   | VARCHAR(255)   | Delivery responsible               |
| corporater_link        | VARCHAR(255)   | Corporater link                    |
| project_manager_id     | INT, FK        | Project manager (project_managers.id)|
| product_owner_id       | INT, FK        | Product owner (product_owners.id)  |
| due_date               | DATE           | Due date                           |
| deployment_model_id    | INT, FK        | Deployment model (deployment_models.id)|
| integrations           | TEXT           | Integrations                       |
| sa_document            | VARCHAR(255)   | SA document                        |
| business_need          | TEXT           | Business need                      |
| created_at             | DATETIME       | Created timestamp                  |
| updated_at             | DATETIME       | Updated timestamp                  |

## Table: work_notes
| Field           | Type         | Description                        |
|-----------------|-------------|------------------------------------|
| id              | INT, PK     | Work note ID                       |
| application_id  | INT, FK     | Application (references applications.id) |
| user_id         | INT, FK     | User (nullable, references users.id)|
| note            | TEXT        | Note text                          |
| type            | VARCHAR(50) | Note type                          |
| attachment_path | VARCHAR(255)| File path to attachment            |
| attachment_type | VARCHAR(50) | Attachment type (image, doc, etc.) |
| created_at      | DATETIME    | Created timestamp                  |
| updated_at      | DATETIME    | Updated timestamp                  |

## Table: phases
| Field | Type         | Description         |
|-------|-------------|---------------------|
| id    | INT, PK     | Phase ID            |
| name  | VARCHAR(100)| Phase name          |

## Table: statuses
| Field | Type         | Description         |
|-------|-------------|---------------------|
| id    | INT, PK     | Status ID           |
| name  | VARCHAR(100)| Status name         |

## Table: deployment_models
| Field | Type         | Description         |
|-------|-------------|---------------------|
| id    | INT, PK     | Deployment model ID |
| name  | VARCHAR(100)| Model name          |

## Table: portfolios
| Field | Type         | Description         |
|-------|-------------|---------------------|
| id    | INT, PK     | Portfolio ID        |
| name  | VARCHAR(100)| Portfolio name      |
| type  | ENUM        | 'preops'/'application'|

## Table: project_managers
| Field | Type         | Description         |
|-------|-------------|---------------------|
| id    | INT, PK     | Project manager ID  |
| name  | VARCHAR(100)| Name                |

## Table: product_owners
| Field | Type         | Description         |
|-------|-------------|---------------------|
| id    | INT, PK     | Product owner ID    |
| name  | VARCHAR(100)| Name                |

## Table: application_relations
| Field                 | Type         | Description                        |
|---------------------- |-------------|------------------------------------|
| id                    | INT, PK     | Relation ID                        |
| application_id        | INT, FK     | Main application (applications.id) |
| related_application_id| INT, FK     | Related application (applications.id)|
| relation_type         | VARCHAR(100)| Type of relation (optional)        |

## Relationships
- `applications.phase_id` → `phases.id`
- `applications.status_id` → `statuses.id`
- `applications.deployment_model_id` → `deployment_models.id`
- `applications.preops_portfolio_id` → `portfolios.id`
- `applications.application_portfolio_id` → `portfolios.id`
- `applications.project_manager_id` → `project_managers.id`
- `applications.product_owner_id` → `product_owners.id`
- `applications.assigned_to_id` → `users.id`
- `work_notes.application_id` → `applications.id`
- `work_notes.user_id` → `users.id`
- `application_relations.application_id` → `applications.id`
- `application_relations.related_application_id` → `applications.id`

---

> Oppdater denne filen når du endrer databasestrukturen!
