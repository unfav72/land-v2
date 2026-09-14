<?php

// backend/controllers/NotificationController.php

class NotificationController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listNotifications() {
        // In production: extract user UID from verified Firebase token
        // For now, return all notifications (or filter by query param)
        try {
            $query = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 50";
            $stmt = $this->db->query($query);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Count unread
            $unreadQuery = "SELECT COUNT(*) as count FROM notifications WHERE is_read = 0";
            $unreadStmt = $this->db->query($unreadQuery);
            $unreadCount = (int)$unreadStmt->fetch(PDO::FETCH_ASSOC)['count'];

            http_response_code(200);
            echo json_encode([
                "unread_count" => $unreadCount,
                "notifications" => $notifications
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch notifications."]);
        }
    }

    public function markRead($id) {
        try {
            $query = "UPDATE notifications SET is_read = 1 WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            http_response_code(200);
            echo json_encode(["message" => "Notification marked as read."]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update notification."]);
        }
    }

    // Helper: create a notification (called from other controllers)
    public static function create($db, $recipientUid, $type, $title, $message, $targetId, $severity = 'info') {
        $id = self::generateUUID();
        $query = "INSERT INTO notifications (id, recipient_uid, type, title, message, target_id, severity) 
                  VALUES (:id, :uid, :type, :title, :message, :target_id, :severity)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":uid", $recipientUid);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $message);
        $stmt->bindParam(":target_id", $targetId);
        $stmt->bindParam(":severity", $severity);
        $stmt->execute();
        return $id;
    }

    private static function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}
?>
