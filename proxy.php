<?php

/*
 * Makes requests to Shashin API
*/

$baseUrl = "<shashin_url>";
$apiKey = "<shashin_api_key>";
$data = file_get_contents('php://input');
$params = [];

if (!empty($data)) {
    $params = json_decode($data, true);
}

if (isset($params["offset"]) && isset($params["limit"]) && isset($params["startDate"]) && isset($params["endDate"])) {

    $offset = $params["offset"];
    $limit = $params["limit"];
    $startDate = $params["startDate"];
    $endDate = $params["endDate"];

    $baseUrl = "http://127.0.0.1:6624";
    $apiKey = "feb7e867-f325-37ea-9198-77d5a790e268";
    $apiUrl = $baseUrl."/api/v1/mapdata/keywords";

    $data = [
        'offset' => $offset,
        'limit' => $limit,
        'startDate' => $startDate,
        'endDate' => $endDate
    ];

    $headers = [
        'Content-Type: application/json',
        'X-API-KEY: ' . $apiKey
    ];

    // create curl resource
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $output = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        $outPutMap = json_decode($output, true);
        // Add base URL in response
        $outPutMap["baseUrl"] = $baseUrl;
        $json = json_encode($outPutMap);
        echo $json;
    }
}

exit();