<?php

// backend/controllers/DocumentController.php

class DocumentController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // GET /api/documents
    public function listDocuments() {
        try {
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;

            // Build basic search
            $where = "1=1";
            $params = [];

            if (!empty($_GET['status'])) {
                $where .= " AND status = :status";
                $params[':status'] = $_GET['status'];
            }
            if (!empty($_GET['search'])) {
                $where .= " AND (original_filename LIKE :search OR id LIKE :search2)";
                $params[':search'] = '%' . $_GET['search'] . '%';
                $params[':search2'] = '%' . $_GET['search'] . '%';
            }

            // Total count
            $countQuery = "SELECT COUNT(*) as total FROM documents WHERE $where";
            $countStmt = $this->db->prepare($countQuery);
            foreach ($params as $k => $v) { $countStmt->bindValue($k, $v); }
            $countStmt->execute();
            $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Fetch page
            $query = "SELECT id, original_filename, mime_type, file_size, status, created_at 
                      FROM documents WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $stmt = $this->db->prepare($query);
            foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
            $stmt->execute();
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode([
                "documents" => $documents,
                "total" => $total,
                "page" => $page,
                "pages" => ceil($total / $limit)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch documents."]);
        }
    }

    // GET /api/documents/{id}
    public function getDocument($id) {
        try {
            $query = "SELECT * FROM documents WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                http_response_code(404);
                echo json_encode(["message" => "Document not found."]);
                return;
            }

            // Fetch extracted fields
            $fieldsQuery = "SELECT * FROM extracted_fields WHERE document_id = :id";
            $fieldsStmt = $this->db->prepare($fieldsQuery);
            $fieldsStmt->bindParam(":id", $id);
            $fieldsStmt->execute();
            $fields = $fieldsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch validation issues
            $issuesQuery = "SELECT * FROM validation_issues WHERE document_id = :id";
            $issuesStmt = $this->db->prepare($issuesQuery);
            $issuesStmt->bindParam(":id", $id);
            $issuesStmt->execute();
            $issues = $issuesStmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode([
                "document" => $document,
                "fields" => $fields,
                "issues" => $issues
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch document."]);
        }
    }

    // POST /api/documents (upload)
    public function uploadDocument() {
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(["message" => "No file uploaded."]);
            return;
        }

        $file = $_FILES['file'];
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = 10 * 1024 * 1024; // 10 MB

        // Validate MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes)) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid file type. Allowed: PDF, JPG, JPEG, PNG."]);
            return;
        }

        // Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid file extension."]);
            return;
        }

        // Validate size
        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(["message" => "File too large. Maximum 10MB."]);
            return;
        }

        try {
            $id = $this->generateUUID();
            $storedName = $this->generateUUID() . '.' . $ext;
            $uploadDir = __DIR__ . '/../storage/uploads/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Move file
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
                http_response_code(500);
                echo json_encode(["message" => "Failed to save file."]);
                return;
            }

            // Get uploader UID from request (in production: extract from verified token)
            $data = $_POST;
            $uploaderUid = $data['uploader_uid'] ?? 'unknown';

            $query = "INSERT INTO documents (id, original_filename, stored_filename, mime_type, file_size, uploader_uid) 
                      VALUES (:id, :orig, :stored, :mime, :size, :uid)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":orig", $file['name']);
            $stmt->bindParam(":stored", $storedName);
            $stmt->bindParam(":mime", $mime);
            $stmt->bindParam(":size", $file['size']);
            $stmt->bindParam(":uid", $uploaderUid);
            $stmt->execute();

            // Audit log
            $auditId = $this->generateUUID();
            $auditQuery = "INSERT INTO audit_logs (id, actor_uid, action, target_type, target_id) VALUES (:id, :uid, 'DOCUMENT_UPLOADED', 'documents', :doc_id)";
            $auditStmt = $this->db->prepare($auditQuery);
            $auditStmt->bindParam(":id", $auditId);
            $auditStmt->bindParam(":uid", $uploaderUid);
            $auditStmt->bindParam(":doc_id", $id);
            $auditStmt->execute();

            http_response_code(201);
            echo json_encode([
                "message" => "Document uploaded successfully.",
                "document_id" => $id
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to upload document."]);
        }
    }

    // POST /api/documents/{id}/process
    public function processDocument($id) {
        try {
            // Update status to PROCESSING
            $update = "UPDATE documents SET status = 'PROCESSING' WHERE id = :id";
            $stmt = $this->db->prepare($update);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            // Call AI service
            $aiUrl = getenv('AI_API_URL') ?: 'http://localhost:8000';
            
            // Get stored filename
            $docQuery = "SELECT stored_filename FROM documents WHERE id = :id";
            $docStmt = $this->db->prepare($docQuery);
            $docStmt->bindParam(":id", $id);
            $docStmt->execute();
            $doc = $docStmt->fetch(PDO::FETCH_ASSOC);

            if (!$doc) {
                http_response_code(404);
                echo json_encode(["message" => "Document not found."]);
                return;
            }

            $filePath = __DIR__ . '/../storage/uploads/' . $doc['stored_filename'];

            // Call FastAPI with file
            $ch = curl_init($aiUrl . '/process-document');
            $cfile = new CURLFile($filePath);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) {
                // Mark as failed
                $failUpdate = "UPDATE documents SET status = 'FAILED' WHERE id = :id";
                $failStmt = $this->db->prepare($failUpdate);
                $failStmt->bindParam(":id", $id);
                $failStmt->execute();

                http_response_code(502);
                echo json_encode(["message" => "AI service unavailable or processing failed."]);
                return;
            }

            $result = json_decode($response, true);

            // Store extracted fields
            $hasLowConfidence = false;
            $confidenceThreshold = 0.75;

            if (isset($result['fields']) && is_array($result['fields'])) {
                foreach ($result['fields'] as $field) {
                    $fieldId = $this->generateUUID();
                    $requiresReview = ($field['confidence'] ?? 0) < $confidenceThreshold;
                    $status = $requiresReview ? 'review_required' : 'pending';
                    if ($requiresReview) $hasLowConfidence = true;

                    $fieldQuery = "INSERT INTO extracted_fields (id, document_id, field_name, ai_value, confidence, source_page, status)
                                   VALUES (:id, :doc_id, :name, :value, :conf, :page, :status)";
                    $fieldStmt = $this->db->prepare($fieldQuery);
                    $fieldStmt->bindParam(":id", $fieldId);
                    $fieldStmt->bindParam(":doc_id", $id);
                    $fieldStmt->bindParam(":name", $field['name']);
                    $fieldStmt->bindParam(":value", $field['value']);
                    $fieldStmt->bindParam(":conf", $field['confidence']);
                    $fieldStmt->bindValue(":page", $field['page'] ?? 1);
                    $fieldStmt->bindParam(":status", $status);
                    $fieldStmt->execute();

                    // Create validation issue for low confidence
                    if ($requiresReview) {
                        $issueId = $this->generateUUID();
                        $reason = "Low confidence extraction score (" . round($field['confidence'] * 100, 1) . "%)";
                        $issueQuery = "INSERT INTO validation_issues (id, document_id, field_name, reason, severity) 
                                       VALUES (:id, :doc_id, :field, :reason, 'Medium')";
                        $issueStmt = $this->db->prepare($issueQuery);
                        $issueStmt->bindParam(":id", $issueId);
                        $issueStmt->bindParam(":doc_id", $id);
                        $issueStmt->bindParam(":field", $field['name']);
                        $issueStmt->bindParam(":reason", $reason);
                        $issueStmt->execute();
                    }
                }
            }

            // Update document status
            $newStatus = $hasLowConfidence ? 'REVIEW REQUIRED' : 'EXTRACTED';
            $statusUpdate = "UPDATE documents SET status = :status WHERE id = :id";
            $statusStmt = $this->db->prepare($statusUpdate);
            $statusStmt->bindParam(":status", $newStatus);
            $statusStmt->bindParam(":id", $id);
            $statusStmt->execute();

            // Notify officers if review required
            if ($hasLowConfidence) {
                require_once __DIR__ . '/NotificationController.php';
                $officerQuery = "SELECT id FROM users WHERE role = 'officer' LIMIT 1";
                $officerStmt = $this->db->query($officerQuery);
                $officer = $officerStmt->fetch(PDO::FETCH_ASSOC);
                if ($officer) {
                    NotificationController::create(
                        $this->db,
                        $officer['id'],
                        'review_required',
                        'Low Confidence Extraction',
                        'Document requires manual review due to low-confidence fields.',
                        $id,
                        'warning'
                    );
                }
            }

            // Audit log
            $auditId = $this->generateUUID();
            $auditQuery = "INSERT INTO audit_logs (id, actor_uid, action, target_type, target_id) VALUES (:id, 'system', 'OCR_PROCESSED', 'documents', :doc_id)";
            $auditStmt = $this->db->prepare($auditQuery);
            $auditStmt->bindParam(":id", $auditId);
            $auditStmt->bindParam(":doc_id", $id);
            $auditStmt->execute();

            http_response_code(200);
            echo json_encode([
                "message" => "Processing complete.",
                "status" => $newStatus,
                "fields_count" => count($result['fields'] ?? [])
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Processing failed."]);
        }
    }

    // PATCH /api/documents/{id} (update fields)
    public function updateFields($id) {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['fields']) || !is_array($data['fields'])) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid fields payload."]);
            return;
        }

        $officerUid = $data['officerUid'] ?? 'unknown';

        try {
            $this->db->beginTransaction();

            foreach ($data['fields'] as $field) {
                // Log correction in audit if changed
                if (isset($field['aiValue']) && $field['aiValue'] !== $field['officerValue']) {
                    $auditId = $this->generateUUID();
                    $auditQuery = "INSERT INTO audit_logs (id, actor_uid, action, target_type, target_id, old_value, new_value) 
                                   VALUES (:id, :uid, 'FIELD_CORRECTED', 'extracted_fields', :field_id, :old, :new)";
                    $auditStmt = $this->db->prepare($auditQuery);
                    $auditStmt->bindParam(":id", $auditId);
                    $auditStmt->bindParam(":uid", $officerUid);
                    $auditStmt->bindParam(":field_id", $field['id']);
                    $auditStmt->bindParam(":old", $field['aiValue']);
                    $auditStmt->bindParam(":new", $field['officerValue']);
                    $auditStmt->execute();
                }

                // Update final value
                $updateQuery = "UPDATE extracted_fields 
                                SET officer_value = :officer_value, final_value = :final_value, status = 'corrected' 
                                WHERE id = :field_id AND document_id = :doc_id";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindParam(":officer_value", $field['officerValue']);
                $updateStmt->bindParam(":final_value", $field['officerValue']);
                $updateStmt->bindParam(":field_id", $field['id']);
                $updateStmt->bindParam(":doc_id", $id);
                $updateStmt->execute();
            }

            $this->db->commit();
            http_response_code(200);
            echo json_encode(["message" => "Fields updated successfully."]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Failed to update fields."]);
        }
    }

    // POST /api/documents/{id}/verify
    public function verifyDocument($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $officerUid = $data['officer_uid'] ?? 'unknown';

        try {
            $this->db->beginTransaction();

            // Update document status
            $update = "UPDATE documents SET status = 'VERIFIED' WHERE id = :id";
            $stmt = $this->db->prepare($update);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            // Create verification record
            $verId = $this->generateUUID();
            $verificationId = 'VER-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $verQuery = "INSERT INTO verification_records (id, document_id, verification_id, officer_uid) 
                         VALUES (:id, :doc_id, :ver_id, :uid)";
            $verStmt = $this->db->prepare($verQuery);
            $verStmt->bindParam(":id", $verId);
            $verStmt->bindParam(":doc_id", $id);
            $verStmt->bindParam(":ver_id", $verificationId);
            $verStmt->bindParam(":uid", $officerUid);
            $verStmt->execute();

            // Mark all fields as verified
            $fieldUpdate = "UPDATE extracted_fields SET status = 'verified', 
                            final_value = COALESCE(officer_value, ai_value) 
                            WHERE document_id = :id";
            $fieldStmt = $this->db->prepare($fieldUpdate);
            $fieldStmt->bindParam(":id", $id);
            $fieldStmt->execute();

            // Audit log
            $auditId = $this->generateUUID();
            $auditQuery = "INSERT INTO audit_logs (id, actor_uid, action, target_type, target_id) 
                           VALUES (:id, :uid, 'RECORD_VERIFIED', 'documents', :doc_id)";
            $auditStmt = $this->db->prepare($auditQuery);
            $auditStmt->bindParam(":id", $auditId);
            $auditStmt->bindParam(":uid", $officerUid);
            $auditStmt->bindParam(":doc_id", $id);
            $auditStmt->execute();

            $this->db->commit();

            http_response_code(200);
            echo json_encode([
                "message" => "Document verified successfully.",
                "verification_id" => $verificationId
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Verification failed."]);
        }
    }

    // POST /api/documents/{id}/reject
    public function rejectDocument($id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $officerUid = $data['officer_uid'] ?? 'unknown';
        $reason = $data['reason'] ?? '';

        try {
            $update = "UPDATE documents SET status = 'CORRECTION REQUIRED' WHERE id = :id";
            $stmt = $this->db->prepare($update);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            // Audit log
            $auditId = $this->generateUUID();
            $auditQuery = "INSERT INTO audit_logs (id, actor_uid, action, target_type, target_id, new_value) 
                           VALUES (:id, :uid, 'RECORD_REJECTED', 'documents', :doc_id, :reason)";
            $auditStmt = $this->db->prepare($auditQuery);
            $auditStmt->bindParam(":id", $auditId);
            $auditStmt->bindParam(":uid", $officerUid);
            $auditStmt->bindParam(":doc_id", $id);
            $auditStmt->bindParam(":reason", $reason);
            $auditStmt->execute();

            http_response_code(200);
            echo json_encode(["message" => "Document rejected."]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Rejection failed."]);
        }
    }

    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}
?>
