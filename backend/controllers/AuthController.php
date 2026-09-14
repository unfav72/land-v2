<?php

// backend/controllers/AuthController.php

class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function verify() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->idToken)) {
            http_response_code(400);
            echo json_encode(["message" => "No ID token provided."]);
            return;
        }

        $idTokenString = $data->idToken;

        // In a real production application, you should verify the Firebase ID token properly.
        // For demonstration/hackathon purposes without Composer/Google Client library installed,
        // we parse the JWT but skip cryptographic verification unless the Google API PHP Client is added.
        
        // --- SIMPLIFIED PARSING (DO NOT USE IN PRODUCTION WITHOUT VERIFICATION) ---
        $parts = explode('.', $idTokenString);
        if (count($parts) !== 3) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid ID token format."]);
            return;
        }

        $payload = json_decode(base64_decode($parts[1]));

        if (!$payload || !isset($payload->user_id)) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid ID token payload."]);
            return;
        }

        $firebaseUid = $payload->user_id;
        $email = isset($payload->email) ? $payload->email : '';
        $name = isset($payload->name) ? $payload->name : '';

        // Check if user exists in database
        $query = "SELECT id, role, status FROM users WHERE firebase_uid = :firebase_uid LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":firebase_uid", $firebaseUid);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['status'] !== 'active') {
                http_response_code(403);
                echo json_encode(["message" => "User account is inactive."]);
                return;
            }
            
            http_response_code(200);
            echo json_encode([
                "message" => "Login successful.",
                "user" => [
                    "id" => $user['id'],
                    "role" => $user['role']
                ]
            ]);
        } else {
            // Register new user (defaulting to public role, unless specifically mapped otherwise)
            // For SIH MVP, we can auto-register them as 'public'
            $newId = $this->generateUUID();
            $insertQuery = "INSERT INTO users (id, firebase_uid, email, display_name, role) VALUES (:id, :firebase_uid, :email, :name, 'public')";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bindParam(":id", $newId);
            $insertStmt->bindParam(":firebase_uid", $firebaseUid);
            $insertStmt->bindParam(":email", $email);
            $insertStmt->bindParam(":name", $name);
            
            if ($insertStmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    "message" => "User registered successfully.",
                    "user" => [
                        "id" => $newId,
                        "role" => "public"
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to register user."]);
            }
        }
    }

    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>
