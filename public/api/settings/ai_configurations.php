<?php
// AI Configurations API for admin settings
session_start();
require_once '../../../src/config/config.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Database connection with fallback
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("AI Config API - Database connected successfully");
} catch (PDOException $e) {
    error_log("AI Config API - Database connection failed: " . $e->getMessage());
    
    // Try fallback configuration
    try {
        $fallback_config = require '../../../src/config/load_env.php';
        $pdo = new PDO(
            "mysql:host=" . $fallback_config['DB_HOST'] . ";dbname=" . $fallback_config['DB_NAME'],
            $fallback_config['DB_USER'],
            $fallback_config['DB_PASS']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        error_log("AI Config API - Fallback database connected successfully");
    } catch (PDOException $e2) {
        error_log("AI Config API - Fallback database connection also failed: " . $e2->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }
}

// Route requests based on HTTP method
$method = $_SERVER['REQUEST_METHOD'];
error_log("AI Config API - Handling $method request");

switch ($method) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo);
        break;
    case 'PUT':
        handlePut($pdo);
        break;
    case 'DELETE':
        handleDelete($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGet($pdo) {
    try {
        error_log("AI Config API - Fetching AI configurations");
        
        // Get all AI configurations
        $stmt = $pdo->query("
            SELECT 
                id,
                analysis_type,
                prompt_template,
                prompt_version,
                model_name,
                model_parameters,
                max_tokens,
                temperature,
                is_active,
                created_at,
                updated_at,
                created_by
            FROM ai_configurations 
            ORDER BY analysis_type, prompt_version DESC
        ");
        $configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data for frontend
        $formattedConfigs = array_map(function($config) {
            // Parse model_parameters if it's JSON
            if ($config['model_parameters']) {
                $params = json_decode($config['model_parameters'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $config['model_parameters'] = $params;
                }
            }
            
            // Format dates
            $config['created_at'] = date('Y-m-d H:i', strtotime($config['created_at']));
            $config['updated_at'] = date('Y-m-d H:i', strtotime($config['updated_at']));
            
            // Truncate prompt template for list view
            $config['prompt_preview'] = strlen($config['prompt_template']) > 100 
                ? substr($config['prompt_template'], 0, 100) . '...' 
                : $config['prompt_template'];
            
            return $config;
        }, $configurations);
        
        error_log("AI Config API - Found " . count($configurations) . " configurations");
        
        echo json_encode([
            'success' => true,
            'configurations' => $formattedConfigs,
            'total' => count($configurations)
        ]);
        
    } catch (Exception $e) {
        error_log("AI Config API - Error fetching configurations: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load AI configurations', 'debug' => $e->getMessage()]);
    }
}

function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['analysis_type']) || !isset($input['prompt_template'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: analysis_type and prompt_template']);
        return;
    }

    $analysisType = trim($input['analysis_type']);
    $promptTemplate = trim($input['prompt_template']);
    $promptVersion = $input['prompt_version'] ?? 'v1.0';
    $modelName = $input['model_name'] ?? 'gpt-3.5-turbo';
    $maxTokens = (int)($input['max_tokens'] ?? 2000);
    $temperature = (float)($input['temperature'] ?? 0.7);
    $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;
    $createdBy = $_SESSION['user_id'];

    // Handle model_parameters
    $modelParameters = null;
    if (isset($input['model_parameters'])) {
        if (is_array($input['model_parameters'])) {
            $modelParameters = json_encode($input['model_parameters']);
        } else {
            $modelParameters = $input['model_parameters'];
        }
    } else {
        // Default parameters
        $modelParameters = json_encode([
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ]);
    }

    if (empty($analysisType) || empty($promptTemplate)) {
        http_response_code(400);
        echo json_encode(['error' => 'Analysis type and prompt template cannot be empty']);
        return;
    }

    try {
        // Check if configuration already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ai_configurations WHERE analysis_type = ? AND prompt_version = ?");
        $stmt->execute([$analysisType, $promptVersion]);
        
        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Configuration with this analysis type and version already exists']);
            return;
        }
        
        // Insert new configuration
        $stmt = $pdo->prepare("
            INSERT INTO ai_configurations 
            (analysis_type, prompt_template, prompt_version, model_name, model_parameters, max_tokens, temperature, is_active, created_by, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $analysisType,
            $promptTemplate,
            $promptVersion,
            $modelName,
            $modelParameters,
            $maxTokens,
            $temperature,
            $isActive,
            $createdBy
        ]);
        
        $configId = $pdo->lastInsertId();
        error_log("AI Config API - Successfully created configuration: $analysisType ($promptVersion) with ID: $configId");
        
        echo json_encode([
            'success' => true,
            'message' => 'AI configuration created successfully',
            'id' => $configId,
            'analysis_type' => $analysisType,
            'prompt_version' => $promptVersion
        ]);
        
    } catch (Exception $e) {
        error_log("AI Config API - Error creating configuration: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create AI configuration', 'debug' => $e->getMessage()]);
    }
}

function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing configuration ID']);
        return;
    }

    $id = (int)$input['id'];
    $updatedBy = $_SESSION['user_id'];
    
    // Build update query dynamically based on provided fields
    $updateFields = [];
    $updateValues = [];
    
    if (isset($input['analysis_type'])) {
        $updateFields[] = 'analysis_type = ?';
        $updateValues[] = trim($input['analysis_type']);
    }
    
    if (isset($input['prompt_template'])) {
        $updateFields[] = 'prompt_template = ?';
        $updateValues[] = trim($input['prompt_template']);
    }
    
    if (isset($input['prompt_version'])) {
        $updateFields[] = 'prompt_version = ?';
        $updateValues[] = trim($input['prompt_version']);
    }
    
    if (isset($input['model_name'])) {
        $updateFields[] = 'model_name = ?';
        $updateValues[] = trim($input['model_name']);
    }
    
    if (isset($input['model_parameters'])) {
        $updateFields[] = 'model_parameters = ?';
        if (is_array($input['model_parameters'])) {
            $updateValues[] = json_encode($input['model_parameters']);
        } else {
            $updateValues[] = $input['model_parameters'];
        }
    }
    
    if (isset($input['max_tokens'])) {
        $updateFields[] = 'max_tokens = ?';
        $updateValues[] = (int)$input['max_tokens'];
    }
    
    if (isset($input['temperature'])) {
        $updateFields[] = 'temperature = ?';
        $updateValues[] = (float)$input['temperature'];
    }
    
    if (isset($input['is_active'])) {
        $updateFields[] = 'is_active = ?';
        $updateValues[] = (int)$input['is_active'];
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    // Add updated_at and updated_by
    $updateFields[] = 'updated_at = NOW()';
    
    try {
        // Check if configuration exists
        $stmt = $pdo->prepare("SELECT id FROM ai_configurations WHERE id = ?");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Configuration not found']);
            return;
        }
        
        // Update configuration
        $updateValues[] = $id;
        $sql = "UPDATE ai_configurations SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);
        
        error_log("AI Config API - Successfully updated configuration ID: $id");
        
        echo json_encode([
            'success' => true,
            'message' => 'AI configuration updated successfully',
            'id' => $id
        ]);
        
    } catch (Exception $e) {
        error_log("AI Config API - Error updating configuration: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update AI configuration', 'debug' => $e->getMessage()]);
    }
}

function handleDelete($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing configuration ID']);
        return;
    }

    $id = (int)$input['id'];

    try {
        // Check if configuration exists
        $stmt = $pdo->prepare("SELECT analysis_type, prompt_version FROM ai_configurations WHERE id = ?");
        $stmt->execute([$id]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            http_response_code(404);
            echo json_encode(['error' => 'Configuration not found']);
            return;
        }
        
        // Delete configuration
        $stmt = $pdo->prepare("DELETE FROM ai_configurations WHERE id = ?");
        $stmt->execute([$id]);
        
        error_log("AI Config API - Successfully deleted configuration: {$config['analysis_type']} ({$config['prompt_version']}) with ID: $id");
        
        echo json_encode([
            'success' => true,
            'message' => 'AI configuration deleted successfully',
            'deleted_config' => $config
        ]);
        
    } catch (Exception $e) {
        error_log("AI Config API - Error deleting configuration: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete AI configuration', 'debug' => $e->getMessage()]);
    }
}
?>
