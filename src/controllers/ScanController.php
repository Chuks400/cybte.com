<?php

require_once __DIR__ . '/../models/ScanResult.php';

class ScanController {

    public function scanWebsite($url){

        $vulnerabilities = [];

        if(!filter_var($url, FILTER_VALIDATE_URL)){
            return ["error" => "Invalid URL"];
        }

        $headers = @get_headers($url);

        if(!$headers){
            $vulnerabilities[] = "Website unreachable";
        }

        if(strpos($url, "https") === false){
            $vulnerabilities[] = "Website not using HTTPS";
        }

        if(empty($vulnerabilities)){
            $vulnerabilities[] = "No obvious vulnerabilities detected";
            $severity = "Low";
        } else {
            $severity = "Medium";
        }

        $scan = new ScanResult();

        $scan->create(
            $url,
            implode(", ", $vulnerabilities),
            $severity
        );

        return [
            "vulnerabilities" => $vulnerabilities,
            "severity" => $severity
        ];
    }

}