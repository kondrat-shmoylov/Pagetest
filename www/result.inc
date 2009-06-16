<?php
$fv = null;
$rv = null;
$pageStats = calculatePageStats($pageData, $fv, $rv);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <title>Web page performance test results</title>
        <meta http-equiv="charset" content="iso-8859-1">
        <meta name="keywords" content="Performance, Optimization, Pagetest, Page Design, CDN, Content Distribution Network">
        <meta name="description" content="Speed up the performance of your web pages with an automated analysis">
        <meta name="author" content="Patrick Meenan">
        <?php if( !$test['test']['completeTime'] ) { ?>
        <meta http-equiv="refresh" content="30"/>
        <?php } ?>
        <style type="text/css">
        <?php 
            include 'pagestyle.css'; 
        ?>
        </style>
    </head>
    <body>
        <div class="page">
            <?php
            $tab = 'Test Result';
            $subtab = 'Summary';
            include 'header.inc';
            ?>
            <div class="content">
			    <div style="float:right; text-align:right;">
                    <?php
                        if( $test['test']['completeTime'] )
				            echo '<a href="/result/' . $id . '/page_data.csv">Raw page data</a> - <a href="/result/' . $id . '/requests.csv">Raw object data</a>';
                    ?>
			    </div>
		        <div style="float:left;">
			        Web page performance test results for <b><a rel="nofollow" href=<?php echo '"' . $url . '"';?>><?php echo $url;?></a></b><br>
			        Test completed - <?php echo $test['test']['completeTime']; if( strlen($test['test']['location']) ) echo ' from ' . $test['test']['location'];?><br>
			        <?php
			        if( (int)$test['test']['authenticated'] == 1)
				        echo '<br><b>Authenticated: ' . $login . '</b>';
			        if( (int)$test['test']['connections'] !== 0)
				         echo '<b>' . $test[test][connections] . ' Browser connections</b><br>';
                    if( $test['test']['script'] ) 
                        echo '<b>Scripted test</b><br>';
                    if( strlen($blockString) )
                        echo "Blocked: <b>$blockString</b><br>";
			        ?>
		        </div>
                <div style="text-align:center; width:100%; clear:both;">
                <br>
                <?php if( !$test['test']['completeTime'] ) { ?>
                    <h3>Test is partially complete.  This page will refresh every 30 seconds until the full test is complete.</h3>
                <?php } ?>
                <?php if( $test['test']['completeTime'] ) { ?>
                <div id="average" style="text-align:center;">
                    <table id="tableResults" class="pretty" align="center" border="1" cellpadding="10" cellspacing="0">
                    <tr>
                        <th align="center" class="empty" valign="middle"></td>
                        <th align="center" valign="middle">Load Time</th>
                        <th align="center" valign="middle">First Byte</th>
                        <th align="center" valign="middle">Start Render</th>
                        <?php if((float)$fv['domTime'] > 0.0) { ?>
                        <th align="center" valign="middle">DOM Element</th>
                        <?php } ?>
                        <th align="center" valign="middle">Document Complete</th>
                        <th align="center" valign="middle">Fully Loaded</th>
                        <th align="center" valign="middle">Requests</th>
                        <th align="center" valign="middle">Bytes In</th>
                    </tr>
                    <tr>
                        <td align="left" valign="middle">First View</td>
                        <?php
                        echo "<td id=\"fvLoadTime\" valign=\"middle\">" . number_format($fv['loadTime'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"fvTTFB\" valign=\"middle\">" . number_format($fv['TTFB'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"fvStartRender\" valign=\"middle\">" . number_format($fv['render'] / 1000.0, 3) . "s</td>\n";
                        if((float)$fv['domTime'] > 0.0)
                            echo "<td id=\"fvDomElement\" valign=\"middle\">" . number_format($fv['domTime'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"fvDocComplete\" valign=\"middle\">" . number_format($fv['docTime'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"fvFullyLoaded\" valign=\"middle\">" . number_format($fv['fullyLoaded'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"fvRequests\" valign=\"middle\">" . number_format($fv['requests'],0) . "</td>\n";
                        echo "<td id=\"fvBytes\" valign=\"middle\">" . number_format($fv['bytesIn'] / 1024, 0) . " KB</td>\n";
                        ?>
                    </tr>
                    <?php if( isset($rv) ) { ?>
                    <tr>
                        <td align="left" class="even" valign="middle">Repeat View</td>
                        <?php
                        echo "<td id=\"rvLoadTime\" class=\"even\" valign=\"middle\">" . number_format($rv['loadTime'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"rvTTFB\" class=\"even\" valign=\"middle\">" . number_format($rv['TTFB'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"rvStartRender\" class=\"even\" valign=\"middle\">" . number_format($rv['render'] / 1000.0, 3) . "s</td>\n";
                        if((float)$rv['domTime'] > 0.0)
                            echo "<td id=\"rvDomElement\" class=\"even\" valign=\"middle\">" . number_format($rv['domTime'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"rvDocComplete\" class=\"even\" valign=\"middle\">" . number_format($rv['docTime'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"rvFullyLoaded\" class=\"even\" valign=\"middle\">" . number_format($rv['fullyLoaded'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"rvRequests\" class=\"even\" valign=\"middle\">" . number_format($rv['requests'],0) . "</td>\n";
                        echo "<td id=\"rvBytes\" class=\"even\" valign=\"middle\">" . number_format($rv['bytesIn'] / 1024, 0) . " KB</td>\n";
                        ?>
                    </tr>
                    <?php } ?>
                    </table><br>
                    <?php if( count($pageData) > 1 && isset($fv['avgRun']) ) 
                            echo '<b><a href="#run' . $fv['avgRun'] . '">Run ' . $fv['avgRun'] . '</a></b> is the closest to the average load time.<br/><br/>'; ?>
                </div>
                <?php } ?>
                <div id="tables" style="text-align:left;">
                <?php
                $runs = count($pageData);
                $fvonly = 1;
                if( isset($rv) )
                    $fvonly = 0;
                for( $run = 1; $run <= $runs; $run++)
                {
                    if( $runs > 1 )
                        echo '<h4><a name="run' . $run. '">Run ' . $run . ':</a></h4>';
                ?>
                    <?php if( allFilesPresent($id, $testPath, $run, $fvonly) ) { ?>
                    <table id="table<?php echo $run;?>" class="pretty" align="center" border="1" cellpadding="20" cellspacing="0">
                    <tr>
                        <th align="center" class="empty" valign="middle"></th>
                        <th align="center" valign="middle">Waterfall</th>
                        <th align="center" valign="middle">Optimization Checklist</th>
                        <th align="center" valign="middle">Screen Shot</th>
                    </tr>
                    <tr>
                        <td align="left" valign="middle">First View<?php 
                            if( isset($pageData[$run][0]['result']) && $pageData[$run][0]['result'] !== 0 && $pageData[$run][0]['result'] !== 99999 )
                                echo '<br>(Error: ' . LookupError($pageData[$run][0]['result']) . ')';
                            else if( isset($pageData[$run][0]['loadTime']) )
                                echo '<br>(' . number_format($pageData[$run][0]['loadTime'] / 1000.0, 3) . 's)';
                        ?></td>
                        <td align="center" valign="middle"><a href="/result/<?php echo "$id/$run/details/"; ?>"><img width=250 src="<?php echo "/result/$id/$run"; ?>_waterfall_thumb.png" BORDER=0/></a></td>
                        <td align="center" valign="middle"><a href="/result/<?php echo "$id/$run/performance_optimization/"; ?>"><img width=250 src="<?php echo "/result/$id/$run"; ?>_optimization_thumb.png" BORDER=0/></a></td>
                        <td align="center" valign="middle"><a href="/result/<?php echo "$id/$run/screen_shot/"; ?>"><img width=250 src="<?php echo "/result/$id/$run"; ?>_screen_thumb.jpg" BORDER=0/></a></td>
                    </tr>

                    <?php if( $fvonly == 0 ) { ?>
                    <tr>
                        <td align="left" class="even" valign="middle">Repeat View<?php 
                            if( isset($pageData[$run][1]['result']) && $pageData[$run][1]['result'] !== 0 && $pageData[$run][1]['result'] !== 99999 )
                                echo '<br>(Error: ' . LookupError($pageData[$run][1]['result']) . ')';
                            else if( isset($pageData[$run][1]['loadTime']) )
                                echo '<br>(' . number_format($pageData[$run][1]['loadTime'] / 1000.0, 3) . 's)';
                        ?></td>
                        <td align="center" class="even" valign="middle"><a href="/result/<?php echo "$id/$run/details/cached/"; ?>"><img width=250 src="<?php echo "/result/$id/$run"; ?>_Cached_waterfall_thumb.png" BORDER=0/></a></td>
                        <td align="center" class="even" valign="middle"><a href="/result/<?php echo "$id/$run/performance_optimization/cached/"; ?>"><img width=250 src="<?php echo "/result/$id/$run"; ?>_Cached_optimization_thumb.png" BORDER=0/></a></td>
                        <td align="center" class="even" valign="middle"><a href="/result/<?php echo "$id/$run/screen_shot/cached/"; ?>"><img width=250 src="<?php echo "/result/$id/$run"; ?>_Cached_screen_thumb.jpg" BORDER=0/></a></td>
                    </tr>
                    <?php } ?>
                    
                    <tr>
                        <td align="left" valign="middle">Content Breakdown</td>
                        <td align="left" valign="middle" colspan="3">
                            <a href="/result/<?php echo "$id/$run/breakdown/"; ?>">
                            <img width=400 height=200 src="/breakdownPie.php?width=400&height=200&type=Requests&fontSize=8<?php echo "&test=$id&run=$run"; ?>" BORDER=0/>
                            <img width=400 height=200 src="/breakdownPie.php?width=400&height=200&type=Bytes&fontSize=8<?php echo "&test=$id&run=$run"; ?>" BORDER=0/>
                            </a>
                        </td>
                    </tr>
                    
                    </table><br>
                    <?php } else { ?>
                        <b>Waiting for test results...</b><br><br>
                    <?php } ?>
                <?php
                }
                ?>
                </div>
                </div>

		        <br>
                <?php include('./ads/results_bottom.inc'); ?>
		        <br>
            </div>
        </div>
        <?php if($settings['analytics']) { ?>
        <script type="text/javascript">
        var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
        document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
        </script>
        <script type="text/javascript">
        var pageTracker = _gat._getTracker(<?php echo '"' . $settings['analytics'] . '"'; ?>);
        pageTracker._initData();
        pageTracker._trackPageview();
        </script>
        <?php } ?>
    </body>
</html>

<?php
/**
* See if all of the result files are present (prevents a bunch of 404's on the server)
* 
* @param mixed $id
* @param mixed $run
* @param mixed $fvonly
*/
function allFilesPresent($id, $testPath, $run, $fvonly)
{
    $ret = false;
    
    if( stat( "$testPath/$run" . "_waterfall.png" ) &&
        stat( "$testPath/$run" . "_optimization.png" ) &&
        stat( "$testPath/$run" . "_screen.jpg" ) &&
        stat( "$testPath/$run" . "_IEWPG.txt" ) &&
        stat( "$testPath/$run" . "_IEWTR.txt" ) &&
        stat( "$testPath/$run" . "_report.txt" ) &&
        stat( "$testPath/$run" . "_optimization.txt" ) )
    {
        if( $fvonly == 0 )
        {
            $ret = false;

            if( stat( "$testPath/$run" . "_Cached_waterfall.png" ) &&
                stat( "$testPath/$run" . "_Cached_optimization.png" ) &&
                stat( "$testPath/$run" . "_Cached_screen.jpg" ) &&
                stat( "$testPath/$run" . "_Cached_IEWPG.txt" ) &&
                stat( "$testPath/$run" . "_Cached_IEWTR.txt" ) &&
                stat( "$testPath/$run" . "_Cached_report.txt" ) &&
                stat( "$testPath/$run" . "_Cached_optimization.txt" ) )
            {
                $ret = true;
            }
        }
        else
            $ret = true;
    }

    return $ret;
}

/**
* Translate an error code into the text description
* 
* @param mixed $error
*/
function LookupError($error)
{
    $errorText = $error;
    
    switch($error)
    {
        case 7: $errorText = "Invalid SSL Cert."; break;
        case 99996: $errorText = "Timed Out waiting for DOM element"; break;
        case 99997: $errorText = "Timed Out"; break;
        case 99998: $errorText = "Timed Out"; break;
        case 88888: $errorText = "Script Error"; break;
        case -2146697211: $errorText = "DNS Lookup Failed"; break;
    }
    
    return $errorText;
}
?>