<?php
//Load Environment variables
require_once('DotEnv.php');
$dotenv = new DotEnv('../.env');
$dotenv->load();

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
    $output["error"] = $message;
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
