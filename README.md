# AppTrack

**AppTrack** is a web-based application management tool developed for the Yggdrasil project in Aker BP. Its purpose is to replace manual spreadsheets and fragmented documentation by offering a centralized and structured application registry.

---

## 🎯 Objective

The goal is to build a modular full-stack web application using:

- **PHP** (backend)
- **MySQL** (database)
- **Bootstrap** (frontend)

The system enables users to register, update, and view relevant information about applications that are part of or affected by the Yggdrasil project. It is designed to scale and evolve, supporting new fields and future integrations, such as:

- **ServiceNow CMDB** integration (Aker BP)
- **Entra ID** authentication
- **ChatGPT** for AI-enhanced functionality

---

## 🔑 Key Features

- **User-friendly interface**: View, register, and update applications via intuitive tables, forms, and dashboards.
- **Structured data fields**, including:
  - `Short Description`: Application name
  - `Application Service`: Linked to ServiceNow CMDB
  - `Relevant for`: Indicates if the application is relevant for Yggdrasil
  - `Phase`: Based on the Yggdrasil Delivery Model (Need, Solution, Build, Implement, Operate)
  - `Status`: Status options (Unknown, Not started, Ongoing, On hold, Completed)
  - `Handover Status`: A 10-step slider tracking handover progress
  - `Information Space`: Link to SharePoint/Viva or other documentation areas
  - `BA SharePoint List`: Link to a list maintained by Business Analysts
  - `Relationship in Yggdrasil`: Links to related applications in the system
  - `Assigned To`: Person responsible for keeping data up to date
  - `Pre-ops Portfolio`: Portfolio ownership within the Yggdrasil project
  - `Application Portfolio`: Target portfolio for IT Operations (e.g. HR, SCM, Digital, etc.)
  - `Delivery Responsible`: Lead alliance/project/vendor responsible for delivery
  - `Link to Corporater`: If available, link to project info in Corporater
  - `Project Manager`: Responsible for project activities involving the application
  - `Product Owner`: Owns the business need and ensures the application meets that need
  - `Due Date`: Target date for go-live or operational use
  - `Deployment Model`: SaaS, On-prem, Externally Hosted, Client App, Hybrid, etc.
  - `Integrations`: Indicates whether the app is part of a data pipeline or has integrations
  - `S.A. Document`: Solution Architect documentation link
  - `Business Need`: Plain-language summary of why the application is needed (max 350 characters)
  - `Work Notes`: Log of comments, decisions, and automatic audit trail of changes

---

## 👥 User Roles

Three user groups are supported:
- **Admin**: Full access (view/edit/delete)
- **Editor**: View and edit applications
- **Viewer**: Read-only access

---

## ⚙️ Backend Setup

- **PHP** is used for form handling and CRUD operations
- **MySQL** as the database engine

### Required Files

- `config.php` – database connection settings
- PHP scripts for Create, Read, Update, Delete
- Form validation and data sanitation logic

---

## 🗃️ Database Structure (MySQL)

Main table: `applications`

| Field | Type | Description |
|-------|------|-------------|
| id | INT (PK, Auto Increment) | Unique ID |
| short_description | VARCHAR | Application name |
| application_service | VARCHAR | Linked ServiceNow entry |
| relevant_for | ENUM | To be decided / Yggdrasil / Not Relevant |
| phase | ENUM | Need / Solution / Build / Implement / Operate |
| status | ENUM | Status indicator |
| handover_status | INT | 10%–100% slider |
| contract_number | VARCHAR | Contract reference |
| contract_responsible | VARCHAR | Person |
| information_space | TEXT | URL |
| ba_sharepoint | TEXT | URL |
| relationship_yggdrasil | JSON / Join table | Linked apps |
| assigned_to | VARCHAR | User ID |
| preops_portfolio | ENUM | Yggdrasil portfolios |
| application_portfolio | ENUM | Aker BP portfolios |
| delivery_responsible | VARCHAR | Lead party |
| corporator_link | TEXT | URL |
| project_manager | VARCHAR | Name |
| product_owner | VARCHAR | Name |
| due_date | DATE | Go-live date |
| deployment_model | ENUM | SaaS, Hybrid, etc. |
| integrations | BOOLEAN | Yes/No |
| sa_document | TEXT | Link to technical spec |
| business_need | TEXT | Short description |
| created_at / updated_at | TIMESTAMP | Record history |

---

## 🚧 Future Enhancements

- User authentication (Entra ID)
- Version and change tracking (audit trail)
- Advanced reporting and export
- Dynamic relationship visualization
- API for external system integration

---

## 🌐 Hosting & Deployment

- Target domain: [apptrack.no](https://apptrack.no)
- Bootstrap ensures mobile responsiveness
- GitHub Copilot assists development

---

## 📦 Delivery Model

The system is aligned with Yggdrasil’s structured **Delivery Model**:

1. **Need** – Recognize business needs
2. **Solution** – Explore technical and commercial solutions
3. **Build** – Develop, test, and configure the application
4. **Implement** – Prepare the organization and IT for operations
5. **Operate** – The application is live and in production

This phased model ensures clear ownership and traceability from idea to operation.

---

## 📄 License

TBD

---

## 🤝 Contributions

Contributions and pull requests are welcome. Please fork the repository and open an issue before submitting major changes.

---

## 📬 Contact

Project Lead: `frank.waaland@akerbp.com` (or your placeholder contact)

---

