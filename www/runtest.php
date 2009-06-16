<?php
    error_reporting(0);

    $xml = false;
    if( !strcasecmp($_REQUEST['f'], 'xml') )
        $xml = true;

    // pull in the test parameters
    $test = array();
    $test['url'] = trim($_REQUEST['url']);
    $test['location'] = trim($_REQUEST['location']);
    $test['domElement'] = trim($_REQUEST['domelement']);
    $test['login'] = trim($_REQUEST['login']);
    $test['password'] = trim($_REQUEST['password']);
    $test['runs'] = (int)$_REQUEST['runs'];
    $test['fvonly'] = (int)$_REQUEST['fvonly'];
    $test['connections'] = (int)$_REQUEST['connections'];
    $test['speed'] = (int)$_REQUEST['speed'];
    $test['private'] = $_REQUEST['private'];
    $test['web10'] = $_REQUEST['web10'];
    $test['script'] = trim($_REQUEST['script']);
    $test['block'] = $_REQUEST['block'];
    $test['authType'] = (int)$_REQUEST['authType'];
    $test['notify'] = trim($_REQUEST['notify']);

    // some myBB integration to get the requesting user
    if( is_dir('./forums') && isset($_COOKIE['mybbuser']) )
    {
        $dir = getcwd();
        try
        {
            define("IN_MYBB",1);
            chdir('forums'); // path to MyBB
            include './global.php';

            $test['uid'] = $mybb->user['uid'];
            $test['user'] = $mybb->user['username'];
        }
        catch(Exception $e)
        {
        }
        chdir($dir);
    }


    // check to make sure the referrer is the same as the host
    if( CheckReferrer() && CheckIp() && CheckUrl($test['url']) )
    {
        // load the location information
        $locations = parse_ini_file('./settings/locations.ini', true);
        $error = NULL;

        ValidateParameters($test, $locations, $error);
        if( !$error )
        {
            if( $test['remoteUrl'] )
            {
                // send the test request to the remote system (only allow this for POST requests for now)
                SendRemoteTest($test, $_POST, $error);
            }
            else
            {
                // generate the test ID
                include_once('unique.inc');
                $id = null;
                if( $test['private'] )
                    $id = md5(uniqid(rand(), true));
                else
                    $id = uniqueId();
                $today = new DateTime("now", new DateTimeZone('America/New_York'));
                $test['id'] = $today->format('ymd_') . $id;
                $test['path'] = 'results/' . $today->format('y');

                // create the folder for the test results
                if( !is_dir($test['path']) )
                    mkdir($test['path']);
                $test['path'] .= $today->format('/m');
                if( !is_dir($test['path']) )
                    mkdir($test['path']);
                $test['path'] .= $today->format('/d');
                if( !is_dir($test['path']) )
                    mkdir($test['path']);
                $test['path'] .= "/$id";
                if( !is_dir($test['path']) )
                    mkdir($test['path']);

                // write out the url, DOM element and login
                file_put_contents("{$test['path']}/url.txt",  $test['url']);
                file_put_contents("{$test['path']}/dom.txt",  $test['domElement']);
                file_put_contents("{$test['path']}/login.txt",  $test['login']);

                // write out the ini file
                $testInfo = "[test]\r\n";
                $testInfo .= "fvonly={$test['fvonly']}\r\n";
                $testInfo .= "runs={$test['runs']}\r\n";
                $testInfo .= "location={$test['locationText']}\r\n";
                $testInfo .= "id={$test['id']}\r\n";
                if( strlen($test['login']) )
                    $testInfo .= "authenticated=1\r\n";
                $testInfo .= "connections={$test['connections']}\r\n";
                if( strlen($test['script']) )
                    $testInfo .= "script=1\r\n";
                if( strlen($test['notify']) )
                    $testInfo .= "notify={$test['notify']}\r\n";

                $testInfo .= "\r\n[runs]\r\n";
                $testInfo .= "total={$test['runs']}\r\n";

                file_put_contents("{$test['path']}/testinfo.ini",  $testInfo);

                // build up the actual test commands
                $testFile = '';
                if( strlen($test['domElement']) )
                    $testFile .= "\r\nDOMElement={$test['domElement']}";
                if( $test['fvonly'] )
                    $testFile .= "\r\nfvonly=1";
                if( $test['connections'] )
                    $testFile .= "\r\nconnections={$test['connections']}";
                if( $test['speed'] )
                    $testFile .= "\r\nspeed={$test['speed']}";
                if( $test['web10'] )
                    $testFile .= "\r\nweb10=1";
                if( $test['block'] )
                {
                    $testFile .= "\r\nblock={$test['block']}";
                    file_put_contents("{$test['path']}/block.txt",  $test['block']);
                }
                $testFile .= "\r\nruns={$test['runs']}\r\n";

                // see if we need to generate a SNS authentication script
                if( strlen($test['login']) && strlen($test['password']) )
                {
                    if( $test['authType'] == 1 )
                        $test['script'] = GenerateSNSScript($test);
                    elseif( $test['authType'] == 2 )
                        $test['script'] = GenerateYandexAuthScript($test);
                    else
                        $testFile .= "\r\nBasic Auth={$test['login']}:{$test['password']}\r\n";
                }

                if( !SubmitUrl($test['id'], $testFile, $test) )
                    $error = "Error sending url to test machine.  Please try back later.";
            }
        }

        // redirect the browser to the test results page
        if( !$error )
        {
            // log the test results
            LogTest($test);

            // only redirect for local tests, otherwise the redirect has already been taken care of
            if( !$test['remoteUrl'] )
            {
                $host  = $_SERVER['HTTP_HOST'];
                $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

                if( $xml )
                {
                    header ('Content-type: text/xml');
                    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                    echo "<response>\n";
                    echo "<statusCode>200</statusCode>\n";
                    echo "<statusText>Ok</statusText>\n";
                    if( strlen($_REQUEST['r']) )
                        echo "<requestId>{$_REQUEST['r']}</requestId>\n";
                    echo "<data>\n";
                    echo "<testId>{$test['id']}</testId>\n";
                    echo "<xmlUrl>http://$host$uri/xmlResult/{$test['id']}/</xmlUrl>\n";
                    echo "<userUrl>http://$host$uri/result/{$test['id']}/</userUrl>\n";
                    echo "</data>\n";
                    echo "</response>\n";

                }
                else
                {
                    header("Location: http://$host$uri/result/{$test['id']}/");
                }
            }
        }
        else
        {
            if( $xml )
            {
                header ('Content-type: text/xml');
                echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                echo "<response>\n";
                echo "<statusCode>400</statusCode>\n";
                echo "<statusText>" . $error . "</statusText>\n";
                if( strlen($_REQUEST['r']) )
                    echo "<requestId>" . $_REQUEST['r'] . "</requestId>\n";
                echo "</response>\n";
            }
            else
            {
                include 'common.inc';
                ?>
                <!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
                <html>
                    <head>
                        <title>Test error</title>
                        <style type="text/css">
                        <?php
                            include 'pagestyle.css';
                        ?>
                        </style>
                    </head>
                    <body>
                        <div class="page">
                            <?php
                            include 'header.inc';
                            ?>
                            <div class="content">
                                <?php
                                echo "<p>$error</p>\n";
                                ?>
                            </div>
                        </div>
                    </body>
                </html>
                <?php
            }
        }
    }
    else
        include 'blocked.php';

/**
* Validate the test options and set intelligent defaults
*
* @param mixed $test
* @param mixed $locations
*/
function ValidateParameters(&$test, $locations, &$error)
{
    if( strlen($test['url']) )
    {
        $settings = parse_ini_file('./settings/settings.ini');
        $maxruns = (int)$settings['maxruns'];
        if( !$maxruns )
            $maxruns = 10;

        // make sure the url starts with http://
        if( strncasecmp($test['url'], 'http:', 5) && strncasecmp($test['url'], 'https:', 6) )
            $test['url'] = 'http://' . $test['url'];

        ValidateURL($test, $error, $settings);
        if( !$error )
        {
            // make sure the test runs are between 1 and 200
            if( $test['runs'] > $maxruns )
                $test['runs'] = $maxruns;
            elseif( $test['runs'] < 1 )
                $test['runs'] = 1;

            // if fvonly is set, make sure it is to an explicit value of 1
            if( $test['fvonly'] > 0 )
                $test['fvonly'] = 1;

            // make sure private is explicitly 1 or 0
            if( $test['private'] )
                $test['private'] = 1;
            else
                $test['private'] = 0;

            // make sure web10 is explicitly 1 or 0
            if( $test['web10'] )
                $test['web10'] = 1;
            else
                $test['web10'] = 0;

            // make sure the number of connections is in a sensible range
            if( $test['connections'] > 20 )
                $test['connections'] = 20;
            elseif( $test['connections'] < 0 )
                $test['connections'] = 0;

            // use the default location if one wasn't specified
            if( !strlen($test['location']) )
                $test['location'] = $locations['locations']['default'];

            // filter out a SPAM bot that is hitting us
            //  for scripted tests, the block command will be in the script
            if( strlen($test['script']) && strlen($test['block']) )
                $error = 'Your test request was flagged by our system as potentially spam-related.  Please contact us if you think this was an error.';

            // figure out what the location working directory and friendly name are
            $test['locationText'] = $locations[$test['location']]['label'];
            $test['workdir'] = $locations[$test['location']]['localDir'];
            $test['remoteUrl']  = $locations[$test['location']]['remoteUrl'];
            $test['remoteLocation'] = $locations[$test['location']]['remoteLocation'];
            if( !strlen($test['workdir']) && !strlen($test['remoteUrl']) )
                $error = "Invalid Location, please try submitting your test request again.";

            // if the speed wasn't specified and there is one for the location, pass it on
            if( !$test['speed'] && $locations[$test['location']]['speed'] )
                $test['speed'] = $locations[$test['location']]['speed'];

            if( $test['script'] )
                ValidateScript($test, $error);
        }
    }
    else
        $error = "Invalid URL, please try submitting your test request again.";

    return $ret;
}

/**
* Validate the uploaded script to make sure it should be run
*
* @param mixed $test
* @param mixed $error
*/
function ValidateScript(&$test, &$error)
{
    $ok = false;
    $lines = explode("\n", $test['script']);
    foreach( $lines as $line )
    {
        $tokens = explode("\t", $line);
        $command = trim($tokens[0]);
        if( !strcasecmp($command, 'navigate') )
            $ok = true;
        elseif( !strcasecmp($command, 'loadVariables') )
            $error = "loadVariables is not a supported command for uploaded scripts.";
        elseif( !strcasecmp($command, 'loadFile') )
            $error = "loadFile is not a supported command for uploaded scripts.";
        elseif( !strcasecmp($command, 'fileDialog') )
            $error = "fileDialog is not a supported command for uploaded scripts.";
    }

    if( !ok )
        $error = "Invalid Script.  Navigate is a required script command.";
}

/**
* Make sure the URL they requested looks valid
*
* @param mixed $test
* @param mixed $error
*/
function ValidateURL(&$test, &$error, &$settings)
{
    $url = parse_url($test['url']);
    $host = $url['host'];

    if( strpos($host, '.') === FALSE )
        $error = "Please enter a Valid URL.  <b>$host</b> is not a valid Internet host name";
    elseif( !strcmp($host, "127.0.0.1") || ((!strncmp($host, "192.168.", 8)  || !strncmp($host, "10.", 3)) && !$settings['allowPrivate']) )
        $error = "You can not test <b>$host</b> from the public Internet.  Your web site needs to be hosted on the public Internet for testing";

    $ip = gethostbynamel($host);
    if( !$ip )
        $error = "<b>$host</b> does not appear to be a valid Internet host name.";
}

/**
* Generate a SNS authentication script for the given URL
*
* @param mixed $test
*/
function GenerateSNSScript($test)
{
    $script = "logdata\t0\n\n";

    $script .= "setEventName\tLaunch\n";
    $script .= "setDOMElement\tname=loginId\n";
    $script .= "navigate\t" . 'https://my.screenname.aol.com/_cqr/login/login.psp?mcState=initialized&sitedomain=search.aol.com&authLev=1&siteState=OrigUrl%3Dhttp%253A%252F%252Fsearch.aol.com%252Faol%252Fwebhome&lang=en&locale=us&seamless=y' . "\n\n";

    $script .= "setValue\tname=loginId\t{$test['login']}\n";
    $script .= "setValue\tname=password\t{$test['password']}\n";
    $script .= "setEventName\tLogin\n";
    $script .= "submitForm\tname=AOLLoginForm\n\n";

    $script .= "logData\t1\n\n";

    if( strlen($test['domElement']) )
        $script .= "setDOMElement\t{$test['domElement']}\n";
    $script .= "navigate\t{$test['url']}\n";

    return $script;
}

function GenerateYandexAuthScript($test)
{
    $script = "logdata\t0\n\n";

    $script .= "setEventName\tLaunch\n";
    $script .= "setDOMElement\tname=login\n";
    $script .= "navigate\t" . 'https://passport.yandex.ru/passport?mode=passport' . "\n\n";

    $script .= "setValue\tname=login\t{$test['login']}\n";
    $script .= "setValue\tname=passwd\t{$test['password']}\n";
    $script .= "setEventName\tLogin\n";
    $script .= "submitForm\tname=MainLogin\n\n";

    $script .= "logData\t1\n\n";

    if( strlen($test['domElement']) )
        $script .= "setDOMElement\t{$test['domElement']}\n";
    $script .= "navigate\t{$test['url']}\n";

    return $script;
}

/**
* Submit the test request file to the server
*
* @param mixed $run
* @param mixed $testRun
* @param mixed $test
*/
function SubmitUrl($run, $testRun, &$test)
{
    $ret = false;

    $out = '';
    if( !strlen($test['script']) )
        $out = $test['url'];
    else
    {
        $out = "script://$run.pts";

        // write out the script file
        file_put_contents($test['workdir'] . "/$run.pts", $test['script']);
    }

    // write out the actual test file
    $out .= $testRun;
    if( file_put_contents($test['workdir'] . "/$run.url", $out) )
        $ret = true;

    return $ret;
}

/**
* Log the actual test in the test log file
*
* @param mixed $test
*/
function LogTest(&$test)
{
    // open the log file
    $filename = "./logs/" . gmdate("Ymd") . ".log";
    $file = fopen( $filename, "a+b",  false);
    if( $file )
    {
        // TODO: add a timeout to the locking loop
        while( !flock($file, LOCK_EX) )
            sleep(1);

        $log = gmdate("Y-m-d G:i:s") . "\t{$_SERVER['REMOTE_ADDR']}" . "\t0" . "\t0";
        $log .= "\t{$test['id']}" . "\t{$test['url']}" . "\t{$test['locationText']}" . "\t{$test['private']}" . "\t{$test['uid']}" . "\t{$test['user']}" . "\r\n";

        fwrite($file, $log);

        fclose($file);
    }
}

/**
* Forward the test request to a remote system
*
* @param mixed $test
* @param mixed $params
* @param mixed $error
*/
function SendRemoteTest(&$test, $params, &$error)
{
    // patch in the correct location (local to the remote test system)
    if( $test['remoteLocation'] )
        $params['location'] = $test['remoteLocation'];

    $data = http_build_query($params);
    $params = array('http' => array(
                      'method' => 'POST',
                      'content' => $data
                      ));
    $ctx = stream_context_create($params);
    $fp = fopen($test['remoteUrl'], 'r', false, $ctx);

    if( $fp )
    {
        $url = '';
        // see if we got redirected
        $meta_data = stream_get_meta_data($fp);
        foreach($meta_data['wrapper_data'] as $response)
            if (substr(strtolower($response), 0, 10) == 'location: ')
                $url = trim(substr($response, 10));

        // if we didn't get redirected, parse the body looking for the javascript redirect (from the old runtest.exe)
        unset($response);
        if( !strlen($url) )
        {
            $response = stream_get_contents($fp);
            if( $response )
            {
                $lines = explode("\n", $response);
                foreach( $lines as $line )
                {
                    if( !strncasecmp($line, "window.location", 15) )
                    {
                        $tokens = explode("=", $line);
                        $relativeUrl = trim($tokens[1], "\r\n\t \"");
                    }
                }
            }

            if( strlen($relativeUrl) )
            {
                // build the absolute URL
                $parts = parse_url($test['remoteUrl']);
                $url = $parts['scheme'] . "://" . $parts['host'];
                if( $parts['port'] )
                    $url .= ":" . $parts['port'];
                $url .= $relativeUrl;
            }
        }

        // redirect the requestor to the real result
        if( strlen($url) )
        {
            $test['id'] = $url;
            header("Location: $url");
        }
        else
            $error = "Error submitting the test request to the remote system (unexpected response).  Please try again later.";
    }
    else
        $error = "Error submitting the test request to the remote system.  Please try again later.";
}

/**
* Check the referrer to make sure it is the same as the host we are serviing from
*
*/
function CheckReferrer()
{
    $ok = true;
/*    $settings = parse_ini_file('./settings/settings.ini');
    if( $settings['checkReferrer'] )
    {
        $host  = $_SERVER['HTTP_HOST'];
        $referrer = parse_url($_SERVER['HTTP_REFERER']);
        if( strcmp($host, $referrer['host']) )
        {
            $ok = false;

            // return a 403
            header("HTTP/1.0 403 Forbidden");
        }
    }
    */
    return $ok;
}

/**
* Make sure the requesting IP isn't on our block list
*
*/
function CheckIp()
{
    $ok = true;
    $ip = $_SERVER['REMOTE_ADDR'];
    $blockIps = file('./settings/blockip.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach( $blockIps as $block )
    {
        $block = trim($block);
        if( strlen($block) && ereg($block, $ip) )
        {
            $ok = false;
            break;
        }
    }

    return $ok;
}

/**
* Make sure the url isn't on our block list
*
* @param mixed $url
*/
function CheckUrl($url)
{
    $ok = true;
    $blockUrls = file('./settings/blockurl.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach( $blockUrls as $block )
    {
        $block = trim($block);
        if( strlen($block) && ereg($block, $url) )
        {
            $ok = false;
            break;
        }
    }

    return $ok;
}
?>
