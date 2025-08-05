# 📋 AppTrack Person- og Rollestruktur Plan
## Strukturert Yggdrasil Digital Organisasjon Integration

**Opprettet**: 5. august 2025  
**Versjon**: 1.0  
**Status**: Planleggingsfase

---

## 🎯 Målsetting

Implementere en strukturert tilnærming for å knytte personer til applikasjoner, prosjekter og initiativer i AppTrack som reflekterer den faktiske Yggdrasil Digital organisasjonsstrukturen med 8 porteføljer, roller og ansvarsområder.

---

## 📊 Analyse av Nåværende Situasjon

### Eksisterende Rollestruktur i AppTrack
**I applications-tabellen:**
```sql
-- Nåværende person-tilknytning (VARCHAR-felt)
assigned_to VARCHAR(100)              -- Data maintenance responsibility  
project_manager VARCHAR(100)          -- Project activities lead
product_owner VARCHAR(100)            -- Business need owner
delivery_responsible VARCHAR(100)     -- Lead vendor/alliance
contract_responsible VARCHAR(255)     -- Commercial lead contact

-- Portfolio-tilknytning (VARCHAR-felt)
preops_portfolio VARCHAR(100)         -- Yggdrasil project portfolio
application_portfolio VARCHAR(100)    -- Target IT operations portfolio
```

**Nåværende users-tabell:**
```sql
id, email, first_name, last_name, display_name, phone, 
is_active, password_hash, role (admin/editor/viewer), created_at
```

### Identifiserte Problemer
1. **Ingen strukturert persondata**: Kun fritekst-felt uten kobling til personer
2. **Manglende rollehierarki**: Ingen definerte rollestrukturer
3. **Begrenset porteføljestyring**: Kun navn-baserte porteføljer uten eierskap
4. **Ingen prosjekt/initiativ-kobling**: Mangler kobling mellom applikasjoner og prosjekter
5. **Ingen Yggdrasil-struktur**: Mangler representasjon av den faktiske organisasjonen

---

## 🏗️ Foreslått Ny Struktur

### Yggdrasil Digital Organisasjonsmodell

### Fagmessige Kommunikasjonslinjer (Ikke Hierarkiske)

```yaml
Faglig Struktur i Yggdrasil Digital:

Hovedledelse (Strategisk nivå):
  - Ander Bay Næss (AOM): Overordnet digital strategi
  - Lars Erik Ydstie (Digital Execution Manager): Utførelse og koordinering
  - Rangval Soldal (PreOps Digital Manager): Pre-operativ digital aktivitet
  - Tina Ødegård (Business Support): Forretningsstøtte

Porteføljenivå (8 fagområder):
  Digital Portfolio Manager (DPM):
    - Kommuniserer med: Porteføljeeier, andre DPMer, hovedledelse
    - Koordinerer: Digitale initiativer i sitt fagområde
    - Ansvar: Digital strategi og prioritering
  
  Fagansvarlig Lead:
    - Kommuniserer med: DPM, prosjektledere, fageksperter
    - Koordinerer: Faglig kvalitet og best practices
    - Ansvar: Faglig ledelse og kompetanse

Prosjekt/Initiativ-nivå (Faglig utførelse):
  Project Owner:
    - Kommuniserer med: DPM, prosjektleder, stakeholders
    - Ansvar: Forretningsresultat og beslutninger
  
  Teknisk Prosjektleder:
    - Kommuniserer med: Project Owner, team, andre prosjektledere
    - Koordinerer: Teknisk utførelse og leveranser
    - Ansvar: Prosjektgjennomføring og kvalitet
  
  Digital Koordinator:
    - Kommuniserer med: Prosjektleder, DPM, andre koordinatorer
    - Koordinerer: Digital transformasjon og alignment
    - Ansvar: Digital coherence på tvers

Fagteam-nivå (Spesialisert arbeid):
  Løsningsarkitekt:
    - Kommuniserer med: Prosjektleder, utviklere, andre arkitekter
    - Ansvar: Teknisk design og arkitektur
  
  Forretningsanalytiker:
    - Kommuniserer med: Project Owner, prosjektleder, brukere
    - Ansvar: Krav og forretningsprosesser
  
  Informasjonsarkitekt:
    - Kommuniserer med: Løsningsarkitekt, forretningsanalytiker
    - Ansvar: Datamodeller og informasjonsflyt
  
  Subject Matter Expert (SME):
    - Kommuniserer med: Prosjektteam, andre SMEer, fagansvarlig
    - Ansvar: Domenekunnskap og faglig validering
  
  Utvikler:
    - Kommuniserer med: Løsningsarkitekt, prosjektleder
    - Ansvar: Koding og implementering

Cross-cutting Roller:
  Cyber Security Specialist:
    - Kommuniserer med: Alle nivåer basert på sikkerhetsbehov
    - Ansvar: Sikkerhet på tvers av initiativer
```

---

## 💾 Database Design for Ny Struktur

### 1. Forenklet Brukertabell (Fagmessig Fokus)
```sql
-- Utvide eksisterende users-tabell - kun faglig relevante felt
ALTER TABLE users ADD COLUMN professional_title VARCHAR(150) NULL;   -- Faglig tittel (Solution Architect, etc.)
ALTER TABLE users ADD COLUMN expertise_areas TEXT NULL;              -- Kompetanseområder (JSON eller kommaseparert)
ALTER TABLE users ADD COLUMN yggdrasil_member BOOLEAN DEFAULT FALSE; -- Aktiv i Yggdrasil Digital
ALTER TABLE users ADD COLUMN preferred_contact VARCHAR(50) NULL;     -- Teams, telefon, etc.
ALTER TABLE users ADD COLUMN bio TEXT NULL;                          -- Faglig bakgrunn/erfaring

-- Ikke nødvendig: employee_id, department, manager_id, office_location, cost_center
-- Rapportering skjer gjennom prosjekt/initiativ-roller
```

### 2. Organisasjonsstruktur Tabeller
```sql
-- Porteføljestruktur
CREATE TABLE yggdrasil_portfolios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    portfolio_owner_id INT,                    -- Porteføljeeier
    digital_portfolio_manager_id INT,          -- DPM
    lead_expert_id INT,                        -- Fagansvarlig Lead
    budget_owner_id INT,                       -- Budsjettansvarlig
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (portfolio_owner_id) REFERENCES users(id),
    FOREIGN KEY (digital_portfolio_manager_id) REFERENCES users(id),
    FOREIGN KEY (lead_expert_id) REFERENCES users(id),
    FOREIGN KEY (budget_owner_id) REFERENCES users(id)
);

-- Prosjekter og initiativer
CREATE TABLE yggdrasil_initiatives (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    initiative_type ENUM('project', 'initiative', 'program') NOT NULL,
    portfolio_id INT NOT NULL,
    project_owner_id INT,                      -- Project Owner
    technical_lead_id INT,                     -- Teknisk Prosjektleder
    digital_coordinator_id INT,                -- Digital Koordinator
    solution_architect_id INT,                 -- Løsningsarkitekt
    status ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    start_date DATE,
    end_date DATE,
    budget DECIMAL(15,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (portfolio_id) REFERENCES yggdrasil_portfolios(id),
    FOREIGN KEY (project_owner_id) REFERENCES users(id),
    FOREIGN KEY (technical_lead_id) REFERENCES users(id),
    FOREIGN KEY (digital_coordinator_id) REFERENCES users(id),
    FOREIGN KEY (solution_architect_id) REFERENCES users(id)
);

-- Drift-porteføljer (ASM)
CREATE TABLE asm_portfolios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    asm_manager_id INT,                        -- Application Service Manager
    deputy_manager_id INT,                     -- Deputy
    portfolio_type ENUM('hr', 'scm', 'digital', 'infrastructure', 'security') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (asm_manager_id) REFERENCES users(id),
    FOREIGN KEY (deputy_manager_id) REFERENCES users(id)
);
```

### 3. Rolledefinisjon System
```sql
-- Standardiserte roller
CREATE TABLE role_definitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_category ENUM('yggdrasil_leadership', 'portfolio_management', 'project_execution', 'asm_operations', 'specialist') NOT NULL,
    description TEXT,
    responsibilities TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Populate with standard roles (English for database)
INSERT INTO role_definitions (role_name, role_category, description, responsibilities) VALUES
-- Yggdrasil Leadership
('AOM', 'yggdrasil_leadership', 'Application Operations Manager', 'Overall responsibility for Yggdrasil Digital organization'),
('Digital Execution Manager', 'yggdrasil_leadership', 'Digital Execution Manager', 'Execution and coordination of digital strategy'),
('PreOps Digital Manager', 'yggdrasil_leadership', 'PreOps Digital Manager', 'Responsible for pre-operational digital activities'),
('Business Support', 'yggdrasil_leadership', 'Business Support', 'Business support for leadership'),

-- Portfolio Management
('Portfolio Owner', 'portfolio_management', 'Portfolio Owner', 'Overall responsibility for portfolio'),
('Digital Portfolio Manager', 'portfolio_management', 'Digital Portfolio Manager', 'Manages digital initiatives in portfolio'),
('Lead Expert', 'portfolio_management', 'Lead Expert', 'Business support at portfolio level'),

-- Project Execution
('Project Owner', 'project_execution', 'Project Owner', 'Project responsibility and ownership'),
('Technical Project Lead', 'project_execution', 'Technical Project Lead', 'Technical project leadership'),
('Digital Project Coordinator', 'project_execution', 'Digital Project Coordinator', 'Digital coordination'),
('Solution Architect', 'project_execution', 'Solution Architect', 'Architecture and solution design'),
('Business Analyst', 'specialist', 'Business Analyst', 'Business analysis and requirements'),
('Developer', 'specialist', 'Developer', 'System development'),
('Information Architect', 'specialist', 'Information Architect', 'Information architecture'),
('Cyber Security Specialist', 'specialist', 'Cyber Security Specialist', 'Security specialist'),
('Subject Matter Expert', 'specialist', 'Subject Matter Expert', 'Domain-specific expertise'),

-- ASM Operations
('Application Service Manager', 'asm_operations', 'Application Service Manager', 'Responsible for application operations'),
('Deputy ASM', 'asm_operations', 'Deputy ASM', 'Deputy for ASM');
```

### 4. Faglig Kommunikasjon og Samarbeid
```sql
-- Communication lines based on professional work (not hierarchical reporting)
CREATE TABLE professional_collaborations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_user_id INT NOT NULL,                 -- Person collaborating
    to_user_id INT NOT NULL,                   -- Person being collaborated with
    collaboration_type ENUM(
        'reports_to',                          -- Professional reporting (project-based)
        'coordinates_with',                    -- Coordination at same level
        'consults_with',                       -- Professional advisory
        'collaborates_with',                   -- Close collaboration
        'escalates_to'                         -- Escalation for decisions
    ) NOT NULL,
    context_type ENUM('initiative', 'portfolio', 'application', 'general') NOT NULL,
    context_id INT NULL,                       -- ID to initiative, portfolio or app
    relationship_strength ENUM('occasional', 'regular', 'frequent', 'daily') DEFAULT 'regular',
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (from_user_id) REFERENCES users(id),
    FOREIGN KEY (to_user_id) REFERENCES users(id),
    
    UNIQUE KEY unique_collaboration (from_user_id, to_user_id, collaboration_type, context_type, context_id)
);

-- Expertise areas and competencies (English for database)
CREATE TABLE user_expertise (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    expertise_area VARCHAR(100) NOT NULL,     -- e.g. "Production Optimization", "Safety Systems"
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') NOT NULL,
    years_experience INT DEFAULT 0,
    certification VARCHAR(255) NULL,          -- Certifications
    is_primary_expertise BOOLEAN DEFAULT FALSE, -- Primary competency
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_expertise (user_id, expertise_area)
);
```

### 5. Person-til-Rolle Mapping (Faglig Basert)
```sql
-- Application-to-person roles (professional connection)
CREATE TABLE application_person_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    role_definition_id INT NOT NULL,
    initiative_id INT NULL,                    -- Connection to project/initiative
    is_lead_role BOOLEAN DEFAULT FALSE,        -- Leadership responsibility for this role
    start_date DATE DEFAULT (CURRENT_DATE),
    end_date DATE NULL,
    allocation_percentage DECIMAL(5,2) DEFAULT 100.00, -- Resource allocation
    collaboration_notes TEXT,                  -- How the person collaborates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_definition_id) REFERENCES role_definitions(id),
    FOREIGN KEY (initiative_id) REFERENCES yggdrasil_initiatives(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    
    UNIQUE KEY unique_app_user_role (application_id, user_id, role_definition_id, start_date)
);

-- Initiative-to-person roles (project team)
CREATE TABLE initiative_person_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    initiative_id INT NOT NULL,
    user_id INT NOT NULL,
    role_definition_id INT NOT NULL,
    is_team_lead BOOLEAN DEFAULT FALSE,        -- Team leader for this role
    allocation_percentage DECIMAL(5,2) DEFAULT 100.00,
    start_date DATE DEFAULT (CURRENT_DATE),
    end_date DATE NULL,
    responsibility_areas TEXT,                 -- Specific areas of responsibility
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    
    FOREIGN KEY (initiative_id) REFERENCES yggdrasil_initiatives(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_definition_id) REFERENCES role_definitions(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    
    UNIQUE KEY unique_initiative_user_role (initiative_id, user_id, role_definition_id, start_date)
);
```

### 6. Modifisere applications-tabellen
```sql
-- Add new fields and deprecate old ones
ALTER TABLE applications ADD COLUMN primary_initiative_id INT NULL;
ALTER TABLE applications ADD COLUMN asm_portfolio_id INT NULL;
ALTER TABLE applications ADD COLUMN yggdrasil_portfolio_id INT NULL;

-- Foreign keys
ALTER TABLE applications ADD FOREIGN KEY (primary_initiative_id) REFERENCES yggdrasil_initiatives(id);
ALTER TABLE applications ADD FOREIGN KEY (asm_portfolio_id) REFERENCES asm_portfolios(id);
ALTER TABLE applications ADD FOREIGN KEY (yggdrasil_portfolio_id) REFERENCES yggdrasil_portfolios(id);

-- Deprecate old fields (kept for backward compatibility)
ALTER TABLE applications ADD COLUMN legacy_assigned_to VARCHAR(100) DEFAULT NULL COMMENT 'Deprecated - use application_person_roles';
ALTER TABLE applications ADD COLUMN legacy_project_manager VARCHAR(100) DEFAULT NULL COMMENT 'Deprecated - use application_person_roles';
ALTER TABLE applications ADD COLUMN legacy_product_owner VARCHAR(100) DEFAULT NULL COMMENT 'Deprecated - use application_person_roles';
ALTER TABLE applications ADD COLUMN legacy_delivery_responsible VARCHAR(100) DEFAULT NULL COMMENT 'Deprecated - use application_person_roles';

-- Migrate existing data
UPDATE applications SET 
    legacy_assigned_to = assigned_to,
    legacy_project_manager = project_manager,
    legacy_product_owner = product_owner,
    legacy_delivery_responsible = delivery_responsible;
```

---

## 🔧 Implementeringsplan

### Fase 1: Database Fundament (Uke 1-2)
**Mål**: Etablere grunnleggende database-struktur

**Tasks:**
1. **Opprett nye tabeller**
   - `yggdrasil_portfolios`
   - `yggdrasil_initiatives` 
   - `asm_portfolios`
   - `role_definitions`
   - `application_person_roles`
   - `initiative_person_roles`

2. **Utvid users-tabell**
   - Legg til Yggdrasil-spesifikke felt
   - Opprett indekser for performance

3. **Populer referansedata**
   - Standard rolledefinisjoner
   - 8 Yggdrasil porteføljer
   - Basis ASM porteføljer

**SQL Script:**
```sql
-- Fase 1 implementering
-- Se detaljerte CREATE TABLE statements ovenfor
```

### Fase 2: Data Migration og API (Uke 3-4)
**Mål**: Migrer eksisterende data og etabler API-endepunkter

**Data Migration Strategy:**
```sql
-- Migrate existing persons to structured format
-- 1. Create users based on existing name fields
INSERT INTO users (email, display_name, professional_title, yggdrasil_member)
SELECT DISTINCT 
    LOWER(CONCAT(REPLACE(project_manager, ' ', '.'), '@akerbp.com')) as email,
    project_manager as display_name,
    'Project Manager' as professional_title,
    TRUE as yggdrasil_member
FROM applications 
WHERE project_manager IS NOT NULL AND project_manager != '';

-- 2. Create application_person_roles based on existing data
INSERT INTO application_person_roles (application_id, user_id, role_definition_id, is_lead_role)
SELECT 
    a.id as application_id,
    u.id as user_id,
    rd.id as role_definition_id,
    TRUE as is_lead_role
FROM applications a
JOIN users u ON u.display_name = a.project_manager
JOIN role_definitions rd ON rd.role_name = 'Technical Project Lead'
WHERE a.project_manager IS NOT NULL AND a.project_manager != '';
```

**New API Endpoints:**
```php
// api/yggdrasil/portfolios.php - Portfolio management
// api/yggdrasil/initiatives.php - Initiative/project management  
// api/yggdrasil/roles.php - Role assignments
// api/persons/search.php - Person search and lookup
// api/persons/roles.php - Person role management
```

### Fase 3: UI Components (Uke 5-6)
**Mål**: Nye brukergrensesnitt for person- og rollehåndtering

**New UI Components:**

1. **Initiative/Project Selector**
```html
<!-- Replace "Delivery Responsible" with Initiative dropdown -->
<div class="form-group-horizontal">
    <label for="primaryInitiative" class="form-label">Initiative/Project</label>
    <select class="form-select" id="primaryInitiative" name="primary_initiative_id">
        <option value="">Select Initiative/Project</option>
        <!-- Populated via API based on portfolio -->
    </select>
</div>
```

2. **Person Role Assignment Widget**
```html
<!-- New section for role management -->
<div class="card mt-4">
    <div class="card-header">
        <h6>Project Team & Roles</h6>
    </div>
    <div class="card-body">
        <div id="personRoleWidget">
            <!-- Dynamic role assignment interface -->
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addPersonRole">
            <i class="bi bi-person-plus"></i> Add Person to Role
        </button>
    </div>
</div>
```

3. **Portfolio Hierarchy Visualization**
```html
<!-- Portfolio structure display -->
<div class="portfolio-hierarchy">
    <div class="portfolio-level">
        <strong>Portfolio:</strong> <span class="portfolio-name">Integrated Operations</span>
        <div class="portfolio-roles">
            <span class="role-badge">Owner: <strong>John Doe</strong></span>
            <span class="role-badge">DPM: <strong>Jane Smith</strong></span>
        </div>
    </div>
    <div class="initiative-level">
        <strong>Initiative:</strong> <span class="initiative-name">Production Optimization Platform</span>
        <div class="initiative-team">
            <!-- Team members with roles -->
        </div>
    </div>
</div>
```

### Fase 4: Advanced Features (Uke 7-8)
**Mål**: Avanserte funksjoner for organisasjonshåndtering

**Person Management Dashboard:**
```php
// public/yggdrasil/organization.php
// - Organisasjonskart
// - Ressursallokering oversikt  
// - Rollestatistikk
// - Team-sammensetniger
```

**Resource Allocation Views:**
```php
// public/yggdrasil/resources.php
// - Hvem jobber på hva
// - Kapasitetsplanlegging
// - Cross-portfolio ressurser
// - Ekspertise-mapping
```

**Reporting & Analytics:**
```php
// public/reports/yggdrasil_analytics.php
// - Portfolio performance
// - Resource utilization
// - Role distribution
// - Initiative progress
```

---

## 📋 Datamodell Eksempler

### Fagmessig Portefølje Setup
```sql
-- Eksempel data for Integrated Operations portefølje
INSERT INTO yggdrasil_portfolios (name, description) VALUES 
('Integrated Operations', 'Fagportefølje for integrerte operasjoner og produksjonsoptimering');

-- Set professional roles for the portfolio (not administrative positions)
INSERT INTO users (email, display_name, professional_title, yggdrasil_member, expertise_areas) VALUES
('john.doe@akerbp.com', 'John Doe', 'Portfolio Owner - Integrated Operations', TRUE, 'Production Systems,Operations Management'),
('jane.smith@akerbp.com', 'Jane Smith', 'Digital Portfolio Manager', TRUE, 'Digital Transformation,Process Optimization'),
('eric.hansen@akerbp.com', 'Eric Hansen', 'Lead Expert - Operations', TRUE, 'Operations Excellence,Maintenance Systems');

UPDATE yggdrasil_portfolios SET 
    portfolio_owner_id = (SELECT id FROM users WHERE email = 'john.doe@akerbp.com'),
    digital_portfolio_manager_id = (SELECT id FROM users WHERE email = 'jane.smith@akerbp.com'),
    lead_expert_id = (SELECT id FROM users WHERE email = 'eric.hansen@akerbp.com')
WHERE name = 'Integrated Operations';

-- Add expertise areas
INSERT INTO user_expertise (user_id, expertise_area, proficiency_level, is_primary_expertise) VALUES
((SELECT id FROM users WHERE email = 'john.doe@akerbp.com'), 'Production Systems', 'expert', TRUE),
((SELECT id FROM users WHERE email = 'jane.smith@akerbp.com'), 'Digital Transformation', 'expert', TRUE),
((SELECT id FROM users WHERE email = 'eric.hansen@akerbp.com'), 'Operations Excellence', 'expert', TRUE);
```

### Faglig Samarbeid og Kommunikasjon
```sql
-- Professional communication lines for the project
-- SME reports professionally to project lead
INSERT INTO professional_collaborations (from_user_id, to_user_id, collaboration_type, context_type, context_id) VALUES
((SELECT id FROM users WHERE email = 'sme.operations@akerbp.com'),
 (SELECT id FROM users WHERE email = 'project.lead@akerbp.com'),
 'reports_to', 'initiative', 
 (SELECT id FROM yggdrasil_initiatives WHERE name = 'Production Optimization Platform'));

-- Solution architect coordinates with business analyst
INSERT INTO professional_collaborations (from_user_id, to_user_id, collaboration_type, context_type, context_id) VALUES
((SELECT id FROM users WHERE email = 'solution.architect@akerbp.com'),
 (SELECT id FROM users WHERE email = 'business.analyst@akerbp.com'),
 'collaborates_with', 'initiative', 
 (SELECT id FROM yggdrasil_initiatives WHERE name = 'Production Optimization Platform'));

-- Project lead coordinates with other project leads
INSERT INTO professional_collaborations (from_user_id, to_user_id, collaboration_type, context_type, context_id) VALUES
((SELECT id FROM users WHERE email = 'project.lead@akerbp.com'),
 (SELECT id FROM users WHERE email = 'other.project.lead@akerbp.com'),
 'coordinates_with', 'general', NULL);

-- Digital Portfolio Manager consults with leadership
INSERT INTO professional_collaborations (from_user_id, to_user_id, collaboration_type, context_type, context_id) VALUES
((SELECT id FROM users WHERE email = 'jane.smith@akerbp.com'),
 (SELECT id FROM users WHERE email = 'lars.erik@akerbp.com'),
 'escalates_to', 'portfolio', 
 (SELECT id FROM yggdrasil_portfolios WHERE name = 'Integrated Operations'));
```
```sql
-- Opprett initiativ i porteføljen
INSERT INTO yggdrasil_initiatives (name, description, portfolio_id, initiative_type) VALUES
('Production Optimization Platform', 'Digital platform for production optimization', 
 (SELECT id FROM yggdrasil_portfolios WHERE name = 'Integrated Operations'), 'project');

-- Sett prosjektteam
INSERT INTO initiative_person_roles (initiative_id, user_id, role_definition_id, is_lead) VALUES
((SELECT id FROM yggdrasil_initiatives WHERE name = 'Production Optimization Platform'),
 (SELECT id FROM users WHERE email = 'project.owner@akerbp.com'),
 (SELECT id FROM role_definitions WHERE role_name = 'Project Owner'), TRUE);
```

### Initiativ med Fagteam
```sql
-- Create initiative in the portfolio
INSERT INTO yggdrasil_initiatives (name, description, portfolio_id, initiative_type) VALUES
('Production Optimization Platform', 'Digital platform for production optimization', 
 (SELECT id FROM yggdrasil_portfolios WHERE name = 'Integrated Operations'), 'project');

-- Set professional team (not hierarchical reporting)
INSERT INTO initiative_person_roles (initiative_id, user_id, role_definition_id, is_team_lead, responsibility_areas) VALUES
-- Project Owner (professional leader for business)
((SELECT id FROM yggdrasil_initiatives WHERE name = 'Production Optimization Platform'),
 (SELECT id FROM users WHERE email = 'project.owner@akerbp.com'),
 (SELECT id FROM role_definitions WHERE role_name = 'Project Owner'), TRUE, 'Business objectives, stakeholder management'),

-- Technical Project Lead (professional leader for technical)
((SELECT id FROM yggdrasil_initiatives WHERE name = 'Production Optimization Platform'),
 (SELECT id FROM users WHERE email = 'tech.lead@akerbp.com'),
 (SELECT id FROM role_definitions WHERE role_name = 'Technical Project Lead'), TRUE, 'Technical delivery, team coordination'),

-- Subject matter experts and specialists
((SELECT id FROM yggdrasil_initiatives WHERE name = 'Production Optimization Platform'),
 (SELECT id FROM users WHERE email = 'solution.architect@akerbp.com'),
 (SELECT id FROM role_definitions WHERE role_name = 'Solution Architect'), FALSE, 'Technical architecture, integration design'),

((SELECT id FROM yggdrasil_initiatives WHERE name = 'Production Optimization Platform'),
 (SELECT id FROM users WHERE email = 'business.analyst@akerbp.com'),
 (SELECT id FROM role_definitions WHERE role_name = 'Business Analyst'), FALSE, 'Requirements analysis, process mapping');
```
```sql
-- Koble applikasjon til initiativ og sett team
UPDATE applications SET 
    primary_initiative_id = (SELECT id FROM yggdrasil_initiatives WHERE name = 'Production Optimization Platform'),
    yggdrasil_portfolio_id = (SELECT id FROM yggdrasil_portfolios WHERE name = 'Integrated Operations')
WHERE id = 123;

-- Sett roller for applikasjonen
INSERT INTO application_person_roles (application_id, user_id, role_definition_id, is_primary) VALUES
(123, (SELECT id FROM users WHERE email = 'solution.architect@akerbp.com'), 
 (SELECT id FROM role_definitions WHERE role_name = 'Solution Architect'), TRUE),
(123, (SELECT id FROM users WHERE email = 'developer1@akerbp.com'),
 (SELECT id FROM role_definitions WHERE role_name = 'Developer'), FALSE),
(123, (SELECT id FROM users WHERE email = 'business.analyst@akerbp.com'),
 (SELECT id FROM role_definitions WHERE role_name = 'Business Analyst'), TRUE);
```

### Applikasjon med Fagroller
```sql
-- Connect application to initiative and professional portfolio
UPDATE applications SET 
    primary_initiative_id = (SELECT id FROM yggdrasil_initiatives WHERE name = 'Production Optimization Platform'),
    yggdrasil_portfolio_id = (SELECT id FROM yggdrasil_portfolios WHERE name = 'Integrated Operations')
WHERE id = 123;

-- Set professional roles for the application (based on work, not hierarchy)
INSERT INTO application_person_roles (application_id, user_id, role_definition_id, is_lead_role, collaboration_notes) VALUES
-- Solution architect leading technical design
(123, (SELECT id FROM users WHERE email = 'solution.architect@akerbp.com'), 
 (SELECT id FROM role_definitions WHERE role_name = 'Solution Architect'), TRUE, 
 'Leads technical design, coordinates with developers and business analyst'),

-- Developers implementing solution
(123, (SELECT id FROM users WHERE email = 'developer1@akerbp.com'),
 (SELECT id FROM role_definitions WHERE role_name = 'Developer'), FALSE,
 'Frontend development, works closely with solution architect'),

-- Business analyst ensuring business requirements
(123, (SELECT id FROM users WHERE email = 'business.analyst@akerbp.com'),
 (SELECT id FROM role_definitions WHERE role_name = 'Business Analyst'), TRUE,
 'Requirements management, user story definition'),

-- SME contributing domain knowledge
(123, (SELECT id FROM users WHERE email = 'operations.sme@akerbp.com'),
 (SELECT id FROM role_definitions WHERE role_name = 'Subject Matter Expert'), FALSE,
 'Production expertise, validates solutions against operational needs');
```

---

## 🔍 Funksjonsendringer i AppTrack
**Replace existing fields:**
```php
// Instead of free text fields:
// <input name="delivery_responsible" type="text">

// New structured approach:
?>
<div class="form-group-horizontal">
    <label for="primaryInitiative" class="form-label">Initiative/Project</label>
    <select class="form-select" id="primaryInitiative" name="primary_initiative_id" required>
        <option value="">Select Initiative/Project</option>
        <?php foreach ($initiatives as $initiative): ?>
            <option value="<?= $initiative['id'] ?>" 
                    data-portfolio="<?= $initiative['portfolio_name'] ?>">
                <?= htmlspecialchars($initiative['name']) ?> 
                (<?= htmlspecialchars($initiative['portfolio_name']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Team Assignment Section -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Project Team & Roles</h6>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPersonModal">
            <i class="bi bi-person-plus"></i> Add Team Member
        </button>
    </div>
    <div class="card-body">
        <div id="teamRolesContainer">
            <!-- Populated via JavaScript -->
        </div>
    </div>
</div>
<?php
```

### Dashboard Filters Enhancement
```php
// New filtering options in dashboard
$filters = [
    'show_mine_only' => [
        'name' => 'Show My Applications',
        'query' => 'WHERE apr.user_id = :user_id AND apr.end_date IS NULL'
    ],
    'my_portfolio' => [
        'name' => 'My Portfolio',
        'query' => 'WHERE yp.digital_portfolio_manager_id = :user_id OR yp.portfolio_owner_id = :user_id'
    ],
    'my_initiatives' => [
        'name' => 'My Initiatives', 
        'query' => 'WHERE yi.project_owner_id = :user_id OR yi.technical_lead_id = :user_id'
    ]
];
```

### Nye Views og Reports (Faglig Fokus)
```php
// Professional organization overview (not administrative)
public function getYggdrasilProfessionalStructure() {
    $sql = "
    SELECT 
        yp.name as portfolio_name,
        yp.description as portfolio_description,
        po.display_name as portfolio_owner,
        po.professional_title as owner_expertise,
        dpm.display_name as digital_portfolio_manager,
        dpm.professional_title as dpm_expertise,
        le.display_name as lead_expert,
        le.professional_title as expert_area,
        COUNT(DISTINCT yi.id) as active_initiatives,
        COUNT(DISTINCT a.id) as supported_applications,
        GROUP_CONCAT(DISTINCT ue.expertise_area) as portfolio_expertise_areas
    FROM yggdrasil_portfolios yp
    LEFT JOIN users po ON yp.portfolio_owner_id = po.id
    LEFT JOIN users dpm ON yp.digital_portfolio_manager_id = dpm.id  
    LEFT JOIN users le ON yp.lead_expert_id = le.id
    LEFT JOIN user_expertise ue ON (ue.user_id = po.id OR ue.user_id = dpm.id OR ue.user_id = le.id)
    LEFT JOIN yggdrasil_initiatives yi ON yp.id = yi.portfolio_id AND yi.is_active = 1
    LEFT JOIN applications a ON yp.id = a.yggdrasil_portfolio_id
    WHERE yp.is_active = 1
    GROUP BY yp.id
    ORDER BY yp.name";
}

// Professional resource allocation (who works on what)
public function getProfessionalResourceAllocation($portfolio_id = null) {
    $sql = "
    SELECT 
        u.display_name,
        u.professional_title,
        GROUP_CONCAT(DISTINCT ue.expertise_area) as expertise_areas,
        rd.role_name as current_role,
        yi.name as initiative_name,
        a.short_description as application_name,
        apr.allocation_percentage,
        apr.collaboration_notes,
        pc.collaboration_type,
        collaborator.display_name as collaborates_with
    FROM application_person_roles apr
    JOIN users u ON apr.user_id = u.id
    JOIN role_definitions rd ON apr.role_definition_id = rd.id
    JOIN applications a ON apr.application_id = a.id
    LEFT JOIN yggdrasil_initiatives yi ON apr.initiative_id = yi.id
    LEFT JOIN user_expertise ue ON u.id = ue.user_id AND ue.is_primary_expertise = 1
    LEFT JOIN professional_collaborations pc ON u.id = pc.from_user_id
    LEFT JOIN users collaborator ON pc.to_user_id = collaborator.id
    WHERE apr.end_date IS NULL OR apr.end_date > CURRENT_DATE";
    
    if ($portfolio_id) {
        $sql .= " AND a.yggdrasil_portfolio_id = :portfolio_id";
    }
    
    $sql .= " ORDER BY u.display_name, apr.start_date";
}

// Professional collaboration network
public function getProfessionalCollaborationNetwork($user_id = null) {
    $sql = "
    SELECT 
        from_user.display_name as person,
        from_user.professional_title as person_role,
        pc.collaboration_type,
        to_user.display_name as collaborates_with,
        to_user.professional_title as collaborator_role,
        pc.context_type,
        pc.relationship_strength,
        CASE 
            WHEN pc.context_type = 'initiative' THEN yi.name
            WHEN pc.context_type = 'portfolio' THEN yp.name
            WHEN pc.context_type = 'application' THEN a.short_description
            ELSE 'General'
        END as context_name
    FROM professional_collaborations pc
    JOIN users from_user ON pc.from_user_id = from_user.id
    JOIN users to_user ON pc.to_user_id = to_user.id
    LEFT JOIN yggdrasil_initiatives yi ON pc.context_type = 'initiative' AND pc.context_id = yi.id
    LEFT JOIN yggdrasil_portfolios yp ON pc.context_type = 'portfolio' AND pc.context_id = yp.id
    LEFT JOIN applications a ON pc.context_type = 'application' AND pc.context_id = a.id
    WHERE pc.is_active = 1";
    
    if ($user_id) {
        $sql .= " AND (pc.from_user_id = :user_id OR pc.to_user_id = :user_id)";
    }
    
    $sql .= " ORDER BY from_user.display_name, pc.relationship_strength DESC";
}
```

---

## 🎯 Migrering av Eksisterende Data

### Automatisk Person-Matching
```php
// Script for å matche eksisterende navn med brukere
class PersonMigration {
    
    public function migrateExistingPersons() {
        // 1. Ekstraher unike navn fra eksisterende felt
        $existingPersons = $this->extractPersonNames();
        
        // 2. Prøv å matche med eksisterende brukere
        foreach ($existingPersons as $name) {
            $user = $this->findOrCreateUser($name);
            $this->migratePersonAssignments($name, $user['id']);
        }
    }
    
    private function extractPersonNames() {
        $sql = "
        SELECT DISTINCT assigned_to as name, 'assigned_to' as role FROM applications WHERE assigned_to IS NOT NULL
        UNION SELECT DISTINCT project_manager, 'project_manager' FROM applications WHERE project_manager IS NOT NULL  
        UNION SELECT DISTINCT product_owner, 'product_owner' FROM applications WHERE product_owner IS NOT NULL
        UNION SELECT DISTINCT delivery_responsible, 'delivery_responsible' FROM applications WHERE delivery_responsible IS NOT NULL";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    private function findOrCreateUser($name) {
        // Prøv å finne eksisterende bruker
        $user = $this->findUserByName($name);
        
        if (!$user) {
            // Opprett ny bruker med estimert email
            $email = $this->generateEmail($name);
            $user = $this->createUser($name, $email);
        }
        
        return $user;
    }
}
```

### Portfolio Migration
```php
// Migrer eksisterende portefølje-data
public function migratePortfolios() {
    // Migrer preops_portfolio til yggdrasil_portfolios
    $preopsPortfolios = $this->getUniqueValues('preops_portfolio');
    foreach ($preopsPortfolios as $portfolio) {
        $this->createYggdrasilPortfolio($portfolio);
    }
    
    // Migrer application_portfolio til asm_portfolios  
    $asmPortfolios = $this->getUniqueValues('application_portfolio');
    foreach ($asmPortfolios as $portfolio) {
        $this->createAsmPortfolio($portfolio);
    }
}
```

---

## 📊 Performance Considerations

### Database Indexing
```sql
-- Kritiske indekser for performance
CREATE INDEX idx_app_person_roles_app_id ON application_person_roles(application_id);
CREATE INDEX idx_app_person_roles_user_id ON application_person_roles(user_id);  
CREATE INDEX idx_app_person_roles_role_id ON application_person_roles(role_definition_id);
CREATE INDEX idx_app_person_roles_active ON application_person_roles(end_date) WHERE end_date IS NULL;

CREATE INDEX idx_initiative_person_roles_init_id ON initiative_person_roles(initiative_id);
CREATE INDEX idx_initiative_person_roles_user_id ON initiative_person_roles(user_id);

CREATE INDEX idx_applications_initiative ON applications(primary_initiative_id);
CREATE INDEX idx_applications_yggdrasil_portfolio ON applications(yggdrasil_portfolio_id);
CREATE INDEX idx_applications_asm_portfolio ON applications(asm_portfolio_id);

CREATE INDEX idx_users_yggdrasil_member ON users(is_yggdrasil_member);
CREATE INDEX idx_users_manager ON users(manager_id);
```

### Query Optimization
```sql
-- Optimalisert query for "Mine applikasjoner"
SELECT DISTINCT a.*, yi.name as initiative_name, yp.name as portfolio_name
FROM applications a
LEFT JOIN application_person_roles apr ON a.id = apr.application_id 
LEFT JOIN yggdrasil_initiatives yi ON a.primary_initiative_id = yi.id
LEFT JOIN yggdrasil_portfolios yp ON a.yggdrasil_portfolio_id = yp.id
WHERE apr.user_id = ? 
AND (apr.end_date IS NULL OR apr.end_date > CURRENT_DATE)
ORDER BY a.updated_at DESC;

-- Optimalisert portfolio oversikt
SELECT 
    yp.*,
    COUNT(DISTINCT yi.id) as initiative_count,
    COUNT(DISTINCT a.id) as application_count,
    COUNT(DISTINCT ipr.user_id) as team_member_count
FROM yggdrasil_portfolios yp
LEFT JOIN yggdrasil_initiatives yi ON yp.id = yi.portfolio_id AND yi.is_active = 1
LEFT JOIN applications a ON yp.id = a.yggdrasil_portfolio_id  
LEFT JOIN initiative_person_roles ipr ON yi.id = ipr.initiative_id AND ipr.end_date IS NULL
WHERE yp.is_active = 1
GROUP BY yp.id;
```

---

## 🔐 Security & Access Control

### Rolle-basert Tilgang
```php
// Utvidet tilgangskontroll
class YggdrasilAccessControl {
    
    public function canViewPortfolio($user_id, $portfolio_id) {
        // Portfolio eiere, DPM og Lead kan se alt i sin portefølje
        $sql = "SELECT id FROM yggdrasil_portfolios 
                WHERE id = ? AND (portfolio_owner_id = ? OR digital_portfolio_manager_id = ? OR lead_expert_id = ?)";
        return $this->db->query($sql, [$portfolio_id, $user_id, $user_id, $user_id])->rowCount() > 0;
    }
    
    public function canEditApplication($user_id, $application_id) {
        // Kan redigere hvis har aktiv rolle på applikasjonen
        $sql = "SELECT id FROM application_person_roles 
                WHERE application_id = ? AND user_id = ? AND (end_date IS NULL OR end_date > CURRENT_DATE)";
        return $this->db->query($sql, [$application_id, $user_id])->rowCount() > 0;
    }
    
    public function canManageTeam($user_id, $initiative_id) {
        // Project Owner og Technical Lead kan administrere team
        $allowedRoles = ['Project Owner', 'Technical Project Lead', 'Digital Project Coordinator'];
        $sql = "SELECT ipr.id FROM initiative_person_roles ipr
                JOIN role_definitions rd ON ipr.role_definition_id = rd.id
                WHERE ipr.initiative_id = ? AND ipr.user_id = ? AND rd.role_name IN ('" . implode("','", $allowedRoles) . "')
                AND (ipr.end_date IS NULL OR ipr.end_date > CURRENT_DATE)";
        return $this->db->query($sql, [$initiative_id, $user_id])->rowCount() > 0;
    }
}
```

### Audit Logging Enhancement
```sql
-- Utvid audit_log for organisasjonsendringer
ALTER TABLE audit_log ADD COLUMN portfolio_id INT NULL;
ALTER TABLE audit_log ADD COLUMN initiative_id INT NULL;
ALTER TABLE audit_log ADD COLUMN role_assignment_id INT NULL;

-- Log alle rolle-endringer
CREATE TRIGGER audit_role_changes 
AFTER INSERT ON application_person_roles
FOR EACH ROW
INSERT INTO audit_log (table_name, record_id, action, changed_by, portfolio_id, initiative_id, role_assignment_id)
VALUES ('application_person_roles', NEW.id, 'ROLE_ASSIGNED', NEW.created_by, 
        (SELECT yggdrasil_portfolio_id FROM applications WHERE id = NEW.application_id),
        NEW.initiative_id, NEW.id);
```

---

## 📈 Metrics & Analytics

### Organisasjons KPIer
```sql
-- Portfolio Performance Metrics
CREATE VIEW portfolio_metrics AS
SELECT 
    yp.name as portfolio_name,
    COUNT(DISTINCT yi.id) as active_initiatives,
    COUNT(DISTINCT a.id) as total_applications,
    COUNT(DISTINCT CASE WHEN a.phase = 'Operate' THEN a.id END) as operational_apps,
    COUNT(DISTINCT apr.user_id) as unique_team_members,
    AVG(apr.allocation_percentage) as avg_allocation,
    COUNT(DISTINCT CASE WHEN yi.status = 'completed' THEN yi.id END) as completed_initiatives
FROM yggdrasil_portfolios yp
LEFT JOIN yggdrasil_initiatives yi ON yp.id = yi.portfolio_id
LEFT JOIN applications a ON yp.id = a.yggdrasil_portfolio_id
LEFT JOIN application_person_roles apr ON a.id = apr.application_id AND apr.end_date IS NULL
WHERE yp.is_active = 1
GROUP BY yp.id;

-- Resource Utilization
CREATE VIEW resource_utilization AS  
SELECT
    u.display_name,
    u.title,
    COUNT(DISTINCT apr.application_id) as assigned_applications,
    COUNT(DISTINCT ipr.initiative_id) as assigned_initiatives,
    AVG(apr.allocation_percentage) as avg_allocation,
    GROUP_CONCAT(DISTINCT rd.role_name) as roles
FROM users u
LEFT JOIN application_person_roles apr ON u.id = apr.user_id AND apr.end_date IS NULL
LEFT JOIN initiative_person_roles ipr ON u.id = ipr.user_id AND ipr.end_date IS NULL  
LEFT JOIN role_definitions rd ON apr.role_definition_id = rd.id
WHERE u.is_yggdrasil_member = 1
GROUP BY u.id;
```

---

## 🚀 Deployment Strategy

### Stegvis Implementering

**Uke 1-2: Database Setup**
- Opprett nye tabeller
- Populer referansedata
- Sett opp indekser

**Uke 3-4: Backend API**
- Nye API-endepunkter
- Data migration scripts
- Backend services

**Uke 5-6: Frontend Integration**  
- Oppdater app forms
- Nye UI-komponenter
- Dashboard-endringer

**Uke 7-8: Testing & Rollout**
- Bruker-testing
- Performance-tuning
- Produksjonsrigging

### Rollback Plan
```sql
-- Emergency rollback procedure
-- 1. Gjenopprett gamle felt
UPDATE applications SET 
    assigned_to = legacy_assigned_to,
    project_manager = legacy_project_manager,
    product_owner = legacy_product_owner,
    delivery_responsible = legacy_delivery_responsible;

-- 2. Deaktiver nye tabeller
ALTER TABLE applications DROP FOREIGN KEY FK_app_initiative;
ALTER TABLE applications DROP FOREIGN KEY FK_app_yggdrasil_portfolio; 
ALTER TABLE applications DROP FOREIGN KEY FK_app_asm_portfolio;
```

---

## 📋 Testing Strategy

### Unit Tests
```php
// Test person-rolle assignments
class PersonRoleTest extends PHPUnit\Framework\TestCase {
    
    public function testAssignPersonToApplication() {
        $assignment = new ApplicationPersonRole();
        $result = $assignment->assign($app_id, $user_id, $role_id);
        $this->assertTrue($result);
    }
    
    public function testPreventDuplicateRoleAssignment() {
        // Test at samme person ikke kan ha samme rolle samtidig
    }
    
    public function testRoleEndDateEnforcement() {
        // Test at roller med end_date ikke vises som aktive
    }
}
```

### Integration Tests  
```php
// Test full workflow
public function testCompleteProjectSetup() {
    // 1. Opprett portfolio
    // 2. Opprett initiativ  
    // 3. Opprett applikasjon
    // 4. Sett team-roller
    // 5. Verifiser alle relasjoner
}
```

### Performance Tests
- Load testing på nye queries
- Index performance validation
- Dashboard loading time metrics

---

## 📞 Neste Steg

### Umiddelbare Handlinger
1. **Godkjenning av plan**: Review og signoff på designet
2. **Database schema review**: Teknisk gjennomgang av design
3. **Resource allocation**: Tildel utviklerressurser  
4. **Timeline finalisering**: Fastsett konkrete datoer

### Avhengigheter
- **Brukerliste fra Aker BP**: Eksisterende ansattliste for matching
- **Organisasjonskart**: Oppdatert Yggdrasil-struktur
- **Rollklargjøring**: Endelig definisjon av alle roller

### Risikoer
- **Data migration kompleksitet**: Matching av eksisterende navn
- **User adoption**: Endring fra fritekst til struktur  
- **Performance impact**: Flere tabeller og relasjoner
- **Training requirements**: Nye arbeidsflyter

---

**Dokument opprettet**: 5. august 2025  
**Neste review**: Etter stakeholder-godkjenning  
**Eier**: AppTrack Development Team  
**Godkjent av**: [Pending approval]
