<?php
// src/services/AIService.php

require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/DataAggregator.php';

class AIService {
    private $db;
    private $openai_api_key;
    private $base_url = 'https://api.openai.com/v1/chat/completions';
    private $dataAggregator;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->dataAggregator = new DataAggregator();
        
        // Load config
        require_once __DIR__ . '/../config/config.php';
        
        // Load OpenAI API key from configuration
        $this->openai_api_key = $this->getOpenAIApiKey();
        
        if (!$this->openai_api_key) {
            throw new Exception('OpenAI API key not configured');
        }
    }
    
    /**
     * Analyze application with AI
     */
    public function analyzeApplication($application_id, $analysis_type, $force_refresh = false) {
        $start_time = microtime(true);
        
        try {
            // Validate analysis type
            $valid_types = ['summary', 'timeline', 'risk_assessment', 'relationship_analysis', 'trend_analysis'];
            if (!in_array($analysis_type, $valid_types)) {
                throw new Exception('Invalid analysis type');
            }
            
            // Gather application context
            $context_data = $this->dataAggregator->gatherApplicationContext($application_id);
            
            // Debug logging
            error_log("AIService analyzeApplication - Context data gathered for app $application_id");
            error_log("Work notes structure: " . json_encode($context_data['work_notes']));
            
            $input_hash = $this->generateInputHash($context_data, $analysis_type);
            
            // Check for cached result (unless force refresh)
            if (!$force_refresh) {
                $cached_result = $this->getCachedAnalysis($application_id, $analysis_type, $input_hash);
                if ($cached_result) {
                    return $cached_result;
                }
            }
            
            // Get AI configuration for this analysis type
            $ai_config = $this->getAIConfiguration($analysis_type);
            if (!$ai_config) {
                throw new Exception('AI configuration not found for analysis type: ' . $analysis_type);
            }
            
            // Prepare prompt
            $prompt = $this->buildPrompt($ai_config['prompt_template'], $context_data);
            
            // Call OpenAI API
            $ai_response = $this->callOpenAI(
                $prompt, 
                $ai_config['model_name'],
                json_decode($ai_config['model_parameters'], true) ?? []
            );
            
            $processing_time = round((microtime(true) - $start_time) * 1000);
            
            // Parse and validate response
            $analysis_result = $this->parseAIResponse($ai_response, $analysis_type);
            
            // Save analysis result
            $analysis_id = $this->saveAnalysisResult(
                $application_id,
                $analysis_type,
                $ai_config['model_name'],
                $ai_config['prompt_version'],
                $input_hash,
                $analysis_result,
                $processing_time,
                $ai_response['usage']['total_tokens'] ?? null
            );
            
            // Log usage
            $this->logAPIUsage(
                $_SESSION['user_id'] ?? null,
                $application_id,
                $analysis_type,
                $ai_config['model_name'],
                $ai_response['usage']['total_tokens'] ?? 0,
                $processing_time,
                'success'
            );
            
            return [
                'id' => $analysis_id,
                'analysis_type' => $analysis_type,
                'result' => $analysis_result,
                'processing_time_ms' => $processing_time,
                'cached' => false,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $processing_time = round((microtime(true) - $start_time) * 1000);
            
            // Log error
            $this->logAPIUsage(
                $_SESSION['user_id'] ?? null,
                $application_id,
                $analysis_type,
                'unknown',
                0,
                $processing_time,
                'error',
                $e->getMessage()
            );
            
            throw $e;
        }
    }
    
    /**
     * Call OpenAI API
     */
    private function callOpenAI($prompt, $model = 'gpt-3.5-turbo', $parameters = []) {
        $default_params = [
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];
        
        $params = array_merge($default_params, $parameters);
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert business analyst specializing in application lifecycle management and IT operations. Provide detailed, structured analysis based on the data provided.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $params['temperature'],
            'max_tokens' => $params['max_tokens'],
            'top_p' => $params['top_p'],
            'frequency_penalty' => $params['frequency_penalty'],
            'presence_penalty' => $params['presence_penalty']
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->base_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->openai_api_key
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            curl_close($ch);
            throw new Exception('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('OpenAI API error: HTTP ' . $http_code . ' - ' . $response);
        }
        
        $decoded_response = json_decode($response, true);
        
        if (!$decoded_response || !isset($decoded_response['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response from OpenAI API');
        }
        
        return $decoded_response;
    }
    
    /**
     * Build prompt from template and context data
     */
    private function buildPrompt($template, $context_data) {
        // Debug logging to see what data we receive
        error_log("AIService buildPrompt - work_notes data structure: " . json_encode($context_data['work_notes']));
        
        // Enhanced multilingual context processing
        $work_notes_summary = '';
        
        // Check multiple possible structures for work notes
        $work_notes_data = null;
        if (!empty($context_data['work_notes']['notes'])) {
            $work_notes_data = $context_data['work_notes']['notes'];
        } elseif (!empty($context_data['work_notes']) && is_array($context_data['work_notes']) && isset($context_data['work_notes'][0])) {
            // Handle case where work_notes is a direct array
            $work_notes_data = $context_data['work_notes'];
        }
        
        if (!empty($work_notes_data)) {
            $total_notes = count($work_notes_data);
            $summary = $context_data['work_notes']['summary'] ?? [];
            $languages = $summary['languages_detected'] ?? [];
            $has_multilingual = $summary['has_multilingual_content'] ?? false;
            
            $work_notes_summary .= "Total work notes found: {$total_notes}\n";
            
            if ($has_multilingual && !empty($languages)) {
                $work_notes_summary .= "Languages detected: " . implode(', ', $languages) . "\n";
                $work_notes_summary .= "Note: This application contains multilingual content. Please analyze both Norwegian and English text, and provide the analysis in English while preserving the meaning of Norwegian content.\n\n";
            }
            
            // Add recent notes with language context
            $work_notes_summary .= "Recent activity (latest notes):\n";
            foreach (array_slice($work_notes_data, 0, 10) as $note) {
                $timestamp = $note['created_at'] ?? 'Unknown';
                $type = $note['type'] ?? 'unknown';
                $content = $note['note'] ?? '';
                $user = $note['user_email'] ?? 'unknown';
                
                $work_notes_summary .= "- [{$timestamp}] {$type} by {$user}: {$content}\n";
            }
        } else {
            $work_notes_summary = "No work notes found for this application.";
            error_log("AIService buildPrompt - No work notes found. Data structure: " . json_encode($context_data['work_notes']));
        }

        // Process User Stories data with business needs focus
        $user_stories_summary = '';
        $user_stories_data = $context_data['user_stories'] ?? [];
        
        if (!empty($user_stories_data['stories'])) {
            $stories = $user_stories_data['stories'];
            $summary = $user_stories_data['summary'] ?? [];
            
            $total_stories = count($stories);
            $user_stories_summary .= "Business Requirements Analysis from User Stories ({$total_stories} total):\n\n";
            
            // Add completion insights with better context
            if (!empty($summary['completion_insights'])) {
                $insights = $summary['completion_insights'];
                $completed = $insights['completion_rate'] ?? 0;
                $in_progress = $insights['in_progress_rate'] ?? 0;
                $backlog = $insights['backlog_rate'] ?? 0;
                $user_stories_summary .= "Development Progress: {$completed}% completed, {$in_progress}% in progress, {$backlog}% in backlog\n";
            }
            
            // Add priority breakdown for planning context
            if (!empty($summary['by_priority'])) {
                $priorities = [];
                foreach ($summary['by_priority'] as $priority => $count) {
                    $priorities[] = "{$count} {$priority} priority";
                }
                $user_stories_summary .= "Requirements Priority Distribution: " . implode(', ', $priorities) . "\n";
            }
            
            // Add business value themes for context
            if (!empty($summary['business_value_themes'])) {
                $themes = [];
                foreach ($summary['business_value_themes'] as $theme => $count) {
                    $themes[] = "{$theme} ({$count})";
                }
                $user_stories_summary .= "Business Value Areas: " . implode(', ', $themes) . "\n\n";
            }
            
            // Add business needs and planned solutions
            $user_stories_summary .= "Key Business Needs and Planned Solutions:\n";
            foreach (array_slice($stories, 0, 8) as $story) {
                $title = $story['title'] ?? 'Untitled';
                $status = $story['status'] ?? 'unknown';
                $priority = $story['priority'] ?? 'unknown';
                $role = $story['role'] ?? '';
                $want_to = $story['want_to'] ?? '';
                $so_that = $story['so_that'] ?? '';
                
                if (!empty($role) && !empty($want_to) && !empty($so_that)) {
                    $user_stories_summary .= "- [{$status}] Business Need: As a {$role}, I want to {$want_to}, so that {$so_that}\n";
                } else {
                    $user_stories_summary .= "- [{$status}] {$title}\n";
                }
            }
        } else {
            $user_stories_summary = "No user stories or business requirements documentation found for this application.";
        }
        
        // Process DataMap diagram data with enhanced DrawFlow parsing
        $datamap_summary = '';
        $datamap_data = $context_data['datamap_diagram'] ?? [];
        
        if (!empty($datamap_data['has_diagram']) && $datamap_data['has_diagram']) {
            $analysis = $datamap_data['analysis'];
            $datamap_summary .= "DataMap Integration Architecture Analysis:\n";
            $datamap_summary .= "This application uses DrawFlow (https://github.com/jerosoler/Drawflow) to visualize integration architecture with {$analysis['node_count']} components.\n\n";
            
            // Extract actual system names and descriptions from raw DrawFlow data
            $systems_info = [];
            if (!empty($datamap_data['raw_data']['drawflow']['Home']['data'])) {
                $datamap_summary .= "Integration Components and Data Flow:\n";
                foreach ($datamap_data['raw_data']['drawflow']['Home']['data'] as $node_id => $node) {
                    $node_data = $node['data'] ?? [];
                    $system_title = $node_data['title'] ?? 'Unknown System';
                    $system_description = $node_data['description'] ?? 'No description';
                    $system_type = $node_data['type'] ?? 'unknown';
                    
                    // Store for connection analysis
                    $systems_info[$node_id] = [
                        'title' => $system_title,
                        'type' => $system_type,
                        'description' => $system_description
                    ];
                    
                    $datamap_summary .= "- {$system_title} ({$system_type}): {$system_description}\n";
                }
                $datamap_summary .= "\n";
                
                // Analyze data flow connections using actual system names
                $datamap_summary .= "Data Flow Architecture:\n";
                foreach ($datamap_data['raw_data']['drawflow']['Home']['data'] as $node_id => $node) {
                    $source_system = $systems_info[$node_id]['title'] ?? 'Unknown';
                    $outputs = $node['outputs'] ?? [];
                    
                    foreach ($outputs as $output_key => $output_data) {
                        $connections = $output_data['connections'] ?? [];
                        foreach ($connections as $connection) {
                            $target_node_id = $connection['node'] ?? null;
                            if ($target_node_id && isset($systems_info[$target_node_id])) {
                                $target_system = $systems_info[$target_node_id]['title'];
                                $target_type = $systems_info[$target_node_id]['type'];
                                $datamap_summary .= "- Data flows from {$source_system} → {$target_system} ({$target_type})\n";
                            }
                        }
                    }
                }
                $datamap_summary .= "\n";
                
                // Group systems by type with actual names
                $systems_by_type = [];
                foreach ($systems_info as $system) {
                    $type = $system['type'];
                    if (!isset($systems_by_type[$type])) {
                        $systems_by_type[$type] = [];
                    }
                    $systems_by_type[$type][] = $system['title'];
                }
                
                foreach ($systems_by_type as $type => $system_names) {
                    $count = count($system_names);
                    $names_list = implode(', ', $system_names);
                    $datamap_summary .= ucfirst(str_replace('_', ' ', $type)) . " ({$count}): {$names_list}\n";
                }
            }
            
            // Connection summary for integration complexity
            $connection_count = count($analysis['connections']);
            $datamap_summary .= "\nIntegration Complexity: {$connection_count} connections between systems\n";
            
            // Add comment analysis - NEW FEATURE
            if (!empty($analysis['comments']) || !empty($analysis['comment_connections'])) {
                $datamap_summary .= "\nArchitecture Comments and Context:\n";
                
                // Include standalone comments
                if (!empty($analysis['comments'])) {
                    foreach ($analysis['comments'] as $comment) {
                        $context_label = ucfirst($comment['context']);
                        $datamap_summary .= "- [{$context_label}] {$comment['text']}\n";
                    }
                }
                
                // Include comments connected to specific systems
                if (!empty($analysis['comment_connections'])) {
                    foreach ($analysis['comment_connections'] as $conn) {
                        $context_label = ucfirst($conn['comment_context']);
                        $connected_systems = array_column($conn['connected_systems'], 'system_name');
                        $systems_list = implode(', ', $connected_systems);
                        
                        if (!empty($systems_list)) {
                            $datamap_summary .= "- [{$context_label}] \"{$conn['comment_text']}\" → Connected to: {$systems_list}\n";
                        } else {
                            $datamap_summary .= "- [{$context_label}] {$conn['comment_text']}\n";
                        }
                    }
                }
                $datamap_summary .= "\n";
            }
            
            // Additional notes for business context
            if (!empty($datamap_data['notes'])) {
                $datamap_summary .= "Architecture Notes: {$datamap_data['notes']}\n";
            }
            
        } else {
            $datamap_summary = "No DataMap integration diagram found. This application may not have defined integration architecture yet or operates independently.";
        }
        
        $replacements = [
            '{application_data}' => json_encode($context_data['application'], JSON_PRETTY_PRINT),
            '{work_notes}' => $work_notes_summary,
            '{user_stories}' => $user_stories_summary,
            '{datamap_diagram}' => $datamap_summary,
            '{work_notes_raw}' => json_encode($context_data['work_notes'], JSON_PRETTY_PRINT),
            '{user_stories_raw}' => json_encode($context_data['user_stories'], JSON_PRETTY_PRINT),
            '{datamap_raw}' => json_encode($context_data['datamap_diagram'], JSON_PRETTY_PRINT),
            '{relationships}' => json_encode($context_data['relationships'], JSON_PRETTY_PRINT),
            '{audit_history}' => json_encode($context_data['audit_history'], JSON_PRETTY_PRINT),
            '{attachments}' => json_encode($context_data['attachments'], JSON_PRETTY_PRINT)
        ];
        
        // Add specific guidance for application data interpretation
        $processed_template = $template;
        $processed_template .= "\n\n**DATA INTERPRETATION GUIDANCE:**\n";
        $processed_template .= "- 'assigned_to' field indicates data maintenance responsibility, NOT project participation - exclude from project team mentions\n";
        $processed_template .= "- 'handover_status' is always a percentage (0-100) representing completion of handover process\n";
        $processed_template .= "- Focus on 'project_manager' and 'product_owner' for actual project leadership roles\n";
        $processed_template .= "- All specific system names from DataMap diagram should be mentioned by name in integration discussions\n";
        $processed_template .= "- User Stories represent actual business needs and planned solutions - integrate this context into business requirements discussion\n";
        $processed_template .= "- DataMap Comments provide critical architectural context - categorized as technical, business, risk, implementation, or documentation notes\n";
        $processed_template .= "- Comments connected to specific systems indicate important considerations for those integrations\n";
        $processed_template .= "- Risk-category comments from DataMap should be highlighted as potential project concerns\n";
        $processed_template .= "- Implementation-category comments indicate planned work or development tasks\n";
        
        // Add debug information for DrawFlow data if available
        if (!empty($datamap_data['has_diagram']) && !empty($datamap_data['raw_data']['drawflow']['Home']['data'])) {
            $processed_template .= "\n**DRAWFLOW DATA AVAILABLE - SYSTEM NAMES TO USE:**\n";
            foreach ($datamap_data['raw_data']['drawflow']['Home']['data'] as $node_id => $node) {
                $node_data = $node['data'] ?? [];
                $node_class = $node['class'] ?? '';
                $title = $node_data['title'] ?? 'Unknown';
                $description = $node_data['description'] ?? '';
                $type = $node_data['type'] ?? 'unknown';
                
                // Skip comment nodes in system list (they're handled separately)
                if (strpos($node_class, 'comment-node') !== false) {
                    continue;
                }
                
                $processed_template .= "- {$title} ({$type}): {$description}\n";
            }
            
            // Add comment context if available
            if (!empty($datamap_data['analysis']['comments']) || !empty($datamap_data['analysis']['comment_connections'])) {
                $processed_template .= "\n**ARCHITECTURE COMMENTS TO CONSIDER:**\n";
                
                // Standalone comments
                if (!empty($datamap_data['analysis']['comments'])) {
                    foreach ($datamap_data['analysis']['comments'] as $comment) {
                        $context = ucfirst($comment['context']);
                        $processed_template .= "- [{$context}] {$comment['text']}\n";
                    }
                }
                
                // Connected comments
                if (!empty($datamap_data['analysis']['comment_connections'])) {
                    foreach ($datamap_data['analysis']['comment_connections'] as $conn) {
                        $context = ucfirst($conn['comment_context']);
                        $systems = array_column($conn['connected_systems'], 'system_name');
                        $systems_text = !empty($systems) ? ' (relates to: ' . implode(', ', $systems) . ')' : '';
                        $processed_template .= "- [{$context}] {$conn['comment_text']}{$systems_text}\n";
                    }
                }
            }
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $processed_template);
    }
    
    /**
     * Parse AI response based on analysis type
     */
    private function parseAIResponse($ai_response, $analysis_type) {
        $content = $ai_response['choices'][0]['message']['content'];
        
        // Try to parse as JSON first
        $json_result = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return [
                'type' => 'structured',
                'data' => $json_result,
                'raw_content' => $content
            ];
        } else {
            // If not valid JSON, return as text with basic parsing
            return [
                'type' => 'text',
                'data' => [
                    'analysis' => $content,
                    'sections' => $this->extractSections($content)
                ],
                'raw_content' => $content
            ];
        }
    }
    
    /**
     * Extract sections from text response
     */
    private function extractSections($content) {
        $sections = [];
        $lines = explode("\n", $content);
        $current_section = null;
        $current_content = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if line is a section header (starts with number or contains ":")
            if (preg_match('/^(\d+\.|[A-Z][^:]*:)/', $line)) {
                // Save previous section
                if ($current_section) {
                    $sections[$current_section] = implode("\n", $current_content);
                }
                
                // Start new section
                $current_section = preg_replace('/^(\d+\.\s*|:\s*$)/', '', $line);
                $current_content = [];
            } else {
                $current_content[] = $line;
            }
        }
        
        // Save last section
        if ($current_section) {
            $sections[$current_section] = implode("\n", $current_content);
        }
        
        return $sections;
    }
    
    /**
     * Generate hash for input data
     */
    private function generateInputHash($context_data, $analysis_type) {
        $hash_data = [
            'analysis_type' => $analysis_type,
            'context' => $context_data
        ];
        return hash('sha256', json_encode($hash_data));
    }
    
    /**
     * Get cached analysis result
     */
    private function getCachedAnalysis($application_id, $analysis_type, $input_hash) {
        $sql = "
            SELECT id, analysis_result, created_at, processing_time_ms, token_count
            FROM ai_analysis 
            WHERE application_id = ? 
            AND analysis_type = ? 
            AND input_data_hash = ?
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY created_at DESC 
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$application_id, $analysis_type, $input_hash]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cached) {
            return [
                'id' => $cached['id'],
                'analysis_type' => $analysis_type,
                'result' => json_decode($cached['analysis_result'], true),
                'processing_time_ms' => $cached['processing_time_ms'],
                'cached' => true,
                'created_at' => $cached['created_at']
            ];
        }
        
        return null;
    }
    
    /**
     * Save analysis result to database
     */
    private function saveAnalysisResult($application_id, $analysis_type, $model, $prompt_version, $input_hash, $result, $processing_time, $token_count) {
        $sql = "
            INSERT INTO ai_analysis (
                application_id, analysis_type, ai_model, prompt_version, 
                input_data_hash, analysis_result, processing_time_ms, 
                token_count, created_by, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $application_id,
            $analysis_type,
            $model,
            $prompt_version,
            $input_hash,
            json_encode($result),
            $processing_time,
            $token_count,
            $_SESSION['user_id'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get AI configuration for analysis type
     */
    private function getAIConfiguration($analysis_type) {
        $sql = "
            SELECT * FROM ai_configurations 
            WHERE analysis_type = ? AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$analysis_type]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Log API usage
     */
    private function logAPIUsage($user_id, $application_id, $analysis_type, $model, $tokens, $processing_time, $status, $error_message = null) {
        $sql = "
            INSERT INTO ai_usage_log (
                user_id, application_id, analysis_type, model_used, 
                tokens_used, processing_time_ms, status, error_message
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $user_id,
            $application_id,
            $analysis_type,
            $model,
            $tokens,
            $processing_time,
            $status,
            $error_message
        ]);
    }
    
    /**
     * Get OpenAI API key from configuration
     */
    private function getOpenAIApiKey() {
        $ai_config = AI_CONFIG;
        return $ai_config['openai_api_key'];
    }
    
    /**
     * Get recent analysis for application
     */
    public function getRecentAnalysis($application_id, $limit = 5) {
        $sql = "
            SELECT id, analysis_type, analysis_result, created_at, processing_time_ms, 
                   JSON_EXTRACT(analysis_result, '$.type') as result_type
            FROM ai_analysis 
            WHERE application_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$application_id, $limit]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse analysis_result JSON for each result
        foreach ($results as &$result) {
            if ($result['analysis_result']) {
                $result['analysis_result'] = json_decode($result['analysis_result'], true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get recent analysis by type
     */
    public function getAnalysisByType($application_id, $analysis_type, $limit = 5) {
        $sql = "
            SELECT id, analysis_type, analysis_result, created_at, processing_time_ms, 
                   JSON_EXTRACT(analysis_result, '$.type') as result_type
            FROM ai_analysis 
            WHERE application_id = ? AND analysis_type = ?
            ORDER BY created_at DESC 
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$application_id, $analysis_type, $limit]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse analysis_result JSON for each result
        foreach ($results as &$result) {
            if ($result['analysis_result']) {
                $result['analysis_result'] = json_decode($result['analysis_result'], true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get analysis by ID
     */
    public function getAnalysisById($analysis_id) {
        $sql = "
            SELECT * FROM ai_analysis 
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$analysis_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $result['analysis_result'] = json_decode($result['analysis_result'], true);
        }
        
        return $result;
    }
}
?>
