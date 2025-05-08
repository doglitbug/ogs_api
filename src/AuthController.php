<?php

class AuthController extends Controller
{
    public function processPostRequest(array $data): void
    {
        if (!array_key_exists('username', $data) || !array_key_exists('password', $data)) {
            error(400, "Missing login credentials");
        }

        $user = $this->database->get_user_by_username($data['username']);

        if (!$user) {
            error(401, "Invalid username or password");
        }

        // if (!password_verify($data['password'], $user['password_hash'])) {
        //     error(401, "Invalid username or password");
        // }

        if ($data['password'] == "") {
            error(401, "Invalid username or password");
        }

        if ($user["locked_out"] === 1) {
            error(403, "Account locked out, please see Admin");
        }

        //TODO Remove location from here when loading/caching on all pages
        $payload = [
            "username" => $user["username"],
            "name" => $user["name"],
            "email" => $user["email"],
            "user_id" => $user['user_id'],
            "location_id" => $user["location_id"],
            "location" => $user["location"],
            "role" => $user["role"],
        ];

        require_once("../src/Jwt.php");
        $JwtController = new Jwt($_ENV["SECRET_KEY"]);
        $token = $JwtController->encode($payload);

        json_response(["token" => $token]);
    }
}
