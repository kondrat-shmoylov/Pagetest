<?php
include 'common.inc';
include 'breakdown.inc';
include 'contentColors.inc';
include 'connectionView.inc';

// walk through the requests and group them by mime type
$requestsFv;
$breakdownFv = getBreakdown($id, $testPath, $run, 0, $requestsFv);
$breakdownRv = array();
$requestsRv = array();
if( (int)$test[test][fvonly] == 0 )
    $breakdownRv = getBreakdown($id, $testPath, $run, 1, $requestsRv);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <title>Web page performance test content breakdown</title>
        <meta http-equiv="charset" content="iso-8859-1">
        <meta name="keywords" content="Performance, Optimization, Pagetest, Page Design, CDN, Content Distribution Network">
        <meta name="description" content="Speed up the performance of your web pages with an automated analysis">
        <meta name="author" content="Patrick Meenan">

        <style type="text/css">
            <?php 
                include 'pagestyle.css'; 
            ?>
            td {
                text-align:center; 
                vertical-align:top; 
                padding:1em;
            }

            div.bar {
                height:12px; 
                margin-top:auto; 
                margin-bottom:auto;
            }

            td.legend {
                white-space:nowrap; 
                text-align:left; 
                vertical-align:top; 
                padding:0;
            }
        </style>
        
        <!--Load the AJAX API-->
        <script type="text/javascript" src="http://www.google.com/jsapi"></script>
        <script type="text/javascript">
    
        // Load the Visualization API and the table package.
        google.load('visualization', '1', {'packages':['table']});
        google.setOnLoadCallback(drawTable);
        function drawTable() {
            var dataFv = new google.visualization.DataTable();
            dataFv.addColumn('string', 'MIME Type');
            dataFv.addColumn('number', 'Requests');
            dataFv.addColumn('number', 'Bytes');
            dataFv.addRows(<?php echo count($breakdownFv); ?>);
            <?php
            $index = 0;
            ksort($breakdownFv);
            foreach($breakdownFv as $type => $data)
            {
                echo "dataFv.setValue($index, 0, '$type');\n";
                echo "dataFv.setValue($index, 1, {$data['requests']});\n";
                echo "dataFv.setValue($index, 2, {$data['bytes']});\n";
                $index++;
            }
            ?>

            var viewRequestsFv = new google.visualization.DataView(dataFv);
            viewRequestsFv.setColumns([0, 1]);
            
            var tableRequestsFv = new google.visualization.Table(document.getElementById('tableRequestsFv_div'));
            tableRequestsFv.draw(viewRequestsFv, {showRowNumber: false, sortColumn: 1, sortAscending: false});

            var viewBytesFv = new google.visualization.DataView(dataFv);
            viewBytesFv.setColumns([0, 2]);
            
            var tableBytesFv = new google.visualization.Table(document.getElementById('tableBytesFv_div'));
            tableBytesFv.draw(viewBytesFv, {showRowNumber: false, sortColumn: 1, sortAscending: false});

            <?php if( count($breakdownRv) ) { ?>
                var dataRv = new google.visualization.DataTable();
                dataRv.addColumn('string', 'MIME Type');
                dataRv.addColumn('number', 'Requests');
                dataRv.addColumn('number', 'Bytes');
                dataRv.addRows(<?php echo count($breakdownRv); ?>);
                <?php
                $index = 0;
                ksort($breakdownRv);
                foreach($breakdownRv as $type => $data)
                {
                    echo "dataRv.setValue($index, 0, '$type');\n";
                    echo "dataRv.setValue($index, 1, {$data['requests']});\n";
                    echo "dataRv.setValue($index, 2, {$data['bytes']});\n";
                    $index++;
                }
                ?>

                var viewRequestsRv = new google.visualization.DataView(dataRv);
                viewRequestsRv.setColumns([0, 1]);
                
                var tableRequestsRv = new google.visualization.Table(document.getElementById('tableRequestsRv_div'));
                tableRequestsRv.draw(viewRequestsRv, {showRowNumber: false, sortColumn: 1, sortAscending: false});

                var viewBytesRv = new google.visualization.DataView(dataRv);
                viewBytesRv.setColumns([0, 2]);
                
                var tableBytesRv = new google.visualization.Table(document.getElementById('tableBytesRv_div'));
                tableBytesRv.draw(viewBytesRv, {showRowNumber: false, sortColumn: 1, sortAscending: false});
            <?php } ?>
        }
        </script>
    </head>
    <body>
        <div class="page">
            <?php
            $tab = 'Test Result';
            $subtab = 'Content Breakdown';
            include 'header.inc';
            ?>
            <div class="content">
                <p>Web page content breakdown for <b><a rel="nofollow" href=<?php echo '"' . $url . '"';?>><?php echo $url;?></a></b><br>
                Test completed - <?php echo $test[test][completeTime];?> from <?php echo $test[test][location];?>
                <?php
                if( (int)$test[test][authenticated] == 1)
                    echo '<br><b>Authenticated: ' . $login . '</b>';
                ?>
                <?php
                if( (int)$test[test][connections] !== 0)
                     echo '<b>' . $test[test][connections] . ' Browser connections</b><br>';
                ?>
                </p>
                <br>
                <br>
                <table align="center">
                    <tr>
                        <th colspan="2">
                        <h2>Content breakdown by MIME type (First  View)</h2>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            <img style="width:500px; height:300px;" src="/breakdownPie.php?width=500&height=300&type=Requests&cached=0<?php echo "&test=$id&run=$run"; ?>"></img>
                        </td>
                        <td>
                            <img style="width:500px; height:300px;" src="/breakdownPie.php?width=500&height=300&type=Bytes&cached=0<?php echo "&test=$id&run=$run"; ?>"></img>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div id="tableRequestsFv_div" style="width: 100%;"></div>
                        </td>
                        <td>
                            <div id="tableBytesFv_div" style="width: 100%;"></div>
                        </td>
                    </tr>
                </table>
                <div style="text-align:center;">
                <h3 name="connection">Connection View (First View)</h3>
                <map name="connection_map">
                <?php
                    $mimeColors = requestColors($requestsFv);
                    $summary = array();
                    $connections = getConnections($requestsFv, $summary);
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
                        <td class="legend"><table><tr><td class="legend"><div class="bar" style="width:2px; background-color:#28BC00"></div></td><td class="legend">Start Render</td></tr></table></td>
                        <?php if((float)$test[$section][domElement] > 0.0) { ?>
                        <td class="legend"><table><tr><td class="legend"><div class="bar" style="width:2px; background-color:#F28300"></div></td><td class="legend">DOM Element</td></tr></table></td>
                        <?php } ?>
                        <td class="legend"><table><tr><td class="legend"><div class="bar" style="width:2px; background-color:#0000FF"></div></td><td class="legend">Document Complete</td></tr></table></td>
                    </tr>
                </table>
                <br>
                <img usemap="#connection_map" border="0" id="connectionView" src="<?php 
                    echo "/connectionView.php?test=$id&run=$run&mime=1&cached=0";?>">
                </div>

                <?php if( count($breakdownRv) ) { ?>
                <br><hr><br>
                <table align="center">
                    <tr>
                        <th colspan="2">
                        <h2>Content breakdown by MIME type (Repeat  View)</h2>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            <img style="width:500px; height:300px;" src="/breakdownPie.php?width=500&height=300&type=Requests&cached=1<?php echo "&test=$id&run=$run"; ?>"></img>
                        </td>
                        <td>
                            <img style="width:500px; height:300px;" src="/breakdownPie.php?width=500&height=300&type=Bytes&cached=1<?php echo "&test=$id&run=$run"; ?>"></img>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div id="tableRequestsRv_div" style="width: 100%;"></div>
                        </td>
                        <td>
                            <div id="tableBytesRv_div" style="width: 100%;"></div>
                        </td>
                    </tr>
                </table>
                <div style="text-align:center;">
                <h3 name="connection">Connection View (Repeat View)</h3>
                <map name="connection_map_rv">
                <?php
                    $mimeColors = requestColors($requestsRv);
                    $summary = array();
                    $connections = getConnections($requestsRv, $summary);
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
                        <td class="legend"><table><tr><td class="legend"><div class="bar" style="width:2px; background-color:#28BC00"></div></td><td class="legend">Start Render</td></tr></table></td>
                        <?php if((float)$test[$section][domElement] > 0.0) { ?>
                        <td class="legend"><table><tr><td class="legend"><div class="bar" style="width:2px; background-color:#F28300"></div></td><td class="legend">DOM Element</td></tr></table></td>
                        <?php } ?>
                        <td class="legend"><table><tr><td class="legend"><div class="bar" style="width:2px; background-color:#0000FF"></div></td><td class="legend">Document Complete</td></tr></table></td>
                    </tr>
                </table>
                <br>
                <img usemap="#connection_map_rv" border="0" id="connectionView" src="<?php 
                    echo "/connectionView.php?test=$id&run=$run&mime=1&cached=1";?>">
                </div>
                <?php } ?>
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