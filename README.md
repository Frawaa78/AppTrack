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

## 🆕 Current Status & Recent Changes

### Implemented Features
- ✅ **User Authentication**: Registration, login, logout with secure password hashing
- ✅ **Database-Driven Forms**: Phase and status values are dynamically fetched from database tables
- ✅ **Responsive UI**: Modern Bootstrap interface with ServiceNow-like design
- ✅ **Application Management**: Create, edit, view applications with comprehensive form fields
- ✅ **Shared Components**: Consistent navigation bar across all pages
- ✅ **Security**: Input validation, prepared statements, session management
- ✅ **Data Import**: ServiceNow CSV import capability
- ✅ **Audit Trail**: Database structure for tracking changes

### Database Modernization
- Phases and statuses are now stored in dedicated lookup tables (`phases`, `statuses`)
- Form dropdowns dynamically populate from database instead of hardcoded arrays
- Normalized database structure with proper relationships

### Current Project Structure
```
AppTrack/
├── public/                 # Web-accessible files
│   ├── index.php          # Welcome/landing page
│   ├── login.php          # User authentication
│   ├── register.php       # User registration
│   ├── dashboard.php      # Main application overview
│   ├── app_form.php       # Create/edit application form
│   ├── app_view.php       # Read-only application details
│   ├── users_admin.php    # User administration (planned)
│   └── shared/
│       └── topbar.php     # Shared navigation component
├── src/                   # Backend logic
│   ├── config/
│   │   └── config.php     # Database configuration
│   ├── db/
│   │   └── db.php         # PDO database singleton class
│   ├── models/            # Data models (planned)
│   └── controllers/       # Business logic (planned)
├── docs/
│   └── database.md        # Complete database documentation
└── README.md
```

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

## ⚙️ Technical Architecture

### Backend Components
- **PHP 8+**: Server-side logic with modern features
- **MySQL 8.0**: Relational database with normalized structure
- **PDO**: Database abstraction layer with prepared statements for security

### Frontend Components
- **Bootstrap 5.3**: Responsive CSS framework
- **Vanilla JavaScript**: Form interactions and dynamic content
- **Choices.js**: Enhanced multi-select dropdowns

### Security Implementation
- Password hashing using PHP's `password_hash()`
- Prepared statements for all database queries
- Input validation and sanitization
- Session-based authentication
- CSRF protection (planned)

### Database Design
- Normalized structure with lookup tables
- Foreign key relationships for data integrity
- Audit logging capability
- Support for file attachments and work notes

For detailed database schema, see `docs/database.md`

---

## 🗃️ Core Data Fields

The application captures comprehensive information about each application:

### Basic Information
- **Short Description**: Application name/identifier
- **Application Service**: Reference to ServiceNow CMDB entry
- **Relevant for**: Yggdrasil relevance classification
- **Business Need**: Plain-language justification (max 350 characters)

### Project Management
- **Phase**: Delivery model stage (Need → Solution → Build → Implement → Operate)
- **Status**: Current progress (Unknown, Not started, Ongoing Work, On Hold, Completed)
- **Handover Status**: 10-step progress slider (0-100%)
- **Due Date**: Target go-live date
- **Project Manager**: Responsible for project activities
- **Product Owner**: Business need owner
- **Delivery Responsible**: Lead vendor/alliance

### Technical Details
- **Deployment Model**: SaaS, On-premise, Externally hosted, Client Application
- **Integrations**: Data pipeline/integration indicator
- **S.A. Document**: Solution Architecture documentation link

### Business Context
- **Pre-ops Portfolio**: Yggdrasil project portfolio
- **Application Portfolio**: Target IT operations portfolio
- **Contract Number**: Reference to commercial agreement
- **Contract Responsible**: Commercial lead
- **Corporator Link**: Project management system reference

### Documentation & Relationships
- **Information Space**: SharePoint/documentation area
- **BA SharePoint List**: Business analyst maintained list
- **Relationship in Yggdrasil**: Connected applications
- **Assigned To**: Data maintenance responsibility

---

## 🚧 Development Roadmap

### Phase 1: Core Foundation ✅ (Complete)
- User registration and authentication
- Database structure and relationships
- Basic CRUD operations for applications
- Responsive UI with Bootstrap
- Dynamic form fields from database

### Phase 2: Enhanced Features (In Progress)
- [ ] Universal search functionality (`search.php`)
- [ ] User administration interface (`users_admin.php`)
- [ ] Work notes and comment system
- [ ] File upload and attachment management
- [ ] Role-based access control implementation

### Phase 3: Advanced Integration (Planned)
- [ ] ServiceNow API integration
- [ ] Entra ID authentication
- [ ] Advanced reporting and analytics
- [ ] Workflow automation
- [ ] API endpoints for external integrations

### Phase 4: Enterprise Features (Future)
- [ ] Multi-tenant support
- [ ] Advanced audit trail with rollback
- [ ] Real-time notifications
- [ ] Dashboard analytics and charts
- [ ] Mobile application

---

## 🔧 Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)

### Quick Start
1. Clone the repository
2. Configure database settings in `src/config/config.php`
3. Import database schema (see `docs/database.md`)
4. Populate lookup tables with default values:
   ```sql
   INSERT INTO phases (name) VALUES 
   ('Need'), ('Solution'), ('Build'), ('Implement'), ('Operate');
   
   INSERT INTO statuses (name) VALUES
   ('Unknown'), ('Not started'), ('Ongoing Work'), ('On Hold'), ('Completed');
   ```
5. Point web server document root to `/public` directory
6. Create first admin user via registration

---

## 📊 Database Integration

### Lookup Tables
The system uses normalized lookup tables for consistent data:
- `phases`: Delivery model phases
- `statuses`: Application status values
- `deployment_models`: Technical deployment types
- `portfolios`: Business and technical portfolios

### Dynamic Form Population
Forms automatically populate dropdowns from database tables, eliminating hardcoded values and enabling easy maintenance.

### Audit Trail
All changes are logged in the `audit_log` table with:
- What changed (table, field, old/new values)
- Who made the change
- When the change occurred

---

## 🌐 Deployment & Hosting

### Target Environment
- **Production URL**: [apptrack.no](https://apptrack.no)
- **Technology Stack**: LAMP (Linux, Apache, MySQL, PHP)
- **Responsive Design**: Mobile-first Bootstrap implementation

### Development Tools
- **GitHub Copilot**: AI-assisted development
- **VS Code**: Primary development environment
- **phpMyAdmin**: Database administration

---

## 📚 Documentation

### Primary Documentation
- `README.md`: Project overview and setup guide
- `docs/database.md`: Complete database schema and relationships

### Code Documentation
- Inline comments in critical functions
- Security implementation notes
- Database query documentation

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

