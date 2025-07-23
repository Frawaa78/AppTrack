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
        $sql = "SELECT us.*, a.short_description as application_name, 
                       u.display_name as created_by_name
                FROM user_stories us 
                LEFT JOIN applications a ON us.application_id = a.id 
                LEFT JOIN users u ON us.created_by = u.id";
        
        $conditions = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['application_id'])) {
            $conditions[] = "us.application_id = :application_id";
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
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get a single user story by ID
     */
    public function getById($id) {
        $sql = "SELECT us.*, a.short_description as application_name, 
                       u.display_name as created_by_name
                FROM user_stories us 
                LEFT JOIN applications a ON us.application_id = a.id 
                LEFT JOIN users u ON us.created_by = u.id
                WHERE us.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
                    story_points, sprint, epic, application_id, jira_url, 
                    sharepoint_url, source, manual_entry, tags, category, 
                    created_by, status, acceptance_criteria, technical_notes, 
                    business_value
                ) VALUES (
                    :title, :jira_id, :role, :want_to, :so_that, :priority,
                    :story_points, :sprint, :epic, :application_id, :jira_url,
                    :sharepoint_url, :source, :manual_entry, :tags, :category,
                    :created_by, :status, :acceptance_criteria, :technical_notes,
                    :business_value
                )";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            ':title' => $data['title'],
            ':jira_id' => $data['jira_id'] ?? null,
            ':role' => $data['role'],
            ':want_to' => $data['want_to'],
            ':so_that' => $data['so_that'],
            ':priority' => $data['priority'] ?? 'Medium',
            ':story_points' => $data['story_points'] ?? null,
            ':sprint' => $data['sprint'] ?? null,
            ':epic' => $data['epic'] ?? null,
            ':application_id' => $data['application_id'] ?? null,
            ':jira_url' => $data['jira_url'] ?? null,
            ':sharepoint_url' => $data['sharepoint_url'] ?? null,
            ':source' => $data['source'] ?? 'manual',
            ':manual_entry' => $data['manual_entry'] ?? true,
            ':tags' => $data['tags'] ?? null,
            ':category' => $data['category'] ?? null,
            ':created_by' => $data['created_by'] ?? null,
            ':status' => $data['status'] ?? 'backlog',
            ':acceptance_criteria' => $data['acceptance_criteria'] ?? null,
            ':technical_notes' => $data['technical_notes'] ?? null,
            ':business_value' => $data['business_value'] ?? null
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
        $sql = "UPDATE user_stories SET 
                    title = :title, jira_id = :jira_id, role = :role, 
                    want_to = :want_to, so_that = :so_that, priority = :priority,
                    story_points = :story_points, sprint = :sprint, epic = :epic,
                    application_id = :application_id, jira_url = :jira_url,
                    sharepoint_url = :sharepoint_url, tags = :tags, category = :category,
                    status = :status, acceptance_criteria = :acceptance_criteria,
                    technical_notes = :technical_notes, business_value = :business_value,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':jira_id' => $data['jira_id'] ?? null,
            ':role' => $data['role'],
            ':want_to' => $data['want_to'],
            ':so_that' => $data['so_that'],
            ':priority' => $data['priority'] ?? 'Medium',
            ':story_points' => $data['story_points'] ?? null,
            ':sprint' => $data['sprint'] ?? null,
            ':epic' => $data['epic'] ?? null,
            ':application_id' => $data['application_id'] ?? null,
            ':jira_url' => $data['jira_url'] ?? null,
            ':sharepoint_url' => $data['sharepoint_url'] ?? null,
            ':tags' => $data['tags'] ?? null,
            ':category' => $data['category'] ?? null,
            ':status' => $data['status'] ?? 'backlog',
            ':acceptance_criteria' => $data['acceptance_criteria'] ?? null,
            ':technical_notes' => $data['technical_notes'] ?? null,
            ':business_value' => $data['business_value'] ?? null
        ]);
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
