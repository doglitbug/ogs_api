<?php

class GarageController extends Controller
{
    public function processGetRequest(string $id, array $data): void
    {
        if ($id) {
            $result = $this->database->get_garage($id);
            if ($result) {
                if ($this->isLoggedIn()) {
                    $result['staff'] = $this->database->get_garage_staff($id);
                }
                json_response(["garage" => $result]);
            } else {
                error(404, "Garage not found");
            }
        }

        $result = $this->database->get_garages($data);
        if ($result) {
            json_response(["garages" => $result]);
        } else {
            error(404, "No Garages found");
        }
    }

    public function processPostRequest(array $data): void
    {
        $this->requireLogin();

        $errors = validate_garage($data);

        if ($errors) {
            error(400, $errors);
        }

        $newID = $this->database->insert_garage($data);

        if ($newID) {
            //Assign ownership
            $this->database->set_user_garage_access($this->getCurrentUserID(), $newID, "Owner");

            //Get new garage
            $result = $this->database->get_garage($newID);

            json_response(["garage" => $result], 201);
        } else {
            error(500, "Error creating Garage");
        }
    }

    public function processPutRequest(string $id, array $data): void
    {
        $this->requireLogin();

        //TODO Check we are allowed to update this user, either self OR admin
        $data['garage_id'] = $id;
        $errors = validate_garage($data);

        if ($errors) {
            error(400, $errors);
        }

        $this->database->update_garage($data);

        //Get updated garage
        $result = $this->database->get_garage($id);

        if ($result) {
            json_response(["garage" => $result]);
        } else {
            error(500, "Error updating Garage");
        }
    }
}
