<?php

abstract class Controller
{
    /**
     * @param Database $database Database
     * @param array $tokenData Provided (and verified) $tokenData from request
     */
    public function __construct(protected Database $database, protected array $tokenData = [])
    {
    }

    /**
     * @param string $verb GET | POST
     * @param string $id Resource ID
     * @param array $data Additional data
     * @return void
     */
    public function processRequest(string $verb, string $id, array $data):void{
        switch ($verb){
            case "GET":
                $this->processGetRequest($id, $data);
                break;
            case "POST":
                $this->processPostRequest($id, $data);
                break;
            default:
                error(500, "Unknown or unimplemented verb", $verb);
        }
    }

    /** Process a GET request
     * @param string $id resource ID
     * @param array $data Body and parameter data
     * @return void
     */
    protected function processGetRequest(string $id, array $data): void
    {
    }

    /** Process a POST request
     * @param string $id resource ID
     * @param array $data Body and parameter data
     * @return void
     */
    protected function processPostRequest(string $id, array $data): void
    {
    }

    /** Process a PUT request
     * @param string $id resource ID
     * @param array $data Body and parameter data
     * @return void
     */
    protected function processPutRequest(string $id, array $data): void
    {
    }

    /** Process a PATCH request
     * @param string $id resource ID
     * @param array $data Body and parameter data
     * @return void
     */
    protected function processPatchRequest(string $id, array $data): void
    {
    }

    /** Process a DELETE request
     * @param string $id resource ID
     * @param array $data Body and parameter data
     * @return void
     */
    protected function processDeleteRequest(string $id, array $data): void
    {
    }
}
