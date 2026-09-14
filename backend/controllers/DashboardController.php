<?php

// backend/controllers/DashboardController.php

class DashboardController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getStats() {
        try {
            $stats = [];

            // Total Documents
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM documents");
            $stats['total_documents'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Processing
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM documents WHERE status = 'PROCESSING'");
            $stmt->execute();
            $stats['processing'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Awaiting Review
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM documents WHERE status IN ('REVIEW REQUIRED', 'UNDER REVIEW')");
            $stmt->execute();
            $stats['awaiting_review'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Verified
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM documents WHERE status = 'VERIFIED'");
            $stmt->execute();
            $stats['verified'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Correction Required
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM documents WHERE status = 'CORRECTION REQUIRED'");
            $stmt->execute();
            $stats['correction_required'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Failed
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM documents WHERE status = 'FAILED'");
            $stmt->execute();
            $stats['failed'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Recent Documents
            $stmt = $this->db->query("SELECT id, original_filename, status, created_at FROM documents ORDER BY created_at DESC LIMIT 10");
            $stats['recent_documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode($stats);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch dashboard stats."]);
        }
    }
}
?>
