<?php

/*
 * Makes requests to twitter API
*/

// if no coordinates found, use http://maps.google.com/maps/api/geocode/json?sensor=false&address=<location> to look up coordinate info
// eg. getCoordinates("The Netherlands");
function getCoordinates($location) {
    $opts = array(
        'http' => array(
            'method' => 'GET'
        )
    );

    // Make the request
    $context = stream_context_create($opts);
    $url = 'http://maps.google.com/maps/api/geocode/json?sensor=false&address='.urlencode($location);
    $json = file_get_contents($url, false, $context);
    $results = json_decode($json, true);
    $coordinates = [];
    if ($results["status"] === "OK" && isset($results["results"][0]["geometry"]["location"])) {
        $location = $results["results"][0]["geometry"]["location"];
        $coordinates = [$location["lat"],$location["lng"]];
    }

    return $coordinates;
}

function callAPI($method, $url, $apiKey, $data = false) {
    $curl = curl_init();

    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'X-Api-Key: ' . $apiKey,
        'Content-Type: application/json;charset=UTF-8'
    ));

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

if (isset($_POST["engine"]) && $_POST["engine"] === "shashin" && isset($_POST["latitude"]) && $_POST["latitude"] !== "" && isset($_POST["longitude"]) && $_POST["longitude"] !== "" && isset($_POST["query"])) {
    $query = $_POST["query"];

    $limit = 500;
    $offset = 0;
    
    if (isset($_POST["offset"])) {
        $offset = $_POST["offset"];
    }

    if (isset($_POST["limit"])) {
        $limit = $_POST["limit"];
    }

    if (isset($_POST["offset"]) && isset($_POST["limit"])) {
        $limit = $_POST["limit"];
        $offset = $_POST["offset"];
    }

    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];

    $baseUrl = "<shashin_url>";
    $apiKey = "<shashin_api_key>";
    $apiUrl = $baseUrl."/api/v1/mapdata/keywords/".$offset."/".$limit;

    $json = callAPI("GET", $apiUrl, $apiKey);
    $result = json_decode($json, true);
    $processedResults = array();

    if (array_key_exists("mapdata",$result) && array_key_exists("keywordMap",$result)) {
        $keywordMap = $result["keywordMap"];

        foreach($result["mapdata"] as $metadata) {
            $currStatus = array();
            $currStatus["id"] = $metadata["id"];

            // Get keywords
            $keywords = "";
            if (array_key_exists($metadata["id"], $keywordMap)) {
                foreach ($keywordMap[$metadata["id"]] as $keyword) {
                    $keywords .= $keyword . ", ";
                }
                $keywords = rtrim($keywords, ", ");

                if ($keywords === "unidentified objects") {
                    $keywords = "";
                }
            }

            $placeName = $metadata["placeName"] === null ? "" : $metadata["placeName"];

            if ($query !== "" &&
                (!str_contains(strtolower($placeName), strtolower($query)) && !str_contains(strtolower($keywords), strtolower($query)))
            ) {
                continue;
            }

            $originalDate = $metadata["year"]."-".$metadata["month"]."-".$metadata["day"];;
            $newDate = date("M d, Y", strtotime($originalDate));
            $currStatus["date"] = $newDate;
            $currStatus["keywords"] = $keywords;
            $currStatus["placeName"] = $metadata["placeName"];
            $currStatus["coordinates"] = [$metadata["lat"], $metadata["lng"]];
            $currStatus["mapMarkerUrl"] = $baseUrl . $metadata["mapMarkerUrl"];
            $currStatus["thumbnailUrlSmall"] = $baseUrl . $metadata["thumbnailUrlSmall"];
            $currStatus["thumbnailUrlOriginal"] = $baseUrl . $metadata["thumbnailUrlOriginal"];
            $currStatus["videoUrl"] = ($metadata["videoUrl"] !== null && $metadata["videoUrl"] !== "") ? $baseUrl . $metadata["videoUrl"] : "";
            $currStatus["viewerUrl"] = ($metadata["videoUrl"] !== null && $metadata["videoUrl"] !== "") ? $baseUrl . "/video/" . $metadata["id"] . "/player" : $baseUrl . "/image/" . $metadata["id"] . "/viewer";
            $processedResults[] = $currStatus;
        }
    }

    echo json_encode($processedResults);
} else if (isset($_POST["radius"]) && isset($_POST["lat"]) && isset($_POST["lng"])) {

    $consumerKey = "<twiiter_api_consumer_key>";
    $consumerSecret = "<twiiter_api_consumer_secret>";
    $bearerToken = $consumerKey . ":" . $consumerSecret;
    $bearerCredentials = base64_encode($bearerToken);
    $apiBase = 'https://api.twitter.com/';

    $lat = $_POST["lat"];
    $lng = $_POST["lng"];
    $radius = $_POST["radius"];

    //Get a bearer token.
    $opts = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Authorization: Basic ' . $bearerCredentials . "\r\n" .
                'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            'content' => 'grant_type=client_credentials'
        )
    );
    $context = stream_context_create($opts);
    $json = file_get_contents($apiBase . 'oauth2/token', false, $context);
    $result = json_decode($json, true);
    if (!is_array($result) || !isset($result['token_type']) || !isset($result['access_token'])) {
        die("Something went wrong. This isn't a valid array: " . $json);
    }
    if ($result['token_type'] !== "bearer") {
        die("Invalid token type. Twitter says we need to make sure this is a bearer.");
    }

    $bearerToken = $result['access_token'];

    $opts = array(
        'http' => array(
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . $bearerToken
        )
    );

    // Make the request
    $context = stream_context_create($opts);
    $geocode = $lat.",".$lng.",".$radius."km";
    $query = "";

    if (isset($_POST["query"]) && strlen($_POST["query"]) > 0) {
        $query = $_POST["query"];
    } else {
        $query = '"EARTHQUAKE WATCH" AND educaciondecr';
    }

    /*
    // Get global trending terms
    $url = "https://api.twitter.com/1.1/trends/place.json?id=1";
    $json = file_get_contents($url, false, $context);
    $trends = json_decode($json, true);
    $trend = "%23trending";
    if (array_key_exists("trends",$trends[0])) {
        // Get a random trending topic
        $rand = rand(0,count($trends[0]["trends"])-1);
        $trend = $trends[0]["trends"][$rand]["query"];
    }
    */

    // Earthquake information
    $url = $apiBase . '1.1/search/tweets.json?q='.urlencode($query).'&geocode='.urlencode($geocode);

    $json = file_get_contents($url, false, $context);
    $tweets = json_decode($json, true);
    $processedTweets = Array();

    if (array_key_exists("statuses",$tweets)) {
        foreach($tweets["statuses"] as $status) {
            if (null != $status["geo"] && array_key_exists("coordinates",$status["geo"]) && !empty($status["geo"]["coordinates"])) {
                $status["coordinates"] = $status["geo"]["coordinates"];
            } else if (null != $status["coordinates"] && array_key_exists("coordinates",$status["coordinates"]) && !empty($status["coordinates"]["coordinates"])) {
                $coordinates = $status["coordinates"]["coordinates"];
                $status["coordinates"] = Array($coordinates[1], $coordinates[0]);
            } else if (null != $status["place"] && array_key_exists("bounding_box",$status["place"]) && !empty($status["place"]["bounding_box"])) {
                $coordinates = $status["place"]["bounding_box"]["coordinates"][0][0];
                $status["coordinates"] = Array($coordinates[1], $coordinates[0]);
            } else if (isset($status["user"]["location"]) && strlen($status["user"]["location"]) > 0) {
                $status["coordinates"] = getCoordinates($status["user"]["location"]);
            }

            $currStatus = Array();
            $currStatus["id"] = $status["id"];
            $currStatus["twitter_url"] = "https://twitter.com/statuses/".$status["id_str"];
            $currStatus["geo"] = $status["geo"];
            $currStatus["coordinates"] = $status["coordinates"];
            $currStatus["place"] = $status["place"];
            $currStatus["text"] = $status["text"];
            $currStatus["media"]["media_url"] = array_key_exists("media",$status["entities"]) ? $status["entities"]["media"][0]["media_url"] : Array();
            $currStatus["media"]["url"] = array_key_exists("media",$status["entities"]) ? $status["entities"]["media"][0]["url"] : Array();
            $currStatus["media"]["display_url"] = array_key_exists("media",$status["entities"]) ? $status["entities"]["media"][0]["display_url"] : Array();
            $currStatus["user"]["id"] = $status["user"]["id"];
            $currStatus["user"]["name"] = $status["user"]["name"];
            $currStatus["user"]["screen_name"] = $status["user"]["screen_name"];
            $currStatus["user"]["location"] = $status["user"]["location"];
            $currStatus["user"]["description"] = $status["user"]["description"];
            $currStatus["user"]["profile_image_url"] = $status["user"]["profile_image_url"];
            array_push($processedTweets,$currStatus);
        }
    }

    echo json_encode($processedTweets);
}

exit();