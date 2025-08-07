# Multi-Architecture DataMap Implementation Plan v1.0

**Dato**: August 7, 2025  
**Status**: Planlegging  
**Prioritet**: Høy  
**Estimat**: 8 uker

---

## 📖 **Executive Summary**

Dette dokumentet beskriver en omfattende utvidelse av AppTrack's DataMap-funksjonalitet for å støtte forskjellige typer arkitekturdiagrammer gjennom et fane-basert system. Løsningen adresserer behovene til forskjellige arkitektroller (løsningsarkitekter, informasjonsarkitekter, enterprisearkitekter, og dataarkitekter) ved å separere arkitekturlag i spesialiserte visninger.

### **Målgrupper**
- **Løsningsarkitekter**: Applikasjoner, tjenester, og systemintegrasjoner
- **Informasjonsarkitekter**: Forretningsprosesser og informasjonsflyt
- **Enterprisearkitekter**: Helhetlig oversikt på tvers av arkitekturlag
- **Dataarkitekter**: Datamodeller, databaser, og dataflyt
- **Infrastrukturarkitekter**: Servere, nettverk, og teknisk infrastruktur

---

## 🎯 **Problemstilling og Motivasjon**

### **Nåværende Begrensninger**
1. **Single View Problem**: Kun ett diagram per applikasjon blander forskjellige arkitekturlag
2. **Node Type Confusion**: Samme node-typer brukes for ulike arkitekturformål
3. **Mixed Context**: Business prosesser og teknisk infrastruktur i samme diagram
4. **Analysis Complexity**: AI må tolke blandede arkitekturtyper samtidig
5. **User Experience**: Arkitekter må navigere irrelevante elementer for sitt fagområde

### **Forretningsmessige Gevinster**
- ✅ **Redusert Kompleksitet**: Klarere separasjon av arkitekturkonsepter
- ✅ **Forbedret Produktivitet**: Arkitekter fokuserer på relevante diagramtyper
- ✅ **Bedre AI-Analyser**: Mer presise insights når arkitekturlag er separert
- ✅ **Skalerbarhet**: Enkelt å legge til nye arkitekturtyper i fremtiden
- ✅ **Compliance**: Bedre støtte for enterprise governance og standarder

---

## 🏗️ **Teknisk Arkitektur**

### **Overordnet Design Patterns**
- **Multi-Tab Interface**: Bootstrap tabs for arkitekturtypenavigasjon
- **Dynamic Content Loading**: Asynkron lasting av arkitektur-spesifikke elementer
- **Modular Database Design**: Normalisert struktur med foreign key relationships
- **Backward Compatibility**: Eksisterende diagrammer migreres uten datatap
- **API Versioning**: Nye endpoints med fallback til eksisterende funksjonalitet

### **Arkitekturtyper (Hovedkategorier)**

#### 1. **Process Architecture** 🔄
- **Formål**: Forretningsprosesser, workflows, og beslutningsflyt
- **Node Types**: Start, Task, Decision, Gateway, End, Subprocess
- **Målgruppe**: Business analysts, informasjonsarkitekter
- **AI Focus**: Prosessoptimalisering, bottleneck detection

#### 2. **Solution Architecture** 🏗️
- **Formål**: Applikasjoner, microservices, og løsningskomponenter
- **Node Types**: Application, Service, Component, Interface, API
- **Målgruppe**: Løsningsarkitekter, systemarkitekter
- **AI Focus**: Dependency analysis, scalability assessment

#### 3. **Infrastructure Architecture** 🔧
- **Formål**: Servere, nettverk, hardware, og driftsplattformer
- **Node Types**: Server, Network, LoadBalancer, Firewall, Storage
- **Målgruppe**: Infrastrukturarkitekter, driftsteam
- **AI Focus**: Capacity planning, security analysis

#### 4. **Data Architecture** 📊
- **Formål**: Databaser, datamodeller, og informasjonsflyt
- **Node Types**: Database, DataWarehouse, Queue, Cache, DataLake
- **Målgruppe**: Dataarkitekter, database administrators
- **AI Focus**: Data governance, performance optimization

#### 5. **Integration Architecture** 🔌
- **Formål**: API-er, meldingssystemer, og systemintegrasjoner
- **Node Types**: API Gateway, Message Queue, ETL, Webhook, Adapter
- **Målgruppe**: Integrasjonsarkitekter, API-utviklere
- **AI Focus**: Integration patterns, data consistency

---

## 🗄️ **Database Design**

### **Ny Datamodell**

```sql
-- Architecture Types Master Data
CREATE TABLE architecture_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,           -- 'process', 'solution', etc.
    display_name VARCHAR(100) NOT NULL,         -- 'Process Architecture'
    description TEXT,                           -- Detailed description
    icon_class VARCHAR(50),                     -- 'fas fa-project-diagram'
    color_scheme VARCHAR(20),                   -- 'blue', 'green', etc.
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active_sort (is_active, sort_order)
);

-- Application-specific diagrams per architecture type
CREATE TABLE application_diagrams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    architecture_type_id INT NOT NULL,
    diagram_data JSON,                          -- DrawFlow diagram JSON
    diagram_notes TEXT,                         -- Architecture-specific notes
    version INT DEFAULT 1,                      -- Version tracking
    is_current BOOLEAN DEFAULT TRUE,            -- Current version flag
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (architecture_type_id) REFERENCES architecture_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id),
    
    UNIQUE KEY unique_app_arch_current (application_id, architecture_type_id, is_current),
    INDEX idx_app_arch (application_id, architecture_type_id),
    INDEX idx_current_version (is_current, updated_at)
);

-- Enhanced Node Templates with Architecture Context
ALTER TABLE node_templates 
ADD COLUMN architecture_type_id INT,
ADD COLUMN category VARCHAR(50),                -- 'process', 'storage', etc.
ADD COLUMN is_connector BOOLEAN DEFAULT FALSE,  -- Special connector nodes
ADD COLUMN validation_rules JSON,               -- Node validation rules
ADD FOREIGN KEY (architecture_type_id) REFERENCES architecture_types(id);

-- Diagram Relationships (Cross-Architecture Links)
CREATE TABLE diagram_relationships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    source_diagram_id INT NOT NULL,
    target_diagram_id INT NOT NULL,
    source_node_id VARCHAR(50),                 -- Node ID in source diagram
    target_node_id VARCHAR(50),                 -- Node ID in target diagram
    relationship_type VARCHAR(50),              -- 'implements', 'depends_on', etc.
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    
    FOREIGN KEY (source_diagram_id) REFERENCES application_diagrams(id) ON DELETE CASCADE,
    FOREIGN KEY (target_diagram_id) REFERENCES application_diagrams(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    
    INDEX idx_source_target (source_diagram_id, target_diagram_id)
);
```

### **Initialisering av Master Data**

```sql
-- Populate architecture types
INSERT INTO architecture_types (name, display_name, description, icon_class, color_scheme, sort_order) VALUES
('process', 'Process Architecture', 'Business processes, workflows, and decision flows', 'fas fa-project-diagram', 'blue', 1),
('solution', 'Solution Architecture', 'Applications, services, and solution components', 'fas fa-cubes', 'green', 2),
('infrastructure', 'Infrastructure', 'Servers, networks, hardware, and operational platforms', 'fas fa-server', 'orange', 3),
('data', 'Data Architecture', 'Databases, data models, and information flow', 'fas fa-database', 'purple', 4),
('integration', 'Integration Architecture', 'APIs, message systems, and system integrations', 'fas fa-plug', 'red', 5);

-- Create architecture-specific node templates
INSERT INTO node_templates (architecture_type_id, type, display_name, icon_class, default_inputs, default_outputs, css_class, category) VALUES
-- Process Architecture Nodes
(1, 'start_event', 'Start Event', 'fas fa-play-circle', 0, 1, 'node-process-start', 'event'),
(1, 'task', 'Task', 'fas fa-tasks', 1, 1, 'node-process-task', 'activity'),
(1, 'decision', 'Decision', 'fas fa-question-circle', 1, 2, 'node-process-decision', 'gateway'),
(1, 'end_event', 'End Event', 'fas fa-stop-circle', 1, 0, 'node-process-end', 'event'),

-- Solution Architecture Nodes  
(2, 'application', 'Application', 'fas fa-desktop', 1, 2, 'node-solution-app', 'component'),
(2, 'microservice', 'Microservice', 'fas fa-cubes', 2, 2, 'node-solution-service', 'component'),
(2, 'api_gateway', 'API Gateway', 'fas fa-door-open', 3, 3, 'node-solution-gateway', 'interface'),

-- Infrastructure Nodes
(3, 'server', 'Server', 'fas fa-server', 2, 2, 'node-infra-server', 'compute'),
(3, 'load_balancer', 'Load Balancer', 'fas fa-balance-scale', 1, 3, 'node-infra-lb', 'network'),
(3, 'firewall', 'Firewall', 'fas fa-shield-alt', 2, 2, 'node-infra-firewall', 'security'),

-- Data Architecture Nodes
(4, 'database', 'Database', 'fas fa-database', 2, 2, 'node-data-db', 'storage'),
(4, 'data_warehouse', 'Data Warehouse', 'fas fa-warehouse', 3, 1, 'node-data-warehouse', 'storage'),
(4, 'message_queue', 'Message Queue', 'fas fa-list', 2, 2, 'node-data-queue', 'messaging'),

-- Integration Nodes
(5, 'rest_api', 'REST API', 'fas fa-plug', 1, 1, 'node-integration-api', 'interface'),
(5, 'webhook', 'Webhook', 'fas fa-share', 1, 1, 'node-integration-webhook', 'interface'),
(5, 'etl_process', 'ETL Process', 'fas fa-sync-alt', 2, 1, 'node-integration-etl', 'processor');
```

### **Data Migration Strategy**

```sql
-- Migrate existing diagrams to solution architecture (default)
INSERT INTO application_diagrams (application_id, architecture_type_id, diagram_data, diagram_notes, created_at, created_by)
SELECT 
    id, 
    2, -- solution architecture as default
    drawflow_diagram, 
    drawflow_notes, 
    COALESCE(updated_at, created_at),
    1 -- default user, update as needed
FROM applications 
WHERE drawflow_diagram IS NOT NULL 
  AND JSON_VALID(drawflow_diagram);

-- Update existing node templates for solution architecture
UPDATE node_templates 
SET architecture_type_id = 2 
WHERE architecture_type_id IS NULL;

-- Archive original fields (keep for rollback safety)
ALTER TABLE applications 
ADD COLUMN drawflow_diagram_backup JSON,
ADD COLUMN drawflow_notes_backup TEXT;

UPDATE applications 
SET drawflow_diagram_backup = drawflow_diagram,
    drawflow_notes_backup = drawflow_notes;
```

---

## 🎨 **Frontend Implementation**

### **User Interface Design**

#### **Tab Navigation Structure**
```html
<!-- Multi-Architecture Tab Interface -->
<div class="architecture-container">
    <div class="architecture-header">
        <ul class="nav nav-tabs architecture-tabs" id="architectureTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="solution-tab" data-bs-toggle="tab" 
                        data-bs-target="#solution-panel" data-arch-type="solution" 
                        type="button" role="tab">
                    <i class="fas fa-cubes text-success"></i>
                    <span>Solution</span>
                    <span class="badge bg-secondary ms-1" id="solution-count">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="process-tab" data-bs-toggle="tab" 
                        data-bs-target="#process-panel" data-arch-type="process" 
                        type="button" role="tab">
                    <i class="fas fa-project-diagram text-primary"></i>
                    <span>Process</span>
                    <span class="badge bg-secondary ms-1" id="process-count">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="infrastructure-tab" data-bs-toggle="tab" 
                        data-bs-target="#infrastructure-panel" data-arch-type="infrastructure" 
                        type="button" role="tab">
                    <i class="fas fa-server text-warning"></i>
                    <span>Infrastructure</span>
                    <span class="badge bg-secondary ms-1" id="infrastructure-count">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="data-tab" data-bs-toggle="tab" 
                        data-bs-target="#data-panel" data-arch-type="data" 
                        type="button" role="tab">
                    <i class="fas fa-database text-info"></i>
                    <span>Data</span>
                    <span class="badge bg-secondary ms-1" id="data-count">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="integration-tab" data-bs-toggle="tab" 
                        data-bs-target="#integration-panel" data-arch-type="integration" 
                        type="button" role="tab">
                    <i class="fas fa-plug text-danger"></i>
                    <span>Integration</span>
                    <span class="badge bg-secondary ms-1" id="integration-count">0</span>
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Content Areas -->
    <div class="tab-content architecture-content" id="architectureTabContent">
        <div class="tab-pane fade show active" id="solution-panel" role="tabpanel">
            <!-- Solution Architecture Editor -->
            <div class="datamap-editor-container">
                <div class="sidebar architecture-sidebar" id="solution-sidebar">
                    <!-- Solution-specific nodes will be loaded here -->
                </div>
                <div class="main-editor">
                    <div class="editor-toolbar">
                        <!-- Architecture-specific toolbar buttons -->
                    </div>
                    <div class="drawflow-container" id="solution-drawflow">
                        <!-- DrawFlow editor for solution architecture -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Similar structure for other architecture types -->
    </div>
</div>
```

#### **Dynamic Sidebar Loading**
```javascript
class ArchitectureManager {
    constructor(applicationId) {
        this.applicationId = applicationId;
        this.currentArchitecture = 'solution';
        this.editors = {}; // Store multiple DrawFlow instances
        this.nodeTemplates = {};
        this.unsavedChanges = {};
    }

    async init() {
        // Load architecture types from database
        await this.loadArchitectureTypes();
        
        // Initialize tab event handlers
        this.setupTabHandlers();
        
        // Load default architecture (solution)
        await this.switchArchitecture('solution');
    }

    async loadArchitectureTypes() {
        try {
            const response = await fetch('/public/api/get_architecture_types.php');
            const data = await response.json();
            if (data.success) {
                this.architectureTypes = data.types;
                console.log('✅ Loaded architecture types:', this.architectureTypes);
            }
        } catch (error) {
            console.error('❌ Failed to load architecture types:', error);
        }
    }

    async switchArchitecture(archType) {
        console.log(`🔄 Switching to ${archType} architecture`);
        
        // Save current diagram if there are unsaved changes
        if (this.unsavedChanges[this.currentArchitecture]) {
            await this.saveDiagram(this.currentArchitecture);
        }
        
        // Load architecture-specific node templates
        await this.loadNodeTemplates(archType);
        
        // Initialize or switch to architecture-specific editor
        await this.initializeEditor(archType);
        
        // Load existing diagram for this architecture
        await this.loadDiagram(archType);
        
        // Update sidebar with relevant nodes
        this.updateSidebar(archType);
        
        // Update current architecture
        this.currentArchitecture = archType;
        
        // Update UI state
        this.updateUI(archType);
    }

    async loadNodeTemplates(archType) {
        try {
            const response = await fetch(`/public/api/get_node_templates.php?architecture_type=${archType}`);
            const data = await response.json();
            if (data.success) {
                this.nodeTemplates[archType] = data.templates;
                console.log(`✅ Loaded ${data.templates.length} node templates for ${archType}`);
            }
        } catch (error) {
            console.error(`❌ Failed to load ${archType} node templates:`, error);
        }
    }

    async loadDiagram(archType) {
        try {
            const response = await fetch(`/public/api/load_diagram.php?application_id=${this.applicationId}&architecture_type=${archType}`);
            const data = await response.json();
            
            if (data.success && data.diagram_data) {
                this.editors[archType].import(data.diagram_data);
                console.log(`📂 Loaded ${archType} diagram`);
                
                // Update node count badge
                const nodeCount = Object.keys(data.diagram_data.drawflow?.Home?.data || {}).length;
                this.updateNodeCountBadge(archType, nodeCount);
            } else {
                // No existing diagram - start fresh
                this.editors[archType].clear();
                this.updateNodeCountBadge(archType, 0);
                console.log(`📂 No existing ${archType} diagram - starting fresh`);
            }
        } catch (error) {
            console.error(`❌ Failed to load ${archType} diagram:`, error);
        }
    }

    async saveDiagram(archType) {
        if (!this.editors[archType]) return;
        
        try {
            const diagramData = this.editors[archType].export();
            const notes = this.getDiagramNotes(archType);
            
            const response = await fetch('/public/api/save_diagram.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    application_id: this.applicationId,
                    architecture_type: archType,
                    diagram_data: diagramData,
                    notes: notes
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.unsavedChanges[archType] = false;
                console.log(`💾 Saved ${archType} diagram`);
                this.showSaveStatus('success', `${archType} diagram saved`);
            }
        } catch (error) {
            console.error(`❌ Failed to save ${archType} diagram:`, error);
            this.showSaveStatus('error', `Failed to save ${archType} diagram`);
        }
    }
}
```

---

## 🔧 **API Design**

### **Nye API Endpoints**

#### **1. Architecture Types Management**
```php
// /public/api/get_architecture_types.php
<?php
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT id, name, display_name, description, icon_class, color_scheme, sort_order
        FROM architecture_types 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'types' => $types
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

#### **2. Enhanced Node Templates**
```php
// /public/api/get_node_templates.php (Enhanced)
<?php
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

$architecture_type = $_GET['architecture_type'] ?? null;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $sql = "
        SELECT nt.*, at.name as architecture_name, at.color_scheme
        FROM node_templates nt
        LEFT JOIN architecture_types at ON nt.architecture_type_id = at.id
        WHERE nt.is_active = 1
    ";
    
    $params = [];
    if ($architecture_type) {
        $sql .= " AND at.name = ?";
        $params[] = $architecture_type;
    }
    
    $sql .= " ORDER BY nt.category, nt.sort_order ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'templates' => $templates,
        'architecture_type' => $architecture_type
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

#### **3. Diagram Management**
```php
// /public/api/load_diagram.php
<?php
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

$application_id = intval($_GET['application_id'] ?? 0);
$architecture_type = $_GET['architecture_type'] ?? null;

if (!$application_id || !$architecture_type) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT ad.diagram_data, ad.diagram_notes, ad.version, ad.updated_at
        FROM application_diagrams ad
        JOIN architecture_types at ON ad.architecture_type_id = at.id
        WHERE ad.application_id = ? AND at.name = ? AND ad.is_current = 1
    ");
    $stmt->execute([$application_id, $architecture_type]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'diagram_data' => $result['diagram_data'] ? json_decode($result['diagram_data'], true) : null,
            'notes' => $result['diagram_notes'],
            'version' => $result['version'],
            'last_updated' => $result['updated_at']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'diagram_data' => null,
            'message' => 'No diagram found for this architecture type'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

// /public/api/save_diagram.php (Enhanced)
<?php
require_once __DIR__ . '/../../src/db/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$application_id = intval($input['application_id'] ?? 0);
$architecture_type = $input['architecture_type'] ?? null;
$diagram_data = $input['diagram_data'] ?? null;
$notes = $input['notes'] ?? '';

if (!$application_id || !$architecture_type || !$diagram_data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get architecture type ID
    $stmt = $pdo->prepare("SELECT id FROM architecture_types WHERE name = ?");
    $stmt->execute([$architecture_type]);
    $arch_type_row = $stmt->fetch();
    
    if (!$arch_type_row) {
        throw new Exception("Invalid architecture type: $architecture_type");
    }
    
    $architecture_type_id = $arch_type_row['id'];
    $diagram_json = json_encode($diagram_data);
    $user_id = $_SESSION['user_id'] ?? 1; // Default user for now
    
    // Use INSERT ... ON DUPLICATE KEY UPDATE for upsert functionality
    $stmt = $pdo->prepare("
        INSERT INTO application_diagrams 
        (application_id, architecture_type_id, diagram_data, diagram_notes, created_by, updated_by) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        diagram_data = VALUES(diagram_data),
        diagram_notes = VALUES(diagram_notes),
        updated_by = VALUES(updated_by),
        updated_at = CURRENT_TIMESTAMP,
        version = version + 1
    ");
    
    $result = $stmt->execute([
        $application_id, 
        $architecture_type_id, 
        $diagram_json, 
        $notes, 
        $user_id, 
        $user_id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Diagram saved successfully',
            'architecture_type' => $architecture_type
        ]);
    } else {
        throw new Exception('Failed to save diagram');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

---

## 🤖 **AI Integration Enhancements**

### **Enhanced DataAggregator for Multi-Architecture**

```php
// Enhanced DataAggregator.php methods
public function getDataMapDiagrams($application_id) {
    try {
        $sql = "
            SELECT at.name, at.display_name, ad.diagram_data, ad.diagram_notes, ad.version
            FROM application_diagrams ad 
            JOIN architecture_types at ON ad.architecture_type_id = at.id
            WHERE ad.application_id = ? AND ad.is_current = 1 AND at.is_active = 1
            ORDER BY at.sort_order
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$application_id]);
        $diagrams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $structured_diagrams = [];
        foreach ($diagrams as $diagram) {
            if ($diagram['diagram_data']) {
                $diagram_data = json_decode($diagram['diagram_data'], true);
                $structured_diagrams[$diagram['name']] = [
                    'display_name' => $diagram['display_name'],
                    'raw_data' => $diagram_data,
                    'notes' => $diagram['diagram_notes'],
                    'version' => $diagram['version'],
                    'analysis' => $this->analyzeArchitectureDiagram($diagram['name'], $diagram_data)
                ];
            }
        }
        
        return [
            'has_diagrams' => !empty($structured_diagrams),
            'diagrams' => $structured_diagrams,
            'cross_architecture_analysis' => $this->analyzeCrossArchitecture($structured_diagrams)
        ];
        
    } catch (Exception $e) {
        error_log("DataAggregator getDataMapDiagrams error: " . $e->getMessage());
        return [
            'has_diagrams' => false,
            'error' => $e->getMessage()
        ];
    }
}

private function analyzeArchitectureDiagram($arch_type, $diagram_data) {
    switch ($arch_type) {
        case 'process':
            return $this->analyzeProcessDiagram($diagram_data);
        case 'solution':
            return $this->analyzeSolutionDiagram($diagram_data);
        case 'infrastructure':
            return $this->analyzeInfrastructureDiagram($diagram_data);
        case 'data':
            return $this->analyzeDataDiagram($diagram_data);
        case 'integration':
            return $this->analyzeIntegrationDiagram($diagram_data);
        default:
            return $this->analyzeGenericDiagram($diagram_data);
    }
}

private function analyzeProcessDiagram($diagram_data) {
    $nodes = $diagram_data['drawflow']['Home']['data'] ?? [];
    
    $analysis = [
        'node_count' => count($nodes),
        'start_events' => 0,
        'end_events' => 0,
        'decision_points' => 0,
        'process_complexity' => 'Low',
        'potential_bottlenecks' => []
    ];
    
    foreach ($nodes as $node) {
        $class = $node['class'] ?? '';
        if (strpos($class, 'start') !== false) $analysis['start_events']++;
        if (strpos($class, 'end') !== false) $analysis['end_events']++;
        if (strpos($class, 'decision') !== false) $analysis['decision_points']++;
    }
    
    // Determine complexity
    if ($analysis['node_count'] > 20 || $analysis['decision_points'] > 5) {
        $analysis['process_complexity'] = 'High';
    } elseif ($analysis['node_count'] > 10 || $analysis['decision_points'] > 2) {
        $analysis['process_complexity'] = 'Medium';
    }
    
    return $analysis;
}

private function analyzeCrossArchitecture($diagrams) {
    $analysis = [
        'completeness_score' => 0,
        'architecture_coverage' => [],
        'missing_architectures' => [],
        'consistency_issues' => [],
        'recommendations' => []
    ];
    
    $expected_architectures = ['process', 'solution', 'infrastructure', 'data', 'integration'];
    $present_architectures = array_keys($diagrams);
    
    $analysis['architecture_coverage'] = $present_architectures;
    $analysis['missing_architectures'] = array_diff($expected_architectures, $present_architectures);
    $analysis['completeness_score'] = (count($present_architectures) / count($expected_architectures)) * 100;
    
    // Generate recommendations based on missing architectures
    foreach ($analysis['missing_architectures'] as $missing) {
        switch ($missing) {
            case 'process':
                $analysis['recommendations'][] = "Consider adding process architecture to document business workflows";
                break;
            case 'infrastructure':
                $analysis['recommendations'][] = "Infrastructure diagram would help with deployment planning";
                break;
            case 'data':
                $analysis['recommendations'][] = "Data architecture diagram recommended for data governance";
                break;
        }
    }
    
    return $analysis;
}
```

### **Architecture-Specific AI Prompts**

```php
// Enhanced AI prompt templates
private function getArchitectureSpecificPrompts() {
    return [
        'process' => [
            'analysis_prompt' => "Analyze this business process diagram focusing on:\n" .
                              "- Process efficiency and bottlenecks\n" .
                              "- Decision point complexity\n" .
                              "- Process flow optimization opportunities\n" .
                              "- Compliance and governance considerations\n" .
                              "- Automation potential",
            'recommendations_prompt' => "Provide process improvement recommendations for:\n" .
                                      "- Workflow optimization\n" .
                                      "- Bottleneck removal\n" .
                                      "- Process automation opportunities\n" .
                                      "- Quality control points"
        ],
        
        'solution' => [
            'analysis_prompt' => "Analyze this solution architecture focusing on:\n" .
                              "- Component dependencies and coupling\n" .
                              "- Scalability and performance patterns\n" .
                              "- Security considerations\n" .
                              "- Microservices design principles\n" .
                              "- Integration complexity",
            'recommendations_prompt' => "Provide solution architecture recommendations for:\n" .
                                      "- Decoupling strategies\n" .
                                      "- Performance optimization\n" .
                                      "- Security hardening\n" .
                                      "- Scalability improvements"
        ],
        
        'infrastructure' => [
            'analysis_prompt' => "Analyze this infrastructure architecture focusing on:\n" .
                              "- Resource capacity and utilization\n" .
                              "- High availability and disaster recovery\n" .
                              "- Security zones and network segmentation\n" .
                              "- Monitoring and observability\n" .
                              "- Cost optimization opportunities",
            'recommendations_prompt' => "Provide infrastructure recommendations for:\n" .
                                      "- Capacity planning\n" .
                                      "- Reliability improvements\n" .
                                      "- Security enhancements\n" .
                                      "- Cost optimization"
        ],
        
        'data' => [
            'analysis_prompt' => "Analyze this data architecture focusing on:\n" .
                              "- Data flow patterns and bottlenecks\n" .
                              "- Data quality and governance\n" .
                              "- Storage optimization\n" .
                              "- Privacy and compliance\n" .
                              "- Backup and recovery strategies",
            'recommendations_prompt' => "Provide data architecture recommendations for:\n" .
                                      "- Data pipeline optimization\n" .
                                      "- Storage efficiency\n" .
                                      "- Data governance implementation\n" .
                                      "- Performance improvements"
        ],
        
        'integration' => [
            'analysis_prompt' => "Analyze this integration architecture focusing on:\n" .
                              "- API design and consistency\n" .
                              "- Message flow patterns\n" .
                              "- Error handling and resilience\n" .
                              "- Security and authentication\n" .
                              "- Performance and throughput",
            'recommendations_prompt' => "Provide integration recommendations for:\n" .
                                      "- API optimization\n" .
                                      "- Resilience patterns\n" .
                                      "- Security improvements\n" .
                                      "- Performance tuning"
        ]
    ];
}
```

---

## 📋 **Implementation Roadmap**

### **Fase 1: Grunnleggende Infrastruktur (Uke 1-2)**

#### **Database Setup**
- [ ] **Dag 1-2**: Opprett nye tabeller (`architecture_types`, `application_diagrams`, etc.)
- [ ] **Dag 3-4**: Implementer data migration scripts
- [ ] **Dag 5-6**: Utvid `node_templates` med arkitektur-kontekst
- [ ] **Dag 7-8**: Test database schema og migration
- [ ] **Dag 9-10**: Opprett initial master data og node templates

#### **Backend API Development**
- [ ] **Dag 1-3**: Implementer `get_architecture_types.php`
- [ ] **Dag 4-6**: Oppdater `get_node_templates.php` med arkitektur-filter
- [ ] **Dag 7-9**: Implementer `load_diagram.php` og `save_diagram.php`
- [ ] **Dag 10**: API testing og dokumentasjon

### **Fase 2: Frontend Core Architecture (Uke 3-4)**

#### **Tab System Implementation**
- [ ] **Dag 1-3**: Bootstrap tabs struktur i `datamap.php`
- [ ] **Dag 4-6**: JavaScript `ArchitectureManager` klasse
- [ ] **Dag 7-9**: Implementer arkitektur-switching logikk
- [ ] **Dag 10-12**: Dynamic sidebar loading per arkitekturtype
- [ ] **Dag 13-14**: Auto-save per arkitektur med conflict handling

#### **DrawFlow Integration**
- [ ] **Dag 1-2**: Multiple DrawFlow instance management
- [ ] **Dag 3-4**: Architecture-specific editor initialization  
- [ ] **Dag 5-6**: Enhanced connection management
- [ ] **Dag 7**: Testing og debugging

### **Fase 3: Node Specialization (Uke 5-6)**

#### **Process Architecture Nodes**
- [ ] **Dag 1-2**: Start/End event nodes
- [ ] **Dag 3-4**: Task og decision nodes
- [ ] **Dag 5-6**: Gateway og subprocess nodes
- [ ] **Dag 7**: Process-specific CSS styling

#### **Infrastructure & Data Nodes**
- [ ] **Dag 1-3**: Server, network, security nodes (Infrastructure)
- [ ] **Dag 4-6**: Database, queue, cache nodes (Data)
- [ ] **Dag 7**: Testing og refinement

#### **Integration Nodes**
- [ ] **Dag 1-2**: API og webhook nodes
- [ ] **Dag 3-4**: Message queue og ETL nodes
- [ ] **Dag 5**: Integration-specific styling
- [ ] **Dag 6-7**: Cross-architecture node relationships

### **Fase 4: AI Enhancement & Finalization (Uke 7-8)**

#### **AI Analysis Enhancement**
- [ ] **Dag 1-3**: Oppdater `DataAggregator` for multi-architecture
- [ ] **Dag 4-6**: Implementer arkitektur-spesifikke AI prompts
- [ ] **Dag 7-9**: Cross-architecture analysis capabilities
- [ ] **Dag 10**: AI testing og optimization

#### **User Experience Polish**
- [ ] **Dag 1-2**: Node count badges og progress indicators
- [ ] **Dag 3-4**: Enhanced save status og conflict resolution
- [ ] **Dag 5-6**: Error handling og validation
- [ ] **Dag 7**: Performance optimization
- [ ] **Dag 8**: Documentation og user guide updates

#### **Testing & Deployment**
- [ ] **Dag 9-10**: Comprehensive testing (unit, integration, user acceptance)
- [ ] **Dag 11-12**: Bug fixes og refinements
- [ ] **Dag 13-14**: Production deployment og monitoring

---

## 🔧 **Testing Strategy**

### **Database Testing**
```sql
-- Test architecture type creation
INSERT INTO architecture_types (name, display_name, icon_class, sort_order) 
VALUES ('test_arch', 'Test Architecture', 'fas fa-test', 99);

-- Test diagram creation
INSERT INTO application_diagrams (application_id, architecture_type_id, diagram_data)
VALUES (1, 1, '{"drawflow":{"Home":{"data":{}}}}');

-- Test node template filtering
SELECT * FROM node_templates nt 
JOIN architecture_types at ON nt.architecture_type_id = at.id 
WHERE at.name = 'solution';
```

### **API Testing**
```bash
# Test architecture types endpoint
curl -X GET "http://localhost/public/api/get_architecture_types.php"

# Test filtered node templates
curl -X GET "http://localhost/public/api/get_node_templates.php?architecture_type=process"

# Test diagram loading
curl -X GET "http://localhost/public/api/load_diagram.php?application_id=1&architecture_type=solution"

# Test diagram saving
curl -X POST "http://localhost/public/api/save_diagram.php" \
     -H "Content-Type: application/json" \
     -d '{"application_id":1,"architecture_type":"solution","diagram_data":{},"notes":"Test"}'
```

### **Frontend Testing Scenarios**
1. **Tab Switching**: Verify smooth transitions between architecture types
2. **Auto-Save**: Confirm diagrams save correctly per architecture
3. **Node Templates**: Validate correct nodes load for each architecture
4. **Conflict Handling**: Test simultaneous editing scenarios
5. **Performance**: Measure load times with multiple large diagrams

---

## 📊 **Success Metrics**

### **Technical Metrics**
- [ ] **Database Performance**: <200ms query response times
- [ ] **Frontend Performance**: <3s initial load, <1s tab switching
- [ ] **API Reliability**: 99.9% uptime, <500ms response times
- [ ] **Data Integrity**: Zero data loss during migration
- [ ] **Cross-Browser Compatibility**: Support for Chrome, Firefox, Safari, Edge

### **User Experience Metrics**
- [ ] **Adoption Rate**: >80% of active users utilize new architecture tabs
- [ ] **User Satisfaction**: >4.5/5 rating in user feedback surveys
- [ ] **Productivity Gain**: 30% reduction in diagram creation time
- [ ] **Error Reduction**: 50% fewer user-reported diagram issues
- [ ] **Training Requirements**: <2 hours training needed for existing users

### **Business Metrics**
- [ ] **Architecture Coverage**: >70% of applications have multiple architecture types
- [ ] **AI Analysis Quality**: 25% improvement in architecture recommendations
- [ ] **Documentation Quality**: 40% increase in architectural documentation completeness
- [ ] **Compliance**: 100% compatibility with enterprise architecture standards

---

## 🚨 **Risk Analysis & Mitigation**

### **Technical Risks**

#### **Database Performance Risk**
- **Risk**: Large JSON diagrams may slow database performance
- **Mitigation**: Implement diagram data compression and pagination
- **Monitoring**: Track query performance metrics and storage growth

#### **Browser Memory Usage**
- **Risk**: Multiple DrawFlow instances may consume excessive memory
- **Mitigation**: Lazy loading and instance cleanup on tab switches
- **Monitoring**: Browser performance testing with large diagrams

#### **Data Migration Risk**
- **Risk**: Existing diagram data corruption during migration
- **Mitigation**: Full backup before migration + rollback procedures
- **Monitoring**: Automated data integrity checks post-migration

### **User Experience Risks**

#### **User Confusion**
- **Risk**: Users may be confused by new tab interface
- **Mitigation**: Progressive rollout with training materials
- **Monitoring**: User feedback tracking and support ticket analysis

#### **Feature Complexity**
- **Risk**: Too many architecture types may overwhelm users
- **Mitigation**: Configurable architecture types per organization
- **Monitoring**: Usage analytics per architecture type

### **Business Risks**

#### **Adoption Resistance**
- **Risk**: Users may resist change from single-diagram approach
- **Mitigation**: Emphasize benefits through demos and success stories
- **Monitoring**: Track adoption rates and gather user feedback

#### **Integration Compatibility**
- **Risk**: Changes may break existing AI analysis or exports
- **Mitigation**: Backward compatibility layer and extensive testing
- **Monitoring**: Monitor AI analysis quality and export functionality

---

## 🔄 **Rollback Strategy**

### **Database Rollback**
```sql
-- If needed, restore original diagram fields
UPDATE applications a 
JOIN application_diagrams ad ON a.id = ad.application_id 
SET a.drawflow_diagram = ad.diagram_data,
    a.drawflow_notes = ad.diagram_notes
WHERE ad.architecture_type_id = 2; -- solution architecture

-- Remove new tables (DANGER: data loss)
DROP TABLE IF EXISTS diagram_relationships;
DROP TABLE IF EXISTS application_diagrams;
DROP TABLE IF EXISTS architecture_types;

-- Restore original node_templates
ALTER TABLE node_templates 
DROP FOREIGN KEY fk_architecture_type,
DROP COLUMN architecture_type_id,
DROP COLUMN category,
DROP COLUMN is_connector,
DROP COLUMN validation_rules;
```

### **Frontend Rollback**
- Keep original `datamap.php` as `datamap_v1_backup.php`
- Redirect `datamap.php` to backup version if needed
- Restore original API endpoints from git backup

### **Feature Flag Approach**
```php
// Feature flag in config
$ENABLE_MULTI_ARCHITECTURE = false;

// In datamap.php
if ($ENABLE_MULTI_ARCHITECTURE) {
    include 'datamap_multi_arch.php';
} else {
    include 'datamap_original.php';
}
```

---

## 📚 **Documentation Updates Required**

### **User Documentation**
- [ ] Update `DATAMAP_GUIDE.md` with multi-architecture instructions
- [ ] Create `MULTI_ARCHITECTURE_USER_GUIDE.md` for end users
- [ ] Update `DATAMAP_QUICK_REFERENCE.md` with new API endpoints
- [ ] Update screenshots and videos in documentation

### **Technical Documentation**
- [ ] Update `database.md` with new schema changes
- [ ] Update `technical-architecture.md` with API changes
- [ ] Create `ARCHITECTURE_TYPE_MANAGEMENT.md` for administrators
- [ ] Update `ui-implementation.md` with new frontend components

### **Developer Documentation**
- [ ] Update API documentation with new endpoints
- [ ] Create `MULTI_ARCH_DEVELOPMENT_GUIDE.md` for future developers
- [ ] Update `TROUBLESHOOTING.md` with new error scenarios
- [ ] Document migration procedures and rollback steps

---

## 🎯 **Conclusion & Next Steps**

Denne multi-architecture utvidelsen av DataMap representerer en betydelig forbedring av AppTrack's arkitekturmuligheter. Løsningen adresserer spesifikke behov for forskjellige arkitektroller mens den opprettholder backward compatibility og forbedrer AI-analysefunksjonalitet.

### **Viktige Fordeler**
1. **Arkitekt-Spesifikk Verdi**: Hver arkitektrolle får relevante verktøy
2. **Forbedret AI**: Mer presise analyser med separerte arkitekturlag  
3. **Skalerbar Design**: Enkelt å legge til nye arkitekturtyper
4. **Enterprise-Ready**: Støtter komplekse organisasjonsstrukturer

### **Umiddelbare Handlinger**
1. **Review & Approval**: Gjennomgang av denne planen med interessenter
2. **Resource Allocation**: Tildeling av utviklingsressurser
3. **Environment Setup**: Forberedelse av utvikling- og testmiljøer
4. **Stakeholder Communication**: Informasjon til slutterbrukere om kommende endringer

### **Langsiktig Visjon**
Multi-architecture DataMap legger grunnlaget for fremtidige forbedringer som:
- Real-time collaboration mellom arkitekter
- Automated compliance checking
- Enterprise architecture repository integration
- Advanced visualization og reporting capabilities

**Estimert total utviklingstid**: 8 uker  
**Estimert ROI**: 6-12 måneder etter implementering  
**Recommended start date**: Umiddelbart etter godkjenning

---

*Dette dokumentet skal oppdateres etter hvert som implementeringen skrider frem og nye krav identifiseres.*
