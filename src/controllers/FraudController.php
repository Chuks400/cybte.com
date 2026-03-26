<?php

require_once __DIR__ . '/../models/RiskScore.php';

class FraudController {

    public function analyzeTransaction($transaction_id, $amount, $location, $device){

        $risk_score = 0;

        if($amount > 1000){
            $risk_score += 40;
        }

        if($location == "unknown"){
            $risk_score += 30;
        }

        if($device == "new"){
            $risk_score += 30;
        }

        if($risk_score >= 70){
            $status = "High Risk";
        } elseif($risk_score >= 40){
            $status = "Medium Risk";
        } else {
            $status = "Low Risk";
        }

        $risk = new RiskScore();
        $risk->create($transaction_id, $risk_score, $status);

        return [
            "risk_score" => $risk_score,
            "status" => $status
        ];
    }

}