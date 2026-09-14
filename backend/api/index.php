<?php

// backend/api/index.php
// Main API router for InfinityFree deployment

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE,PATCH");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DocumentController.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../controllers/RequestController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($uri, '/'));

// Find "api" segment and get resource/action after it
$apiIndex = array_search('api', $uri);
if ($apiIndex === false) {
    http_response_code(404);
    echo json_encode(["message" => "API endpoint not found."]);
    exit();
}

$resource = isset($uri[$apiIndex + 1]) ? $uri[$apiIndex + 1] : null;
$id = isset($uri[$apiIndex + 2]) ? $uri[$apiIndex + 2] : null;
$action = isset($uri[$apiIndex + 3]) ? $uri[$apiIndex + 3] : null;
$method = $_SERVER['REQUEST_METHOD'];

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(["message" => "Database connection failed."]);
    exit();
}

switch ($resource) {
    // --- AUTH ---
    case 'auth':
        $controller = new AuthController($db);
        if ($id === 'verify' && $method === 'POST') {
            $controller->verify();
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Auth endpoint not found."]);
        }
        break;

    // --- DASHBOARD ---
    case 'dashboard':
        $controller = new DashboardController($db);
        if ($method === 'GET') {
            $controller->getStats();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed."]);
        }
        break;

    // --- DOCUMENTS ---
    case 'documents':
        $controller = new DocumentController($db);
        if ($method === 'GET' && !$id) {
            $controller->listDocuments();
        } elseif ($method === 'GET' && $id && !$action) {
            $controller->getDocument($id);
        } elseif ($method === 'GET' && $id && $action === 'extraction') {
            $controller->getDocument($id);
        } elseif ($method === 'POST' && !$id) {
            $controller->uploadDocument();
        } elseif ($method === 'POST' && $id && $action === 'process') {
            $controller->processDocument($id);
        } elseif ($method === 'POST' && $id && $action === 'verify') {
            $controller->verifyDocument($id);
        } elseif ($method === 'POST' && $id && $action === 'reject') {
            $controller->rejectDocument($id);
        } elseif ($method === 'PATCH' && $id) {
            $controller->updateFields($id);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Document endpoint not found."]);
        }
        break;

    // --- NOTIFICATIONS ---
    case 'notifications':
        $controller = new NotificationController($db);
        if ($method === 'GET' && !$id) {
            $controller->listNotifications();
        } elseif ($method === 'PATCH' && $id && $action === 'read') {
            $controller->markRead($id);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Notification endpoint not found."]);
        }
        break;

    // --- DIGITIZATION REQUESTS ---
    case 'requests':
        $controller = new RequestController($db);
        if ($method === 'GET' && !$id) {
            $controller->listRequests();
        } elseif ($method === 'GET' && $id) {
            $controller->getRequest($id);
        } elseif ($method === 'POST' && !$id) {
            $controller->createRequest();
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Request endpoint not found."]);
        }
        break;

    // --- QR VERIFICATION (Public) ---
    case 'verify':
        if ($method === 'GET' && $id) {
            $query = "SELECT vr.verification_id, vr.verified_at, d.status 
                      FROM verification_records vr 
                      JOIN documents d ON vr.document_id = d.id 
                      WHERE vr.verification_id = :vid LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":vid", $id);
            $stmt->execute();
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($record) {
                http_response_code(200);
                echo json_encode(["verified" => true, "data" => $record]);
            } else {
                http_response_code(404);
                echo json_encode(["verified" => false, "message" => "Verification record not found."]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Verify endpoint not found."]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found."]);
        break;
}
?>
