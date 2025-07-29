<?php
// src/services/DataAggregator.php

require_once __DIR__ . '/../db/db.php';

class DataAggregator {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Check if a table exists in the database
     */
    private function tableExists($tableName) {
        try {
            // Use direct string interpolation instead of prepared statement for SHOW TABLES
            // Escape the table name to prevent SQL injection
            $escapedTableName = $this->db->quote($tableName);
            $sql = "SHOW TABLES LIKE $escapedTableName";
            $stmt = $this->db->query($sql);
            $result = $stmt->rowCount() > 0;
            error_log("DataAggregator tableExists('$tableName'): " . ($result ? 'true' : 'false'));
            return $result;
        } catch (Exception $e) {
            error_log("DataAggregator tableExists('$tableName') error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gather comprehensive application context for AI analysis
     */
    public function gatherApplicationContext($application_id) {
        return [
            'application' => $this->getApplicationData($application_id),
            'work_notes' => $this->getWorkNotesData($application_id),
            'user_stories' => $this->getUserStoriesData($application_id),
            'relationships' => $this->getRelationshipData($application_id),
            'audit_history' => $this->getAuditHistory($application_id),
            'attachments' => $this->getAttachmentSummary($application_id),
            'datamap_diagram' => $this->getDataMapDiagram($application_id),
            'context_timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get complete application data
     */
    public function getApplicationData($application_id) {
        $sql = "
            SELECT 
                id,
                short_description,
                application_service,
                relevant_for,
                phase,
                status,
                handover_status,
                contract_number,
                contract_responsible,
                information_space,
                ba_sharepoint_list,
                relationship_yggdrasil,
                assigned_to,
                preops_portfolio,
                application_portfolio,
                delivery_responsible,
                corporator_link,
                project_manager,
                product_owner,
                due_date,
                deployment_model,
                integrations,
                sa_document,
                business_need
            FROM applications 
            WHERE id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$application_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            throw new Exception('Application not found');
        }
        
        // Process relationships to include names
        if (!empty($application['relationship_yggdrasil'])) {
            $application['related_applications'] = $this->getRelatedApplicationNames(
                $application['relationship_yggdrasil']
            );
        }
        
        return $application;
    }
    
    /**
     * Get work notes data with user information
     */
    public function getWorkNotesData($application_id, $limit = 50) {
        try {
            // Enhanced debugging for work_notes table
            if (!$this->tableExists('work_notes')) {
                error_log("DataAggregator: work_notes table does not exist");
                return [
                    'notes' => [],
                    'summary' => [
                        'total_count' => 0,
                        'by_type' => [],
                        'recent_activity' => false,
                        'has_attachments' => false,
                        'debug_info' => 'work_notes table not found'
                    ]
                ];
            }
            
            // Enhanced SQL query with better error handling
            $sql = "
                SELECT 
                    wn.id,
                    wn.note,
                    wn.type,
                    wn.priority,
                    wn.attachment_filename,
                    wn.attachment_size,
                    wn.attachment_mime_type,
                    wn.created_at,
                    wn.updated_at,
                    wn.is_visible,
                    u.email as user_email
                FROM work_notes wn
                LEFT JOIN users u ON wn.user_id = u.id
                WHERE wn.application_id = ? 
                AND wn.is_visible = 1
                ORDER BY wn.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$application_id, $limit]);
            $work_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Enhanced debugging
            error_log("DataAggregator: Found " . count($work_notes) . " work notes for application_id: " . $application_id);
            
            // Group by type and add summary statistics with multilingual support
            $summary = [
                'total_count' => count($work_notes),
                'by_type' => [],
                'by_priority' => [],
                'recent_activity' => array_slice($work_notes, 0, 10),
                'notes' => $work_notes,
                'has_multilingual_content' => false,
                'languages_detected' => []
            ];
            
            foreach ($work_notes as $note) {
                // Type statistics
                $summary['by_type'][$note['type']] = ($summary['by_type'][$note['type']] ?? 0) + 1;
                $summary['by_priority'][$note['priority']] = ($summary['by_priority'][$note['priority']] ?? 0) + 1;
                
                // Detect language patterns (Norwegian vs English)
                if (!empty($note['note'])) {
                    $noteText = strtolower($note['note']);
                    
                    // Norwegian indicators
                    $norwegianWords = ['og', 'med', 'på', 'til', 'for', 'av', 'det', 'er', 'ikke', 'som', 'må', 'også', 'denne', 'dette'];
                    $norwegianCount = 0;
                    foreach ($norwegianWords as $word) {
                        if (strpos($noteText, ' ' . $word . ' ') !== false || strpos($noteText, $word . ' ') === 0) {
                            $norwegianCount++;
                        }
                    }
                    
                    // English indicators  
                    $englishWords = ['the', 'and', 'with', 'for', 'this', 'that', 'is', 'are', 'was', 'were', 'have', 'has', 'will', 'can'];
                    $englishCount = 0;
                    foreach ($englishWords as $word) {
                        if (strpos($noteText, ' ' . $word . ' ') !== false || strpos($noteText, $word . ' ') === 0) {
                            $englishCount++;
                        }
                    }
                    
                    if ($norwegianCount > $englishCount && $norwegianCount > 0) {
                        $summary['languages_detected'][] = 'Norwegian';
                        $summary['has_multilingual_content'] = true;
                    } elseif ($englishCount > 0) {
                        $summary['languages_detected'][] = 'English';
                    }
                }
            }
            
            // Remove duplicates from language detection
            $summary['languages_detected'] = array_unique($summary['languages_detected']);
            
            // Return consistent structure with notes and summary
            return [
                'notes' => $work_notes,
                'summary' => $summary
            ];
            
        } catch (Exception $e) {
            error_log("DataAggregator getWorkNotesData error: " . $e->getMessage());
            return [
                'notes' => [],
                'summary' => [
                    'total_count' => 0,
                    'by_type' => [],
                    'recent_activity' => false,
                    'has_attachments' => false,
                    'error' => $e->getMessage(),
                    'debug_info' => 'Exception occurred in getWorkNotesData'
                ]
            ];
        }
    }
    
    /**
     * Get relationship data with application details
     */
    public function getRelationshipData($application_id) {
        // Check if application_relations table exists
        if (!$this->tableExists('application_relations')) {
            // Fallback to simple relationship_yggdrasil field parsing
            $app_data = $this->getApplicationData($application_id);
            $relationships = [];
            
            if (!empty($app_data['relationship_yggdrasil'])) {
                $related_ids = array_map('trim', explode(',', $app_data['relationship_yggdrasil']));
                foreach ($related_ids as $related_id) {
                    if (is_numeric($related_id)) {
                        $sql = "SELECT id, short_description, application_service, phase, status FROM applications WHERE id = ?";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([$related_id]);
                        $related_app = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($related_app) {
                            $relationships[] = [
                                'id' => null,
                                'related_application_id' => $related_app['id'],
                                'relation_type' => 'related',
                                'created_at' => null,
                                'related_app_name' => $related_app['short_description'],
                                'related_app_service' => $related_app['application_service'],
                                'related_app_phase' => $related_app['phase'],
                                'related_app_status' => $related_app['status']
                            ];
                        }
                    }
                }
            }
            
            return [
                'direct_relationships' => $relationships,
                'reverse_relationships' => [],
                'summary' => [
                    'total_count' => count($relationships),
                    'by_type' => ['related' => count($relationships)]
                ]
            ];
        }
        
        // If application_relations table exists, use it
        $sql = "
            SELECT 
                ar.id,
                ar.related_application_id,
                ar.relation_type,
                a.short_description as related_app_name,
                a.application_service as related_app_service,
                a.phase as related_app_phase,
                a.status as related_app_status
            FROM application_relations ar
            JOIN applications a ON ar.related_application_id = a.id
            WHERE ar.application_id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$application_id]);
        $relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also get reverse relationships (where this app is the related one)
        $sql_reverse = "
            SELECT 
                ar.id,
                ar.application_id as related_application_id,
                ar.relation_type,
                a.short_description as related_app_name,
                a.application_service as related_app_service,
                a.phase as related_app_phase,
                a.status as related_app_status,
                'reverse' as relationship_direction
            FROM application_relations ar
            JOIN applications a ON ar.application_id = a.id
            WHERE ar.related_application_id = ?
        ";
        
        $stmt = $this->db->prepare($sql_reverse);
        $stmt->execute([$application_id]);
        $reverse_relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'outgoing_relationships' => $relationships,
            'incoming_relationships' => $reverse_relationships,
            'total_relationships' => count($relationships) + count($reverse_relationships),
            'relationship_types' => $this->summarizeRelationshipTypes($relationships, $reverse_relationships)
        ];
    }
    
    /**
     * Get audit history with meaningful descriptions
     */
    public function getAuditHistory($application_id, $limit = 100) {
        // Check if audit_log table exists
        if (!$this->tableExists('audit_log')) {
            return [
                'entries' => [],
                'summary' => [
                    'total_changes' => 0,
                    'recent_activity' => false,
                    'most_changed_fields' => [],
                    'change_frequency' => 'low'
                ]
            ];
        }
        
        $sql = "
            SELECT 
                al.id,
                al.field_name,
                al.old_value,
                al.new_value,
                al.changed_at,
                al.action,
                u.email as changed_by_email
            FROM audit_log al
            LEFT JOIN users u ON al.changed_by = u.id
            WHERE al.table_name = 'applications' 
            AND al.record_id = ?
            ORDER BY al.changed_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$application_id, $limit]);
        $audit_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process entries to make them more readable
        $processed_entries = [];
        foreach ($audit_entries as $entry) {
            $processed_entry = $entry;
            $processed_entry['human_readable'] = $this->generateHumanReadableChange(
                $entry['field_name'], 
                $entry['old_value'], 
                $entry['new_value']
            );
            $processed_entries[] = $processed_entry;
        }
        
        return [
            'total_changes' => count($processed_entries),
            'recent_changes' => array_slice($processed_entries, 0, 10),
            'all_changes' => $processed_entries,
            'field_change_frequency' => $this->calculateChangeFrequency($processed_entries)
        ];
    }
    
    /**
     * Get attachment summary
     */
    public function getAttachmentSummary($application_id) {
        // Check if work_notes table exists
        if (!$this->tableExists('work_notes')) {
            return [
                'summary' => [
                    'total_attachments' => 0,
                    'total_size' => 0,
                    'file_types' => null,
                    'avg_file_size' => 0,
                    'latest_attachment' => null,
                    'earliest_attachment' => null
                ],
                'attachments' => [],
                'file_types_array' => []
            ];
        }
        
        $sql = "
            SELECT 
                COUNT(*) as total_attachments,
                SUM(attachment_size) as total_size,
                GROUP_CONCAT(DISTINCT attachment_mime_type) as file_types,
                AVG(attachment_size) as avg_file_size,
                MAX(created_at) as latest_attachment,
                MIN(created_at) as earliest_attachment
            FROM work_notes 
            WHERE application_id = ? 
            AND attachment_filename IS NOT NULL
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$application_id]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get individual attachment details
        $sql_details = "
            SELECT 
                attachment_filename,
                attachment_size,
                attachment_mime_type,
                created_at,
                note
            FROM work_notes 
            WHERE application_id = ? 
            AND attachment_filename IS NOT NULL
            ORDER BY created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql_details);
        $stmt->execute([$application_id]);
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'summary' => $summary,
            'attachments' => $attachments,
            'file_types_array' => $summary['file_types'] ? explode(',', $summary['file_types']) : []
        ];
    }
    
    /**
     * Get related application names from comma-separated IDs
     */
    private function getRelatedApplicationNames($relationship_ids) {
        if (empty($relationship_ids)) {
            return [];
        }
        
        $ids = array_map('trim', explode(',', $relationship_ids));
        $ids = array_filter($ids, 'is_numeric');
        
        if (empty($ids)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, short_description, application_service FROM applications WHERE id IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Summarize relationship types
     */
    private function summarizeRelationshipTypes($outgoing, $incoming) {
        $types = [];
        
        foreach ($outgoing as $rel) {
            $type = $rel['relation_type'] ?? 'unspecified';
            $types[$type] = ($types[$type] ?? 0) + 1;
        }
        
        foreach ($incoming as $rel) {
            $type = ($rel['relation_type'] ?? 'unspecified') . '_incoming';
            $types[$type] = ($types[$type] ?? 0) + 1;
        }
        
        return $types;
    }
    
    /**
     * Generate human-readable change description
     */
    private function generateHumanReadableChange($field_name, $old_value, $new_value) {
        // Handle relationship_yggdrasil specially
        if ($field_name === 'relationship_yggdrasil') {
            $old_names = $this->convertIdsToNames($old_value);
            $new_names = $this->convertIdsToNames($new_value);
            
            if (empty($old_value)) {
                return "Related applications set to: " . $new_names;
            } elseif (empty($new_value)) {
                return "Related applications cleared (was: " . $old_names . ")";
            } else {
                return "Related applications changed from \"" . $old_names . "\" to \"" . $new_names . "\"";
            }
        }
        
        // Standard field changes
        $field_label = ucfirst(str_replace('_', ' ', $field_name));
        
        if (empty($old_value)) {
            return "$field_label set to: $new_value";
        } elseif (empty($new_value)) {
            return "$field_label cleared (was: $old_value)";
        } else {
            return "$field_label changed from \"$old_value\" to \"$new_value\"";
        }
    }
    
    /**
     * Convert IDs to application names (for relationships)
     */
    private function convertIdsToNames($ids_string) {
        if (empty($ids_string)) {
            return '';
        }
        
        $related_apps = $this->getRelatedApplicationNames($ids_string);
        $names = array_column($related_apps, 'short_description');
        return implode(', ', $names);
    }
    
    /**
     * Calculate change frequency by field
     */
    private function calculateChangeFrequency($audit_entries) {
        $frequency = [];
        
        foreach ($audit_entries as $entry) {
            $field = $entry['field_name'];
            $frequency[$field] = ($frequency[$field] ?? 0) + 1;
        }
        
        return $frequency;
    }
    
    /**
     * Get application activity timeline
     */
    public function getActivityTimeline($application_id, $days = 30) {
        $activities = [];
        
        // Add work notes if table exists
        if ($this->tableExists('work_notes')) {
            $sql = "
                SELECT 
                    'work_note' as activity_type,
                    created_at as activity_date,
                    type as activity_subtype,
                    note as activity_description
                FROM work_notes 
                WHERE application_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND is_visible = 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$application_id, $days]);
            $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        // Add audit log if table exists
        if ($this->tableExists('audit_log')) {
            $sql = "
                SELECT 
                    'audit_log' as activity_type,
                    changed_at as activity_date,
                    field_name as activity_subtype,
                    CONCAT(field_name, ' changed') as activity_description
                FROM audit_log 
                WHERE table_name = 'applications' 
                AND record_id = ?
                AND changed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$application_id, $days]);
            $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        // Sort by activity_date DESC
        usort($activities, function($a, $b) {
            return strtotime($b['activity_date']) - strtotime($a['activity_date']);
        });
        
        return $activities;
    }

    /**
     * Get user stories data for the application
     */
    public function getUserStoriesData($application_id, $limit = 50) {
        try {
            // Check if user_stories table exists
            if (!$this->tableExists('user_stories')) {
                error_log("DataAggregator: user_stories table does not exist");
                return [
                    'stories' => [],
                    'summary' => [
                        'total_count' => 0,
                        'by_status' => [],
                        'by_priority' => [],
                        'by_category' => [],
                        'recent_activity' => false,
                        'debug_info' => 'user_stories table not found'
                    ]
                ];
            }

            // Get user stories for the application
            $sql = "
                SELECT 
                    us.id,
                    us.title,
                    us.role,
                    us.want_to,
                    us.so_that,
                    us.priority,
                    us.status,
                    us.category,
                    us.tags,
                    us.jira_id,
                    us.jira_url,
                    us.sharepoint_url,
                    us.source,
                    us.created_at,
                    us.updated_at,
                    u.display_name as created_by_name,
                    u.email as created_by_email
                FROM user_stories us
                LEFT JOIN users u ON us.created_by = u.id
                WHERE FIND_IN_SET(?, us.application_id) > 0
                ORDER BY us.created_at DESC
                LIMIT ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$application_id, $limit]);
            $user_stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("DataAggregator: Found " . count($user_stories) . " user stories for application_id: " . $application_id);

            // Build summary statistics
            $summary = [
                'total_count' => count($user_stories),
                'by_status' => [],
                'by_priority' => [],
                'by_category' => [],
                'recent_activity' => array_slice($user_stories, 0, 5),
                'completion_insights' => [],
                'business_value_themes' => []
            ];

            // Group by different dimensions
            foreach ($user_stories as $story) {
                // Status statistics
                $summary['by_status'][$story['status']] = ($summary['by_status'][$story['status']] ?? 0) + 1;
                
                // Priority statistics
                $summary['by_priority'][$story['priority']] = ($summary['by_priority'][$story['priority']] ?? 0) + 1;
                
                // Category statistics
                if (!empty($story['category'])) {
                    $summary['by_category'][$story['category']] = ($summary['by_category'][$story['category']] ?? 0) + 1;
                }

                // Extract business value themes from "so_that" field
                if (!empty($story['so_that'])) {
                    $value_text = strtolower($story['so_that']);
                    // Look for common business value keywords
                    $value_keywords = [
                        'efficiency' => ['efficient', 'faster', 'quick', 'speed', 'time'],
                        'user_experience' => ['user', 'experience', 'usability', 'interface', 'friendly'],
                        'automation' => ['automat', 'manual', 'process', 'workflow'],
                        'integration' => ['integrat', 'connect', 'sync', 'data', 'system'],
                        'compliance' => ['complian', 'security', 'audit', 'regulation', 'policy'],
                        'analytics' => ['report', 'analytic', 'insight', 'track', 'monitor']
                    ];

                    foreach ($value_keywords as $theme => $keywords) {
                        foreach ($keywords as $keyword) {
                            if (strpos($value_text, $keyword) !== false) {
                                $summary['business_value_themes'][$theme] = ($summary['business_value_themes'][$theme] ?? 0) + 1;
                                break;
                            }
                        }
                    }
                }
            }

            // Calculate completion insights
            $total = count($user_stories);
            if ($total > 0) {
                $done = $summary['by_status']['done'] ?? 0;
                $in_progress = $summary['by_status']['in_progress'] ?? 0;
                $backlog = $summary['by_status']['backlog'] ?? 0;

                $summary['completion_insights'] = [
                    'completion_rate' => round(($done / $total) * 100, 1),
                    'in_progress_rate' => round(($in_progress / $total) * 100, 1),
                    'backlog_rate' => round(($backlog / $total) * 100, 1),
                    'total_stories' => $total
                ];
            }

            return [
                'stories' => $user_stories,
                'summary' => $summary
            ];

        } catch (Exception $e) {
            error_log("DataAggregator getUserStoriesData error: " . $e->getMessage());
            return [
                'stories' => [],
                'summary' => [
                    'total_count' => 0,
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Get DataMap diagram analysis
     */
    public function getDataMapDiagram($application_id) {
        try {
            $sql = "SELECT drawflow_diagram, drawflow_notes FROM applications WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$application_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || !$result['drawflow_diagram']) {
                return [
                    'has_diagram' => false,
                    'analysis' => 'No DataMap diagram found for this application.'
                ];
            }
            
            $diagram_data = json_decode($result['drawflow_diagram'], true);
            
            if (!$diagram_data || !isset($diagram_data['drawflow']['Home']['data'])) {
                return [
                    'has_diagram' => false,
                    'analysis' => 'DataMap diagram exists but contains no flow data.'
                ];
            }
            
            $nodes = $diagram_data['drawflow']['Home']['data'];
            $comment_connections = $diagram_data['commentConnections'] ?? [];
            $analysis = $this->analyzeDataFlowDiagram($nodes);
            
            // Add comment connections analysis
            $analysis['comment_connections'] = $this->analyzeCommentConnections($comment_connections, $nodes);
            
            return [
                'has_diagram' => true,
                'raw_data' => $diagram_data,
                'notes' => $result['drawflow_notes'],
                'analysis' => $analysis
            ];
            
        } catch (Exception $e) {
            error_log("DataAggregator getDataMapDiagram error: " . $e->getMessage());
            return [
                'has_diagram' => false,
                'analysis' => 'Error retrieving DataMap diagram: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Analyze the structure and flow of the DataMap diagram
     */
    private function analyzeDataFlowDiagram($nodes) {
        $analysis = [
            'node_count' => count($nodes),
            'node_types' => [],
            'connections' => [],
            'data_sources' => [],
            'data_destinations' => [],
            'transformations' => [],
            'flow_patterns' => [],
            'comments' => []
        ];
        
        foreach ($nodes as $nodeId => $nodeData) {
            // Extract node information using enhanced parsing
            $nodeClass = $nodeData['class'] ?? 'unknown';
            $inputs = $nodeData['inputs'] ?? [];
            $outputs = $nodeData['outputs'] ?? [];
            
            // Count node types
            $analysis['node_types'][$nodeClass] = ($analysis['node_types'][$nodeClass] ?? 0) + 1;
            
            // Parse node content for meaningful information using enhanced method
            $nodeInfo = $this->parseNodeContentEnhanced($nodeData);
            
            // Check if this is a comment node
            if (strpos($nodeClass, 'comment-node') !== false) {
                $analysis['comments'][] = [
                    'id' => $nodeId,
                    'text' => $nodeInfo['title'] ?? $nodeInfo['display_text'] ?? 'Empty comment',
                    'type' => 'comment',
                    'context' => $this->determineCommentContext($nodeInfo['title'] ?? $nodeInfo['display_text'] ?? '')
                ];
                // Skip further processing for comment nodes
                continue;
            }
            
            // Categorize nodes based on their role
            if (empty($inputs) && !empty($outputs)) {
                $analysis['data_sources'][] = [
                    'id' => $nodeId,
                    'type' => $nodeClass,
                    'info' => $nodeInfo,
                    'output_count' => count($outputs)
                ];
            } elseif (!empty($inputs) && empty($outputs)) {
                $analysis['data_destinations'][] = [
                    'id' => $nodeId,
                    'type' => $nodeClass,
                    'info' => $nodeInfo,
                    'input_count' => count($inputs)
                ];
            } elseif (!empty($inputs) && !empty($outputs)) {
                $analysis['transformations'][] = [
                    'id' => $nodeId,
                    'type' => $nodeClass,
                    'info' => $nodeInfo,
                    'input_count' => count($inputs),
                    'output_count' => count($outputs)
                ];
            }
            
            // Track connections
            foreach ($outputs as $outputKey => $output) {
                if (isset($output['connections'])) {
                    foreach ($output['connections'] as $connection) {
                        $analysis['connections'][] = [
                            'from' => $nodeId,
                            'to' => $connection['node'],
                            'from_output' => $outputKey,
                            'to_input' => $connection['output']
                        ];
                    }
                }
            }
        }
        
        // Analyze flow patterns
        $analysis['flow_patterns'] = $this->identifyFlowPatterns($analysis);
        
        return $analysis;
    }

    /**
     * Parse node HTML content to extract meaningful information
     */
    private function parseNodeContent($html, $nodeClass) {
        // Remove HTML tags and extract text content
        $text = strip_tags($html);
        $text = trim(preg_replace('/\s+/', ' ', $text));
        
        // Try to extract structured information based on node type
        $info = [
            'display_text' => $text,
            'extracted_data' => []
        ];
        
        // Look for common patterns in node content
        if (preg_match('/Database:\s*(.+?)(?:\n|$)/i', $text, $matches)) {
            $info['extracted_data']['database'] = trim($matches[1]);
        }
        
        if (preg_match('/Table:\s*(.+?)(?:\n|$)/i', $text, $matches)) {
            $info['extracted_data']['table'] = trim($matches[1]);
        }
        
        if (preg_match('/API:\s*(.+?)(?:\n|$)/i', $text, $matches)) {
            $info['extracted_data']['api'] = trim($matches[1]);
        }
        
        if (preg_match('/System:\s*(.+?)(?:\n|$)/i', $text, $matches)) {
            $info['extracted_data']['system'] = trim($matches[1]);
        }
        
        return $info;
    }

    /**
     * Enhanced node content parsing that also extracts structured data from node data field
     */
    private function parseNodeContentEnhanced($nodeData) {
        $html = $nodeData['html'] ?? '';
        $nodeClass = $nodeData['class'] ?? 'unknown';
        $structuredData = $nodeData['data'] ?? [];
        
        // Start with basic HTML parsing
        $info = $this->parseNodeContent($html, $nodeClass);
        
        // Override with structured data if available (more reliable)
        if (!empty($structuredData['title'])) {
            $info['title'] = $structuredData['title'];
        }
        
        if (!empty($structuredData['description'])) {
            $info['description'] = $structuredData['description'];
        }
        
        if (!empty($structuredData['type'])) {
            $info['type'] = $structuredData['type'];
        }
        
        return $info;
    }

    /**
     * Identify common flow patterns in the diagram
     */
    private function identifyFlowPatterns($analysis) {
        $patterns = [];
        
        // Simple linear flow
        if (count($analysis['data_sources']) == 1 && count($analysis['data_destinations']) == 1) {
            $patterns[] = 'Simple linear data flow from single source to single destination';
        }
        
        // Fan-out pattern
        if (count($analysis['data_sources']) == 1 && count($analysis['data_destinations']) > 1) {
            $patterns[] = 'Fan-out pattern: single source distributing to multiple destinations';
        }
        
        // Fan-in pattern
        if (count($analysis['data_sources']) > 1 && count($analysis['data_destinations']) == 1) {
            $patterns[] = 'Fan-in pattern: multiple sources consolidating to single destination';
        }
        
        // Complex transformation
        if (count($analysis['transformations']) > 2) {
            $patterns[] = 'Complex transformation pipeline with multiple processing steps';
        }
        
        // Hub and spoke
        $transformationNodes = count($analysis['transformations']);
        $totalConnections = count($analysis['connections']);
        if ($transformationNodes > 0 && $totalConnections > $transformationNodes * 2) {
            $patterns[] = 'Hub and spoke pattern with central processing nodes';
        }
        
        return $patterns;
    }

    /**
     * Determine the context/category of a comment based on its text content
     */
    private function determineCommentContext($commentText) {
        $text = strtolower($commentText);
        
        // Technical/Architecture context
        if (preg_match('/\b(api|database|server|integration|architecture|technical|system|performance|security)\b/', $text)) {
            return 'technical';
        }
        
        // Business context
        if (preg_match('/\b(business|requirement|user|customer|process|workflow|goal|objective)\b/', $text)) {
            return 'business';
        }
        
        // Risk/Issue context  
        if (preg_match('/\b(risk|issue|problem|concern|warning|critical|error|fix|bug)\b/', $text)) {
            return 'risk';
        }
        
        // Implementation/Development context
        if (preg_match('/\b(todo|implement|develop|build|create|deploy|migration|update|upgrade)\b/', $text)) {
            return 'implementation';
        }
        
        // Documentation context
        if (preg_match('/\b(note|documentation|info|information|explain|description)\b/', $text)) {
            return 'documentation';
        }
        
        return 'general';
    }

    /**
     * Analyze comment connections to understand which comments relate to which systems
     */
    private function analyzeCommentConnections($comment_connections, $nodes) {
        $connections_analysis = [];
        
        foreach ($comment_connections as $comment_node_id => $connections) {
            $comment_data = $nodes[$comment_node_id] ?? null;
            if (!$comment_data) continue;
            
            $comment_info = $this->parseNodeContentEnhanced($comment_data);
            $comment_text = $comment_info['title'] ?? $comment_info['display_text'] ?? 'Empty comment';
            
            $connected_systems = [];
            foreach ($connections as $connection) {
                $target_node_id = $connection['targetId'] ?? null;
                if ($target_node_id && isset($nodes[$target_node_id])) {
                    $target_info = $this->parseNodeContentEnhanced($nodes[$target_node_id]);
                    $connected_systems[] = [
                        'node_id' => $target_node_id,
                        'system_name' => $target_info['title'] ?? $target_info['display_text'] ?? 'Unknown System',
                        'system_type' => $nodes[$target_node_id]['class'] ?? 'unknown'
                    ];
                }
            }
            
            $connections_analysis[] = [
                'comment_id' => $comment_node_id,
                'comment_text' => $comment_text,
                'comment_context' => $this->determineCommentContext($comment_text),
                'connected_systems' => $connected_systems,
                'connection_count' => count($connected_systems)
            ];
        }
        
        return $connections_analysis;
    }
}
?>
