<?php 
include 'common.inc';
include 'object_detail.inc'; 
include 'page_data.inc';
$data = loadPageRunData($testPath, $run, $cached);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <title>Web page performance test details</title>
        <meta http-equiv="charset" content="iso-8859-1">
        <meta name="keywords" content="Performance, Optimization, Pagetest, Page Design, CDN, Content Distribution Network">
        <meta name="description" content="Speed up the performance of your web pages with an automated analysis">
        <meta name="author" content="Patrick Meenan">

    <style type="text/css">
        <?php 
            include 'pagestyle.css'; 
        ?>
        div.bar {
			height:12px; 
			margin-top:auto; 
			margin-bottom:auto;
		}
		
		.left {text-align:left;}
		.center {text-align:center;}

		.indented1 {padding-left: 40pt;}
		.indented2 {padding-left: 80pt;}
		
        td {
			white-space:nowrap; 
			text-align:left; 
			vertical-align:top; 
		}
		
        td.center {
			text-align:center;
		}

		table.details {
		  margin-left:auto; margin-right:auto;
		  background: whitesmoke;
		  border-collapse: collapse;
		}
		table.details th, table.details td {
		  border: 1px silver solid;
		  padding: 0.2em;
		  text-align: center;
		  font-size: smaller;
		}
		table.details th {
		  background: gainsboro;
		}
		table.details caption {
		  margin-left: inherit;
		  margin-right: inherit;
		  background: whitesmoke;
		}
		table.details th.reqUrl, table.details td.reqUrl {
		  text-align: left;
		  width: 30em; 
		  word-wrap: break-word;
		}
		table.details td.even {
		  background: gainsboro;
		}
		table.details td.odd {
		  background: whitesmoke;
		}
		table.details td.evenRender {
		  background: #dfffdf;
		}
		table.details td.oddRender {
		  background: #ecffec;
		}
		table.details td.evenDoc {
		  background: #dfdfff;
		}
		table.details td.oddDoc {
		  background: #ececff;
		}
		table.details td.warning {
		  background: #ffff88;
		}
		table.details td.error {
		  background: #ff8888;
		}
    </style>

    </head>
    <body>
        <div class="page">
            <?php
            $tab = 'Test Result';
            $subtab = 'Detailed Results';
            include 'header.inc';
            ?>
            <div class="content">
				<p>Web page performance test details for <b><a rel="nofollow" href=<?php echo '"' . $url . '"';?>><?php echo $url;?></a></b><br>
				Test completed - <?php echo $test[test][completeTime];?> from <?php echo $test[test][location];?>
				<?php
				if( (int)$test[test][authenticated] == 1)
					echo '<br><b>Authenticated: ' . $login . '</b>';
				if( (int)$test[test][connections] !== 0)
					 echo '<b>' . $test[test][connections] . ' Browser connections</b><br>';
                if( strlen($blockString) )
                    echo "Blocked: <b>$blockString</b><br>";
				?>
				</p>
		        <br>
                <table id="tableResults" class="pretty" align="center" border="1" cellpadding="10" cellspacing="0">
                    <tr>
                        <th align="center" valign="middle">Load Time</th>
                        <th align="center" valign="middle">First Byte</th>
                        <th align="center" valign="middle">Start Render</th>
                        <?php if( (float)$data['domTime'] > 0.0 ) { ?>
                        <th align="center" valign="middle">DOM Element</th>
                        <?php } ?>
                        <th align="center" valign="middle">Document Complete</th>
                        <th align="center" valign="middle">Fully Loaded</th>
                        <th align="center" valign="middle">Requests</th>
                        <th align="center" valign="middle">Bytes In</th>
                        <th align="center" valign="middle">Result (error code)</th>
                    </tr>
                    <tr>
                        <?php
                        echo "<td id=\"LoadTime\" valign=\"middle\">" . number_format($data['loadTime'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"TTFB\" valign=\"middle\">" . number_format($data['TTFB'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"startRender\" valign=\"middle\">" . number_format($data['render'] / 1000.0, 3) . "s</td>\n";
                        if( (float)$data['domTime'] > 0.0 )
                            echo "<td id=\"domTime\" valign=\"middle\">" . number_format($data['domTime'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"docComplete\" valign=\"middle\">" . number_format($data['docTime'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"fullyLoaded\" valign=\"middle\">" . number_format($data['fullyLoaded'] / 1000.0, 3) . "s</td>\n";
                        echo "<td id=\"requests\" valign=\"middle\">{$data['requests']}</td>\n";
                        echo "<td id=\"bytesIn\" valign=\"middle\">" . number_format($data['bytesIn'] / 1024, 0) . " KB</td>\n";
                        echo "<td id=\"result\" valign=\"middle\">{$data['result']}</td>\n";
                        ?>
                    </tr>
                </table>
                <br>
                <?php 
                $secure = false;
                $haveLocations = false;
                $requests = getRequests($id, $testPath, $run, $_GET["cached"], $secure, $haveLocations, true);
                ?>
                <div style="text-align:center;">
                <h3 name="waterfall_view">Waterfall View</h3>
                <table border="1" cellpadding="2px" cellspacing="0" style="width:auto; font-size:70%; margin-left:auto; margin-right:auto;">
                    <tr>
                        <td><table><tr><td><div class="bar" style="width:15px; background-color:#007B84"></div></td><td>DNS Lookup</td></tr></table></td>
                        <td><table><tr><td><div class="bar" style="width:15px; background-color:#FF7B00"></div></td><td>Initial Connection</td></tr></table></td>
                        <?php if($secure) { ?>
                        <td><table><tr><td><div class="bar" style="width:15px; background-color:#CF25DF"></div></td><td>SSL Negotiation</td></tr></table></td>
                        <?php } ?>
                        <td><table><tr><td><div class="bar" style="width:15px; background-color:#00FF00"></div></td><td>Time to First Byte</td></tr></table></td>
                        <td><table><tr><td><div class="bar" style="width:15px; background-color:#007BFF"></div></td><td>Content Download</td></tr></table></td>
                        <td><table><tr><td><div class="bar" style="width:2px; background-color:#28BC00"></div></td><td>Start Render</td></tr></table></td>
                        <?php if( (float)$data['domTime'] > 0.0 ) { ?>
                        <td><table><tr><td><div class="bar" style="width:2px; background-color:#F28300"></div></td><td>DOM Element</td></tr></table></td>
                        <?php } ?>
                        <td><table><tr><td><div class="bar" style="width:2px; background-color:#0000FF"></div></td><td>Document Complete</td></tr></table></td>
                        <td style="vertical-align:middle;"><div style="background-color:#FFFF00">3xx result</div></td>
                        <td style="vertical-align:middle;"><div style="background-color:#FF0000">4xx+ result</div></td>
                    </tr>
                </table>
                <br>
                <map name="waterfall_map">
                <?php
			        $reqCount = count($requests);
			        for( $i = 0; $i < $reqCount; $i++)
			        {
                        $index = $i + 1;
                        $top = 17 + ($index * 16);
                        $title = $index . ': ' . $requests[$i]['host'] . $requests[$i]['url'];
				        echo '<area href="#request' . $index . '" alt="' . $title . '" title="' . $title . '" shape=RECT coords="0,' . $top . ',1000,' . ($top + 16) . '">' . "\n";
			        }
                ?>
                </map>
                <img alt="If the waterfall doesn't display, please try refreshing the page" usemap="#waterfall_map" border="0" id="waterfall" src="<?php 
                    $cached='';
                    if((int)$_GET["cached"] == 1)
                        $cached='_Cached';
                    echo substr($testPath, 1) . '/' . $run . $cached . '_waterfall.png"';?>>
                <h3 name="connection_view">Connection View</h3>
                <map name="connection_map">
                <?php
                    include 'contentColors.inc';
                    include 'connectionView.inc';
                    $mimeColors = requestColors($requests);
                    $summary = array();
                    $connections = getConnections($requests, $summary);
                    $map = drawImage($connections, $summary, $run, $cached, $test, $url, $mime, $mimeColors, true);
                    foreach($map as $entry)
                    {
                        if( $entry['request'] !== NULL )
                        {
                            $index = $entry['request'] + 1;
                            $title = $index . ': ' . $entry['url'];
                            echo '<area href="#request' . $index . '" alt="' . $title . '" title="' . $title . '" shape=RECT coords="' . $entry['left'] . ',' . $entry['top'] . ',' . $entry['right'] . ',' . $entry['bottom'] . '">' . "\n";
                        }
                        else
                            echo '<area href="#request" alt="' . $entry['url'] . '" title="' . $entry['url'] . '" shape=RECT coords="' . $entry['left'] . ',' . $entry['top'] . ',' . $entry['right'] . ',' . $entry['bottom'] . '">' . "\n";
                    }
                ?>
                </map>
                <table border="1" cellpadding="2px" cellspacing="0" style="width:auto; font-size:70%; margin-left:auto; margin-right:auto;">
                    <tr>
                        <td><table><tr><td><div class="bar" style="width:15px; background-color:#007B84"></div></td><td>DNS Lookup</td></tr></table></td>
                        <td><table><tr><td><div class="bar" style="width:15px; background-color:#FF7B00"></div></td><td>Initial Connection</td></tr></table></td>
                        <?php if($secure) { ?>
                        <td><table><tr><td><div class="bar" style="width:15px; background-color:#CF25DF"></div></td><td>SSL Negotiation</td></tr></table></td>
                        <?php } ?>
                        <td><table><tr><td><div class="bar" style="width:15px; background-color:#00FF00"></div></td><td>Time to First Byte</td></tr></table></td>
                        <td><table><tr><td><div class="bar" style="width:15px; background-color:#007BFF"></div></td><td>Content Download</td></tr></table></td>
                        <td><table><tr><td><div class="bar" style="width:2px; background-color:#28BC00"></div></td><td>Start Render</td></tr></table></td>
                        <?php if( (float)$data['domTime'] > 0.0 ) { ?>
                        <td><table><tr><td><div class="bar" style="width:2px; background-color:#F28300"></div></td><td>DOM Element</td></tr></table></td>
                        <?php } ?>
                        <td><table><tr><td><div class="bar" style="width:2px; background-color:#0000FF"></div></td><td>Document Complete</td></tr></table></td>
                    </tr>
                </table>
                <br>
                <img alt="If the waterfall doesn't display, please try refreshing the page" usemap="#connection_map" border="0" id="connectionView" src="<?php 
                    echo "/connectionView.php?test=$id&run=$run&cached=" . $_GET['cached'];?>">
                </div>
		        <br><br> 
                <?php include('./ads/details_middle.inc'); ?>

		        <br>
		        <?php include 'waterfall_detail.inc'; ?>
                <div style="width:100%;float:none;clear:both;"></div>
            </div>
        </div>
        <?php if($settings[analytics]) { ?>
        <script type="text/javascript">
        var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
        document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
        </script>
        <script type="text/javascript">
        var pageTracker = _gat._getTracker(<?php echo '"' . $settings[analytics] . '"'; ?>);
        pageTracker._initData();
        pageTracker._trackPageview();
        </script>
        <?php } ?>
    </body>
</html>
