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
            'relationships' => $this->getRelationshipData($application_id),
            'audit_history' => $this->getAuditHistory($application_id),
            'attachments' => $this->getAttachmentSummary($application_id),
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
}
?>
