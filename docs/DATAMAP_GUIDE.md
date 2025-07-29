# DataMap Visual Architecture Guide v3.3.2

## Overview

DataMap is AppTrack's advanced visual system mapping feature that replaced Mermaid.js with the powerful DrawFlow library for interactive diagram creation and editing. This guide covers the complete DataMap functionality, from basic usage to advanced AI integration.

---

## 🎯 Key Features

### Visual System Mapping
- **Interactive Canvas**: Drag-and-drop diagram editor with professional node templates
- **Real-time Collaboration**: Auto-save functionality with conflict resolution
- **Node-Based Architecture**: Multiple node types with configurable inputs/outputs
- **Connection Management**: Visual connection lines with automatic routing
- **Comment Integration**: Technical, business, risk, and implementation annotations

### AI-Powered Analysis
- **Diagram Interpretation**: AI analysis of DataMap diagrams for architectural insights
- **Integration Mapping**: Automatic detection of system integration patterns
- **Architecture Recommendations**: AI-powered suggestions for improvements
- **Multilingual Support**: Norwegian/English content processing with preserved context

---

## 🛠️ Technical Architecture

### DrawFlow Integration
- **Library**: DrawFlow (https://github.com/jerosoler/Drawflow)
- **Storage**: JSON format in `applications.drawflow_diagram` field
- **Notes**: Text annotations in `applications.drawflow_notes` field
- **Templates**: Database-driven node configurations with CSS classes

### Database Schema
```sql
-- DataMap storage fields in applications table
drawflow_diagram    JSON     -- Complete diagram structure
drawflow_notes      TEXT     -- Additional annotations and comments

-- AI integration
ai_analysis         -- Includes diagram interpretation
ai_configurations   -- DataMap-specific prompt templates
```

---

## 📋 Node Types and Usage

### Application Nodes
- **Purpose**: Software applications and systems
- **Inputs**: 1 (default)
- **Outputs**: 1 (default)
- **Use Case**: Core business applications, microservices

### Service Nodes
- **Purpose**: API endpoints and services
- **Inputs**: 1 (default)
- **Outputs**: 2 (default)
- **Use Case**: REST APIs, microservices, integration endpoints

### Database Nodes
- **Purpose**: Data stores and repositories
- **Inputs**: 2 (default)
- **Outputs**: 1 (default)
- **Use Case**: SQL databases, NoSQL stores, data warehouses

### External System Nodes
- **Purpose**: Third-party integrations
- **Inputs**: 1 (default)
- **Outputs**: 1 (default)
- **Use Case**: Vendor systems, cloud services, legacy applications

### Visualization Nodes
- **Purpose**: Dashboards and reporting tools
- **Inputs**: 2 (default)
- **Outputs**: 0 (default)
- **Use Case**: BI dashboards, monitoring tools, reporting systems

### Comment Nodes
- **Purpose**: Documentation and annotations
- **Inputs**: 0 (no connections)
- **Outputs**: 0 (no connections)
- **Types**: Technical, Business, Risk, Implementation, Documentation

---

## 🎨 User Interface Guide

### Main Editor Interface
```
┌─────────────────────────────────────────────────┐
│ Toolbar: [Add Node] [Clear] [Export] [Save]    │
├─────────────────────────────────────────────────┤
│                                                 │
│  Interactive Canvas Area                        │
│  - Drag nodes to position                       │
│  - Click outputs to create connections          │
│  - Right-click for context menus                │
│                                                 │
└─────────────────────────────────────────────────┘
```

### Node Structure
```
┌─────────────────────────┐
│ ≡≡≡ (Grip Handle)       │  ← Drag handle for positioning
├─────────────────────────┤
│ 🔧 Node Title          │  ← Editable title
│ Description text...     │  ← Editable description
│ ● Input   Output ●     │  ← Connection points
└─────────────────────────┘
```

### Context Menu Options
- **Edit Node**: Modify title and description inline
- **Delete Node**: Remove node and all connections
- **Add Comment**: Create annotation for selected node
- **Connection Options**: Manage input/output connections

---

## 🔗 Connection Management

### Creating Connections
1. **Click Output Point**: Start from source node output circle
2. **Drag to Input Point**: Connect to target node input circle
3. **Auto-routing**: Connection lines automatically route around obstacles
4. **Validation**: System prevents invalid connections

### Connection Types
- **Data Flow**: Standard connections showing data movement
- **Comment Links**: Visual connections from comment nodes (dashed yellow lines)
- **Dependency Links**: System dependency relationships

### Connection Rules
- **One-to-Many**: Single output can connect to multiple inputs
- **Many-to-One**: Multiple outputs can connect to single input
- **No Loops**: System prevents circular dependencies
- **Type Validation**: Ensures compatible node type connections

---

## 🤖 AI Integration Features

### DataMap Analysis
The AI system processes DataMap diagrams to provide:

#### System Architecture Insights
- **Integration Complexity**: Analysis of connection patterns and dependencies
- **Data Flow Analysis**: Interpretation of data movement between systems
- **Architecture Patterns**: Identification of common architectural patterns
- **Bottleneck Detection**: Potential performance or scalability issues

#### Business Context Analysis
- **Comment Processing**: Interpretation of technical and business annotations
- **Risk Assessment**: Analysis of risk comments and architectural risks
- **Implementation Notes**: Processing of implementation-specific comments
- **Documentation Integration**: Automatic inclusion of diagram context in reports

### AI Prompt Templates
Current templates with DataMap integration:

#### v3.3-english-only (Latest)
- **Focus**: English-language analysis with DataMap interpretation
- **Features**: System name extraction, data flow analysis, business purpose interpretation
- **Token Limit**: 1800 tokens optimized for diagram processing

#### v3.0-integration-focused
- **Focus**: Integration architecture and system dependencies
- **Features**: Enhanced DataMap system analysis, integration pattern recognition
- **Token Limit**: 2000 tokens for comprehensive architecture analysis

#### v2.0-narrative
- **Focus**: Business narrative with technical context
- **Features**: Stakeholder-friendly summaries including DataMap insights
- **Token Limit**: 2000 tokens for detailed business communication

---

## 📊 Export and Integration

### Export Options
- **JSON Export**: Complete diagram data for backup and version control
- **PNG Export**: High-resolution image for documentation
- **PDF Export**: Professional diagrams for presentations
- **SVG Export**: Scalable vector graphics for web integration

### API Integration
```php
// DataMap API endpoints
GET  /api/load_drawflow_diagram.php     // Load diagram data
POST /api/save_drawflow_diagram.php     // Save diagram changes
GET  /api/get_application_data.php      // Get application context
POST /api/ai_analysis.php              // Trigger AI analysis with DataMap
```

### Integration with Other Modules
- **Application View**: DataMap editor embedded directly in application pages
- **AI Analysis**: Diagram data automatically included in AI analysis requests
- **Work Notes**: DataMap changes tracked in application activity history
- **Handover Process**: Diagrams included in handover documentation

---

## 🔧 Development and Customization

### Node Template Configuration
Node templates are stored in the database with configurable properties:

```javascript
{
    "html": "Node HTML template with placeholders",
    "inputs": 2,           // Number of input connection points
    "outputs": 1,          // Number of output connection points  
    "class": "css-class"   // Styling class for node appearance
}
```

### Custom Node Types
To add new node types:

1. **Database Entry**: Add template to node templates table
2. **CSS Styling**: Define appearance in `drawflow-theme.css`
3. **JavaScript**: Update node creation logic in `datamap.php`
4. **AI Integration**: Update prompt templates to recognize new node types

### Performance Optimization
- **Auto-save Debouncing**: Prevents excessive save operations during editing
- **Connection Caching**: Optimized connection line rendering
- **Node Template Caching**: Database templates cached for performance
- **Change Detection**: Only saves when actual changes are made

---

## 🔍 Troubleshooting

### Common Issues

#### Cannot Draw Connections from New Nodes
- **Cause**: Event handler conflicts with grip handle system
- **Solution**: Ensure output elements don't have `stopImmediatePropagation()` in event handlers
- **Fixed in**: v3.3.2 with improved event handling

#### Diagram Not Saving
- **Cause**: JSON serialization errors or database connection issues
- **Solution**: Check browser console for errors, verify database connection
- **Debug**: Enable auto-save status indicator in UI

#### AI Analysis Missing DataMap Data
- **Cause**: Diagram data not included in analysis request
- **Solution**: Verify DataAggregator.php includes diagram data in context
- **Check**: AI analysis logs for diagram processing confirmation

### Performance Issues
- **Large Diagrams**: Consider splitting complex diagrams into multiple views
- **Connection Count**: Optimize diagrams with excessive connections
- **Auto-save Frequency**: Adjust debounce timing for better performance

---

## 🚀 Future Enhancements

### Planned Features
- **Team Collaboration**: Real-time multi-user editing
- **Version Control**: Diagram version history and comparison
- **Template Library**: Pre-built diagram templates for common patterns
- **Advanced Export**: PowerPoint and Visio format support
- **Mobile Optimization**: Enhanced mobile editing experience

### Integration Roadmap
- **CMDB Integration**: Automatic synchronization with configuration databases
- **Architecture Validation**: Automated architecture compliance checking
- **Dependency Tracking**: Live dependency monitoring and alerting
- **Cost Analysis**: Architecture cost modeling and optimization

---

## 📚 Additional Resources

- **DrawFlow Documentation**: https://github.com/jerosoler/Drawflow
- **Database Schema**: `/docs/database.md` - Complete database documentation
- **AI Integration**: `/docs/AI_FEATURES_README.md` - AI analysis features
- **API Documentation**: `/docs/technical-architecture.md` - API reference

For technical support or feature requests, contact the development team or create an issue in the project repository.
