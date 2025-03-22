<?php
global $db;
require_once('../src/initialize.php');
require_once("../src/Jwt.php");
$JwtController = new Jwt($_ENV["SECRET_KEY"]);

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$parts = explode("/", $path);

$version = $parts[2]; // v1
$resource = $parts[3] ?? null;
$id = $parts[4] ?? null;

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

//TODO even if post from a form?
if ($contentType !== 'application/json') {
    error(415, "Only JSON content is supported");
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$params = [];
if (!empty($_SERVER['QUERY_STRING'])) {
    $parsed = parse_url('?' . $_SERVER['QUERY_STRING']);
    $query = $parsed['query'];
    parse_str($query, $params);
}
//Prefer $body over $params if duplicate keys
$data = array_merge($params, $body);

$JwtController->authenticateJWTToken();
$tokenData = $JwtController->data ?? [];
$method = $_SERVER['REQUEST_METHOD'];

switch ($resource) {
    case "login":
        require_once("../src/AuthController.php");
        //$controller = new AuthController($db);
        //$controller->login();
        break;
    case "garage":
        require_once("../src/GarageController.php");
        $controller = new GarageController($db);
        $controller->processRequest($method, $id, $data, $tokenData);
        break;
    case "item":
        require_once("../src/ItemController.php");
        $controller = new ItemController($db);
        $controller->processRequest($method, $id, $data, $tokenData);
        break;
    default:
        echo error(404, "Unknown resource: $resource", $data);
}