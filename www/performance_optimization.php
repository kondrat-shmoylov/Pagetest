<?php 
include 'common.inc';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <title>Web page performance optimization check results</title>
        <meta http-equiv="charset" content="iso-8859-1">
        <meta name="keywords" content="Performance, Optimization, Pagetest, Page Design, CDN, Content Distribution Network">
        <meta name="description" content="Speed up the performance of your web pages with an automated analysis">
        <meta name="author" content="Patrick Meenan">

        <style type="text/css">
            <?php 
                include 'pagestyle.css'; 
            ?>
            td.nowrap {white-space:nowrap;}
            th.nowrap {white-space:nowrap;}
            tr.blank {height:2ex;}
			.indented1 {padding-left: 40pt;}
			.indented2 {padding-left: 80pt;}
        </style>
    </head>
    <body>
        <div class="page">
            <?php
            $tab = 'Test Result';
            $subtab = 'Performance Review';
            include 'header.inc';
            ?>
            <div class="content">
				<p>Web page performance optimization check results for <b><a rel="nofollow" href=<?php echo '"' . $url . '"';?>><?php echo $url;?></a></b><br>
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
                <div style="text-align:center;">
                    <img alt="If the optimization results don't display, please try refreshing the page" id="image" src="<?php 
                        $cached='';
                        if((int)$_GET["cached"] == 1)
                            $cached='_Cached';
                        echo substr($testPath, 1) . '/' . $run . $cached . '_optimization.png"';?>>
                    <br>
                </div>

		        <br>
                <?php include('./ads/optimization_middle.inc'); ?>
		        <br>

                <h2>Details:</h2>
                <?php
                    require 'optimization.inc';
                    dumpOptimizationReport($testPath, $run, $cached);
                    echo '<p></p><br>';
                    include('./ads/optimization_bottom.inc');
                    echo '<br>';
                    dumpOptimizationGlossary($settings);
                ?>
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
