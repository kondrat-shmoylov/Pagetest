<?php 
// shared initializiation/loading code
error_reporting(0);

// load the install-specific settings
$settings = parse_ini_file('./settings/settings.ini');

// Load the test-specific data
$id = htmlspecialchars($_GET["test"]);

// if we didn't get an id explicitly, try from cookie
if( !$id )
    $id = $_COOKIE['tid'];

if( $id )
{
    // set the test ID cookie (session cookie) if we're looking at a different test
    if( strcmp($id, $_COOKIE['tid']) )
        setcookie('tid', $id);
    
    $testPath = "./results/$id";
    if( strpos($id, '_') == 6 )
    {
        $parts = explode('_', $id);
        $testPath = './results/' . substr($parts[0], 0, 2) . '/' . substr($parts[0], 2, 2) . '/' . substr($parts[0], 4, 2) . '/' . $parts[1];
    }
    elseif( strlen($settings['olddir']) )
    {
        if( $settings['oldsubdir'] )
            $testPath = "./results/{$settings['olddir']}/_" . strtoupper(substr($id, 0, 1)) . "/$id";
        else
            $testPath = "./results/{$settings['olddir']}/$id";
    }
    $run = htmlspecialchars($_GET["run"]);
    $cached = (int)$_GET["cached"];
    if( is_file("$testPath/testinfo.ini") )
        $test = parse_ini_file("$testPath/testinfo.ini",true);
    if( is_file("$testPath/url.txt") )
        $url = htmlspecialchars(file_get_contents("$testPath/url.txt"));
    if( is_file("$testPath/dom.txt") )
        $dom = htmlspecialchars(file_get_contents("$testPath/dom.txt"));
    if( is_file("$testPath/login.txt") )
        $login = htmlspecialchars(file_get_contents("$testPath/login.txt"));
    if( is_file("$testPath/block.txt") )
        $blockString = htmlspecialchars(file_get_contents("$testPath/block.txt"));
}
?>
