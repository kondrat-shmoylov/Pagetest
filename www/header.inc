<div class="header">
    <a href="/"><img class= "logo" src="/images/logo.png"></a>
    <?php include('./ads/header.inc'); ?>

    <div id="htabs">
        <ul>
        <?php
            if( $id )
                $resultUrl = "/result/$id/";
                
            $tabs = array( 'Home' => '/',
                            'New Test' => '/test',
                            'Test Result' => $resultUrl,
                            'Test History' => '/testlog/30'
                            );

            if( is_dir('forums') )
                $tabs['Forums'] = '/forums/';
                            
            if( $settings['contact'] )
                $tabs['Contact'] = '/contact';
            
            foreach( $tabs as $tabName => $tabUrl )
            {
                // make sure we have a test result to navigate to
                if( strlen($tabUrl) )
                {
                    // highlight the current tab
                    $current = '';
                    if( !strcasecmp($tabName, $tab) )
                        $current = ' id="current"';
                    echo "<li$current><a href=\"$tabUrl\"><span>$tabName</span></a></li>";
                }
            }
        ?>
        </ul>
    </div>
    <div class="header" style="float:none; clear:both; height:0;"></div>
    <?php
    // put up the sub-menu if we are looking at test results
    if( !strcasecmp('Test Result', $tab) )
    {
        echo '<div id="navcontainer">';
        echo '<ul id="navlist">';
        
        if( !$run )
        {
            if( isset($fv) && isset($fv['avgRun']) )
                $run = $fv['avgRun'];
            else
                $run = 1;
        }
        
        $cTxt = '';
        if( $cached )
            $cTxt = 'cached/';

        $tabs = array( 'Summary' => "/result/$id/",
                    'Detailed Results' => "/result/$id/$run/details/$cTxt",
                    'Performance Review' => "/result/$id/$run/performance_optimization/$cTxt",
                    'Optimization Report' => "/result/$id/$run/optimization_report/$cTxt",
                    'Content Breakdown' => "/result/$id/$run/breakdown/$cTxt",
                    'Domain Breakdown' => "/result/$id/$run/domains/$cTxt",
                    'Screen Shot' => "/result/$id/$run/screen_shot/$cTxt"
                    );

        foreach( $tabs as $tabName => $tabUrl )
        {
            // make sure we have a test result to navigate to
            if( strlen($tabUrl) )
            {
                // highlight the current tab
                $current = '';
                if( !strcasecmp($tabName, $subtab) )
                    $current = ' id="active"';
                echo "<li$current><a href=\"$tabUrl\">$tabName</a></li>";
            }
        }

        echo '</ul>';
        echo '</div>';
    }
    ?>
</div>