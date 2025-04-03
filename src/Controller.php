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
     * @param string $verb GET | POST | PATCH etc
     * @param string $id resource ID from URL
     * @param array $data Body and parameter data
     * @return void
     */
    abstract public function processRequest(string $verb, string $id, array $data): void;
}
