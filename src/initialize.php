<?php
//Load Environment variables
require_once('DotEnv.php');
$dotenv = new DotEnv('../.env');
$dotenv->load();

// Allow from any origin
if (isset($_SERVER["HTTP_ORIGIN"])) {
    // You can decide if the origin in $_SERVER['HTTP_ORIGIN'] is something you want to allow, or as we do here, just allow all
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    //No HTTP_ORIGIN set, so we allow any. You can disallow if needed here
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 600");    // cache for 10 minutes

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"]))
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT"); //Make sure you remove those you do not want to support

    if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    //Just exit with 200 OK with the above headers for OPTIONS method
    exit(0);
}
header("Content-type: application/json; charset=UTF-8");

/**
 * Generic error handler
 * @param int $statusCode HTTP Code for error
 * @param string $message Message
 * @param string $e Extended error message for debugging
 * @return never
 */
function error(int $statusCode, string $message, string $e = "")
{
    http_response_code($statusCode);
    $output["message"] = $message;
    if ($_ENV['APPLICATION_ENV'] === "DEV") {
        $output["extended"] = $e;
    }
    echo json_encode($output);
    if (isset($db))
        $db->disconnect();
    die();
}

//Connect to database
require_once('Database.php');
$db = new Database();
$db->connect();
