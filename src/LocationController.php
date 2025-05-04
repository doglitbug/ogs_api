<?php

class LocationController extends Controller
{
    public function processGetRequest(string $id, array $data): void
    {
        $result = $this->get_locations();
        if ($result) {
            json_response(["location" => ["locations" => $result]]);
        } else {
            error(404, "No locations found", [$data]);
        }
    }

    /** Get locations
     * @param array $data Unused, maybe used for a search later?
     * @return array
     */
    public function get_locations(array $data = []): array
    {
        $query = <<<SQL
        SELECT  location.location_id,
                location.description
        FROM location
        ORDER BY description = "Unknown" DESC, description
        SQL;

        return $this->database->get_query($query);
    }
}