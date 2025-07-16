-- Sample data for testing Activity Tracker og Work Notes
-- Kjør disse SQL-kommandoene i phpMyAdmin for å få testdata

-- Sample work notes (erstatt user_id og application_id med riktige verdier)
INSERT INTO work_notes (application_id, user_id, note, type, priority, created_at, updated_at) VALUES
(1, 1, 'Initial setup completed. Database configured and basic functionality tested.', 'comment', 'medium', '2025-07-15 09:30:00', '2025-07-15 09:30:00'),
(1, 1, 'Integration with ServiceNow CMDB needs to be prioritized for next sprint.', 'change', 'high', '2025-07-15 14:20:00', '2025-07-15 14:20:00'),
(1, 2, 'Security review completed. No critical issues found, but we need to implement additional logging.', 'comment', 'medium', '2025-07-16 08:45:00', '2025-07-16 08:45:00'),
(1, 1, 'Performance issue detected during load testing. Response time exceeds 2 seconds under high load.', 'problem', 'high', '2025-07-16 11:15:00', '2025-07-16 11:15:00'),
(1, 2, 'Fixed the performance issue by optimizing database queries and adding proper indexing.', 'comment', 'medium', '2025-07-16 15:30:00', '2025-07-16 15:30:00'),
(2, 1, 'Started development phase. Backend API structure defined and first endpoints implemented.', 'comment', 'medium', '2025-07-14 10:00:00', '2025-07-14 10:00:00'),
(2, 2, 'Frontend mockups approved by stakeholders. Ready to start UI implementation.', 'comment', 'low', '2025-07-15 13:00:00', '2025-07-15 13:00:00'),
(2, 1, 'Deployment to staging environment successful. Ready for user acceptance testing.', 'comment', 'medium', '2025-07-16 09:00:00', '2025-07-16 09:00:00');

-- Sample audit log entries (automatiske endringer)
INSERT INTO audit_log (table_name, record_id, field_name, old_value, new_value, changed_by, action, change_summary, created_at) VALUES
('applications', 1, 'phase', 'Need', 'Solution', 1, 'UPDATE', 'Phase changed from "Need" to "Solution"', '2025-07-15 10:00:00'),
('applications', 1, 'status', 'Not started', 'Ongoing Work', 1, 'UPDATE', 'Status changed from "Not started" to "Ongoing Work"', '2025-07-15 10:05:00'),
('applications', 1, 'handover_status', '0', '20', 1, 'UPDATE', 'Handover Status changed from "0" to "20"', '2025-07-15 14:30:00'),
('applications', 1, 'assigned_to', '', 'john.doe@akerbp.com', 2, 'UPDATE', 'Assigned To set to: john.doe@akerbp.com', '2025-07-15 15:00:00'),
('applications', 1, 'project_manager', '', 'Jane Smith', 1, 'UPDATE', 'Project Manager set to: Jane Smith', '2025-07-16 08:00:00'),
('applications', 1, 'phase', 'Solution', 'Build', 1, 'UPDATE', 'Phase changed from "Solution" to "Build"', '2025-07-16 12:00:00'),
('applications', 1, 'handover_status', '20', '40', 1, 'UPDATE', 'Handover Status changed from "20" to "40"', '2025-07-16 14:00:00'),
('applications', 2, 'application_created', '', 'New application created', 1, 'INSERT', 'New application created', '2025-07-14 09:30:00'),
('applications', 2, 'phase', 'Need', 'Solution', 2, 'UPDATE', 'Phase changed from "Need" to "Solution"', '2025-07-15 11:00:00'),
('applications', 2, 'status', 'Not started', 'Ongoing Work', 2, 'UPDATE', 'Status changed from "Not started" to "Ongoing Work"', '2025-07-15 11:30:00'),
('applications', 2, 'deployment_model', '', 'SaaS', 1, 'UPDATE', 'Deployment Model set to: SaaS', '2025-07-15 16:00:00'),
('applications', 2, 'integrations', 'Not defined', 'Yes', 2, 'UPDATE', 'Integrations changed from "Not defined" to "Yes"', '2025-07-16 10:00:00');

-- Noen ekstra work notes med forskjellige typer
INSERT INTO work_notes (application_id, user_id, note, type, priority, created_at, updated_at) VALUES
(1, 2, 'Weekly status meeting held. All stakeholders aligned on current progress and next steps.', 'comment', 'low', '2025-07-16 16:00:00', '2025-07-16 16:00:00'),
(2, 1, 'User feedback received: The interface needs to be more intuitive. UX team will review and propose improvements.', 'change', 'medium', '2025-07-16 17:00:00', '2025-07-16 17:00:00'),
(1, 1, 'Critical bug found in production: Data synchronization failing for large datasets. Investigating immediately.', 'problem', 'high', '2025-07-16 18:00:00', '2025-07-16 18:00:00');

-- Legg til en skjult aktivitet for å teste admin-funksjonalitet
INSERT INTO work_notes (application_id, user_id, note, type, priority, is_visible, created_at, updated_at) VALUES
(1, 1, 'Internal note: Consider replacing vendor due to performance issues. Keep confidential until decision is made.', 'comment', 'high', 0, '2025-07-16 19:00:00', '2025-07-16 19:00:00');
