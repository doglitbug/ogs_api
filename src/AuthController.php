<?php

class AuthController extends Controller
{
    public function processPostRequest(string $id, array $data): void
    {
        if (!array_key_exists('username', $data) || !array_key_exists('password', $data)) {
            error(400, "Missing login credentials");
        }

        $user = $this->getUserForLogin($data['username']);

        if (!$user) {
            error(401, "Invalid username or password");
        }

        if ($user["locked_out"] === 1) {
            error(403, "Account locked out, please see Admin");
        }

        // if (!password_verify($data['password'], $user['password_hash'])) {
        //     error(401, "Invalid username or password");
        // }
        if ($data['password'] == "") {
            error(401, "Invalid username or password");
        }

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

    /** Get user by username for logging in
     * @param string $username Username
     * @return array|null User details
     */
    public function getUserForLogin(string $username): array|null
    {
        $query = <<<SQL
        SELECT  user_id,
                username,
                name,
                email,
                location_id,
                location.description as location,
                locked_out,
                IFNULL(admin.description, 'User') as role
        FROM user
        LEFT JOIN location using (location_id)
        LEFT JOIN user_admin using (user_id)
        LEFT JOIN admin using (admin_id)
        WHERE username = ?
        LIMIT 1
        SQL;

        $result = $this->database->get_query($query, "s", [$username]);
        return $result ? $result[0] : null;
    }
}
