-- Sample data for Lenel S2 application
-- Kjør disse SQL-kommandoene i phpMyAdmin for å få testdata for Lenel S2

-- Bruker application_id=429 som er riktig ID for Lenel S2 i databasen
-- Bruker user_id=2 (Frank Waaland) og user_id=3 (Ola Normann) som eksisterer i users-tabellen

-- Work notes for Lenel S2
INSERT INTO work_notes (application_id, user_id, note, type, priority, created_at, updated_at) VALUES
(429, 2, 'Lenel S2 migration planning initiated. Current version running on legacy Windows Server 2016. Need to upgrade to supported platform.', 'comment', 'high', '2025-07-10 09:00:00', '2025-07-10 09:00:00'),
(429, 3, 'Security assessment completed for access control integration. Identified potential vulnerabilities in current badge reader configuration.', 'problem', 'high', '2025-07-11 10:30:00', '2025-07-11 10:30:00'),
(429, 2, 'Met with facilities team to discuss door controller replacement schedule. 47 controllers need firmware updates before migration.', 'comment', 'medium', '2025-07-12 14:15:00', '2025-07-12 14:15:00'),
(429, 3, 'Integration testing with Entra ID completed successfully. SSO authentication working as expected with proper role mapping.', 'comment', 'medium', '2025-07-13 11:00:00', '2025-07-13 11:00:00'),
(429, 2, 'Critical issue: Badge reader in Building A (Zone 3) experiencing intermittent connectivity issues. Affecting emergency egress procedures.', 'problem', 'high', '2025-07-14 08:45:00', '2025-07-14 08:45:00'),
(429, 3, 'Backup procedures tested and verified. Database replication to DR site functioning correctly. Recovery time objective: 4 hours.', 'comment', 'medium', '2025-07-14 16:20:00', '2025-07-14 16:20:00'),
(429, 2, 'User training sessions scheduled for week 29. Training materials prepared covering new mobile app features and emergency procedures.', 'change', 'medium', '2025-07-15 13:30:00', '2025-07-15 13:30:00'),
(429, 3, 'Performance optimization completed. Database queries improved, reducing card swipe response time from 1.2s to 0.3s.', 'comment', 'low', '2025-07-16 09:15:00', '2025-07-16 09:15:00'),
(429, 2, 'Vendor escalation: Hardware delivery delayed by 2 weeks due to supply chain issues. Adjusting project timeline accordingly.', 'problem', 'medium', '2025-07-16 14:45:00', '2025-07-16 14:45:00');

-- Audit log entries for Lenel S2 (automatiske endringer)
INSERT INTO audit_log (table_name, record_id, field_name, old_value, new_value, changed_by, action, changed_at) VALUES
('applications', 429, 'phase', 'Need', 'Solution', 2, 'UPDATE', '2025-07-10 08:00:00'),
('applications', 429, 'status', 'Not started', 'Ongoing Work', 2, 'UPDATE', '2025-07-10 08:30:00'),
('applications', 429, 'assigned_to', '', 'security.admin@akerbp.com', 3, 'UPDATE', '2025-07-10 09:30:00'),
('applications', 429, 'preops_portfolio', '', 'Security & Safety', 2, 'UPDATE', '2025-07-11 10:00:00'),
('applications', 429, 'application_portfolio', '', 'Digital', 2, 'UPDATE', '2025-07-11 10:15:00'),
('applications', 429, 'handover_status', '0', '20', 2, 'UPDATE', '2025-07-12 15:00:00'),
('applications', 429, 'project_manager', '', 'Lars Hansen', 3, 'UPDATE', '2025-07-12 16:00:00'),
('applications', 429, 'product_owner', '', 'Security Operations Team', 2, 'UPDATE', '2025-07-13 09:00:00'),
('applications', 429, 'deployment_model', '', 'On-premises', 3, 'UPDATE', '2025-07-13 14:00:00'),
('applications', 429, 'integrations', 'Not defined', 'Yes', 2, 'UPDATE', '2025-07-14 10:00:00'),
('applications', 429, 'sa_document', '', 'https://sharepoint.akerbp.com/security/lenel-s2-architecture', 3, 'UPDATE', '2025-07-14 11:00:00'),
('applications', 429, 'phase', 'Solution', 'Build', 2, 'UPDATE', '2025-07-15 10:00:00'),
('applications', 429, 'handover_status', '20', '40', 3, 'UPDATE', '2025-07-15 17:00:00'),
('applications', 429, 'due_date', '', '2025-08-31', 2, 'UPDATE', '2025-07-16 08:00:00'),
('applications', 429, 'delivery_responsible', '', 'Johnson Controls', 3, 'UPDATE', '2025-07-16 10:00:00');

-- Flere work notes med spesifikke Lenel S2-relaterte problemstillinger
INSERT INTO work_notes (application_id, user_id, note, type, priority, created_at, updated_at) VALUES
(429, 2, 'Badge enrollment process needs optimization. Current manual process takes 15 minutes per employee. Implementing bulk import feature.', 'change', 'medium', '2025-07-16 11:30:00', '2025-07-16 11:30:00'),
(429, 3, 'Anti-passback configuration updated for high-security zones. Implemented stricter rules for executive floor and data center access.', 'comment', 'high', '2025-07-16 12:00:00', '2025-07-16 12:00:00'),
(429, 2, 'Fire alarm integration testing completed. All emergency unlock procedures verified with fire safety team. Documentation updated.', 'comment', 'medium', '2025-07-16 15:30:00', '2025-07-16 15:30:00'),
(429, 3, 'Visitor management system integration requires additional API development. Current visitor badges not properly tracked in S2 database.', 'problem', 'medium', '2025-07-16 16:45:00', '2025-07-16 16:45:00');

-- En skjult work note for admin testing
INSERT INTO work_notes (application_id, user_id, note, type, priority, is_visible, created_at, updated_at) VALUES
(429, 2, 'Internal security review identified potential insider threat monitoring gaps. Recommending enhanced audit logging and real-time alerting.', 'comment', 'high', 0, '2025-07-16 18:00:00', '2025-07-16 18:00:00');
