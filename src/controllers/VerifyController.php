<?php

require_once __DIR__ . '/../models/Identity.php';

class VerifyController {

    public function submitVerification($user_id, $document_type, $document_number){

        $identity = new Identity();

        if($identity->create($user_id, $document_type, $document_number)){

            echo "Verification submitted successfully.";

        } else {

            echo "Verification failed.";

        }

    }

}