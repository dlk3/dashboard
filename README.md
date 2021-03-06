# Dashboard

This is a web page designed to function as a family dashboard.  It consolidates several on-line calendars into a single family schedule and displays weather forecast information relevant to our outdoor activities.  At my house it is displayed in a kiosked browser window on a wall-mounted display that is rotated 90 degrees (1080px wide by 1920px high) powered by a Raspberry PI.

The system components in this repository are:

+ <code>index.php</code> - the main page.  The page includes a meta refresh tag that causes it to refresh itself every 30 minutes.
+ <code>basepage.css</code> - CSS stylesheet with the page's basic styles
+ <code>dashboardClass.php</code> - PHP code for the page, mainly the code that constructs the schedule portion of the page by consolidating events from the various individual calendars.
+ <code>calendarSources.php</code> - included by dashboardClass.php, this file provides the URLs for the various calendars that will be consolidated into the schedule.  This information is broken out into its own file since it is sensitive information and should be protected.  A template for the file is provided in this repository - <code>calendarSources.php.template</code>.  The URLs point to ICS-formated calendar files such as those available from Google Calendar.
+ <code>schedule.css</code> - stylesheet with styles for the schedule portion of the page.
+ <code>dashboardScripts.js</code> - Javascript functions that populate the weather forecast section of the page with weather data.
+ <code>weather.css</code> - stylesheet with styles for the weather portion of the page.
+ <code>get-weather</code> - A Python script which in a contuous loop fetching weather data updates from the NOAA web services and writing it into a file every thirty minutes.  That file is then used as the source for weather data by the functions in <code>calendarScripts.js</code>.  The script requires three arguments on the command line, the latitude of your location, the longitude, the full path to the file where it should write its output, and the full path to a file where it can write log messages.
+ <code>get-weather.service.template</code> - a systemd service definition template that can be used to set up the get-weather script to start at system boot.  This file requires customization and renaming before use.
+ <code>humidity.php</code> - a web service that receives humidity and temperature information from an IoT humidity sensor and writes it to a file on disk.  <code>dashboardClass.php</code> reads this file and includes the information it contains in the page.
+ <code>composer.json</code> - use the <code>composer</code> utility to install the <code>johngrogg/ics-parser</code> PHP module that is used by <code>dashboardClass.php</code>.

Other components of the system that are not included in this repository:

+ <code>backgrounds/*</code> - a directory of images that are used as page backgrounds for the dashboard.  The images should be pre-formatted to fit the dimensions of the dashboard display.  No attempt to adjust the images is made.   An image is randomly selected every time the page is refreshed.  Any files in this directory with jpg, jpeg, png or gif extensions will be used.  I use portraits of family members, relatives, and pets as my page backgrounds.
+ CanvasJS - a Javascript utility used to create temperature, humidity, and wind speed forecast graphs on the dashboard.  Download it from [canvasjs.com](https://canvasjs.com).  Place <code>canvas.min.js</code> in a subdirectory called <code>js/canvasjs/</code> or modify the link in <code>index.php</code> that points to its location.  Note that the free version of this utility overlays a watermark on graphs that it draws.  Purchasing a licensed copy of the code will remove the watermark.  (Or you can hack the script to remove it.  Instructions will not be provided.)

## Data Sources

The schedule section of the page will consume any ICS-format data feed that can be accessed via a URL of some type.  Most major calendar services like Google Calendar or Apple's iCal/iCloud calendar service provide these.

This system pulls weather data from REST web services provided by NOAA and Synoptics.  You must register for a (free) API token from Synoptics in order to use their services.  See https://developers.synopticdata.com/mesonet/ for information.  NOAA's web services are open.  No token is required.  See https://www.weather.gov/documentation/services-web-api for information.

## Testing

I use a Docker image of a PHP web server to test the code on my local workstation:

    podman run -d --rm -p 8080:80 --name dashboard -v ~/src/dashboard:/var/www/html php:7.2-apache

The Apache server in this container logs to the system console so this command will tail the logs:

    podman logs -f dashboard

## Installation

This is the way I did it on my web server.  Adjust as required:

+ I cloned this repository to a run-time directory on my web server - <code>/opt/dashboard</code>
+ I uploaded my background image files into <code>/opt/dashboard/backgrounds</code>
+ I uploaded the CanvasJS utility as <code>/opt/dashboard/js/canvasjs/canvasjs.min.js</code>
+ I changed into the <code>/opt/dashboard</code> directory and ran the command <code>composer install</code>.  This created the <code>/opt/dashboard/vendor</code> directory containing the ics-parser PHP module.
+ I created a <code>/opt/dashboard/calendarSources.php</code> file containing the URLs of all the calendars I want to consolidate into the schedule, based on the template.
+ I set up the get-weather.service definition to run the <code>get-weather</code> script as a system service writing its output to <code>/opt/dashboard/weather_data.json</code>.
+ I added the <code>Alias</code> and <code>Directory</code> configuration statements to my Apache server configuration for the <code>/opt/dashboard</code> directory.
<pre>
        # dashboard - a calendar and weather data wall screen, secured with basic authentication
        Alias /dashboard "/opt/dashboard"
        &lt;Directory "/opt/dashboard/">
            Options -Indexes
            AuthType Basic
            AuthName "Dashboard"
            AuthBasicProvider file
            AuthUserFile "/usr/local/apache/passwd/dashboard"
            Require user &lt;USERID>
        &lt;/Directory>
</pre>
+ I created the authentication users file:<br /><code>sudo mkdir -p /usr/local/apache/passwd</code><br /><code>sudo htpasswd -c /usr/local/apache/passwd/dashboard *userid*</code><br /><code> sudo chown -R apache:apache /usr/local/apache</code><br /><code>sudo chmod 700 /usr/local/apache/passwd</code><br /><code>sudo chmod 600 /usr/local/apache/passwd/*</code><br />
+ I configured my IoT humidity sensor to call the <code>dashboard/humidity.php</code> web service so that it would write a <code>humidity.json</code> file in the <code>/opt/dashboard</code> directory.  Since the web service runs as the "apache" user, that user needs write access to update this file:<br /><code>touch /opt/dashboard/humidity.json</code><br /><code>sudo chown apache:apache /opt/dashboard/humidity.json</code>

That is all!
