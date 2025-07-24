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

        // Process User Stories data
        $user_stories_summary = '';
        $user_stories_data = $context_data['user_stories'] ?? [];
        
        if (!empty($user_stories_data['stories'])) {
            $stories = $user_stories_data['stories'];
            $summary = $user_stories_data['summary'] ?? [];
            
            $total_stories = count($stories);
            $user_stories_summary .= "Total user stories found: {$total_stories}\n";
            
            // Add completion insights
            if (!empty($summary['completion_insights'])) {
                $insights = $summary['completion_insights'];
                $user_stories_summary .= "Story completion: {$insights['completion_rate']}% done, {$insights['in_progress_rate']}% in progress, {$insights['backlog_rate']}% in backlog\n";
            }
            
            // Add priority breakdown
            if (!empty($summary['by_priority'])) {
                $priorities = [];
                foreach ($summary['by_priority'] as $priority => $count) {
                    $priorities[] = "{$priority}: {$count}";
                }
                $user_stories_summary .= "Priority breakdown: " . implode(', ', $priorities) . "\n";
            }
            
            // Add business value themes
            if (!empty($summary['business_value_themes'])) {
                $themes = [];
                foreach ($summary['business_value_themes'] as $theme => $count) {
                    $themes[] = "{$theme}: {$count}";
                }
                $user_stories_summary .= "Business value themes: " . implode(', ', $themes) . "\n\n";
            }
            
            // Add detailed stories (top 10)
            $user_stories_summary .= "Key User Stories:\n";
            foreach (array_slice($stories, 0, 10) as $story) {
                $title = $story['title'] ?? 'Untitled';
                $status = $story['status'] ?? 'unknown';
                $priority = $story['priority'] ?? 'unknown';
                $role = $story['role'] ?? '';
                $want_to = $story['want_to'] ?? '';
                $so_that = $story['so_that'] ?? '';
                
                $user_stories_summary .= "- [{$status}/{$priority}] {$title}\n";
                $user_stories_summary .= "  As a {$role}, I want to {$want_to}, so that {$so_that}\n";
            }
        } else {
            $user_stories_summary = "No user stories found for this application.";
        }
        
        $replacements = [
            '{application_data}' => json_encode($context_data['application'], JSON_PRETTY_PRINT),
            '{work_notes}' => $work_notes_summary,
            '{user_stories}' => $user_stories_summary,
            '{work_notes_raw}' => json_encode($context_data['work_notes'], JSON_PRETTY_PRINT),
            '{user_stories_raw}' => json_encode($context_data['user_stories'], JSON_PRETTY_PRINT),
            '{relationships}' => json_encode($context_data['relationships'], JSON_PRETTY_PRINT),
            '{audit_history}' => json_encode($context_data['audit_history'], JSON_PRETTY_PRINT),
            '{attachments}' => json_encode($context_data['attachments'], JSON_PRETTY_PRINT)
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
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
