# AppTrack

**AppTrack** is a web-based tool for managing application-related information in a digital project.

This project is built from scratch using:
- **PHP** (backend)
- **MySQL** (data storage)
- **Bootstrap** (frontend layout and components)
- **Choices.js** (enhanced select fields)
- **GitHub Copilot** for assisted code development

## 🌟 Purpose
AppTrack replaces manual spreadsheets and disconnected documentation by providing a structured interface for application registration, updates, and tracking throughout the delivery lifecycle.

## 🔧 Features
- Register and update application information
- Visual indicators for delivery phase, status, and handover progress
- Support for linking to documentation (e.g., SharePoint, Corporator)
- Relationship mapping between applications
- Toggle-based and dynamic field visibility
- Tooltip and popover descriptions for usability
- Mobile-friendly responsive design using Bootstrap

## 🗃️ Database
A MySQL database should include a table named `applications` with fields such as:
- `id`, `short_description`, `phase`, `status`, `handover_status`
- `contract_number`, `information_space`, `assigned_to`, `due_date`
- `relationship_yggdrasil`, `deployment_model`, `integrations`, `sa_document`
- and more, based on the HTML form inputs.

## 🚀 Getting Started
1. Clone the repository
2. Set up your MySQL database and create the `applications` table
3. Configure your `config.php` with DB credentials
4. Deploy to your PHP-enabled web server
5. Start using the form via your browser

## 📦 Planned Improvements
- Authentication / user roles
- Search and filter functionality
- Application dashboards
- Export to Excel/CSV
- Logging and activity tracking

## 📍 Domain
Production target: **[apptrack.no](https://apptrack.no)**

## 📅 Status
Project initialized: 2025-06-27  
Version: `0.1-alpha`

---

© 2025 Frank Waaland / AppTrack Project

