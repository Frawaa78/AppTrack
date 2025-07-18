<?php
// src/managers/ActivityManager.php

require_once __DIR__ . '/../db/db.php';

class ActivityManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Hent kombinert aktivitetsfeed for en applikasjon med pagination
     * @param int $application_id
     * @param array $filters ['show_work_notes_only' => bool, 'show_hidden' => bool, 'user_id' => int, 'from_date' => string]
     * @param int $limit Number of activities to return
     * @param int $offset Number of activities to skip
     * @return array
     */
    public function getActivityFeed($application_id, $filters = [], $limit = null, $offset = 0) {
        $activities = [];
        
        // Standard filter-verdier
        $show_work_notes_only = $filters['show_work_notes_only'] ?? false;
        $show_hidden = $filters['show_hidden'] ?? false;
        $user_filter = $filters['user_id'] ?? null;
        $from_date = $filters['from_date'] ?? null;
        
        $params = [':application_id' => $application_id];
        
        // Hent Work Notes
        $work_notes_query = "
            SELECT 
                'work_note' as activity_type,
                wn.id,
                wn.application_id,
                wn.user_id,
                u.email as user_email,
                u.display_name as user_display_name,
                wn.note as content,
                wn.type,
                wn.priority,
                wn.attachment_filename,
                wn.attachment_size,
                wn.attachment_mime_type,
                wn.created_at,
                wn.is_visible
            FROM work_notes wn
            LEFT JOIN users u ON wn.user_id = u.id
            WHERE wn.application_id = :application_id
        ";
        
        if (!$show_hidden) {
            $work_notes_query .= " AND wn.is_visible = 1";
        }
        if ($user_filter) {
            $work_notes_query .= " AND wn.user_id = :user_filter";
            $params[':user_filter'] = $user_filter;
        }
        if ($from_date) {
            $work_notes_query .= " AND wn.created_at >= :from_date";
            $params[':from_date'] = $from_date;
        }
        
        $stmt = $this->db->prepare($work_notes_query);
        $stmt->execute($params);
        $work_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($work_notes as $note) {
            $activities[] = $note;
        }
        
        // Hent Audit Log (kun hvis ikke "work notes only")
        if (!$show_work_notes_only) {
            $audit_query = "
                SELECT 
                    'audit_log' as activity_type,
                    al.id,
                    al.record_id as application_id,
                    al.changed_by as user_id,
                    u.email as user_email,
                    u.display_name as user_display_name,
                    al.field_name,
                    al.old_value,
                    al.new_value,
                    'change' as type,
                    'medium' as priority,
                    NULL as attachment_filename,
                    NULL as attachment_size,
                    NULL as attachment_mime_type,
                    al.changed_at as created_at,
                    1 as is_visible
                FROM audit_log al
                LEFT JOIN users u ON al.changed_by = u.id
                WHERE al.table_name = 'applications' 
                AND al.record_id = :application_id
            ";
            
            if ($user_filter) {
                $audit_query .= " AND al.changed_by = :user_filter";
            }
            if ($from_date) {
                $audit_query .= " AND al.changed_at >= :from_date";
            }
            
            $stmt = $this->db->prepare($audit_query);
            $stmt->execute($params);
            $audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($audit_logs as $log) {
                // Generate human-readable content based on field type
                $log['content'] = $this->generateAuditLogContent($log['field_name'], $log['old_value'], $log['new_value']);
                $activities[] = $log;
            }
        }
        
        // Sorter kronologisk (nyeste først)
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Implementer pagination i PHP
        if ($limit !== null) {
            $activities = array_slice($activities, $offset, $limit);
        }
        
        return $activities;
    }
    
    /**
     * Hent totalt antall aktiviteter for paginering
     * @param int $application_id
     * @param array $filters
     * @return int
     */
    public function getActivityCount($application_id, $filters = []) {
        $show_work_notes_only = $filters['show_work_notes_only'] ?? false;
        $show_hidden = $filters['show_hidden'] ?? false;
        $user_filter = $filters['user_id'] ?? null;
        $from_date = $filters['from_date'] ?? null;
        
        $params = [':application_id' => $application_id];
        $total_count = 0;
        
        // Work Notes count
        $work_notes_count = "
            SELECT COUNT(*) as count
            FROM work_notes wn
            WHERE wn.application_id = :application_id
        ";
        
        if (!$show_hidden) {
            $work_notes_count .= " AND wn.is_visible = 1";
        }
        if ($user_filter) {
            $work_notes_count .= " AND wn.user_id = :user_filter";
            $params[':user_filter'] = $user_filter;
        }
        if ($from_date) {
            $work_notes_count .= " AND wn.created_at >= :from_date";
            $params[':from_date'] = $from_date;
        }
        
        $stmt = $this->db->prepare($work_notes_count);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_count += (int)($result['count'] ?? 0);
        
        // Audit Log count (kun hvis ikke "work notes only")
        if (!$show_work_notes_only) {
            $audit_count = "
                SELECT COUNT(*) as count
                FROM audit_log al
                WHERE al.table_name = 'applications' 
                AND al.record_id = :application_id
            ";
            
            if ($user_filter) {
                $audit_count .= " AND al.changed_by = :user_filter";
            }
            if ($from_date) {
                $audit_count .= " AND al.changed_at >= :from_date";
            }
            
            $stmt = $this->db->prepare($audit_count);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_count += (int)($result['count'] ?? 0);
        }
        
        return $total_count;
    }
    
    /**
     * Legg til manuell work note
     */
    public function addWorkNote($application_id, $user_id, $note, $type = 'comment', $priority = 'medium', $attachment = null) {
        $sql = "
            INSERT INTO work_notes (
                application_id, user_id, note, type, priority, 
                attachment_data, attachment_filename, attachment_size, attachment_mime_type,
                created_at, updated_at
            ) VALUES (
                :application_id, :user_id, :note, :type, :priority,
                :attachment_data, :attachment_filename, :attachment_size, :attachment_mime_type,
                NOW(), NOW()
            )
        ";
        
        $params = [
            ':application_id' => $application_id,
            ':user_id' => $user_id,
            ':note' => $note,
            ':type' => $type,
            ':priority' => $priority,
            ':attachment_data' => $attachment['data'] ?? null,
            ':attachment_filename' => $attachment['filename'] ?? null,
            ':attachment_size' => $attachment['size'] ?? null,
            ':attachment_mime_type' => $attachment['mime_type'] ?? null
        ];
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Loggfør automatisk feltendring
     */
    public function logFieldChange($application_id, $field_name, $old_value, $new_value, $user_id, $action = 'UPDATE') {
        // Ikke logg hvis verdiene er like
        if ($old_value === $new_value) {
            return true;
        }
        
        $sql = "
            INSERT INTO audit_log (
                table_name, record_id, field_name, old_value, new_value, 
                changed_by, action, changed_at
            ) VALUES (
                'applications', :record_id, :field_name, :old_value, :new_value,
                :changed_by, :action, NOW()
            )
        ";
        
        $params = [
            ':record_id' => $application_id,
            ':field_name' => $field_name,
            ':old_value' => $old_value,
            ':new_value' => $new_value,
            ':changed_by' => $user_id,
            ':action' => $action
        ];
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Generer menneskelig lesbar beskrivelse av endring
     */
    private function generateChangeDescription($field_name, $old_value, $new_value) {
        $field_labels = [
            'phase' => 'Phase',
            'status' => 'Status',
            'preops_portfolio' => 'Pre-ops Portfolio',
            'application_portfolio' => 'Application Portfolio',
            'assigned_to' => 'Assigned To',
            'project_manager' => 'Project Manager',
            'product_owner' => 'Product Owner',
            'due_date' => 'Due Date',
            'handover_status' => 'Handover Status'
        ];
        
        $field_label = $field_labels[$field_name] ?? ucfirst(str_replace('_', ' ', $field_name));
        
        if (empty($old_value)) {
            return "$field_label set to: $new_value";
        } elseif (empty($new_value)) {
            return "$field_label cleared (was: $old_value)";
        } else {
            return "$field_label changed from \"$old_value\" to \"$new_value\"";
        }
    }
    
    /**
     * Generate human-readable audit log content
     */
    private function generateAuditLogContent($field_name, $old_value, $new_value) {
        // Handle relationship_yggdrasil field specially - convert IDs to application names
        if ($field_name === 'relationship_yggdrasil') {
            $old_names = $this->convertIdsToApplicationNames($old_value);
            $new_names = $this->convertIdsToApplicationNames($new_value);
            
            if (empty($old_value)) {
                return "relationship_yggdrasil set to: " . $new_names;
            } elseif (empty($new_value)) {
                return "relationship_yggdrasil cleared (was: " . $old_names . ")";
            } else {
                return "relationship_yggdrasil changed from \"" . $old_names . "\" to \"" . $new_names . "\"";
            }
        }
        
        // For all other fields, use standard format
        if (empty($old_value)) {
            return $field_name . " set to: " . $new_value;
        } elseif (empty($new_value)) {
            return $field_name . " cleared (was: " . $old_value . ")";
        } else {
            return $field_name . " changed from \"" . $old_value . "\" to \"" . $new_value . "\"";
        }
    }
    
    /**
     * Convert comma-separated application IDs to application names
     */
    private function convertIdsToApplicationNames($ids_string) {
        if (empty($ids_string)) {
            return '';
        }
        
        $ids = array_map('trim', explode(',', $ids_string));
        $ids = array_filter($ids, 'is_numeric'); // Only keep numeric IDs
        
        if (empty($ids)) {
            return '';
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT id, short_description FROM applications WHERE id IN ($placeholders)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($ids);
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $names = [];
            foreach ($applications as $app) {
                $names[] = $app['short_description'];
            }
            
            return implode(', ', $names);
        } catch (Exception $e) {
            // If something goes wrong, return the original IDs
            return $ids_string;
        }
    }
    
    /**
     * Skjul aktivitet (kun for admin - kun work notes)
     */
    public function hideActivity($activity_type, $activity_id, $user_role) {
        if ($user_role !== 'admin') {
            return false;
        }
        
        // Kun work notes kan skjules
        if ($activity_type === 'work_note') {
            $sql = "UPDATE work_notes SET is_visible = 0 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $activity_id]);
        }
        
        return false;
    }
    
    /**
     * Vis skjult aktivitet (kun for admin - kun work notes)
     */
    public function showActivity($activity_type, $activity_id, $user_role) {
        if ($user_role !== 'admin') {
            return false;
        }
        
        // Kun work notes kan vises/skjules
        if ($activity_type === 'work_note') {
            $sql = "UPDATE work_notes SET is_visible = 1 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $activity_id]);
        }
        
        return false;
    }
    
    /**
     * Last ned vedlegg
     */
    public function getAttachment($work_note_id) {
        $sql = "
            SELECT attachment_data, attachment_filename, attachment_mime_type 
            FROM work_notes 
            WHERE id = :id AND attachment_data IS NOT NULL
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $work_note_id]);
        return $stmt->fetch();
    }
    
    /**
     * Tillatte filtyper
     */
    public function getAllowedFileTypes() {
        return [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain', 'text/csv',
            'application/zip', 'application/x-rar-compressed'
        ];
    }
    
    /**
     * Valider opplastet fil
     */
    public function validateFile($file) {
        $allowed_types = $this->getAllowedFileTypes();
        $max_size = 10 * 1024 * 1024; // 10MB
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload error'];
        }
        
        if ($file['size'] > $max_size) {
            return ['valid' => false, 'error' => 'File too large (max 10MB)'];
        }
        
        if (!in_array($file['type'], $allowed_types)) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }
        
        return ['valid' => true];
    }
}
