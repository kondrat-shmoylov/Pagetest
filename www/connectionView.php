<?php
header ("Content-type: image/png");
include 'common.inc';
include 'object_detail.inc'; 
include 'contentColors.inc';
include 'connectionView.inc';

$mime = $_GET['mime'];

// get all of the requests
$secure = false;
$haveLocations = false;
$requests = getRequests($id, $testPath, $run, $cached, $secure, $haveLocations, false);
$mimeColors = requestColors($requests);

$summary = array();
$connections = getConnections($requests, $summary);
$im = drawImage($connections, $summary, $run, $cached, $test, $url, $mime, $mimeColors, false);

// spit the image out to the browser
imagepng($im);
imagedestroy($im);

?>
