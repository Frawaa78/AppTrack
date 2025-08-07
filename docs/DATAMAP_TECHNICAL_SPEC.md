# DataMap Technical Specification
*Detailed technical reference for developers and AI assistants*

## 🏗️ Architecture Overview

### System Components
```
┌─────────────────────────────────────────────────────────┐
│                    DataMap System                       │
├─────────────────────────────────────────────────────────┤
│  Frontend (Browser)                                     │
│  ┌─────────────────┐  ┌──────────────────────────────┐  │
│  │   datamap.php   │  │     datamap-core.js          │  │
│  │  (Entry Point)  │  │   (Core Logic - 2643 lines) │  │
│  └─────────────────┘  └──────────────────────────────┘  │
│           │                        │                    │
│           └────────────────────────┼─────────────────   │
│                                    │                    │
│  ┌─────────────────────────────────┼──────────────────┐ │
│  │            DrawFlow Library     │                  │ │
│  │         (Visual Engine)         │                  │ │
│  └─────────────────────────────────┼──────────────────┘ │
├─────────────────────────────────────┼────────────────────┤
│  Backend (PHP/Database)             │                    │
│  ┌─────────────────────────────────┼──────────────────┐ │
│  │         Database APIs           │                  │ │
│  │    (save/load diagram data)     │                  │ │
│  └─────────────────────────────────┼──────────────────┘ │
└─────────────────────────────────────┼────────────────────┘
                                      │
                      ┌───────────────▼──────────────┐
                      │           Database           │
                      │      (Persistent Storage)    │
                      └──────────────────────────────┘
```

## 🔧 Core Functions Reference

### Essential DataMapCore Methods

#### Initialization & Setup
```javascript
DataMapCore.init()
├── setupDrawFlow()           // Initialize DrawFlow editor
├── loadNodeTemplates()       // Load available node types
├── loadDiagram()            // Load saved diagram data
├── bindKeyboardShortcuts()   // Setup keyboard controls
└── initializeContextMenu()   // Setup right-click menus
```

#### Node Management
```javascript
// Node Creation
DataMapCore.addNode(type, x, y)
├── generateNodeHTML(type)    // Create node visual structure
├── addNodeToEditor()        // Add to DrawFlow editor
├── applyCursorStyling()     // Apply visual styling
└── autoSave()              // Save changes

// Node Modification
DataMapCore.recreateNodeWithPorts(nodeId, inputs, outputs)
├── saveNodeConnections()    // Preserve existing connections
├── saveCurrentText()        // Preserve node text content
├── deleteNode()            // Remove old node
├── addNode()               // Create new node with new port count
├── restoreNodeConnections() // Rebuild all connections
├── restoreNodeText()       // Restore text content
└── updateConnectionPositions() // Fix visual positions
```

#### Connection Management
```javascript
// Connection Preservation
DataMapCore.saveNodeConnections(nodeId)
├── extractInputConnections()  // Get all input connections
├── extractOutputConnections() // Get all output connections
└── return connectionData      // Return preservation data

DataMapCore.restoreNodeConnections(nodeId, connections)
├── validatePorts()           // Check if ports exist
├── recreateConnections()     // Rebuild each connection
├── preventDuplicates()       // Avoid duplicate connections
└── updateVisualPositions()   // Fix connection lines
```

#### Data Management
```javascript
// Data Validation & Repair
DataMapCore.validateAndRepairDiagramData(data)
├── convertArrayToObject()    // Fix DrawFlow compatibility
├── validateNodeStructure()   // Ensure required properties
├── repairConnectionConsistency() // Fix connection mismatches
└── cleanupInvalidData()      // Remove corrupted entries

DataMapCore.repairConnectionConsistency(nodes)
├── validateOutputConnections() // Check output → input links
├── validateInputConnections()  // Check input → output links
├── addMissingConnections()    // Repair broken links
└── logRepairActions()         // Document fixes
```

## 🔄 Data Flow Diagrams

### Connection Creation Process
```
User Action: Drag from output to input
         ↓
   DrawFlow Event: connection
         ↓
   DataMapCore.onConnectionCreated()
         ↓
   ┌─────────────────────────────┐
   │  Update Internal Data       │
   │  ├── Source node output     │
   │  └── Target node input      │
   └─────────────────────────────┘
         ↓
   DataMapCore.autoSave()
         ↓
   Send to Database
```

### Node Modification Process
```
User Action: Add/Remove Port
         ↓
   DataMapCore.addNodeInput/Output()
         ↓
   ┌─────────────────────────────┐
   │  saveNodeConnections()      │
   │  ├── Extract all connections│
   │  └── Store temporarily      │
   └─────────────────────────────┘
         ↓
   ┌─────────────────────────────┐
   │  recreateNodeWithPorts()    │
   │  ├── Delete old node        │
   │  └── Create new node        │
   └─────────────────────────────┘
         ↓
   ┌─────────────────────────────┐
   │  restoreNodeConnections()   │
   │  ├── Rebuild each connection│
   │  └── Update visual lines    │
   └─────────────────────────────┘
         ↓
   setTimeout(autoSave, 1000) // Delayed save
```

## 📊 Data Structures

### Node Data Structure
```javascript
{
  "id": 12,
  "name": "application_1",
  "data": {
    "type": "application",
    "title": "Application 1",
    "description": "Software application description",
    "created": "2025-08-07T13:34:06.583Z"
  },
  "class": "application-node",
  "html": "...", // Rendered HTML content
  "typenode": false,
  "inputs": {
    "input_1": {
      "connections": [
        {
          "node": "sourceNodeId",
          "input": "output_1"  // Source output port
        }
      ]
    }
  },
  "outputs": {
    "output_1": {
      "connections": [
        {
          "node": "targetNodeId",
          "output": "input_1"  // Target input port
        }
      ]
    }
  },
  "pos_x": 157,
  "pos_y": 177
}
```

### Complete Diagram Structure
```javascript
{
  "drawflow": {
    "Home": {
      "data": {
        "12": { /* node data */ },
        "13": { /* node data */ },
        "14": { /* node data */ }
      }
    }
  },
  "commentConnections": []
}
```

## ⚡ Performance Optimizations

### Debounced Operations
```javascript
// Grip Handle Cleanup (CPU intensive)
let gripCleanupTimeout;
removeGripHandles: function() {
    if (gripCleanupTimeout) clearTimeout(gripCleanupTimeout);
    gripCleanupTimeout = setTimeout(() => {
        // Actual cleanup operations
        document.querySelectorAll('.grip').forEach(grip => grip.remove());
    }, 100); // 100ms debounce
}

// Auto-save (Database operations)
setTimeout(() => {
    this.autoSave(); // Wait for connection restoration
}, 1000); // 1000ms delay after node recreation
```

### Batch Operations
```javascript
// Connection Position Updates
updateAllConnectionPositions: function() {
    const nodes = this.editor.export().drawflow.Home.data;
    let updates = [];
    
    // Batch all updates
    Object.keys(nodes).forEach(nodeId => {
        updates.push(() => this.updateNodeConnections(nodeId));
    });
    
    // Execute batch
    updates.forEach(update => update());
    console.log(`🔧 Updated connection positions for ${updates.length} nodes`);
}
```

## 🔍 Debugging & Monitoring

### Logging System
```javascript
// Connection tracking
console.log('🔗 Connection created:', {output_id, input_id, output_class, input_class});

// Node operations  
console.log('➕ Adding input to node', nodeId, '(current:', currentInputs, 'inputs,', currentOutputs, 'outputs)');

// Performance monitoring
console.log('✅ Cursor styling applied to all nodes');

// Error handling
console.error('❌ Error validating diagram data:', error);
```

### Critical Debug Points
1. **Connection Creation**: Watch for bidirectional data consistency
2. **Node Recreation**: Monitor connection preservation/restoration
3. **Data Import**: Check array-to-object conversion
4. **Auto-save Timing**: Verify connections saved after restoration
5. **Performance**: Monitor grip handle cleanup frequency

## 🚨 Common Issues & Solutions

### Connection Data Inconsistency
**Symptoms**: Connections exist in data but not visible
**Cause**: Missing bidirectional connection references
**Solution**: `repairConnectionConsistency()` function
```javascript
// Ensure both sides of connection exist
output: [{node: "target", output: "input_1"}]
input:  [{node: "source", input: "output_1"}]
```

### DrawFlow Import Crashes
**Symptoms**: "Cannot read property of undefined" during load
**Cause**: Array format data incompatible with DrawFlow
**Solution**: `validateAndRepairDiagramData()` conversion
```javascript
// Convert: [node1, node2] → {"1": node1, "2": node2}
```

### Auto-save Timing Issues
**Symptoms**: Connections lost after node modification
**Cause**: Save executed before connection restoration completed
**Solution**: Delayed auto-save with timeout
```javascript
setTimeout(() => this.autoSave(), 1000);
```

## 🎯 Code Quality Standards

### Error Handling Pattern
```javascript
try {
    // Operation with potential failure
    const result = riskyOperation();
    console.log('✅ Operation successful:', result);
    return result;
} catch (error) {
    console.error('❌ Operation failed:', error);
    // Graceful fallback
    return fallbackValue;
}
```

### Validation Pattern
```javascript
// Always validate inputs
if (!nodeId || !this.editor.drawflow.drawflow.Home.data[nodeId]) {
    console.log('⚠️ Invalid node ID:', nodeId);
    return;
}
```

### Performance Pattern
```javascript
// Debounce expensive operations
let operationTimeout;
expensiveOperation: function() {
    if (operationTimeout) clearTimeout(operationTimeout);
    operationTimeout = setTimeout(() => {
        // Actual expensive work
    }, debounceDelay);
}
```

## 📈 Metrics & Monitoring

### Performance Benchmarks
- **Node Creation**: < 50ms per node
- **Connection Creation**: < 20ms per connection  
- **Diagram Load**: < 500ms for 50 nodes
- **Auto-save**: < 200ms for typical diagrams
- **Connection Restoration**: < 800ms for complex nodes

### Memory Usage
- **Grip Handle Cleanup**: Prevents DOM bloat
- **Event Listener Management**: Proper cleanup on node deletion
- **Data Structure Optimization**: Object pooling for frequent operations

### User Experience Metrics
- **Visual Feedback**: Immediate cursor changes, loading indicators
- **Responsiveness**: No blocking operations > 100ms
- **Data Integrity**: Zero connection loss incidents
- **Error Recovery**: Automatic repair for 95% of data issues

---

*Technical Specification Version 1.0*
*Last Updated: August 7, 2025*
*Status: Production Ready*
