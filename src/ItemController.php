<?php

class ItemController extends Controller
{
    public function processGetRequest(string $id, array $data): void
    {
        if ($id) {
            $result = $this->database->get_item($id);
            if ($result) {
                json_response(["item" => $result]);
            } else {
                error(404, "Item not found");
            }
        }

        $result = $this->database->get_items($data);
        if ($result) {
            json_response(["items" => $result]);
        } else {
            error(404, "No items found", [$data]);
        }
    }
}
