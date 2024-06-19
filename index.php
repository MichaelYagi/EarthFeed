<!DOCTYPE HTML>
<html>
    <head>
        <script src="http://www.webglearth.com/v2/api.js"></script>
        <script>
            let earth;
            let query = "";
            let currMarkers = {};
            let animateRequest;

            function setQuery(aQuery, currCoordinates, radius, earth, WE) {
                query = aQuery;
                for (const id in currMarkers) {
                    if (currMarkers.hasOwnProperty(id)) {
                        const marker = currMarkers[id];
                        marker.removeFrom(earth);
                    }
                }
                currMarkers = {};
                // getTweets(currCoordinates,radius,earth,WE);
                getShashin(currCoordinates,radius,earth,WE);
            }

            function initialize() {
                earth = new WE.map('earth_div');
                let currCoordinates = [46.8011, 8.2266];
                earth.setView(currCoordinates, 3);
                const radius = 10000;
                WE.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
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

                // let startTime = new Date();
                // let lastRounded = 0;
                // let rounded = 0;
                // let elapsedTargetMS = 15000;
                // requestAnimationFrame(function animate(now) {
                //     const c = earth.getPosition();
                //     const elapsed = before ? now - before : 0;
                //     before = now;
                //     earth.setCenter([c[0], c[1] + 0.1*(elapsed/30)]);
                //     let endTime = new Date();
                //     let timeElapsed = endTime - startTime;
                //     rounded = Math.round(timeElapsed/elapsedTargetMS)*elapsedTargetMS
                //     if (rounded !== lastRounded) {
                //         lastRounded = rounded;
                //         getShashin(c,radius,earth,WE);
                //     }
                //
                //     requestAnimationFrame(animate);
                // });

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
                const params = "engine=shashin" + "&query=" + query + "&latitude=" + coordinates[0] + "&longitude=" + coordinates[1];

                proxyRequest(params,"POST",function(response) {
                    console.log(response);
                    for(let i = 0; i < response.length; i++) {
                        const metadata = response[i];
                        if (metadata["coordinates"] != null) {
                            const marker = WE.marker(metadata["coordinates"]);
                            marker["id"] = metadata["id"];
                            marker.addTo(earth);
                            currMarkers[metadata["id"]] = marker;
                            let markerContent = '<img src="'+metadata["mapMarkerUrl"]+'""><br>';
                            if (metadata["placeName"] !== null && metadata["placeName"].length > 0) {
                                markerContent += metadata["placeName"];
                            }

                            if (metadata["videoUrl"].length > 0) {
                                markerContent += "<br><a href='"+metadata["videoUrl"]+"' target='_blank'>Video link</a>";
                            } else {
                                markerContent += "<br><a href='"+metadata["thumbnailUrlOriginal"]+"' target='_blank'>Image link</a>";
                            }

                            marker.bindPopup(markerContent);
                        }
                    }
                })
            }

            function getTweets(coordinates, radius, earth, WE) {
                const lat = coordinates[0];
                const lng = coordinates[1];
                const params = "radius=" + radius + "&lat=" + lat + "&lng=" + lng + "&query=" + query;

                proxyRequest(params,"POST",function(response) {
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

            function proxyRequest(params,action,callback) {
                const xhttp = new XMLHttpRequest();
                xhttp.timeout = 5000; // time in milliseconds
                xhttp.open(action, "./proxy.php", true);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhttp.onreadystatechange = function() {
                    if (xhttp.readyState == 4) {
                        if (xhttp.status == 200) {
                            const respObj = JSON.parse(xhttp.responseText);
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
                <input title="Search Query" type="text" id="query" name="query" value='restaurant'>
                <input type="submit">
            </form>
        </div>
        <div id="earth_div"></div>
    </body>
</html>
