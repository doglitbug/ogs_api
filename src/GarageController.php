<?php

class GarageController
{
    public function __construct(private Database $database)
    {
    }

    public function processRequest(string $verb, string $id, array $data, array $tokenData): void
    {
        switch ($verb) {
            case "GET":
                if ($id) {
                    $result = $this->get_garage($id);
                    if ($result) {
                        echo json_encode(["garage" => $result]);
                    } else {
                        error(404, "Garage not found");
                    }
                    //TODO Check null, visible, owner or worker etc
                } else {
                    $result = $this->get_garages();
                    if ($result) {
                        echo json_encode(["garages" => ["items" => $result]]);
                    } else {
                        error(404, "No Garages found");
                    }
                    //TODO Check null, visible, owner or worker etc
                }
                break;
            case "POST":
                break;

        }
    }

    /** Get an individual garage
     * @param string $garage_id
     * @return array
     */
    private function get_garage(string $garage_id): array
    {
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

        $result = $this->database->get_query($query, "s", [$garage_id]);

        return $result ? $result[0] : [];
    }

    public function get_garages(array $options = []): array
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
