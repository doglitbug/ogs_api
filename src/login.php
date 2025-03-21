<?php
global $db, $JwtController;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('ALLOW: POST');
    exit();
}

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType !== 'application/json') {
    error(415, "Only JSON content is supported");
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data === null) {
    error(400, "Invalid JSON data");
}

if (!array_key_exists('username', $data) || !array_key_exists('password', $data)) {
    error(400, "Missing login credentials");
}

$user = $db->get_user_by_username($data['username']);

if (!$user) {
    error(401, "Invalid username or password");
}

if ($user["locked_out"] === 1) {
    error(403, "Account locked out, please see Admin");
}

// if (!password_verify($data['password'], $user['password_hash'])) {
//     error(401, "Invalid username or password");
// }

$payload = [
    "id" => $user['user_id'],
    "username" => $user["username"],
    "name" => $user["name"],
    "role" => $user["role"]
];

//$payload = $user;

$token = $JwtController->encode($payload);

echo json_encode(["message" => "success", "token" => $token]);
die();