<?php
// src/controllers/UserStoryController.php

require_once __DIR__ . '/../models/UserStory.php';

class UserStoryController {
    private $userStoryModel;
    
    public function __construct() {
        $this->userStoryModel = new UserStory();
    }
    
    /**
     * Get all user stories with filtering
     */
    public function index($filters = []) {
        try {
            return [
                'success' => true,
                'data' => $this->userStoryModel->getAll($filters),
                'statistics' => $this->userStoryModel->getStatistics()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch user stories: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get a specific user story
     */
    public function show($id) {
        try {
            $story = $this->userStoryModel->getById($id);
            
            if (!$story) {
                return [
                    'success' => false,
                    'error' => 'User story not found'
                ];
            }
            
            return [
                'success' => true,
                'data' => $story
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch user story: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a new user story
     */
    public function create($data) {
        try {
            // Validate required fields
            $requiredFields = ['title', 'role', 'want_to', 'so_that'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                    ];
                }
            }
            
            // Sanitize input data
            $cleanData = $this->sanitizeUserStoryData($data);
            
            // Add created_by from session
            if (isset($_SESSION['user_id'])) {
                $cleanData['created_by'] = $_SESSION['user_id'];
            }
            
            $storyId = $this->userStoryModel->create($cleanData);
            
            if ($storyId) {
                return [
                    'success' => true,
                    'data' => ['id' => $storyId],
                    'message' => 'User story created successfully'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to create user story'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create user story: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update an existing user story
     */
    public function update($id, $data) {
        try {
            // Check if story exists
            $existingStory = $this->userStoryModel->getById($id);
            if (!$existingStory) {
                return [
                    'success' => false,
                    'error' => 'User story not found'
                ];
            }
            
            // For partial updates (inline editing), only validate provided fields
            // Only validate all required fields if this is a complete update
            $isPartialUpdate = count($data) < 4; // If less than 4 fields, it's likely a partial update
            
            if (!$isPartialUpdate) {
                // Validate required fields for complete updates
                $requiredFields = ['title', 'role', 'want_to', 'so_that'];
                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        return [
                            'success' => false,
                            'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                        ];
                    }
                }
            }
            
            // Sanitize input data
            $cleanData = $this->sanitizeUserStoryData($data);
            
            $result = $this->userStoryModel->update($id, $cleanData);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User story updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to update user story'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to update user story: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a user story
     */
    public function delete($id) {
        try {
            // Check if story exists
            $existingStory = $this->userStoryModel->getById($id);
            if (!$existingStory) {
                return [
                    'success' => false,
                    'error' => 'User story not found'
                ];
            }
            
            $result = $this->userStoryModel->delete($id);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User story deleted successfully'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to delete user story'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to delete user story: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user stories for a specific application
     */
    public function getByApplication($applicationId) {
        try {
            return [
                'success' => true,
                'data' => $this->userStoryModel->getByApplicationId($applicationId)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch application user stories: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Search user stories
     */
    public function search($query) {
        try {
            return [
                'success' => true,
                'data' => $this->userStoryModel->search($query)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to search user stories: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get form options for dropdowns
     */
    public function getFormOptions() {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get applications for dropdown
            $stmt = $db->prepare('SELECT id, short_description FROM applications ORDER BY short_description');
            $stmt->execute();
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'applications' => $applications,
                    'priorities' => $this->userStoryModel->getPriorityOptions(),
                    'statuses' => $this->userStoryModel->getStatusOptions()
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch form options: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sanitize user story data
     */
    private function sanitizeUserStoryData($data) {
        $cleanData = [];
        
        // Text fields that should be trimmed and escaped
        $textFields = [
            'title', 'jira_id', 'role', 'want_to', 'so_that',
            'jira_url', 'sharepoint_url', 'tags', 'category'
        ];
        
        foreach ($textFields as $field) {
            if (isset($data[$field])) {
                $cleanData[$field] = trim($data[$field]);
                // Only convert empty strings to null for non-required fields
                if ($cleanData[$field] === '' && !in_array($field, ['title', 'role', 'want_to', 'so_that'])) {
                    $cleanData[$field] = null;
                }
            }
        }
        
        // Handle application_id as comma-separated string for partial updates
        if (isset($data['application_id']) && !empty($data['application_id'])) {
            $cleanData['application_id'] = $data['application_id'];
        }
        
        // Handle multiple application IDs
        if (isset($data['application_ids']) && is_array($data['application_ids'])) {
            $cleanData['application_ids'] = array_filter(array_map('intval', $data['application_ids']));
        }
        
        // Enum fields with validation
        if (isset($data['priority']) && in_array($data['priority'], $this->userStoryModel->getPriorityOptions())) {
            $cleanData['priority'] = $data['priority'];
        }
        
        if (isset($data['status']) && in_array($data['status'], $this->userStoryModel->getStatusOptions())) {
            $cleanData['status'] = $data['status'];
        }
        
        if (isset($data['source']) && in_array($data['source'], ['manual', 'jira_import', 'sharepoint_import'])) {
            $cleanData['source'] = $data['source'];
        }
        
        // Boolean fields
        if (isset($data['manual_entry'])) {
            $cleanData['manual_entry'] = (bool)$data['manual_entry'];
        }
        
        return $cleanData;
    }
}
