<?php
// src/models/UserStory.php

require_once __DIR__ . '/../db/db.php';

class UserStory {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all user stories with optional filtering
     */
    public function getAll($filters = []) {
        $sql = "SELECT us.*, u.display_name as created_by_name
                FROM user_stories us 
                LEFT JOIN users u ON us.created_by = u.id";
        
        $conditions = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['application_id'])) {
            // Handle both single application_id and comma-separated values using FIND_IN_SET
            $conditions[] = "FIND_IN_SET(:application_id, us.application_id) > 0";
            $params[':application_id'] = $filters['application_id'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "us.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = "us.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['created_by'])) {
            $conditions[] = "us.created_by = :created_by";
            $params[':created_by'] = $filters['created_by'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(us.title LIKE :search OR us.role LIKE :search OR us.want_to LIKE :search OR us.so_that LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY us.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Resolve application names for each story
        foreach ($stories as &$story) {
            $story['application_name'] = $this->getApplicationNames($story['application_id']);
        }
        
        return $stories;
    }
    
    /**
     * Get application names for comma-separated application IDs
     */
    private function getApplicationNames($applicationIds) {
        if (empty($applicationIds)) {
            return '';
        }
        
        // Split comma-separated IDs
        $ids = array_map('trim', explode(',', $applicationIds));
        $ids = array_filter($ids); // Remove empty values
        
        if (empty($ids)) {
            return '';
        }
        
        // Create placeholders for IN clause
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        $sql = "SELECT short_description FROM applications WHERE id IN ($placeholders) ORDER BY short_description";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        
        $names = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return implode(', ', $names);
    }
    
    /**
     * Get a single user story by ID
     */
    public function getById($id) {
        $sql = "SELECT us.*, u.display_name as created_by_name
                FROM user_stories us 
                LEFT JOIN users u ON us.created_by = u.id
                WHERE us.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $story = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($story) {
            $story['application_name'] = $this->getApplicationNames($story['application_id']);
        }
        
        return $story;
    }
    
    /**
     * Get user stories for a specific application
     */
    public function getByApplicationId($applicationId) {
        return $this->getAll(['application_id' => $applicationId]);
    }
    
    /**
     * Create a new user story
     */
    public function create($data) {
        $sql = "INSERT INTO user_stories (
                    title, jira_id, role, want_to, so_that, priority, 
                    application_id, jira_url, 
                    sharepoint_url, source, manual_entry, tags, category, 
                    created_by, status
                ) VALUES (
                    :title, :jira_id, :role, :want_to, :so_that, :priority,
                    :application_id, :jira_url,
                    :sharepoint_url, :source, :manual_entry, :tags, :category,
                    :created_by, :status
                )";
        
        $stmt = $this->db->prepare($sql);
        
        // Handle multiple application IDs - for now store as comma-separated
        $applicationId = null;
        if (isset($data['application_ids']) && is_array($data['application_ids']) && !empty($data['application_ids'])) {
            $applicationId = implode(',', array_map('intval', $data['application_ids']));
        } elseif (isset($data['application_id']) && !empty($data['application_id'])) {
            $applicationId = $data['application_id'];
        }
        
        $result = $stmt->execute([
            ':title' => $data['title'],
            ':jira_id' => $data['jira_id'] ?? null,
            ':role' => $data['role'],
            ':want_to' => $data['want_to'],
            ':so_that' => $data['so_that'],
            ':priority' => $data['priority'] ?? 'Medium',
            ':application_id' => $applicationId,
            ':jira_url' => $data['jira_url'] ?? null,
            ':sharepoint_url' => $data['sharepoint_url'] ?? null,
            ':source' => $data['source'] ?? 'manual',
            ':manual_entry' => $data['manual_entry'] ?? true,
            ':tags' => $data['tags'] ?? null,
            ':category' => $data['category'] ?? null,
            ':created_by' => $data['created_by'] ?? null,
            ':status' => $data['status'] ?? 'backlog'
        ]);
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update an existing user story
     */
    public function update($id, $data) {
        // Build dynamic SQL based on provided fields
        $allowedFields = [
            'title', 'jira_id', 'role', 'want_to', 'so_that', 'priority',
            'application_id', 'jira_url', 'sharepoint_url', 'tags', 'category', 'status'
        ];
        
        $updateFields = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        // Handle application_ids array (convert to comma-separated string)
        if (isset($data['application_ids']) && is_array($data['application_ids'])) {
            if (!empty($data['application_ids'])) {
                $updateFields[] = "application_id = :application_id";
                $params[':application_id'] = implode(',', array_map('intval', $data['application_ids']));
            } else {
                $updateFields[] = "application_id = :application_id";
                $params[':application_id'] = null;
            }
        }
        
        // Always update the timestamp
        $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
        
        if (empty($updateFields)) {
            return true; // Nothing to update
        }
        
        $sql = "UPDATE user_stories SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * Delete a user story
     */
    public function delete($id) {
        $sql = "DELETE FROM user_stories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get priority options
     */
    public function getPriorityOptions() {
        return ['Low', 'Medium', 'High', 'Critical'];
    }
    
    /**
     * Get status options
     */
    public function getStatusOptions() {
        return ['backlog', 'in_progress', 'review', 'done', 'cancelled'];
    }
    
    /**
     * Get statistics for dashboard
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_stories,
                    SUM(CASE WHEN status = 'backlog' THEN 1 ELSE 0 END) as backlog_count,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN status = 'review' THEN 1 ELSE 0 END) as review_count,
                    SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done_count,
                    SUM(CASE WHEN priority = 'Critical' THEN 1 ELSE 0 END) as critical_count,
                    SUM(CASE WHEN priority = 'High' THEN 1 ELSE 0 END) as high_count
                FROM user_stories";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search user stories
     */
    public function search($query) {
        return $this->getAll(['search' => $query]);
    }
}
