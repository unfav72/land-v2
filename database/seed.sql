-- seed.sql (Development ONLY)
-- Do NOT use in production

INSERT INTO `users` (`id`, `firebase_uid`, `email`, `display_name`, `role`, `status`) VALUES
('user-1', 'firebase-uid-officer-1', 'officer@example.com', 'Test Officer', 'officer', 'active'),
('user-2', 'firebase-uid-public-1', 'public@example.com', 'Test Public User', 'public', 'active');

INSERT INTO `documents` (`id`, `original_filename`, `stored_filename`, `mime_type`, `file_size`, `uploader_uid`, `status`) VALUES
('doc-1', 'land_record_1998.pdf', 'f8b92e4c-9a4f-4d7f-document1.pdf', 'application/pdf', 1024000, 'user-1', 'VERIFIED'),
('doc-2', 'mutation_record_2005.jpg', 'a1b2c3d4-e5f6-7g8h-document2.jpg', 'image/jpeg', 512000, 'user-2', 'UNDER REVIEW');

INSERT INTO `extracted_fields` (`id`, `document_id`, `field_name`, `ai_value`, `confidence`, `source_page`, `status`, `officer_value`, `final_value`) VALUES
('field-1', 'doc-1', 'survey_number', '124/38A', 92.50, 1, 'verified', '124/38A', '124/38A'),
('field-2', 'doc-1', 'owner_name', 'Ramesh Kumar', 88.00, 1, 'verified', 'Ramesh Kumar', 'Ramesh Kumar'),
('field-3', 'doc-2', 'survey_number', '55/B', 65.00, 1, 'review_required', NULL, NULL);

INSERT INTO `validation_issues` (`id`, `document_id`, `field_name`, `reason`, `severity`, `status`) VALUES
('issue-1', 'doc-2', 'survey_number', 'Low confidence extraction score (65.00 < 75.00)', 'Medium', 'open');

INSERT INTO `notifications` (`id`, `recipient_uid`, `type`, `title`, `message`, `target_id`, `severity`, `is_read`) VALUES
('notif-1', 'user-1', 'review_required', 'Low Confidence Score', 'Document requires manual review for Survey Number', 'doc-2', 'warning', FALSE);

INSERT INTO `verification_records` (`id`, `document_id`, `verification_id`, `officer_uid`) VALUES
('verif-1', 'doc-1', 'VER-8F72AC91', 'user-1');

INSERT INTO `digitization_requests` (`id`, `request_id`, `applicant_name`, `contact_info`, `village`, `document_id`, `uploader_uid`, `status`) VALUES
('req-1', 'LDR-2026-000001', 'Test Public User', '9876543210', 'Sample Village', 'doc-2', 'user-2', 'UNDER REVIEW');
