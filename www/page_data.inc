<?php
/**
* Load the page results directly from the results files
* 
* @param mixed $id
* @param mixed $testPath
* @param mixed $run
* @param mixed $cached
*/
function loadAllPageData($testPath)
{
    $ret = array();
    
    // loop through all of the files
    $files = scandir($testPath);
    foreach( $files as $file )
    {
        if(is_file("$testPath/$file"))
        {
            $parts = pathinfo($file);
            if( !strcasecmp( $parts['extension'], 'txt') && strpos($file, '_IEWPG') !== FALSE )
            {
                $run = (int)strtok($file, '_');
                if( $run )
                {
                    if( !isset($ret[$run]) )
                        $ret[$run] = array();
                    $cached = 0;
                    if( strpos($file, '_Cached') !== FALSE )
                        $cached = 1;
                    $ret[$run][$cached] = loadPageData("$testPath/$file");
                }
            }
        }
    }
    
    return $ret;
}

/**
* Load the page data for the given run
* 
* @param mixed $testPath
* @param mixed $run
* @param mixed $fv
* @param mixed $rv
*/
function loadPageRunData($testPath, $run, $cached)
{
    $ret = null;

    if( $cached )
        $ret = loadPageData("$testPath/{$run}_Cached_IEWPG.txt");
    else
        $ret = loadPageData("$testPath/{$run}_IEWPG.txt");

    return $ret;
}

/**
* Load the page data from the specified file
* 
* @param mixed $file
*/
function loadPageData($file)
{
    $ret = null;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if( $lines)
    {
        // loop through each line in the file
        foreach($lines as $linenum => $line) 
        {
            // skip the header line
            if( $linenum > 0)
            {
                $fields = explode("\t", $line);
                if( count($fields) > 34 )
                {
                    $ret = array(   'URL' => htmlspecialchars($fields[3]),
                                    'loadTime' => (int)$fields[4],
                                    'TTFB' => (int)$fields[5],
                                    'bytesOut' => (int)$fields[7],
                                    'bytesIn' => (int)$fields[8],
                                    'requests' => (int)$fields[11],
                                    'result' => (int)$fields[17],
                                    'render' => (int)$fields[18],
                                    'fullyLoaded' => (int)$fields[22],
                                    'cached' => (int)$fields[27],
                                    'web' => (int)$fields[30],
                                    'docTime' => (int)$fields[32],
                                    'domTime' => (int)$fields[34]
                                );
                }
            }
        }
    }
    
    return $ret;
}

/**
* Calculate the averages and identify the run closest to the average
* 
* @param mixed $pageData
*/
function calculatePageStats($pageData, &$fv, &$rv)
{
    $fvCount = 0;
    $rvCount = 0;
    
    // calculate the averages
    foreach( $pageData as $run => $data )
    {
        if( isset($data[0]) && $data[0]['cached'] === 0 )
        {
            // only look at non-error runs
            if( $data[0]['result'] === 0 || $data[0]['result'] === 99999 )
            {
                if( !isset($fv) )
                    $fv = array();

                $fv['loadTime'] += $data[0]['loadTime'];
                $fv['TTFB'] += $data[0]['TTFB'];
                $fv['bytesIn'] += $data[0]['bytesIn'];
                $fv['requests'] += $data[0]['requests'];
                $fv['render'] += $data[0]['render'];
                $fv['fullyLoaded'] += $data[0]['fullyLoaded'];
                $fv['docTime'] += $data[0]['docTime'];
                $fv['domTime'] += $data[0]['domTime'];
                $fvCount++;
            }
        }

        if( isset($data[1]) && $data[1]['cached'] )
        {
            // only look at non-error runs
            if( $data[1]['result'] === 0 || $data[1]['result'] === 99999 )
            {
                if( !isset($rv) )
                    $rv = array();
                
                $rv['loadTime'] += $data[1]['loadTime'];
                $rv['TTFB'] += $data[1]['TTFB'];
                $rv['bytesIn'] += $data[1]['bytesIn'];
                $rv['requests'] += $data[1]['requests'];
                $rv['render'] += $data[1]['render'];
                $rv['fullyLoaded'] += $data[1]['fullyLoaded'];
                $rv['docTime'] += $data[1]['docTime'];
                $rv['domTime'] += $data[1]['domTime'];
                $rvCount++;
            }
        }
    }

    // calculate the first view stats    
    if( isset($fv) )
    {
        $fv['loadTime'] /= (double)$fvCount;
        $fv['TTFB'] /= (double)$fvCount;
        $fv['bytesIn'] /= (double)$fvCount;
        $fv['requests'] /= (double)$fvCount;
        $fv['render'] /= (double)$fvCount;
        $fv['fullyLoaded'] /= (double)$fvCount;
        $fv['docTime'] /= (double)$fvCount;
        $fv['domTime'] /= (double)$fvCount;
        
        // go through and find the run closest to the average
        $closest = -1;
        $distance = 10000000000;

        foreach( $pageData as $run => $data )
        {
            if( isset($data[0]) && ($data[0]['result'] === 0 || $data[0]['result'] === 99999) )
            {
                $curDist = abs($data[0]['loadTime'] - $fv['loadTime']);
                if( $curDist < $distance )
                {
                    $closest = $run;
                    $distance = $curDist;
                }
            }
        }
        
        if( $closest != -1 )
            $fv['avgRun'] = $closest;
    }

    // calculate the repeat view stats    
    if( isset($rv) )
    {
        $rv['loadTime'] /= (double)$rvCount;
        $rv['TTFB'] /= (double)$rvCount;
        $rv['bytesIn'] /= (double)$rvCount;
        $rv['requests'] /= (double)$rvCount;
        $rv['render'] /= (double)$rvCount;
        $rv['fullyLoaded'] /= (double)$rvCount;
        $rv['docTime'] /= (double)$rvCount;
        $rv['domTime'] /= (double)$rvCount;
        
        // go through and find the run closest to the average
        $closest = -1;
        $distance = 10000000000;

        foreach( $pageData as $run => $data )
        {
            if( isset($data[1]) && ($data[1]['result'] === 0 || $data[1]['result'] === 99999) )
            {
                $curDist = abs($data[1]['loadTime'] - $rv['loadTime']);
                if( $curDist < $distance )
                {
                    $closest = $run;
                    $distance = $curDist;
                }
            }
        }
        
        if( $closest != -1 )
            $rv['avgRun'] = $closest;
    }
}
?>