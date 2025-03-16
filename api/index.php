<?php
require_once('../src/initialize.php');
require_once("../src/Jwt.php");
$JwtController = new Jwt($_ENV["SECRET_KEY"]);

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$parts = explode("/", $path);

$version = $parts[2];
$resource = $parts[3] ?? null;
$id = $parts[4] ?? null;

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

//TODO even if post from a form?
if ($contentType !== 'application/json') {
    error(415, "Only JSON content is supported");
}

$data = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

//No token required
switch ($resource) {
    case "login":
        require_once("../src/login.php");
        break;
    case "garage":
        require_once("../src/GarageController");
        $controller = new GarageController($db);
        $controller->processRequest($method, $id, $data);
        break;
    default:
        echo json_encode(["message" => "Unknown resource: $resource"]);
        break;
}

//Log in required
$valid = $JwtController->authenticateJWTToken();
