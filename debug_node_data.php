<?php
// Debug script to check what's saved in node data
require_once 'src/config/config.php';
require_once 'src/db/db.php';

$application_id = isset($_GET['app_id']) ? intval($_GET['app_id']) : 1;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT drawflow_diagram FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Debug: Saved Diagram Data for App ID: $application_id</h2>";
    
    if ($result && $result['drawflow_diagram']) {
        $data = json_decode($result['drawflow_diagram'], true);
        echo "<h3>Raw JSON Data:</h3>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        
        if (isset($data['drawflow']['Home']['data'])) {
            echo "<h3>Node Data Details:</h3>";
            foreach ($data['drawflow']['Home']['data'] as $nodeId => $nodeInfo) {
                echo "<h4>Node: $nodeId</h4>";
                echo "<p><strong>Class:</strong> " . ($nodeInfo['class'] ?? 'N/A') . "</p>";
                echo "<p><strong>Data:</strong></p>";
                echo "<pre>" . json_encode($nodeInfo['data'] ?? [], JSON_PRETTY_PRINT) . "</pre>";
                echo "<p><strong>HTML:</strong></p>";
                echo "<pre>" . htmlspecialchars($nodeInfo['html'] ?? 'N/A') . "</pre>";
                echo "<hr>";
            }
        }
    } else {
        echo "<p>No diagram data found for this application.</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
