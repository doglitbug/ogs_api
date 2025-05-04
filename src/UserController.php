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
            json_response(["user" => $result]);
        } else {
            error(404, "User not found");
        }
    }

    public function processPutRequest(string $id, array $data): void
    {
        //Looking at any user details requires being logged in to prevent web scraping bots
        if (!$this->isLoggedIn()) {
            error(403, "Please log in to continue");
        }

        if ($id == "") $id = $this->getCurrentUserID();

        //TODO Check we are allowed to update this user, either self OR admin

        //TODO Check valid data!
        $errors = [];
        //$errors['name']="Please enter a real name";

        if ($errors) {
            error(400, $errors);
        }

        $result = $this->updateUser($id, $data);

        if ($result) {
            json_response(["user" => $result]);
        } else {
            error(500, "Error updating User");
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
                IFNULL(admin.description, 'User') as role,
                user.description
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

    public function updateUser(string $id, array $data): array
    {
        $query = <<<SQL
        UPDATE user SET username = ?,
                        name = ?,
                        description = ?,
                        location_id = ?
        WHERE user_id = ?
        LIMIT 1
        SQL;

        $this->database->update_query($query, "sssss",
            [$data['username'],
                $data['name'],
                $data['description'],
                $data['location_id'],
                $id
            ]);

        return $this->getUser($id);
    }
}
