<?php

require_once __DIR__ . '/../models/ApiKey.php';

class ApiController {

    public function createKey($user_id){

        $api = new ApiKey();

        $key = $api->generate($user_id);

        return $key;
    }

}