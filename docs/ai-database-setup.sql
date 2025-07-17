-- AI Analysis Database Setup
-- Phase 1: Basic AI Integration

-- AI analysis results table
CREATE TABLE ai_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    analysis_type ENUM('summary', 'timeline', 'risk_assessment', 'relationship_analysis', 'trend_analysis') NOT NULL,
    ai_model VARCHAR(100) NOT NULL DEFAULT 'gpt-3.5-turbo',
    prompt_version VARCHAR(50) NOT NULL DEFAULT 'v1.0',
    input_data_hash VARCHAR(64) NOT NULL,
    analysis_result JSON NOT NULL,
    confidence_score DECIMAL(3,2) NULL,
    processing_time_ms INT NULL,
    token_count INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    created_by INT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_app_type (application_id, analysis_type),
    INDEX idx_expires (expires_at),
    INDEX idx_hash (input_data_hash)
);

-- Data snapshots for historical preservation
CREATE TABLE data_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    snapshot_type ENUM('application_data', 'work_notes', 'relationships', 'full_context') NOT NULL,
    data_snapshot JSON NOT NULL,
    snapshot_hash VARCHAR(64) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    triggered_by ENUM('manual', 'ai_analysis', 'schedule', 'before_delete') NOT NULL,
    trigger_user_id INT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (trigger_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_app_date (application_id, created_at),
    INDEX idx_type (snapshot_type),
    INDEX idx_hash (snapshot_hash)
);

-- AI configuration and prompts
CREATE TABLE ai_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analysis_type VARCHAR(100) NOT NULL,
    prompt_template TEXT NOT NULL,
    prompt_version VARCHAR(50) NOT NULL,
    model_name VARCHAR(100) NOT NULL DEFAULT 'gpt-3.5-turbo',
    model_parameters JSON NULL,
    max_tokens INT DEFAULT 2000,
    temperature DECIMAL(3,2) DEFAULT 0.7,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type_active (analysis_type, is_active),
    INDEX idx_version (prompt_version)
);

-- AI API usage tracking
CREATE TABLE ai_usage_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    application_id INT NULL,
    analysis_type VARCHAR(100) NOT NULL,
    model_used VARCHAR(100) NOT NULL,
    tokens_used INT NOT NULL,
    cost_estimate DECIMAL(10,6) NULL,
    processing_time_ms INT NOT NULL,
    status ENUM('success', 'error', 'timeout') NOT NULL,
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, created_at),
    INDEX idx_app_date (application_id, created_at),
    INDEX idx_status (status)
);

-- Insert default AI configurations
INSERT INTO ai_configurations (analysis_type, prompt_template, prompt_version, model_name, model_parameters) VALUES
('summary', 'Analyze the following application data and provide a comprehensive summary. Focus on the application purpose, current status, key relationships, and any notable patterns in the activity log.\n\nApplication Data:\n{application_data}\n\nWork Notes:\n{work_notes}\n\nRelationships:\n{relationships}\n\nAudit History:\n{audit_history}\n\nPlease provide a structured analysis with the following sections:\n1. Application Overview\n2. Current Status & Progress\n3. Key Relationships & Dependencies\n4. Recent Activity Summary\n5. Notable Observations\n\nFormat the response as JSON with clear sections.', 'v1.0', 'gpt-3.5-turbo', '{"temperature": 0.7, "max_tokens": 2000}'),

('timeline', 'Create a detailed timeline analysis for this application based on the provided data. Identify key milestones, changes, and patterns over time.\n\nApplication Data:\n{application_data}\n\nWork Notes:\n{work_notes}\n\nAudit History:\n{audit_history}\n\nPlease create a structured timeline with:\n1. Key Milestones (phase changes, status updates)\n2. Significant Events (major work notes, relationship changes)\n3. Pattern Analysis (frequency of updates, activity patterns)\n4. Timeline Summary\n\nFormat as JSON with chronological events and analysis.', 'v1.0', 'gpt-3.5-turbo', '{"temperature": 0.6, "max_tokens": 2500}'),

('risk_assessment', 'Perform a risk assessment for this application based on the available data. Identify potential risks, concerns, and recommendations.\n\nApplication Data:\n{application_data}\n\nWork Notes:\n{work_notes}\n\nRelationships:\n{relationships}\n\nAudit History:\n{audit_history}\n\nProvide a risk analysis including:\n1. Identified Risks (technical, operational, timeline)\n2. Risk Levels (high, medium, low)\n3. Potential Impact\n4. Recommendations\n5. Monitoring Suggestions\n\nFormat as JSON with structured risk assessment.', 'v1.0', 'gpt-3.5-turbo', '{"temperature": 0.5, "max_tokens": 2500}');
