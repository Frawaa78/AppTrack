# AppTrack

**AppTrack** is a web-based application management tool developed for the Yggdrasil project in Aker BP. Its purpose is to replace manual spreadsheets and fragmented documentation by offering a centralized, structured application registry.

---

## 🎯 Objective

The goal is to build a modular full-stack web application using:

- **PHP 8+** (backend)
- **MySQL 8.0** (database)
- **Bootstrap 5.3** (frontend framework)
- **Choices.js** (enhanced multi-select components)
- **Vanilla JavaScript** (interactive functionality)

The system enables users to register, update, and view relevant information about applications that are part of or affected by the Yggdrasil project. It is designed to scale and evolve, supporting new fields and future integrations, such as:

- **ServiceNow CMDB** integration (Aker BP)
- **Entra ID** authentication
- **AI-enhanced functionality** integration

---

## 🆕 Current Status & Recent Changes

### Implemented Features ✅
- **User Authentication**: Registration, login, logout with secure password hashing
- **Database-Driven Forms**: Phase and status values are dynamically fetched from database tables
- **Modern Responsive UI**: Space-efficient horizontal form layout with Bootstrap 5.3 integration
- **Application Management**: Create, edit, view applications with comprehensive form validation
- **Enhanced Form Experience**: 
  - Horizontal layout with fixed-width labels (160px) for perfect alignment
  - Clear buttons for URL fields with intuitive X icons
  - Interactive handover slider with 11 visual progress markers (0-100%)
  - Centered tooltips with precise positioning and descriptive text
  - Real-time progress feedback and dynamic marker highlighting
- **Advanced Search**: Related applications field with real-time database search via Choices.js
- **Shared Components**: Consistent navigation bar and styling across all pages
- **Security**: Input validation, prepared statements, session management
- **Cross-Page Consistency**: Identical styling and behavior between form and view modes
- **Reliable JavaScript**: Window-scoped functions with proper error handling

### Latest Major Update: Version 2.0 (July 2025)
Complete UI/UX redesign focusing on space efficiency and user experience:

#### **Form Layout Revolution**
- **50% space reduction**: Horizontal layout vs. previous vertical design
- **Perfect alignment**: Fixed-width labels with right-alignment for visual consistency
- **Responsive design**: Adapts seamlessly to different screen sizes

#### **Activity Tracking System** 🆕
- **Comprehensive Activity Feed**: Combines manual work notes and automatic audit logging
- **Work Notes**: Manual entries with comments, problems, and changes
- **Audit Trail**: Automatic logging of all field modifications
- **File Attachments**: Support for uploading documents, images, and archives (up to 10MB)
- **Priority System**: Visual indicators for low, medium, and high priority items
- **Real-time Updates**: Activities appear immediately without page refresh
- **Filtering Options**: "Work Notes Only" filter for focused view
- **Admin Controls**: Hide/show functionality for sensitive information
- **User Attribution**: All activities linked to users with email display
- **Relative Timestamps**: Human-friendly time formatting
- **RESTful API**: Complete backend API for activity management

#### **Interactive Enhancements**
- **Enhanced Handover Slider**: 11 visual markers, dynamic highlighting, centered tooltips
- **Clear Buttons**: X buttons for quick field clearing on URL inputs
- **Smart Search**: Real-time application search with formatted dropdown results
- **Improved UX**: Removed blue glow effects, consistent styling, better button feedback

#### **Technical Improvements**
- **Modular CSS Architecture**: Component-based styling with organized imports
- **Reliable JavaScript**: Fixed event handling, window-scoped functions, proper error handling
- **API Integration**: RESTful search endpoint for related applications
- **Cross-Platform Consistency**: Identical experience between edit and view modes

### Current Architecture Overview
```
AppTrack/
├── public/                     # Web-accessible files
│   ├── index.php              # Welcome/landing page  
│   ├── login.php              # User authentication
│   ├── register.php           # User registration
│   ├── dashboard.php          # Main application overview
│   ├── app_form.php           # Create/edit application form with activity tracker
│   ├── app_view.php           # Read-only application details
│   ├── users_admin.php        # User administration
│   ├── api/                   # RESTful API endpoints
│   │   ├── search_applications.php  # Application search endpoint
│   │   ├── search_users.php         # User search endpoint  
│   │   ├── get_activity_feed.php    # Activity tracker data
│   │   ├── add_work_note.php        # Manual activity creation
│   │   ├── hide_activity.php        # Admin activity control
│   │   ├── show_activity.php        # Admin activity control
│   │   └── download_attachment.php  # File download handler
│   └── shared/
│       ├── topbar.php         # Consistent navigation component
│       └── activity_tracker.php     # Activity tracking widget
├── src/                       # Backend logic  
│   ├── config/
│   │   └── config.php         # Database configuration
│   ├── db/
│   │   └── db.php             # PDO database singleton class
│   ├── models/                # Data models
│   │   ├── Application.php    # Application entity
│   │   └── User.php           # User entity and authentication
│   ├── controllers/           # Business logic controllers
│   │   ├── ApplicationController.php  # Application CRUD operations
│   │   ├── AuthController.php        # Authentication logic
│   │   └── UserController.php        # User management
│   └── managers/              # Service layer
│       └── ActivityManager.php       # Activity tracking system
├── assets/                    # Organized static assets
│   ├── css/
│   │   ├── main.css          # Primary stylesheet with imports
│   │   ├── components/       # Component-specific styles
│   │   │   ├── activity-tracker.css  # Activity feed styling
│   │   │   ├── forms.css     # Form layout and styling
│   │   │   ├── buttons.css   # Button components
│   │   │   ├── choices.css   # Multi-select dropdown styling
│   │   │   ├── range-slider.css # Slider component styling
│   │   │   └── user-dropdown.css # User interface components
│   │   └── pages/            # Page-specific styles
│   │       └── app-view.css  # Application view page
│   └── js/
│       ├── main.js           # Core JavaScript functionality
│       ├── components/       # Reusable JavaScript components
│       │   ├── activity-tracker.js   # Activity system frontend
│       │   ├── form-handlers.js      # Form interaction logic
│       │   └── choices-init.js       # Multi-select initialization
│       └── pages/            # Page-specific JavaScript
│           ├── app-form.js   # Form page enhancements
│           └── app-view.js   # View page functionality
├── docs/                      # Comprehensive documentation
│   ├── database.md           # Complete database schema with activity tracking
│   ├── technical-architecture.md  # System architecture guide
│   └── ui-implementation.md       # UI/UX technical guide
└── README.md                 # This file
```

---

## 🔑 Key Features

### User Interface & Experience
- **Modern Horizontal Layout**: Space-efficient design with 50% less vertical scrolling
- **Perfect Visual Alignment**: Fixed-width labels (160px) with right-alignment for consistency
- **Interactive Form Elements**: 
  - Enhanced handover slider with 11 visual progress markers (0-100%)
  - Real-time tooltip positioning with descriptive progress text
  - Clear (X) buttons for quick URL field clearing
  - Dynamic visual feedback for all interactive elements
- **Cross-Platform Consistency**: Identical styling between edit and view modes
- **Responsive Design**: Optimized for desktop, tablet, and mobile devices

### Data Management & Search
- **Comprehensive Application Registry**: Complete tracking of all Yggdrasil-related applications
- **Advanced Search Functionality**: Real-time database search with formatted results
- **Smart Relationships**: Related applications linking with bidirectional updates
- **Form Validation**: Client-side and server-side validation with user-friendly error messages
- **Data Import**: ServiceNow CSV import capability for bulk data entry

### Application Data Fields
The system captures comprehensive information structured around the Yggdrasil Delivery Model:

#### **Basic Information**
- **Short Description**: Application name/identifier
- **Application Service**: Reference to ServiceNow CMDB entry
- **Relevant for**: Yggdrasil relevance classification (To be decided, Yggdrasil, Not relevant)
- **Business Need**: Plain-language justification (max 350 characters)

#### **Project Management**
- **Phase**: Delivery model stage (Need → Solution → Build → Implement → Operate)
- **Status**: Current progress (Unknown, Not started, Ongoing Work, On Hold, Completed)
- **Handover Status**: Interactive 10-step progress slider (0-100%) with descriptive tooltips
- **Due Date**: Target go-live date with date picker
- **Project Manager**: Responsible for project activities
- **Product Owner**: Business need owner
- **Delivery Responsible**: Lead vendor/alliance

#### **Technical Details**
- **Deployment Model**: SaaS, On-premise, Externally hosted, Client Application
- **Integrations**: Data pipeline/integration indicator with conditional S.A. Document field
- **S.A. Document**: Solution Architecture documentation link (appears when Integrations = "Yes")

#### **Business Context & Contracts**
- **Pre-ops Portfolio**: Yggdrasil project portfolio assignment
- **Application Portfolio**: Target IT operations portfolio (HR, SCM, Digital, etc.)
- **Contract Number**: Reference to commercial agreement
- **Contract Responsible**: Commercial lead contact
- **Corporator Link**: Project management system reference

#### **Documentation & Relationships**
- **Information Space**: SharePoint/Viva documentation area with clickable links
- **BA SharePoint List**: Business analyst maintained list with external link support
- **Related Applications**: Multi-select search with real-time database lookup
- **Assigned To**: Data maintenance responsibility with user search

---

## 👥 User Roles & Security

### Role-Based Access Control
- **Admin**: Full access (view/edit/delete all applications and user management)
- **Editor**: View and edit applications (cannot manage users)
- **Viewer**: Read-only access to all application data

### Security Implementation
- **Password Security**: BCrypt hashing with salt for all user passwords
- **Database Security**: Prepared statements preventing SQL injection attacks
- **Session Management**: Secure session handling with proper timeout
- **Input Validation**: Client-side and server-side validation with sanitization
- **Authorization**: Role-based page access control
- **CSRF Protection**: Planned implementation for form submissions

---

## ⚙️ Technical Architecture

### Backend Technology Stack
- **PHP 8+**: Modern server-side logic with type declarations and improved performance
- **MySQL 8.0**: Relational database with optimized query performance
- **PDO Database Layer**: Secure database abstraction with prepared statements
- **RESTful API**: Clean API endpoints for search and data operations

### Frontend Technology Stack
- **Bootstrap 5.3**: Latest responsive CSS framework with enhanced components
- **Vanilla JavaScript (ES6+)**: Modern JavaScript without dependencies for core functionality
- **Choices.js**: Enhanced multi-select dropdowns with search capabilities
- **Custom CSS Architecture**: Modular component-based styling system

### Database Design Principles
- **Normalized Structure**: Lookup tables for phases, statuses, and deployment models
- **Foreign Key Relationships**: Data integrity with proper constraints
- **Audit Trail Capability**: Complete change tracking for compliance
- **Performance Optimization**: Indexed fields for fast search operations
- **Extensible Schema**: Easy addition of new fields and relationships

### CSS Architecture
```
assets/css/
├── main.css              # Primary import file
├── components/           # Reusable component styles
│   ├── forms.css        # Form layout and styling
│   ├── buttons.css      # Button components
│   ├── choices.css      # Multi-select dropdown styling
│   └── range-slider.css # Interactive slider components
└── pages/               # Page-specific styling
```

### JavaScript Architecture
```
assets/js/
├── main.js              # Core functionality and initialization
├── components/          # Reusable JavaScript modules
│   ├── form-handlers.js # Form interaction logic
│   └── choices-init.js  # Multi-select initialization
└── pages/              # Page-specific JavaScript
```

---

## 🗃️ Complete Data Schema

### Applications Table Structure
All form fields are properly mapped to database columns with appropriate data types:

| Category | Fields | Database Implementation |
|----------|--------|------------------------|
| **Identity** | Short Description, Application Service | VARCHAR(255), NOT NULL + NULL |
| **Classification** | Relevant For, Phase, Status | VARCHAR(255) with lookup table references |
| **Progress** | Handover Status | INT (0-100) with default 0 |
| **Contracts** | Contract Number, Contract Responsible | VARCHAR(255), both nullable |
| **Documentation** | Information Space, BA SharePoint, S.A. Document | TEXT fields supporting long URLs |
| **Relationships** | Related Applications, Assigned To | TEXT (comma-separated) + VARCHAR(255) |
| **Portfolios** | Pre-ops Portfolio, Application Portfolio | VARCHAR(255) for both |
| **Management** | Delivery Responsible, Project Manager, Product Owner | VARCHAR(255) for all |
| **Technical** | Deployment Model, Integrations | VARCHAR(255) with predefined options |
| **Timeline** | Due Date | DATE type |
| **Description** | Business Need | TEXT for extended content |

### Lookup Tables
- **phases**: Need, Solution, Build, Implement, Operate
- **statuses**: Unknown, Not started, Ongoing Work, On Hold, Completed  
- **deployment_models**: Client Application, On-premise, SaaS, Externally hosted

For complete database documentation, see `docs/database.md`

---

## 🚧 Development Roadmap

### Phase 1: Core Foundation ✅ (COMPLETE)
- [x] User registration and authentication system
- [x] Complete database structure with all required columns
- [x] Modern responsive UI with horizontal form layout
- [x] Dynamic form fields populated from database
- [x] Enhanced form experience with interactive elements
- [x] Real-time search for related applications
- [x] Cross-page styling consistency
- [x] Reliable JavaScript with proper error handling
- [x] Form validation and security implementation

### Phase 2: Enhanced Features 🔄 (IN PROGRESS)
- [ ] Universal search functionality across all applications
- [ ] User administration interface with role management
- [ ] Work notes and comment system for applications
- [ ] File upload and attachment management
- [ ] Advanced audit trail with change rollback capability
- [ ] Export functionality (PDF, Excel, CSV)

### Phase 3: Integration & Automation 📋 (PLANNED)
- [ ] ServiceNow CMDB API integration for real-time data sync
- [ ] Entra ID authentication for single sign-on
- [ ] Advanced reporting with charts and analytics
- [ ] Workflow automation for status changes
- [ ] Email notifications for important updates
- [ ] Dashboard analytics with visual progress indicators

### Phase 4: Enterprise Features 🚀 (FUTURE)
- [ ] Multi-tenant support for multiple organizations
- [ ] Advanced permissions with field-level access control
- [ ] Real-time collaboration features
- [ ] Mobile application with offline capabilities
- [ ] API ecosystem for third-party integrations
- [ ] Advanced analytics and business intelligence

---

## 🔧 Installation & Setup

### System Requirements
- **PHP 8.0+** with PDO MySQL extension
- **MySQL 8.0+** or MariaDB 10.4+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Modern Browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

### Quick Installation
1. **Clone Repository**
   ```bash
   git clone [repository-url]
   cd AppTrack
   ```

2. **Configure Database**
   ```php
   // src/config/config.php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'apptrack');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

3. **Import Database Schema**
   ```bash
   mysql -u username -p apptrack < docs/database_schema.sql
   ```

4. **Populate Lookup Tables**
   ```sql
   INSERT INTO phases (name) VALUES 
   ('Need'), ('Solution'), ('Build'), ('Implement'), ('Operate');
   
   INSERT INTO statuses (name) VALUES
   ('Unknown'), ('Not started'), ('Ongoing Work'), ('On Hold'), ('Completed');
   
   INSERT INTO deployment_models (name) VALUES
   ('Client Application'), ('On-premise'), ('SaaS'), ('Externally hosted');
   ```

5. **Configure Web Server**
   - Point document root to `/public` directory
   - Enable URL rewriting (if using clean URLs)
   - Set appropriate file permissions

6. **Create Admin User**
   - Navigate to `/register.php`
   - Create first user account
   - Manually set role to 'admin' in database if needed

### Production Deployment Checklist
- [ ] Remove development files (`*.sql` scripts, test files)
- [ ] Configure proper error logging (disable display_errors)
- [ ] Set up SSL certificate for HTTPS
- [ ] Configure database backups
- [ ] Set up monitoring and alerting
- [ ] Review file permissions and security settings

---

## 📊 API Documentation

### Search Endpoint
**GET** `/api/search_applications.php`

Search for applications with real-time filtering:

```javascript
// Example usage
fetch('/api/search_applications.php?q=finance&exclude=5&selected=1,3,7')
  .then(response => response.json())
  .then(applications => {
    // Handle search results
  });
```

**Parameters:**
- `q` (string): Search query (minimum 2 characters)
- `exclude` (int): Application ID to exclude from results
- `selected` (string): Comma-separated list of already selected IDs

**Response Format:**
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

---

## 🌐 Browser Compatibility & Performance

### Supported Browsers
- **Chrome**: 90+ (recommended)
- **Firefox**: 88+
- **Safari**: 14+
- **Edge**: 90+
- **Mobile**: iOS Safari 14+, Chrome Mobile 90+

### Performance Features
- **Optimized CSS**: Component-based architecture for faster loading
- **Efficient JavaScript**: Minimal DOM manipulation with event delegation
- **Database Optimization**: Indexed search fields and prepared statements
- **Responsive Images**: Properly sized assets for different screen densities
- **Caching Strategy**: Browser caching for static assets

---

## 📚 Documentation Structure

### Primary Documentation
- **README.md**: Complete project overview and setup guide
- **docs/database.md**: Comprehensive database schema and relationships
- **docs/ui-implementation.md**: Technical UI/UX implementation guide
- **CHANGELOG.md**: Detailed version history and feature updates

### Code Documentation
- **Inline Comments**: Critical functions and complex logic explained
- **Security Notes**: Implementation details for security features
- **API Documentation**: Endpoint specifications and usage examples
- **Database Queries**: Complex query explanations and optimization notes

---

## 📦 Yggdrasil Delivery Model Integration

The system is perfectly aligned with Aker BP's **Yggdrasil Delivery Model**, providing structured tracking through each phase:

### Phase Structure
1. **Need** – Recognize and document business needs
   - Business justification captured in Business Need field
   - Initial stakeholder identification and requirements gathering
   
2. **Solution** – Explore technical and commercial solutions  
   - Solution architecture documentation via S.A. Document field
   - Deployment model selection and technical planning
   
3. **Build** – Develop, test, and configure the application
   - Project management through assigned roles and timelines
   - Integration planning and technical implementation
   
4. **Implement** – Prepare organization and IT for operations
   - Handover status tracking with detailed 10-step progress monitoring
   - Portfolio assignment and operational readiness
   
5. **Operate** – Application is live and in production
   - Ongoing maintenance responsibility assignment
   - Continuous monitoring and updates

### Progress Tracking Features
- **Phase Management**: Visual button interface for current delivery phase
- **Status Monitoring**: Granular status tracking (Unknown → Not started → Ongoing → On Hold → Completed)
- **Handover Progress**: Interactive slider with 11 markers providing detailed handover status from 0% to 100%
- **Timeline Management**: Due date tracking with calendar integration
- **Stakeholder Clarity**: Clear role assignments (Project Manager, Product Owner, Delivery Responsible)

This phased approach ensures clear ownership, accountability, and traceability from initial business need to full operational status.

---

## 📄 License

**Proprietary Software** - Aker BP ASA

This software is developed for internal use within Aker BP and its approved contractors. All rights reserved.

---

## 🤝 Contributing

### Development Guidelines
- Follow established coding standards (PSR-12 for PHP)
- Write comprehensive comments for complex functionality
- Include database migration scripts for schema changes
- Test thoroughly across supported browsers
- Update documentation for any feature changes

### Pull Request Process
1. Fork the repository and create a feature branch
2. Make changes with appropriate tests and documentation
3. Ensure all existing functionality remains intact
4. Submit pull request with detailed description of changes
5. Code review and approval process before merging

---

## 📞 Support & Contact

### Primary Contacts
- **Project Lead**: `frank.waaland@akerbp.com`
- **Technical Support**: IT Service Desk
- **Business Queries**: Yggdrasil Project Office

### Development Environment
- **Repository**: Internal Aker BP GitLab/GitHub
- **Issue Tracking**: Integrated with repository
- **Documentation**: Maintained in `/docs` directory
- **Updates**: Regular releases with changelog documentation

---

## 🔄 Version History

**Current Version**: 2.0.0 (July 2025)
- Complete UI/UX redesign with horizontal layout
- Enhanced interactive elements and search functionality
- Improved cross-browser compatibility and performance

**Previous Versions**:
- 1.5.0: Enhanced read-only views and database optimization
- 1.0.0: Initial release with core functionality

For detailed version history, see `CHANGELOG.md`

---

## 🎯 Future Vision

AppTrack is designed to evolve into a comprehensive application lifecycle management platform, supporting:

- **Enterprise Integration**: Full ServiceNow CMDB synchronization
- **Advanced Analytics**: Business intelligence and reporting dashboards  
- **Workflow Automation**: Intelligent routing and approval processes
- **Mobile Excellence**: Native mobile applications for field teams
- **AI Enhancement**: Intelligent recommendations and automated data entry
- **Ecosystem Integration**: APIs for third-party tool connectivity

The foundation established in version 2.0 provides a robust platform for these future enhancements while maintaining the core focus on user experience and data integrity.

---

