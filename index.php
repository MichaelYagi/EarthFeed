<!DOCTYPE HTML>
<html>
    <head>
        <script src="http://www.webglearth.com/v2/api.js"></script>
        <script>
            var earth;
            var query = "";
            var currMarkers = {};
            var animateRequest;

            function setQuery(aQuery, currCoordinates, radius, earth, WE) {
                query = aQuery;
                for (var tweetId in currMarkers) {
                    if (currMarkers.hasOwnProperty(tweetId)) {
                        var marker = currMarkers[tweetId];
                        marker.removeFrom(earth);
                    }
                }
                currMarkers = {};
                getTweets(currCoordinates,radius,earth,WE);
            }

            function initialize() {
                earth = new WE.map('earth_div');
                var currCoordinates = [46.8011, 8.2266];
                earth.setView(currCoordinates, 3);
                var radius = 10000;
                WE.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
                    attribution: '© OpenStreetMap contributors'
                }).addTo(earth);

                // Start a simple rotation animation
                var before = null;
                var first = false;

                function animate(now) {
                    // Array of lat/long
                    var c = earth.getPosition();

                    var elapsed = before? now - before: 0;
                    before = now;

                    if (false === first || getHaversine(c[0],c[1],currCoordinates[0],currCoordinates[1]) > 9000) {
                        first = true;
                        currCoordinates = c;
                        getTweets(c,radius,earth,WE);
                    }

                    earth.setCenter([c[0], c[1] + 0.1*(elapsed/30)]);
                    animateRequest = requestAnimationFrame(animate);
                }

                function start() {
                    if (!animateRequest) {
                        var d = new Date();
                        var n = d.getMilliseconds();
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

            function getTweets(coordinates, radius, earth, WE) {
                var lat = coordinates[0];
                var lng = coordinates[1];
                var params = "radius=" + radius + "&lat=" + lat + "&lng=" + lng + "&query=" + query;

                proxyRequest(params,function(response) {
                    for(var i=0;i<response.length;i++) {
                        var tweet = response[i];
                        if (tweet["coordinates"] != null) {
                            var marker = WE.marker(tweet["coordinates"]);
                            if (false === currMarkers.hasOwnProperty(tweet["id"])) {
                                marker["tweetId"] = tweet["id"];
                                marker.addTo(earth);
                                currMarkers[tweet["id"]] = marker;
                                var markerContent = tweet["user"].hasOwnProperty("profile_image_url") ?
                                                    '<img src="' + tweet["user"]["profile_image_url"] + '" height="25"><br>' : '';
                                markerContent += "<a href='" + tweet["twitter_url"] + "' target='_blank'>" + tweet["user"]["screen_name"] + "</a>:<br>" + tweet["text"];
                                marker.bindPopup(markerContent);
                            }

                        }
                    }
                })
            }

            function proxyRequest(params,callback) {
                var xhttp = new XMLHttpRequest();
                xhttp.timeout = 5000; // time in milliseconds
                xhttp.open("POST", "./proxy.php", true);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhttp.onreadystatechange = function() {
                    if (xhttp.readyState == 4) {
                        if (xhttp.status == 200) {
                            var respObj = JSON.parse(xhttp.responseText);
                            callback(respObj);
                        }
                    }
                };
                xhttp.ontimeout = function () {
                    // Do nothing on timeout
                };
                xhttp.send(params);
            }

            function getHaversine(lat1,lon1,lat2,lon2) {
                var R = 6371; // Radius of the earth in km
                var dLat = (lat2-lat1) * (Math.PI/180);  // deg2rad below
                var dLon = (lon2-lon1) * (Math.PI/180);
                var a =
                        Math.sin(dLat/2) * Math.sin(dLat/2) +
                        Math.cos(lat1* (Math.PI/180)) * Math.cos(lat2 * (Math.PI/180)) *
                        Math.sin(dLon/2) * Math.sin(dLon/2)
                    ;
                var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return R * c;
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
                <input title="Search Query" type="text" id="query" name="query" value='"EARTHQUAKE WATCH" AND educaciondecr'>
            </form>
        </div>
        <div id="earth_div"></div>
    </body>
</html>
