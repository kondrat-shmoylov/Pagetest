<?php
include 'common.inc';
if( !$settings['maxruns'] )
    $settings['maxruns'] = 10;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <title><?php echo $settings['product'] . ' web page performance test';?></title>
        <meta http-equiv="charset" content="iso-8859-1">
        <meta name="keywords" content="Performance, Optimization, Pagetest, Page Design, performance site web, internet performance, website performance, web applications testing, web application performance, Internet Tools, Web Development, Open Source, http viewer, debugger, http sniffer, ssl, monitor, http header, http header viewer">
        <meta name="description" content="Speed up the performance of your web pages with an automated analysis">
        <meta name="author" content="Patrick Meenan">
        <script type="text/javascript" src="pagetest.js">
        /***********************************************
        * Tab Content script v2.2- © Dynamic Drive DHTML code library (www.dynamicdrive.com)
        * This notice MUST stay intact for legal use
        * Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
        ***********************************************/
        </script>
        <script type="text/javascript">
        function ValidateInput(form)
        {
            if( form.url.value == "" )
            {
                alert( "Please enter an URL to test." );
                form.url.focus();
                return false
            }

            var runs = form.runs.value;
            if( runs < 1 || runs > <?php echo $settings['maxruns']; ?> )
            {
                alert( "Please select a number of runs between 1 and <?php echo $settings['maxruns']; ?>." );
                form.runs.focus();
                return false
            }

            var date = new Date();
            date.setTime(date.getTime()+(730*24*60*60*1000));
            var expires = "; expires="+date.toGMTString();
            var options = 0;
            if( form.private.checked )
                options = 1;
            document.cookie = 'testOptions=' + options + expires + '; path=/';

            return true;
        }
        </script>
        <style type="text/css">
        <?php
            include 'pagestyle.css';
            include 'style.css';
        ?>
        </style>
    </head>
    <body>
        <div class="page">
            <?php
            $tab = 'New Test';
            include 'header.inc';
            ?>
            <div class="content">
               <form name="urlEntry" action="/runtest.php" method="POST" onsubmit="return ValidateInput(this)">
                    <div class="stepname">Step 1 - Enter Test URL</div>
                    <br>
                    <div class="stepcontents">URL: <input id="url" type="text" name="url" style="width:30em"><br></div>
                    <br>
                    <div class="stepname">Step 2 - Choose Test Location/Configuration</div><br>
                    <div class="stepcontents">
                        <table border="0">
                        <tr><th style="width:2em;"></th><th style="width:10em;">Location</th><th style="width:6em;">Down Speed</th>
                        <th style="width:6em;">Up Speed</th><th style="width:6em;">Connectivity</th><th style="width:5em;">Browser</th>
                        <?php
                        if( $settings['countTests'] )
                            echo '<th style="width:5em;">Pending Tests</th>';
                        ?>
                        </tr>
                        <?php
                        echo "\n";
                        $locations = parse_ini_file('./settings/locations.ini', true);
                        $count = 0;
                        foreach($locations as $location => $loc)
                        {
                            // ignore the global 'locations' section
                            if( strcasecmp($location, 'locations') )
                            {
                                if( strncasecmp($location, 'space', 5) )
                                {
                                    $count++;
                                    $checked = '';
                                    if( strlen($locations['locations']['default']) )
                                    {
                                        if( !strcasecmp($location, $locations['locations']['default']) )
                                            $checked = ' checked=checked';
                                    }
                                    elseif( $count == 1 )
                                        $checked = ' checked=checked';
                                    $bg = "";
                                    $count = 0;
                                    if( $settings['countTests'] )
                                    {
                                        if( $loc['remoteDir'] )
                                        {
                                            $count = countTests($loc['remoteDir']);
                                            if( $count > 20 )
                                                $bg = " style=\"background-color:red; font-weight:bold;\"";
                                            elseif( $count > 10 )
                                                $bg = " style=\"background-color:yellow; font-weight:bold;\"";
                                        }
                                    }
                                    echo "\t\t\t\t\t\t<tr><td><input id=\"location$location\" type=\"radio\" $checked name=\"location\" value=\"$location\"></td>";
                                    echo "<td>{$loc['location']}</td><td>{$loc['down']}</td><td>{$loc['up']}</td><td>{$loc['connectivity']}</td><td>{$loc['browser']}</td>";
                                    if( $settings['countTests'] )
                                    {
                                        if( $count )
                                            echo "<td$bg>$count</td>";
                                        else
                                            echo "<td>-</td>";
                                    }
                                    echo "</tr>\n";
                                }
                                else
                                {
                                    // insert a blank line
                                    echo "\t\t\t\t\t\t<tr><td><br></td></tr>\n";
                                }
                            }
                        }
                        ?>
                        </table>
                    </div>
                    <br>
                    <div class="stepname">Step 3 - Test Options</div>
                    <br>
                    <div class="stepcontents" id="optionsDiv">
                        <ul id="tabs" class="shadetabs">
                            <li><a href="#" rel="Basic" class="selected">Basic Settings</a></li>
                            <li><a href="#" rel="Advanced">Advanced Settings</a></li>
                            <li><a href="#" rel="Auth">Auth</a></li>
                            <li><a href="#" rel="Script">Script</a></li>
                            <li><a href="#" rel="Block">Block</a></li>
                        </ul>

                        <div style="border:1px solid gray; width:42em; height:11em; margin-bottom: 1em; padding: 2em 15px 15px 15px">
                            <div id="Basic" class="tabcontent">
                                Number of runs (1-<?php echo $settings['maxruns']; ?>): <input id="runs" size=3 maxlength=3 type="text" name="runs" value="1"><br>
                                <br>
                                <input id="viewBoth" type="radio" name="fvonly" checked=checked value="0">First View and Repeat View<br>
                                <input id="viewFirst" type="radio" name="fvonly" value="1">First View Only<br>
                                <br>
                                <input id="private" type="checkbox" name="private"<?php if( (int)$_COOKIE["testOptions"] == 1 ) echo " checked=checked"; ?>>Keep test results private (don't log them in the test history and use a non-guessable test ID)<br>
                            </div>

                            <div id="Advanced" class="tabcontent">
                                <input id="docComplete" type="checkbox" name="web10">Stop measurement at Document Complete (usually measures until activity stops)<br><br>
                                <input id="connections" size=2 maxlength=2 type="text" name="connections" value=""> Parallel browser connections (leave blank for browser default)<br><br>
                                DOM Element: <input id="DOMElement" style="width:70%;" type="text" name="domelement"><br>
                                Waits for and records when the indicated DOM element becomes available on the page.  The DOM element
                                is identified in <b>attribute=value</b> format where "attribute" is the attribute to match on (id, className, name, innerText, etc.)
                                and "value" is the value of that attribute (case sensitive).  For example, on SNS pages <b>name=loginId</b>
                                would be the DOM element for the Screen Name entry field.
                            </div>

                            <div id="Auth" class="tabcontent">
                                <?php if($settings['enableSNS']) { ?>
                                <input id="authSNS" type="radio" name="authType" checked=checked value="1">AOL SNS&nbsp;
                                <input id="authBasic" type="radio" name="authType" value="0">HTTP Basic Auth
                                <?php } elseif($settings['enableYandexAuth']) { ?>
                                <input id="authYandexAuth" type="radio" name="authType" checked=checked value="2">Yandex Auth&nbsp;
                                <input id="authBasic" type="radio" name="authType" value="0">HTTP Basic Auth
                                <?php } else { ?>
                                HTTP Basic Authentication
                                <?php } ?><br><br>
                                Login:<br/><input id="login" type="text" name="login"><br>
                                Password:<br/><input id="password" type="password" autocomplete="off" name="password"><br><br>
                                <span style="color:red; font-weight:bold;">PLEASE USE A TEST ACCOUNT!</span>&nbsp; We also strongly recommend making the test request private as your credentials may be available to anyone viewing the results.
                            </div>


                            <div id="Script" class="tabcontent">
                                Enter Script (Go <a href="http://pagetest.wiki.sourceforge.net/Hosted+Scripting">here</a> for information on scripting):<br>
                                <textarea id="script" rows="8" cols="80" name="script"></textarea>
                            </div>

                            <div id="Block" class="tabcontent">
                                Block requests containing (space separated list):<br>
                                <textarea id="block" rows="8" cols="80" name="block"></textarea>
                            </div>
                        </div>
                        <script type="text/javascript">
                        var tabs=new ddtabcontent("tabs")
                        tabs.setselectedClassTarget("link") //"link" or "linkparent"
                        tabs.init()
                        </script>
                    </div>
                    <br>
                    <div class="stepname">Step 4 - Submit Test</div>
                        <br>
                        <div class="greytextbutton"><input class="artzBtn def" id="Submit" type="submit" value="Submit"></div>
                        <br>
                </form>
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

<?php
/**
* Count the number of tests running in a given location
*
* @param mixed $dir
*/
function countTests($dir)
{
    $count = 0;

    $files = scandir($dir);
    foreach( $files as $file )
    {
        $parts = pathinfo($file);
        if( !strcasecmp( $parts['extension'], 'url') )
            $count++;
    }

    return $count;
}
?>