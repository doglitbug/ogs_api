<?php

class AuthController
{
    public function __construct(private Database $database)
    {
    }

    public function processRequest(string $verb, string $id, array $data, array $tokenData): void
    {
        //Log in user
        if ($verb != "POST") {
            http_response_code(405);
            header('ALLOW: POST');
        }

        if (!array_key_exists('username', $data) || !array_key_exists('password', $data)) {
            error(400, "Missing login credentials");
        }

        $user = $this->get_user_for_login($data['username']);

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

        echo json_encode(["message" => "success", "token" => $token]);
        die();
    }

    /** Get user by username for logging in
     * @param string $username Username
     * @return array|null User details
     */
    public function get_user_for_login(string $username): array|null
    {
        $query = <<<SQL
        SELECT  user_id,
                username,
                name,
                email,
                location_id,
                location.description as location,
                locked_out,
                IFNULL(admin.description, 'User') as role,
                user.created_at,
                user.updated_at
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
