<?php
//Load Environment variables

use JetBrains\PhpStorm\NoReturn;

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
 * @param string $error Message
 * @param string|array $extended Extended error message or an array for debugging
 */
#[NoReturn] function error(int $statusCode, string $error, string|array $extended = ""): void
{
    $output["error"] = $error;
    if ($_ENV['APPLICATION_ENV'] === "DEV") {
        $output["extended"] = $extended;
    }

    json_response($output, $statusCode);
}

/** Output a JSON response and terminate
 * @param array $output Response to encode and sent
 * @param int $statusCode Defaults to 200
 * @return void
 */
#[NoReturn] function json_response(array $output, int $statusCode = 200): void
{
    if (isset($db))
        $db->disconnect();

    http_response_code($statusCode);
    echo json_encode($output);
    die();
}

//Connect to database
require_once('Database.php');
$db = new Database();
$db->connect();
