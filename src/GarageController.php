<?php

class GarageController extends Controller
{
    public function processGetRequest(string $id, array $options): void
    {
        if ($id) {
            $result = $this->get_garage($id, $options);
            if ($result) {
                json_response(["garage" => $result]);
            } else {
                error(404, "Garage not found");
            }
        }
        //TODO Check null, visible, owner or worker etc

        $result = $this->get_garages($options);
        if ($result) {
            echo json_response(["garages" => ["results" => $result]]);
        } else {
            error(404, "No Garages found");
        }
        //TODO Check null, visible, owner or worker etc
    }

    /** Get an individual garage
     * @param string $garage_id
     * @param array $options
     * @return array
     */
    private function get_garage(string $garage_id, array $options): array
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

        if (isset($options['visible'])) {
            $query .= <<<SQL
                $where_and visible = ?
            SQL;
            $types .= "s";
            $values[] = $options['visible'];
            $where_and = "AND";
        }

        $result = $this->database->get_query($query, $types, $values, $options);

        return $result ? $result[0] : [];
    }

    public function get_garages(array $options): array
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

        if (isset($options['visible'])) {
            $query .= <<<SQL
                $where_and visible = ?
            SQL;
            $types .= "s";
            $values[] = $options['visible'];
            $where_and = "AND";
        }

        if (isset($options['search']) && $options['search']) {
            $options['search'] = '%' . $options['search'] . '%';
            $query .= <<<SQL
                $where_and (name LIKE ?
                OR garage.description LIKE ?)
            SQL;
            $types .= "ss";
            array_push($values, $options['search'], $options['search']);
            $where_and = "AND";
        }

        return $this->database->get_query($query, $types, $values, $options);
    }
}
