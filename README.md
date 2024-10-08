This is an experimental toy. Use in conjunction with [Shashin](https://github.com/MichaelYagi/shashin). Display photos uploaded to Shashin. Uses WebGL Earth (http://www.webglearth.org) and the Shashin API. 
Type in a query to search for keywords and place names. Press spacebar to start or stop rotation.

Edit config.php and replace ```<shashin_url>``` and ```<shashin_api_key>``` with the Shashin base URL and Shashin API key respectively.

Requires ```curl``` and ```openssl``` PHP extensions.

Query parameters

| Key | &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Value&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | Default | Description |
|---|---|---|---|
|```view```|```street\|sat```|```street```|Street or satellite view|
|```marker```|```boolean```|```true```|Use image map markers if set to true|
|```range```|<nobr>```YYYY-MM-DD,YYYY-MM-DD```</nobr>|N/A<br>Default is to get the first 500 available results|Start date must set before or equal to end date. eg. ```2015-03-01,2015-03-04```|
|```page```|```numeric```|```0```|Page number|
|```size```|```numeric```|```500```|The number of results returned|
|```latlng```|```latitude,longitude```|Default on initialization is 49.169087251026724, -123.1464338053518|Set the latitude and longitude|

eg. ```index.html?start=2015-03-01&end=2015-03-30&limit=100```

<img src="https://michaelyagi.github.io/images/earthfeed2.png" alt="Earthfeed" width="500"/>
