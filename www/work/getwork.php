<?php
header('Content-type: text/plain');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
set_time_limit(300);
$location = $_GET['location'];
$key = $_GET['key'];

// load all of the locations
$locations = parse_ini_file('../settings/locations.ini', true);
$settings = parse_ini_file('../settings/settings.ini');

$workDir = $locations[$location]['localDir'];
$locKey = $locations[$location]['key'];
if( strlen($workDir) && (!strlen($locKey) || !strcmp($key, $locKey)) )
{
    // lock the working directory for the given location
    $lockFile = fopen( $workDir . '/lock.dat', 'a+b',  false);
    if( $lockFile )
    {
        // TODO: add a timeout to the locking loop
        while( !flock($lockFile, LOCK_EX) )
            sleep(1);

        // load the first work file
        $files = scandir($workDir);
        $fileName;
        $testId;
        foreach( $files as $file )
        {
            if(is_file("$workDir/$file"))
            {
                $parts = pathinfo($file);
                if( !strcasecmp( $parts['extension'], 'url') )
                {
                    $testId = basename($file, ".url");;
                    $fileName = "$workDir/$file";
                    break;
                }
            }
        }
        
        if( strlen($fileName) )
        {
            $testInfo = file_get_contents($fileName);
            echo "Test ID=$testId\r\nurl=" . $testInfo;
            unlink($fileName);
            
            // see if there is a script file
            $fileName = str_replace('.url', '.pts', $fileName);
            if( is_file($fileName) )
            {
                $script = trim(file_get_contents($fileName));
                if( strlen($script) )
                {
                    echo "\r\n[Script]\r\n";
                    echo $script;
                }
            }
            $ok = true;
            
            // figure out the path to the results
            $testPath = "../results/$testId";
            if( strpos($testId, '_') == 6 )
            {
                $parts = explode('_', $testId);
                $testPath = '../results/' . substr($parts[0], 0, 2) . '/' . substr($parts[0], 2, 2) . '/' . substr($parts[0], 4, 2) . '/' . $parts[1];
            }
            elseif( strlen($settings['olddir']) )
            {
                if( $settings['oldsubdir'] )
                    $testPath = "../results/{$settings['olddir']}/_" . strtoupper(substr($testId, 0, 1)) . "/$testId";
                else
                    $testPath = "../results/{$settings['olddir']}/$testId";
            }

            // flag the test with the start time
            $ini = file_get_contents("$testPath/testinfo.ini");
            $start = "[test]\r\nstartTime=" . date("m/d/y G:i:s");
            $out = str_replace('[test]', $start, $ini);
            file_put_contents("$testPath/testinfo.ini", $out);
        }
        
        fclose($lockFile);
    }
}
?>
