<?php

class UserController extends Controller
{
    public function processGetRequest(string $id, array $data): void
    {
        //Looking at any user details requires being logged in to prevent web scraping bots
        if (!$this->isLoggedIn()) {
            error(403, "Please log in to continue");
        }

        if ($id == "") $id = $this->getCurrentUserID();

        $result = $this->getUser($id);
        if ($result) {
            echo json_response(["user" => $result]);
        } else {
            error(404, "user not found");
        }
    }

    public function getUser(string $user_id): array|null
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
        WHERE user_id = ?
        LIMIT 1
        SQL;

        $result = $this->database->get_query($query, "s", [$user_id]);
        return $result ? $result[0] : null;
    }
}
