<?php

class LocationController extends Controller
{
    public function processGetRequest(string $id, array $data): void
    {
        $result = $this->database->get_locations();
        if ($result) {
            json_response(["locations" => $result]);
        } else {
            error(404, "No locations found", [$data]);
        }
    }
}
