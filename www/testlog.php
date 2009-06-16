<?php 
include 'common.inc';

$uid = NULL;
$user = NULL;
$admin = false;
// some myBB integration to get the requesting user
if( is_dir('./forums') && isset($_COOKIE['mybbuser']) )
{
    $dir = getcwd();
    try
    {
        define("IN_MYBB",1);
        chdir('forums'); // path to MyBB
        include './global.php';

        $uid = $mybb->user['uid'];
        $user = $mybb->user['username'];
        if( $mybb->usergroup['cancp'] )
            $admin = true;
    }
    catch(Exception $e)
    {
    }
    chdir($dir);
}

// shared initializiation/loading code
error_reporting(0);
$days = (int)$_GET["days"];
$filter = $_GET["filter"];
$filterstr = NULL;
if( $filter && strlen($filter))
    $filterstr = strtolower($filter);
$includeip = false;
if( $admin || (int)$_GET["ip"] == 1 )
    $includeip = true;
$includePrivate = false;
if( $admin || (int)$_GET["private"] == 1 )
    $includePrivate = true;
date_default_timezone_set('GMT');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <title><?php echo $days . ' day test log';?></title>
        <meta http-equiv="charset" content="iso-8859-1">
        <meta name="keywords" content="Performance, Optimization, Pagetest, Page Design, CDN, Content Distribution Network">
        <meta name="description" content="Speed up the performance of your web pages with an automated analysis">
        <meta name="author" content="Patrick Meenan">
		<style type="text/css">
        <?php 
            include 'pagestyle.css'; 
        ?>
			h4 {text-align: center;}
			table {text-align:left;}
			th {white-space:nowrap; text-decoration:underline;}
			td.date {white-space:nowrap;}
			td.location {white-space:nowrap;}
			td.url {white-space:nowrap;}
            td.ip {white-space:nowrap;}
            td.uid {white-space:nowrap;}
		</style>
    </head>
    <body>
        <div class="page">
            <?php
            $tab = 'Test History';
            include 'header.inc';
            ?>
            <div class="content">
                <form style="text-align:center;" name="filterLog" method="get" action="/testlog.php">
                    View <select name="days" size="1">
                            <option value="1" <?php if ($days == 1) echo "selected"; ?>>1 Day</option>
                            <option value="7" <?php if ($days == 7) echo "selected"; ?>>7 Days</option>
                            <option value="30" <?php if ($days == 30) echo "selected"; ?>>30 Days</option>
                            <option value="182" <?php if ($days == 182) echo "selected"; ?>>6 Months</option>
                            <option value="365" <?php if ($days == 365) echo "selected"; ?>>1 Year</option>
                         </select> test log for URLs containing <input id="filter" name="filter" type="text" style="width:30em" value="<?php echo $filter ?>"> <input id="Submit" type="submit" value="Go">
                </form>
                <h4>Clicking on an url will bring you to the results for that test</h4>
		        <table border="0" cellpadding="5px" cellspacing="0">
			        <tr>
				        <th>Date/Time (EST)</th>
				        <th>From</th>
                        <?php
                        if( $includeip )
                            echo '<th>Requested By</th>';
                        if( $admin )
                            echo '<th>User</th>';
                        ?>
				        <th>Url</th>
			        </tr>
			        <?php
			        // loop through the number of days we are supposed to display
                    $rowCount = 0;
			        $targetDate = new DateTime("now", new DateTimeZone('GMT'));
			        for($offset = 0; $offset <= $days; $offset++)
			        {
				        // figure out the name of the log file
				        $fileName = './logs/' . $targetDate->format("Ymd") . '.log';
				        
				        // load the log file into an array of lines
				        $lines = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				        if( $lines)
				        {
					        // walk through them backwards
					        $records = array_reverse($lines);
					        foreach($records as $line)
					        {
						        $date = NULL;
						        $location = NULL;
						        $url = NULL;
						        $guid = NULL;
                                $ip = NULL;
                                $testUID = NULL;
                                $testUser = NULL;
                                $private = false;
						        
						        // tokenize the line
						        $parseLine = str_replace("\t", "\t ", $line);
						        $token = strtok($parseLine, "\t");
						        $column = 0;
						        while($token)
						        {
							        $column++;
							        $token = trim($token);
							        if( strlen($token) > 0)
							        {
								        switch($column)
								        {
									        case 1: $date = $token; break;
                                            case 2: $ip = $token; break;
									        case 5: $guid = $token; break;
									        case 6: $url = htmlspecialchars($token); break;
									        case 7: $location = $token; break;
                                            case 8: $private = ($token == '1' ); break;
                                            case 9: $testUID = $token; break;
                                            case 10: $testUser = $token; break;
								        }
							        }
							        
							        // on to the next token
							        $token = strtok("\t");
						        }
						        
						        if( $date && $location && $url && $guid)
						        {
                                    $ok = true;
                                    if($filterstr)
                                    {
                                        $urlLower = strtolower($url);
                                        if( !strstr($urlLower, $filterstr) )
                                            $ok = false;
                                    }
                                    
                                    // see if it is supposed to be filtered out
                                    if( $private && !$includePrivate )
                                        $ok = false;
                                    
                                    if( $ok )
                                    {
                                        $rowCount++;
							            $newDate = new DateTime($date, new DateTimeZone('GMT'));
							            $newDate->setTimezone(new DateTimeZone('EST'));
							            
							            echo '<tr><td class="date">';
                                        if( $private )
                                            echo '<b>';
                                        echo $newDate->format("Y/m/d H:i:s");
                                        if( $private )
                                            echo '</b>';
                                        echo '</td>';
							            echo '<td class="location">' . $location . '</td>';
                                        if($includeip)
                                            echo '<td class="ip">' . $ip . '</td>';
                                        
                                        if( $admin )
                                        {
                                            if( isset($testUID) )
                                                echo '<td class="uid">' . "$testUser ($testUID)" . '</td>';
                                            else
                                                echo '<td class="uid"></td>';
                                        }
                                            
                                        
                                        if( strncasecmp($guid, 'http:', 5) && strncasecmp($guid, 'https:', 6) )
							                echo '<td class="url"><a href="/result/' . $guid . '/">' . $url . '</a></td></tr>';
                                        else
                                            echo '<td class="url"><a href="' . $guid . '">' . $url . '</a></td></tr>';
                                        
                                        // split the tables every 30 rows so the browser doesn't wait for ALL the results
                                        if( $rowCount % 30 == 0 )
                                        {
                                            echo '</table><table border="0" cellpadding="5px" cellspacing="0">';
                                            flush();
                                            ob_flush();
                                        }
                                    }
						        }
					        }
				        }
				        
				        // on to the previous day
				        $targetDate->modify('-1 day');
			        }
			        ?>
		        </table>
            </div>
        </div>
    </body>
</html>
