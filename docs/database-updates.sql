-- Database-oppdateringer for Work Notes og Activity Tracker
-- Kjør disse SQL-kommandoene i phpMyAdmin

-- Forbedret work_notes tabell
ALTER TABLE work_notes 
ADD COLUMN priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
ADD COLUMN is_visible BOOLEAN DEFAULT 1,
ADD COLUMN attachment_data LONGBLOB NULL,
ADD COLUMN attachment_filename VARCHAR(255) NULL,
ADD COLUMN attachment_size INT NULL,
ADD COLUMN attachment_mime_type VARCHAR(100) NULL;

-- Fjern gamle attachment-kolonner hvis de finnes
-- ALTER TABLE work_notes DROP COLUMN attachment_path;
-- ALTER TABLE work_notes DROP COLUMN attachment_type;

-- Forbedret audit_log tabell  
ALTER TABLE audit_log
ADD COLUMN change_summary VARCHAR(500),
ADD COLUMN is_visible BOOLEAN DEFAULT 1;

-- Indekser for bedre ytelse
CREATE INDEX idx_work_notes_app_created ON work_notes(application_id, created_at DESC);
CREATE INDEX idx_audit_log_record_created ON audit_log(table_name, record_id, created_at DESC);
CREATE INDEX idx_work_notes_visible ON work_notes(is_visible);
CREATE INDEX idx_audit_log_visible ON audit_log(is_visible);
