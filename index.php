<!DOCTYPE HTML>
<html>
    <head>
        <style>
            #earth_div {
                position:absolute !important;
                top:0;
                right:0;
                bottom:0;
                left:0;
                background:#000 url(stars.jpg);
                background-size:cover;
                touch-action:none;
            }
        </style>
        <script src="/webglearth.min.js"></script>
        <script type="text/javascript">
            const baseUrl = "<shashin_url>";
            const apiKey = "<shashin_api_key>";

            let earth;
            let query = "";
            let currMarkers = {};
            let animateRequest;
            let data = {};

            function setQuery(aQuery, currCoordinates, radius, earth, WE) {
                query = aQuery;
                clearMarkers(earth);
                getShashin(currCoordinates,radius,earth,WE);
            }

            function initialize() {
                earth = new WE.map('earth_div',{
                    'atmosphere': true,
                    'sky': true,
                    'position': [0, 0],
                    'panning': true,
                    'tilting': true,
                    'zooming': true
                });
                let currCoordinates = [46.8011, 8.2266];
                earth.setView(currCoordinates, 3);
                const radius = 10000;
                WE.tileLayer('https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=YlQvLcNKq0a4aFDX2z3O',{
                    attribution: '© OpenStreetMap contributors'
                }).addTo(earth);

                // Start a simple rotation animation
                let before = null;
                let first = false;

                function animate(now) {
                    // Array of lat/long
                    const c = earth.getPosition();

                    const elapsed = before ? now - before : 0;
                    before = now;

                    if (false === first || getHaversine(c[0],c[1],currCoordinates[0],currCoordinates[1]) > 9000) {
                        first = true;
                        currCoordinates = c;
                        // getTweets(c,radius,earth,WE);
                        getShashin(c,radius,earth,WE);
                    }

                    earth.setCenter([c[0], c[1] + 0.1*(elapsed/30)]);
                    animateRequest = requestAnimationFrame(animate);
                }

                function start() {
                    if (!animateRequest) {
                        const d = new Date();
                        let n = d.getMilliseconds();
                        if (null != before) {
                            before = null;
                            n = before;
                        }

                        animate(n);
                    }
                }

                function stop() {
                    if (animateRequest) {
                        window.cancelAnimationFrame(animateRequest);
                        animateRequest = undefined;
                    }
                }

                start();

                document.getElementById("searchForm").addEventListener("submit", function(event) {
                    event.preventDefault();
                    setQuery(document.getElementById("query").value, currCoordinates, radius, earth, WE);
                },false);

                // Execute after clicking spacebar
                document.body.onkeyup = function(e){
                    if(e.keyCode == 32){
                        if (animateRequest === undefined) {
                            start();
                        } else {
                            stop();
                        }
                    }
                };
            }

            function getShashin(coordinates, radius, earth, WE) {
                const urlParams = new URLSearchParams(window.location.search);
                let offset = urlParams.get('offset');
                let limit = urlParams.get('limit');

                if (offset === null) {
                    offset = 0;
                }
                if (limit === null) {
                    limit = 500;
                }

                clearMarkers(earth);



                const url = baseUrl+"/api/v1/mapdata/keywords/"+offset+"/"+limit

                apiRequest(url,"GET",apiKey,null,function(response) {
                    console.log(response);
                    data = response;

                    if (data.hasOwnProperty("mapdata") && data.hasOwnProperty("keywordMap")) {
                        const mapdata = data["mapdata"];
                        const keywordMap = data["keywordMap"];

                        for (let i = 0; i < mapdata.length; i++) {
                            const metadata = mapdata[i];

                            if (metadata["lat"] != null && metadata["lng"] != null) {
                                const marker = WE.marker([metadata["lat"], metadata["lng"]], baseUrl+metadata["mapMarkerUrl"], 30, 30);
                                if (false === currMarkers.hasOwnProperty(metadata["id"])) {
                                    marker["id"] = metadata["id"];

                                    currMarkers[metadata["id"]] = marker;
                                    let markerContent = '<img src="' + baseUrl+metadata["thumbnailUrlSmall"] + '" height="100" "><br>';
                                    markerContent += metadata["year"] + "-" + metadata["month"] + "-" + metadata["day"] + "<br><br>";
                                    let placeNameStr = "";
                                    if (metadata["placeName"] !== null && metadata["placeName"].length > 0) {
                                        placeNameStr = metadata["placeName"];
                                        markerContent += placeNameStr + "<br><br>";
                                    }

                                    let keywordListStr = "";
                                    if (keywordMap.hasOwnProperty(metadata["id"])) {
                                        const keywordList = keywordMap[metadata["id"]];
                                        if (keywordList.length > 0) {
                                            keywordListStr = keywordList.join(", ");
                                            if (keywordListStr !== "unidentified objects") {
                                                markerContent += "Keywords: " + keywordListStr + "<br><br>";
                                            }
                                        }
                                    }

                                    const viewerUrl = (metadata["videoUrl"] !== null && metadata["videoUrl"] !== "") ? baseUrl + "/video/" + metadata["id"] + "/player" : baseUrl + "/image/" + metadata["id"] + "/viewer";
                                    if (metadata["videoUrl"] !== null && metadata["videoUrl"] !== "") {
                                        markerContent += "<a href='" + viewerUrl + "' target='_blank'>Video link</a>";
                                    } else {
                                        markerContent += "<a href='" + viewerUrl + "' target='_blank'>Image link</a>";
                                    }

                                    if (query !== "" &&
                                        placeNameStr.toLowerCase().indexOf(query.toLowerCase()) === -1 &&
                                        keywordListStr.toLowerCase().indexOf(query.toLowerCase()) === -1
                                    ) {
                                        continue;
                                    }

                                    marker.addTo(earth);
                                    marker.bindPopup(markerContent);
                                }
                            }
                        }
                    }
                });
            }

            function apiRequest(url,action,apiKey,params,callback) {
                const xhttp = new XMLHttpRequest();
                xhttp.timeout = 5000; // time in milliseconds
                xhttp.open(action, url, true);
                xhttp.setRequestHeader("Content-type", "application/json");
                xhttp.setRequestHeader("X-Api-Key", apiKey);
                xhttp.onreadystatechange = function() {
                    if (xhttp.readyState === 4 && xhttp.status === 200) {
                        const respObj = JSON.parse(xhttp.responseText);
                        callback(respObj);
                    }
                };
                xhttp.ontimeout = function () {
                    // Do nothing on timeout
                };
                if (params === null) {
                    xhttp.send();
                } else {
                    xhttp.send(params);
                }

            }

            function getHaversine(lat1,lon1,lat2,lon2) {
                const R = 6371; // Radius of the earth in km
                const dLat = (lat2 - lat1) * (Math.PI / 180);  // deg2rad below
                const dLon = (lon2 - lon1) * (Math.PI / 180);
                const a =
                    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * (Math.PI / 180)) * Math.cos(lat2 * (Math.PI / 180)) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2)
                ;
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

            function clearMarkers(earth) {
                for (const id in currMarkers) {
                    if (currMarkers.hasOwnProperty(id)) {
                        const marker = currMarkers[id];
                        marker.removeFrom(earth);
                    }
                }
                currMarkers = {};
            }
        </script>
        <style>
            html, body{padding: 0; margin: 0;}
            #earth_div{ top: 0; right: 0; bottom: 0; left: 0; z-index: 0; position: absolute !important;}
            #inputs {position: absolute; top:10px; left: 10px; z-index: 1;}
        </style>
        <title>Earthfeed</title>
    </head>
    <body onload="initialize()">
        <div id="inputs">
            <form id="searchForm">
                <input title="Search Query" type="text" id="query" name="query" value=''>
                <input type="submit">
            </form>
        </div>
        <div id="earth_div"></div>
    </body>
</html>
