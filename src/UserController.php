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

        $result = $this->database->get_user_by_id($id);
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
        $data['user_id'] = $id;
        $errors = validate_user($data);

        if ($errors) {
            error(400, $errors);
        }

        $result = $this->database->update_user($data);

        if ($result) {
            json_response(["user" => $result]);
        } else {
            error(500, "Error updating User");
        }
    }
}
