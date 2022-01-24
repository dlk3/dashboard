# Dashboard

This is a web page designed to function as a family dashboard.  It consolidates several on-line calendars into a single family schedule and displays weather forecast information relevant to our outdoor activities.  At my house it is displayed in a kiosked browser window on a wall-mounted display that is rotated 90 degrees (1080px wide by 1920px high) powered by a Raspberry PI.

The components of this system are:

+ <code>index.php</code> - the main page.  The page includes a meta refresh tag that causes it to refresh itself every 30 minutes.
+ <code>basepage.css</code> - CSS stylesheet with the page's basic styles
+ <code>dashboardClass.php</code> - PHP code for the page, mainly the code that constructs the schedule portion of the page by consolidating events from the various individual calendars.
+ <code>calendarSources.php</code> - included by dashboardClass.php, this file provides the URLs for the various calendars that will be consolidated into the schedule.  This information is broken out into its own file since it is sensitive information and should be protected.  A template for the file is provided in this project - <code>calendarSources.php.template</code>.  The URLs point to ICS-formated calendar files such as those available from Google Calendar.
+ <code>schedule.css</code> - stylesheet with styles for the schedule portion of the page.
+ <code>dashboardScripts.js</code> - Javascript functions that populate the weather forecast section of the page with weather data.
+ <code>weather.css</code> - stylesheet with styles for the weather portion of the page.
+ <code>get-weather</code> - A Python script which runs once every 30 minutes to fetch weather data from the NOAA web services and write it into a file.  That file is then used as the source for weather data by the functions in <code>calendarScripts.js</code>.  The script requires three arguments on the command line, the latitude of your location, the longitude, and the full path to the file where it should write its output.
+ <code>humidity.php</code> - a web service that receives humidity and temperature information from an IoT humidity sensor and writes it to a file on disk.  <code>dashboardClass.php</code> reads this file and  makes the information it contains part of the page.
+ <code>composer.json</code> - use the <code>composer</code> utility to install the <code>johngrogg/ics-parser</code> PHP module that is used by <code>dashboardClass.php</code>.

Other components of the system that are not included in this repository:

+ <code>backgrounds/*</code> - a directory of images that are used as page backgrounds for the dashboard.  The images should be pre-formatted to fit the dimensions of the dashboard display.  No attempt to adjust the images is made.   An image is randomly selected every time the page is refreshed.  Any files in this directory with jpg, jpeg, png or gif extensions will be used.  I use portraits of family members, relatives, and pets as my page backgrounds.
+ CanvasJS - a Javascript utility used to create temperature, humidity, and wind speed forecast graphs on the dashboard.  Download it from [canvasjs.com](https://canvasjs.com).  Place <code>canvas.min.js</code> in a subdirectory called <code>js/canvasjs/</code> or modify the link in <code>index.php</code> that points to its location.  Note that the free version of this utility overlays a watermark on graphs that it draws.  Purchasing a licensed copy of the code will remove the watermark.  (Or you can hack the script's code to remove the watermark.  Instructions will not be provided.)

## Testing

I use a Docker image of a PHP web server to test the code on my local workstation:

    podman run -d --rm -p 8080:80 --name dashboard -v ~/src/dashboard:/var/www/html php:7.2-apache

The Apache server in this container logs to the system console so this command will tail the logs:

    podman logs -f dashboard

## Installation

This is the way I did it.  Adjust as required:

+ I cloned this repository to a run-time directory on my web server - <code>/opt/dashboard</code>
+ I uploaded my background image files into <code>/opt/dashboard/backgrounds</code>
+ I uploaded the CanvasJS utility as <code>/opt/dashboard/js/canvasjs/canvasjs.min.js</code>
+ I changed into the <code>/opt/dashboard</code> directory and ran the command <code>composer install</code>.  This created the <code>/opt/dashboard/vendor</code> directory containing the ics-parser PHP module.
+ I created a <code>/opt/dashboard/calendarSources.php</code> file containing the URLs of all the calendars I want to consolidate into the schedule, based on the template.
+ I created a cron configuration file to execute the <code>get-weather</code> script every thirty minutes, writing its output to <code>/opt/dashboard/weather_data.json</code>.
+ I added the <code>Alias</code> and <code>Directory</code> configuration statements to my Apache server configuration for the <code>/opt/dashboard</code> directory.
<pre>
        # dashboard - a calendar and weather data wall screen, secured with basic authentication
        Alias /dashboard "/opt/dashboard"
        &lt;Directory "/opt/dashboard/">
            Options +FollowSymLinks -Indexes
            AuthType Basic
            AuthName "Dashboard"
            AuthBasicProvider file
            AuthUserFile "/usr/local/apache/passwd/dashboard"
            Require user &lt;USERID>
        &lt;/Directory>
</pre>
+ I created the authentication users file:<br /><br />    <code>sudo mkdir -p /usr/local/apache/passwd</code><br /><code>sudo htpasswd -c /usr/local/apache/passwd/dashboard *userid*</code><br /><code> sudo chown -R apache:apache /usr/local/apache</code><br /><code>sudo chmod 700 /usr/local/apache/passwd</code><br /><code>sudo chmod 600 /usr/local/apache/passwd/*</code><br />
+ I configured my IoT humidity sensor to call the <code>dashboard/humidity.php</code> web service so that it would write a <code>humidity.json</code> file in the <code>/opt/dashboard</code> directory.  Since the web server runs as the "apache" user, that user needs write access to update this file.<br /><br /><code>touch /opt/dashboard/humidity.json</code><br /><code>sudo chown apache:apache /opt/dashboard/humidity.json</code>

That is all!
