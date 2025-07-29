# DataMap Quick Reference Guide

## Node Types & Defaults

| Node Type | Inputs | Outputs | Use Case |
|-----------|--------|---------|----------|
| Application | 1 | 1 | Core business applications |
| Service | 1 | 2 | APIs, microservices |
| Database | 2 | 1 | Data stores |
| External System | 1 | 1 | Third-party integrations |
| Visualization | 2 | 0 | Dashboards, reports |
| Comment | 0 | 0 | Annotations |

## Essential JavaScript Functions

```javascript
// Core DataMap operations in datamap.php

// Initialize editor
const editor = new Drawflow(container);

// Add node with template
editor.addNode('node_name', inputs, outputs, x, y, 'node_class', data, html);

// Save diagram
function saveDiagram() {
    const exportData = editor.export();
    // Send to save_drawflow_diagram.php
}

// Load diagram
function loadDiagram(jsonData) {
    editor.import(jsonData);
}

// Setup drag handles (Fixed in v3.3.2)
function setupDragHandles() {
    // Ensure output elements can still be clicked
    // Do NOT use stopImmediatePropagation() on output elements
}
```

## Database Fields

```sql
-- In applications table
drawflow_diagram    JSON    -- Complete diagram structure
drawflow_notes      TEXT    -- Additional annotations

-- Node templates (example)
INSERT INTO node_templates (name, html, inputs, outputs, class) VALUES
('application', '<div>App: {{title}}</div>', 1, 1, 'node-application');
```

## API Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/load_drawflow_diagram.php` | GET | Load diagram |
| `/api/save_drawflow_diagram.php` | POST | Save diagram |
| `/api/ai_analysis.php` | POST | AI analysis with diagram |

## AI Integration Data Structure

```json
{
    "context": {
        "datamap_diagram": {
            "nodes": {...},
            "connections": {...}
        },
        "datamap_notes": "Additional annotations"
    }
}
```

## CSS Classes for Nodes

```css
/* In drawflow-theme.css */
.node-application { background: #2196F3; }
.node-service { background: #4CAF50; }
.node-database { background: #FF9800; }
.node-external { background: #9C27B0; }
.node-visualization { background: #F44336; }
.node-comment { background: #FFC107; border: 2px dashed #333; }
```

## Common Issues & Quick Fixes

| Issue | Quick Fix |
|-------|-----------|
| Can't connect from new nodes | Check setupDragHandles() doesn't block output clicks |
| Diagram not saving | Verify JSON structure and database connection |
| AI missing diagram data | Ensure DataAggregator includes diagram in context |
| Nodes overlapping | Implement grid-based positioning |

## Version History

- **v3.3.2**: Fixed output connection issues with grip handles
- **v3.3.1**: Added AI integration with DataMap analysis
- **v3.3.0**: Initial DataMap implementation replacing Mermaid.js
