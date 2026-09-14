<?php

// backend/controllers/RequestController.php

class RequestController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listRequests() {
        try {
            $query = "SELECT * FROM digitization_requests ORDER BY created_at DESC LIMIT 50";
            $stmt = $this->db->query($query);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode(["requests" => $requests]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch requests."]);
        }
    }

    public function getRequest($id) {
        try {
            $query = "SELECT * FROM digitization_requests WHERE id = :id OR request_id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                http_response_code(404);
                echo json_encode(["message" => "Request not found."]);
                return;
            }

            http_response_code(200);
            echo json_encode(["request" => $request]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch request."]);
        }
    }

    public function createRequest() {
        $data = json_decode(file_get_contents("php://input"), true);

        $requiredFields = ['applicant_name', 'document_id', 'uploader_uid'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: $field"]);
                return;
            }
        }

        try {
            $id = $this->generateUUID();
            $requestId = 'LDR-' . date('Y') . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

            $query = "INSERT INTO digitization_requests 
                      (id, request_id, applicant_name, contact_info, village, taluk, district, document_type, survey_number, description, document_id, uploader_uid)
                      VALUES (:id, :request_id, :name, :contact, :village, :taluk, :district, :doc_type, :survey, :desc, :doc_id, :uid)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":request_id", $requestId);
            $stmt->bindParam(":name", $data['applicant_name']);
            $stmt->bindValue(":contact", $data['contact_info'] ?? null);
            $stmt->bindValue(":village", $data['village'] ?? null);
            $stmt->bindValue(":taluk", $data['taluk'] ?? null);
            $stmt->bindValue(":district", $data['district'] ?? null);
            $stmt->bindValue(":doc_type", $data['document_type'] ?? null);
            $stmt->bindValue(":survey", $data['survey_number'] ?? null);
            $stmt->bindValue(":desc", $data['description'] ?? null);
            $stmt->bindParam(":doc_id", $data['document_id']);
            $stmt->bindParam(":uid", $data['uploader_uid']);
            $stmt->execute();

            // Notify officers about new request
            require_once __DIR__ . '/NotificationController.php';
            // In production, query officer UIDs. For MVP, use first known officer.
            $officerQuery = "SELECT id FROM users WHERE role = 'officer' LIMIT 1";
            $officerStmt = $this->db->query($officerQuery);
            $officer = $officerStmt->fetch(PDO::FETCH_ASSOC);
            if ($officer) {
                NotificationController::create(
                    $this->db,
                    $officer['id'],
                    'new_request',
                    'New Digitization Request',
                    "A public user has submitted request $requestId",
                    $id,
                    'info'
                );
            }

            http_response_code(201);
            echo json_encode([
                "message" => "Request submitted successfully.",
                "request_id" => $requestId
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create request."]);
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
