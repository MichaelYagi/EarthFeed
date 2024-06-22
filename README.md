This is an experimental toy. Use in conjunction with [Shashin](https://github.com/MichaelYagi/shashin). Displays photos uploaded to Shashin. Uses WebGL Earth (http://www.webglearth.org) and the Shashin API. 
Type in a query to search for keywords and place names. Press spacebar to start or stop rotation.

Edit proxy.php and replace ```<shashin_url>``` with the Shashin base URL and ```<shashin_api_key>``` with a Shashin API key.

Query parameters
| Key | Value | Default | Description |
|---|---|---|---|
|```view```|```street\|sat```|```street```|Street or satellite view|
|```marker```|```true\|false```|```true```|Use image map markers if set to true|
|```start```|```YYYY-MM-DD```|Default is to get the latest 500 results|Map start date, must be used with and set before or equal to ```end```. eg. ```2015-03-01```|
|```end```|```YYYY-MM-DD```|Default is to get the latest 500 results|Map end date, must be used with and set after or equal to ```start```. eg. ```2015-03-30```|
|```offset```|```numeric```|```0```|The number of results to skip before returning anything|
|```limit```|```numeric```|```500```|The number of results returned|

eg. ```index.html?start=2015-03-01&end=2015-03-30&limit=100```

<img src="https://michaelyagi.github.io/images/earthfeed2.png" alt="Earthfeed" width="500"/>
