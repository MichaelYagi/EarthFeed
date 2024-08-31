let data = {};
let earth;
let query = "";
let currMarkers = {};
let animateRequest;
let showMarkerImage = true;
let resultCount = 0;
const mapMarkerSize = 30;
document.getElementById("rotateToggle").disabled = true;
document.getElementById("submit").disabled = true;
document.getElementById("query").disabled = true;

function setQuery(aQuery, currCoordinates, radius, earth, WE) {
    query = aQuery;
    clearMarkers(earth);
    getShashin();
}

function initialize() {
    earth = new WE.map('earth_div', {
        'atmosphere': true,
        'sky': false,
        'position': [0, 0],
        'panning': true,
        'tilting': true,
        'zooming': true
    });
    let currCoordinates = [49.169087251026724, -123.1464338053518];

    const urlParams = new URLSearchParams(window.location.search);
    let latlng = urlParams.get('latlng');
    let latlngArray = latlng === null ? [] : latlng.split(",");

    if (latlngArray.length > 1) {
        const lat = parseFloat(latlngArray[0].trim());
        const lng = parseFloat(latlngArray[1].trim());
        currCoordinates = [lat, lng];
        earth.setView(currCoordinates, 3);
        earth.setZoom(20);
    } else {
        earth.setView(currCoordinates, 3);
    }

    const radius = 10000;

    let view = urlParams.get('view');
    if (view === null || (view !== "street" && view !== "satellite" && view !== "sat")) {
        view = "street";
    }

    let mapOptions = {
        attribution: 'EarthFeed © MapTiler',
        tileSize: 512,
        zoomOffset: -1
    };
    let tile = "https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=YlQvLcNKq0a4aFDX2z3O";
    if (view === "satellite" || view === "sat") {
        tile = "https://api.maptiler.com/maps/hybrid/256/{z}/{x}/{y}.jpg?key=YlQvLcNKq0a4aFDX2z3O"; //https://api.maptiler.com/maps/hybrid/{z}/{x}/{y}.jpg?key=YlQvLcNKq0a4aFDX2z3O
    }

    WE.tileLayer(tile, mapOptions).addTo(earth);

    getShashin();
    const d = new Date();
    let before = d.getMilliseconds();

    function animate(now) {
        // Array of lat/long
        const c = earth.getPosition();

        const elapsed = before ? now - before : 0;
        before = now;

        if (getHaversine(c[0], c[1], currCoordinates[0], currCoordinates[1]) > 9000) {
            currCoordinates = c;
        }

        earth.setCenter([c[0], c[1] - 0.1 * (elapsed / 30)]);
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

    document.getElementById("searchForm").addEventListener("submit", function (event) {
        event.preventDefault();
        setQuery(document.getElementById("query").value, currCoordinates, radius, earth, WE);
    }, false);

    document.getElementById("rotateToggle").addEventListener("click", function (event) {
        event.preventDefault();

        if (animateRequest === undefined) {
            document.getElementById("rotateToggle").textContent = "Stop";
            start();
        } else {
            document.getElementById("rotateToggle").textContent = "Start";
            stop();
        }
    }, false);

    // Execute after clicking spacebar
    document.body.onkeyup = function (e) {
        if (e.code.toLowerCase() === "space") {
            if (animateRequest === undefined) {
                document.getElementById("rotateToggle").textContent = "Stop";
                start();
            } else {
                document.getElementById("rotateToggle").textContent = "Start";
                stop();
            }
        }
    };
}

function isValidDate(dateString) {
    const dateStringArray = dateString.split(" ");
    if (dateStringArray.length > 1) {
        dateString = dateStringArray[0];
    }

    // First check for the pattern
    if (!/^\d{4}\-\d\d\-\d\d$/.test(dateString) && !/^\d{4}\-\d{1,2}\-\d{1,2}$/.test(dateString)
    ) {
        return false;
    }

    // Parse the date parts to integers
    const parts = dateString.split("-");
    const day = parseInt(parts[2], 10);
    const month = parseInt(parts[1], 10);
    const year = parseInt(parts[0], 10);

    // Check the ranges of month and year
    if (year < 1000 || year > 3000 || month === 0 || month > 12) {
        return false;
    }

    const monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    // Adjust for leap years
    if (year % 400 === 0 || (year % 100 !== 0 && year % 4 === 0)) {
        monthLength[1] = 29;
    }

    // Check the range of the day
    return day > 0 && day <= monthLength[month - 1];
}

function getShashin() {
    document.getElementById("rotateToggle").disabled = true;
    document.getElementById("submit").disabled = true;
    document.getElementById("query").disabled = true;

    const urlParams = new URLSearchParams(window.location.search);
    let offset = urlParams.get('offset');
    let limit = urlParams.get('limit');
    let dateRange = urlParams.get('range');
    let startDate = "";
    let endDate = "";
    if (dateRange !== null) {
        const dateRangeArr = dateRange.split(",");
        if (dateRangeArr.length > 1) {
            startDate = dateRangeArr[0].trim();
            endDate = dateRangeArr[1].trim();
        }
    } else {
        dateRange = "";
    }
    let latlng = urlParams.get('latlng');
    if (latlng === null) {
        latlng = "";
    }

    showMarkerImage = urlParams.get('marker');

    if (startDate !== "" && !isValidDate(startDate)) {
        console.warn("Warning: Start date invalid. Must be format YYY-MM-DD.");
        showToast("Warning", "Start date invalid. Defaulting to retrieve first 500 available results.", "#CC5500");
        startDate = "";
    }
    if (endDate !== "" && !isValidDate(endDate)) {
        console.warn("Warning: End date invalid. Must be format YYY-MM-DD.");
        showToast("Warning", "End date invalid. Defaulting to retrieve first 500 available results.", "#CC5500");
        endDate = "";
    }
    if (startDate !== "" && endDate !== "") {
        const startDateObj = new Date(startDate);
        const endDateObj = new Date(endDate);

        if (startDateObj > endDateObj) {
            console.warn("Warning: Start date must not be greater than end date.");
            showToast("Warning", "Start date must not be greater than end date. Defaulting to retrieve first 500 available results.", "#CC5500");
        }
    }

    if (offset === null) {
        offset = 0;
    }

    if (limit === null) {
        limit = 500;
    }

    if (showMarkerImage === null) {
        showMarkerImage = true;
    } else if (showMarkerImage === 'false') {
        showMarkerImage = false;
    } else if (showMarkerImage === 'true') {
        showMarkerImage = true;
    } else {
        showMarkerImage = true;
    }

    clearMarkers(earth);

    const params = {
        offset: offset,
        limit: limit,
        startDate: startDate,
        endDate: endDate
    };

    const popoverBody = '<strong>view</strong>: ' + (urlParams.get('view') === null ? "street" : urlParams.get('view')) + "<br>" +
        '<strong>marker</strong>: ' + (showMarkerImage === true ? "true" : "false") + "<br>" +
        '<strong>offset</strong>: ' + offset + "<br>" +
        '<strong>limit</strong>: ' + limit + "<br>" +
        '<strong>range</strong>: ' + dateRange + "<br>" +
        '<strong>latlng</strong>: ' + latlng + "<br>" +
        '<strong>results</strong>: <span id="resultValue"></span>';

    const popover = bootstrap.Popover.getOrCreateInstance('#infoPopover') // Returns a Bootstrap popover instance
    popover.setContent({
        '.popover-header': 'Parameter Values',
        '.popover-body': popoverBody
    });

    apiRequest("proxy.php", "POST", params, function (response) {
        data = response;

        if (data.hasOwnProperty("mapdata") && data.hasOwnProperty("keywordMap") && data.hasOwnProperty("baseUrl")) {
            const mapdata = data["mapdata"];
            const keywordMap = data["keywordMap"];
            const baseUrl = data["baseUrl"];

            resultCount = 0;

            for (let i = 0; i < mapdata.length; i++) {
                const metadata = mapdata[i];

                if (metadata["lat"] != null && metadata["lng"] != null) {
                    let marker = null;
                    if (showMarkerImage === true) {
                        marker = WE.marker([metadata["lat"], metadata["lng"]], baseUrl + escape(metadata["mapMarkerUrl"]), mapMarkerSize, mapMarkerSize);
                    } else {
                        marker = WE.marker([metadata["lat"], metadata["lng"]], null, 25, 41);
                    }
                    if (false === currMarkers.hasOwnProperty(metadata["id"])) {
                        marker["id"] = metadata["id"];

                        currMarkers[metadata["id"]] = marker;
                        let markerContent = '<img src="' + baseUrl + escape(metadata["thumbnailUrlSmall"]) + '" height="100" "><br>';
                        const takenDate = new Date(metadata["year"] + "-" + metadata["month"] + "-" + metadata["day"]);
                        const options = {weekday: 'long', year: 'numeric', month: 'short', day: 'numeric'};

                        markerContent += takenDate.toLocaleDateString('en-us', options) + "<br><br>";
                        let placeNameStr = "";
                        let placeType = "";
                        let placeNameRaw = "";
                        if (metadata["placeName"] !== null && metadata["placeName"].length > 0) {
                            placeNameStr = metadata["placeName"];
                            placeNameRaw = placeNameStr;
                            const placeArr = placeNameStr.split(";");

                            if (placeArr.length > 1) {
                                placeNameStr = placeArr[0];
                                placeType = placeArr[1];
                            }

                            markerContent += placeNameStr + "<br><br>";
                        }

                        let keywordList = "";
                        if (keywordMap.hasOwnProperty(metadata["id"])) {
                            keywordList = keywordMap[metadata["id"]];
                            if (keywordList.length > 0) {
                                markerContent += "Keywords: " + keywordList.replace(",", ", ") + ((placeType.length > 0) ? (", " + placeType) : "") + "<br><br>";
                            } else if (keywordList.length === 0 && placeType.length > 0) {
                                markerContent += "Keywords: " + placeType + "<br><br>";
                            }
                        }

                        markerContent += metadata["lat"]+","+ metadata["lng"]+"&nbsp;&nbsp;<a href='javascript:;' onclick='copyCoordinates(\""+metadata["lat"]+","+ metadata["lng"]+"\");return false;' title='Copy coordinates'>&loz;</a><br><br>";
                        markerContent += "<input type='hidden' id='latlngValue' value='"+metadata["lat"]+","+ metadata["lng"]+"'>";

                        const viewerUrl = (metadata["type"].indexOf("video") > -1) ? baseUrl + "/video/" + metadata["id"] + "/player" : baseUrl + "/image/" + metadata["id"] + "/viewer";
                        if (metadata["type"].indexOf("video") > -1) {
                            markerContent += "<a href='" + viewerUrl + "' target='_blank'>Video link</a>";
                        } else {
                            markerContent += "<a href='" + viewerUrl + "' target='_blank'>Image link</a>";
                        }

                        if (query !== "" &&
                            placeNameRaw.toLowerCase().indexOf(query.toLowerCase()) === -1 &&
                            keywordList.toLowerCase().indexOf(query.toLowerCase()) === -1 &&
                            markerContent.toLowerCase().indexOf(query.toLowerCase()) === -1 &&
                            metadata["id"].toLowerCase().indexOf(query.toLowerCase()) === -1 &&
                            metadata["thumbnailUrlSmall"].toLowerCase().indexOf(query.toLowerCase()) === -1
                        ) {
                            continue;
                        }

                        resultCount++;

                        marker.addTo(earth);
                        marker.bindPopup(markerContent);
                    }
                }
            }

            let toastTitle = "Results Returned";
            if (resultCount === 0) {
                toastTitle = "No Results";
            }

            showToast(toastTitle, (resultCount + " result" + (resultCount === 1 ? "" : "s") + " returned."), null, "webglEarthToast1");
        } else if (data.hasOwnProperty("error")) {
            console.warn("Error: " + data["error"]);
            showToast("Error", "Error retrieving results: " + data["error"], "#FF0000");
        } else {
            console.warn("Error: Something went wrong!", "#FF0000");
            showToast("Error", "Something went wrong retrieving results!");
        }

        document.getElementById("rotateToggle").disabled = false;
        document.getElementById("submit").disabled = false;
        document.getElementById("query").disabled = false;
    });
}

function copyCoordinates(coordinates) {
    // Navigator clipboard api needs a secure context (https)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(coordinates).then(function () {
            showToast("Coordinates copied!", coordinates + " copied!");
        }).catch(function (err) {
            showToast("Error!", coordinates + " could not be copied! " + error, "#FF0000");
        });
    } else {
        // Use the 'out of viewport hidden text area' trick
        const textArea = document.createElement("textarea");
        textArea.value = coordinates;

        // Move textarea out of the viewport so it's not visible
        textArea.style.position = "absolute";
        textArea.style.left = "-999999px";

        document.body.prepend(textArea);
        textArea.select();

        try {
            document.execCommand('copy');
        } catch (error) {
            showToast("Error!", coordinates + " could not be copied! " + error, "#FF0000");
        } finally {
            textArea.remove();
            showToast("Coordinates copied!", coordinates + " copied!");
        }
    }
}

function apiRequest(url, action, params, callback) {
    showToast("Loading data", "Loading data", null, "webglEarthToast1");

    const xhttp = new XMLHttpRequest();
    xhttp.timeout = 10000; // time in milliseconds
    xhttp.open(action, url, true);
    xhttp.setRequestHeader("Content-type", "application/json");
    xhttp.onreadystatechange = function () {
        if (xhttp.readyState === 4 && xhttp.status === 200) {
            // console.log("Response: "+xhttp.responseText)
            const respObj = JSON.parse(xhttp.responseText);
            callback(respObj);
        }
    };
    xhttp.ontimeout = function () {
        showToast("Error", "Request to "+url+" timed out.", "#FF0000");
    };
    if (params === null) {
        xhttp.send();
    } else {
        xhttp.send(JSON.stringify(params));
    }
}

function getHaversine(lat1, lon1, lat2, lon2) {
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

function showToast(title, message, colour, target) {
    if (colour === undefined || colour === null) {
        colour = "#007aff";
    }
    if (target === undefined || target === null) {
        target = "webglEarthToast";
    }

    if (target === "webglEarthToast") {
        document.getElementById("webglEarthColour").setAttribute("fill", colour);
        document.getElementById("webglEarthTitle").innerHTML = title;
        document.getElementById("webglEarthMessage").innerHTML = message;
    } else if (target === "webglEarthToast1") {
        document.getElementById("webglEarthColour1").setAttribute("fill", colour);
        document.getElementById("webglEarthTitle1").innerHTML = title;
        document.getElementById("webglEarthMessage1").innerHTML = message;
    }

    const toastEl = document.getElementById(target);
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}

const popoverTrigger = document.getElementById("infoPopover");
new bootstrap.Popover(popoverTrigger, {
    html: true
});

popoverTrigger.addEventListener('show.bs.popover', function () {
    let obEl = "resultValue";
    let obTimeout = 3000;
    waitForElement("#"+obEl, obTimeout).then(function() {
        document.getElementById("resultValue").innerHTML = resultCount.toString();
    }).catch(() => {
        console.log(observableEl + " element did not load in " + (observableTimeout/1000) + " seconds");
    });
});

const toastEl = document.getElementById("webglEarthToast");
toastEl.addEventListener('hidden.bs.toast', () => {
    document.getElementById("webglEarthColour").setAttribute("fill", "");
    document.getElementById("webglEarthTitle").innerHTML = "";
    document.getElementById("webglEarthMessage").innerHTML = "";
});
const toastEl1 = document.getElementById("webglEarthToast1");
toastEl1.addEventListener('hidden.bs.toast', () => {
    document.getElementById("webglEarthColour1").setAttribute("fill", "");
    document.getElementById("webglEarthTitle1").innerHTML = "";
    document.getElementById("webglEarthMessage1").innerHTML = "";
});

function waitForElement(querySelector, timeout) {
    return new Promise((resolve, reject) => {
        let timer = null;

        if (document.querySelectorAll(querySelector).length > 0) {
            return resolve();
        }

        const observer = new MutationObserver(() => {
            if (document.querySelectorAll(querySelector).length > 0) {
                observer.disconnect();
                if (timer !== null) {
                    clearTimeout(timer);
                }
                return resolve();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        if (timeout) {
            timer = setTimeout(() => {
                observer.disconnect();
                reject();
            }, timeout);
        }
    });
}

let observableEl = "cesium-credit-text";
let observableTimeout = 3000;
waitForElement("."+observableEl, observableTimeout).then(function() {
    let creditTextArray = document.getElementsByClassName(observableEl);
    for (const i in creditTextArray) {
        if (creditTextArray.hasOwnProperty(i)) {
            const creditTextArrayElement = creditTextArray[i];
            creditTextArrayElement.innerHTML = creditTextArrayElement.innerText
                .replace("EarthFeed", "<a href='https://github.com/MichaelYagi/EarthFeed' target='_blank' title='EarthFeed GitHub link'>EarthFeed</a>")
                .replace("MapTiler", "<a href='https://www.maptiler.com/' target='_blank' title='MapTiler link'>MapTiler</a>");
        }
    }
}).catch(() => {
    console.log(observableEl + " element did not load in " + (observableTimeout/1000) + " seconds");
});