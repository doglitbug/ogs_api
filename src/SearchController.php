<?php

class SearchController extends Controller
{
    public function processRequest(string $verb, string $id, array $data): void
    {
        switch ($verb) {
            case "GET":
                $result = $this->get_items($data);
                if ($result) {
                    echo json_encode(["search" => ["items" => $result]]);
                } else {
                    error(404, "No items found", [$data]);
                }
            //TODO Check null, visible, owner or worker etc

        }
    }

    /** Get items, usually from an individual garage with primary image
     * @param array $options garage_id: Filter to particular garage
     *                       search: Filter to search
     *                       visible: Hide hidden items (required for pagination to work)
     *                       paginate: Use pagination to return only a subset
     * @return array
     */
    public function get_items(array $options = []): array
    {
        $types = "";
        $values = array();

        $query = <<<SQL
        SELECT  item.item_id,
                garage_id,
                item.name,
                item.description,
                item.visible,
                item.updated_at,
                item.created_at,
                image.image_id,
                image.width,
                image.height,
                location.description as location,
                CONCAT(path, '/', filename) as source
        FROM item
        LEFT JOIN LATERAL (SELECT   *
                                    FROM item_image
                                    WHERE item.item_id = item_image.item_id
                                    ORDER BY main DESC
                                    LIMIT 1) as iii
        using (item_id)
        LEFT JOIN (SELECT   *
                            FROM image) as image using (image_id)
        LEFT JOIN garage USING (garage_id)
        LEFT JOIN location USING (location_id)
        SQL;

        $where_and = "WHERE";

        if (isset($options['garage_id'])) {
            $query .= <<<SQL
                $where_and garage_id = ?
            SQL;
            $types .= "s";
            $values[] = $options['garage_id'];
            $where_and = "AND";
        }

        if (isset($options['visible'])) {
            $query .= <<<SQL
            $where_and item.visible = '1' AND garage.visible = '1'
        SQL;
            $where_and = "AND";
        }

        if (isset($options['search']) && $options['search'] != "") {
            $query .= <<<SQL
                $where_and MATCH (item.name, item.description) AGAINST (?)
            SQL;
            $types .= "s";
            $values[] = $options['search'];
            $where_and = "AND";
        }

        return $this->database->get_query($query, $types, $values, $options);
    }
}