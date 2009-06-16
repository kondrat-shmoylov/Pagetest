<?php
require_once('../lib/pclzip.lib.php');
header('Content-type: text/plain');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
set_time_limit(300);
$location = $_REQUEST['location'];
$key = $_REQUEST['key'];
$done = $_REQUEST['done'];
$id = $_REQUEST['id'];

// load all of the locations
$locations = parse_ini_file('../settings/locations.ini', true);
$settings = parse_ini_file('../settings/settings.ini');

$locKey = $locations[$location]['key'];
if( (!strlen($locKey) || !strcmp($key, $locKey)) || !strcmp($_SERVER['REMOTE_ADDR'], "127.0.0.1") )
{
    if( isset($_FILES['file']) )
    {
        $fileName = $_FILES['file']['name'];
        $fileBase = strtok($fileName, '.');
        $id = strtok($fileBase, '-');
    }
    
    // figure out the path to the results
    $testPath = "../results/$id";
    if( strpos($id, '_') == 6 )
    {
        $parts = explode('_', $id);
        $testPath = '../results/' . substr($parts[0], 0, 2) . '/' . substr($parts[0], 2, 2) . '/' . substr($parts[0], 4, 2) . '/' . $parts[1];
    }
    elseif( strlen($settings['olddir']) )
    {
        if( $settings['oldsubdir'] )
            $testPath = "../results/{$settings['olddir']}/_" . strtoupper(substr($id, 0, 1)) . "/$id";
        else
            $testPath = "../results/{$settings['olddir']}/$id";
    }
        
    // extract the zip file
    if( isset($_FILES['file']) )
    {
        $archive = new PclZip($_FILES['file']['tmp_name']);
        $list = $archive->extract(PCLZIP_OPT_PATH, "$testPath/", PCLZIP_OPT_REMOVE_ALL_PATH);
    }
    
    // see if the test is complete
    if( $done )
    {
        $test = file_get_contents("$testPath/testinfo.ini");

        // update the completion time if it isn't already set
        if( !strpos($test, 'completeTime') )
        {
            $complete = "[test]\r\ncompleteTime=" . date("m/d/y G:i:s");
            $out = str_replace('[test]', $complete, $test);
            file_put_contents("$testPath/testinfo.ini", $out);
        }
        
        // do any other post-processing (e-mail notification for example)
        if( isset($settings['notifyFrom']) && is_file("$testPath/testinfo.ini") )
        {
            $test = parse_ini_file("$testPath/testinfo.ini",true);
            if( strlen($test['test']['notify']) )
                notify( $test['test']['notify'], $settings['notifyFrom'], $id, $testPath, $settings['host'] );
        }
    }
}

/**
* Send a mail notification to the user
* 
* @param mixed $mailto
* @param mixed $id
* @param mixed $testPath
*/
function notify( $mailto, $from,  $id, $testPath, $host )
{
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $headers .= "From: $from\r\n";
    $headers .= "Reply-To: $from";
    
    $url;
    if( is_file("$testPath/url.txt") )
        $url = htmlspecialchars(file_get_contents("$testPath/url.txt"));
    $shorturl = substr($url, 0, 40);
    if( strlen($url) > 40 )
        $shorturl .= '...';
    
    $subject = "Test results for $shorturl";
    
    if( !isset($host) )
        $host  = $_SERVER['HTTP_HOST'];

    // calculate the results
    require_once '../page_data.inc';
    $pageData = loadAllPageData($testPath);
    $fv = null;
    $rv = null;
    $pageStats = calculatePageStats($pageData, $fv, $rv);
    if( isset($fv) )
    {
        $load = number_format($fv['loadTime'] / 1000.0, 3);
        $render = number_format($fv['render'] / 1000.0, 3);
        $requests = number_format($fv['requests'],0);
        $bytes = number_format($fv['bytesIn'] / 1024, 0);
        $result = "http://$host/result/$id";
        
        // capture the optimization report
        require_once '../optimization.inc';
        ob_start();
        dumpOptimizationReport($testPath, 1, 0);
        $optimization = ob_get_contents();
        ob_end_clean();
        
        // build the message body
        $body = 
        "<html>
            <head>
                <title>$subject</title>
                <style type=\"text/css\">
                    .indented1 {padding-left: 40pt;}
                    .indented2 {padding-left: 80pt;}
                </style>
            </head>
            <body>
            <p>The full test results for <a href=\"$url\">$url</a> are now <a href=\"$result/\">available</a>.</p>
            <p>The page loaded in <b>$load seconds</b> with the user first seeing something on the page after <b>$render seconds</b>.  To download 
            the page required <b>$requests requests</b> and <b>$bytes KB</b>.</p>
            <p>Here is what the page looked like when it loaded (click the image for a larger view):<br><a href=\"$result/1/screen_shot/\"><img src=\"$result/1_screen_thumb.jpg\"></a></p>
            <h3>Here are the things on the page that could use improving:</h3>
            $optimization
            </body>
        </html>";

        // send the actual mail
        mail($mailto, $subject, $body, $headers);
    }
}
?>
