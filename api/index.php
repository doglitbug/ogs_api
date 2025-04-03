<?php
global $db;
require_once('../src/initialize.php');
require_once("../src/Jwt.php");
$JwtController = new Jwt($_ENV["SECRET_KEY"]);


$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$parts = explode("/", $path);

$version = $parts[2]; // v2
$resource = $parts[3] ?? null;
$id = $parts[4] ?? "";

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

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
//Prefer $body over $params if duplicate keys exist
$data = array_merge($params, $body);

//TODO Check data is valid and without SQL injection etc

$JwtController->authenticateJWTToken();
$tokenData = $JwtController->data ?? [];
$verb = $_SERVER['REQUEST_METHOD'];

switch ($resource) {
    case "login":
        require_once("../src/AuthController.php");
        $controller = new AuthController($db);
        $controller->processRequest($verb, "", $data, []);
        break;
    case "garage":
        require_once("../src/GarageController.php");
        $controller = new GarageController($db);
        $controller->processRequest($verb, $id, $data, $tokenData);
        break;
    case "item":
        require_once("../src/ItemController.php");
        $controller = new ItemController($db);
        $controller->processRequest($verb, $id, $data, $tokenData);
        break;
    case "search":
        require_once("../src/SearchController.php");
        $controller = new SearchController($db);
        $controller->processRequest($verb, $id, $data, $tokenData);
        break;
    default:
        echo error(404, "Unknown resource: $resource", $data);
}