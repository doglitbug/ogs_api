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

    /** Protect login required functions
     * @return void
     */
    protected function requireLogin(): void
    {
        if (!$this->tokenData) {
            error(403, "Please log in to continue");
        }
    }

    /** Check to see if the current user is logged in or a guest
     * @return bool
     */
    protected function isLoggedIn(): bool
    {
        return (bool)$this->tokenData;
    }

    /** Check to see if this user is an Admin
     * @return bool
     */
    protected function isAdmin(): bool
    {
        return (bool)$this->tokenData["role"] == "Admin";
    }

    /** Get the currently logged-in users ID, assumes logged in check already done
     * @return string
     */
    protected function getCurrentUserID(): string
    {
        try {
            return $this->tokenData["user_id"];
        } catch (Exception $e) {
            error(500, "Unknown user state", $e);
        }
    }

    /**
     * @param string $verb GET | POST
     * @param string $id Resource ID
     * @param array $data Additional data
     * @return void
     */
    public function processRequest(string $verb, string $id, array $data): void
    {
        //TODO Uppercase verb, is this required?
        switch ($verb) {
            case "GET":
                $this->processGetRequest($id, $data);
                break;
            case "POST":
                //TODO Require log in here and on all following verbs?
                $this->processPostRequest($data);
                break;
            case "PUT":
                $this->processPutRequest($id, $data);
                break;
            case "DELETE":
                $this->processDeleteRequest($id, $data);
                break;
            default:
                error(500, "Unknown or unimplemented verb", $verb);
        }
    }

    /** Process a GET request to retrieve a resource
     * @param string $id resource ID
     * @param array $data Body and parameter data
     * @return void
     */
    protected function processGetRequest(string $id, array $data): void
    {
        error(500, "Not implemented", ["id" => $id, "data" => $data]);
    }

    /** Process a POST request to create a new resource
     * @param array $data Body and parameter data
     * @return void
     */
    protected function processPostRequest(array $data): void
    {
        error(500, "Not implemented", ["data" => $data]);
    }

    /** Process a PUT request to update an existing resource
     * This can be used to partially update as well at this time(no PATCH)
     * @param string $id resource ID
     * @param array $data Body and parameter data
     * @return void
     */
    protected function processPutRequest(string $id, array $data): void
    {
        error(500, "Not implemented", ["id" => $id, "data" => $data]);
    }

    /** Process a DELETE request to delete a resource
     * @param string $id resource ID
     * @param array $data Body and parameter data
     * @return void
     */
    protected function processDeleteRequest(string $id, array $data): void
    {
        error(500, "Not implemented", ["id" => $id, "data" => $data]);
    }
}
