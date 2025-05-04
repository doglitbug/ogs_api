<?php

class GarageController extends Controller
{
    public function processGetRequest(string $id, array $data): void
    {
        if ($id) {
            $result = $this->get_garage($id, $data);
            if ($result) {
                json_response(["garage" => $result]);
            } else {
                error(404, "Garage not found");
            }
        }
        //TODO Check null, visible, owner or worker etc

        $result = $this->get_garages($data);
        if ($result) {
            json_response(["garage" => ["garages" => $result]]);
        } else {
            error(404, "No Garages found");
        }
        //TODO Check null, visible, owner or worker etc
    }

    /** Get an individual garage
     * @param string $garage_id
     * @param array $data
     * @return array
     */
    private function get_garage(string $garage_id, array $data): array
    {
        $types = "";
        $values = array();

        $query = <<<SQL
        SELECT  garage_id,
                name,
                garage.description,
                location.description as location,
                location.location_id,
                visible,
                garage.updated_at,
                garage.created_at
        FROM garage
        LEFT JOIN location using (location_id)
        WHERE garage_id = ?
        LIMIT 1
        SQL;

        $types .= "s";
        $values[] = $garage_id;

        $where_and = "WHERE";

        if (isset($data['visible'])) {
            $query .= <<<SQL
                $where_and visible = ?
            SQL;
            $types .= "s";
            $values[] = $data['visible'];
            $where_and = "AND";
        }

        $result = $this->database->get_query($query, $types, $values, $data);

        return $result ? $result[0] : [];
    }

    public function get_garages(array $data): array
    {
        $types = "";
        $values = array();

        $query = <<<SQL
        SELECT  garage_id,
                name,
                garage.description,
                location.description as location,
                visible,
                garage.updated_at,
                garage.created_at
        FROM garage
        LEFT JOIN location using (location_id)
        SQL;

        $where_and = "WHERE";

        if (isset($data['visible'])) {
            $query .= <<<SQL
                $where_and visible = ?
            SQL;
            $types .= "s";
            $values[] = $data['visible'];
            $where_and = "AND";
        }

        if (isset($data['search']) && $data['search']) {
            $data['search'] = '%' . $data['search'] . '%';
            $query .= <<<SQL
                $where_and (name LIKE ?
                OR garage.description LIKE ?)
            SQL;
            $types .= "ss";
            array_push($values, $data['search'], $data['search']);
            $where_and = "AND";
        }

        return $this->database->get_query($query, $types, $values, $data);
    }
}
