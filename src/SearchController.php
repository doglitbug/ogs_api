<?php

class SearchController extends Controller
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

        //Default to page 1
        //TODO Check if the page is actually a number
        if (!isset($data['page'])) {
            $data['page'] = 1;
        }

        $result = $this->database->get_items($data);
        if ($result) {
            $total = $this->database->get_items_total($data);
            json_response(["search" => $result,
                "current_page" => $data['page'],
                "last_page"=>intdiv($total, 12),
                "total_items" => $total]);
        } else {
            error(404, "No items found", [$data]);
        }
    }
}
