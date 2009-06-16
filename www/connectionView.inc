<?php
/**
* Translate the object data into a series of requests per connection
* 
* @param mixed $requests
*/
function getConnections(&$requests, &$summary)
{
    // initialize some defaults
    $connections = array();
    $summary['end'] = 0;

    foreach($requests as $request)
    {
        $socket = $request['socket'];
        if( $socket !== NULL )
        {
            // see if there is already an entry for this socket
            $index = -1;
            for( $i = 0; $i < count($connections); $i++ )
            {
                if( $connections[$i]['socket'] == (int)$socket )
                {
                    $index = $i;
                    break;
                }
            }
            
            if( $index == -1 )
            {
                // see if there are any matching host entries
                $insertAfter = -1;
                for( $i = count($connections) - 1; $i >= 0; $i-- )
                {
                    if( !strcmp($request['host'], $connections[$i]['host'] ) )
                    {
                        $insertAfter = $i;
                        break;
                    }
                }
                
                $index = count($connections);
                if( $insertAfter >= 0 && $insertAfter < count($connections) - 1 )
                {
                    $index = $insertAfter + 1;
                    for( $i = count($connections); $i > $index; $i-- )
                        $connections[$i] = $connections[$i - 1];
                }

                $connections[$index] = array();
                $connections[$index]['socket'] = (int)$socket;
                $connections[$index]['host'] = $request['host'];
                $connections[$index]['ip'] = $request['ip'];
                $connections[$index]['requests'] = array();
            }
                
            $connections[$index]['requests'][] = $request;
            
            if( $request['offset'] + $request['loadTime'] > $summary['end'] && strstr($request['url'], 'favicon.ico') === FALSE )
                $summary['end'] = $request['offset'] + $request['loadTime'];
        }
    }
    
    return $connections;
}

/**
* Draw the connection view image
* 
* @param mixed $connections
* @return resource
*/
function drawImage(&$connections, &$summary, $run, $cached, &$test, $url, $mime, $mimeColors, $imageMap)
{
    $width = 1000;
    $font = 2;
    $rowHeight = imagefontheight($font) + 4;
    $fontWidth = imagefontwidth($font);
    $height = 2 + (2 * $rowHeight) + ($rowHeight * count($connections));
    $totalHeight = $height;
    if( $mime )
        $totalHeight += (count($mimeColors) + 1) * $rowHeight;

    $left = 250;
    $w = $width - 2 - $left;
    $range = (double)$summary['end'];

    // draw a bunch of stuff that is only needed if we're ACTUALLY drawing the chart        
    if( $imageMap )
    {
        $map = array();
        $entry['url'] = $url;
        $entry['left'] = 4;
        $entry['right'] = $left;
        $entry['top'] = $rowHeight;;
        $entry['bottom'] = (2 * $rowHeight) - 1;
        
        $map[] = $entry;
    }
    else
    {
        $im = imagecreatetruecolor($width, $totalHeight);
    
        // allocate the ccolors we will need
        $black = imagecolorallocate($im, 0, 0, 0);
        $white = imagecolorallocate($im, 255, 255, 255);
        $grey = imagecolorallocate($im, 240, 240, 240);
        $dkGrey = imagecolorallocate($im, 192, 192, 192);
        
        $colDNS = array(0,123,132);
        $colConnect = array(255, 123, 0);
        $colSSL = array(207, 37, 223);
        $colRequest = array(0, 255, 0);
        $colContent = array(0,123,255);

        // give it a white background and black border
        imagefilledrectangle($im, 0, 0, $width, $totalHeight, $white);
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $black);
        
        // do the alternating row coloring (after a 2 row header)
        $y = 1 + ($rowHeight * 2);
        while( $y < $height - 1 )
        {
            imagefilledrectangle($im, 1, $y, $width - 2, $y + $rowHeight - 1, $grey);
            $y += $rowHeight * 2;
        }
        
        // draw the separator
        imageline($im, $left, 0, $left, $height - 1, $black);
        
        // draw the time scale
        $scale = array(1,2,5);
        if( $range > 0 )
        {
            // calculate the appropriate intervals
            $maxIndex = count($scale) - 1;
            $index = $maxIndex;
            $mult = 1000.0;
            $inc = $mult * $scale[$index];
            while( $range / $inc < 20 )
            {
                $index--;
                if( $index < 0 )
                {
                    $index = $maxIndex;
                    $mult /= 10.0;
                }
                $inc = $mult * $scale[$index];
            }
            while( $range / $inc > 20 )
            {
                $index++;
                if( $index > $maxIndex )
                {
                    $index = 0;
                    $mult *= 10.0;
                }
                $inc = $mult * $scale[$index];
            }
            
            // draw the markers
            $pos = $inc;
            $tmInc = $mult * $scale[$index];
            $tm = $tmInc;
            while( $pos < $range )
            {
                $x = $left + (int)(($pos / $range) * $w);
                imageline($im, $x, 1 + $rowHeight, $x, $height - 2, $dkGrey);

                // draw the text label
                $places = 2;
                if( $tmInc >= 1000 )
                    $places = 0;
                else if( $tmInc >= 100 )
                    $places = 1;
                $val = number_format($tm / 1000.0, $places);
                $x -= (int)((double)$fontWidth * ((double)strlen($val) / 2.0));
                imagestring($im, $font, $x, 3, $val, $black);

                $tm += $tmInc;
                $pos += $inc;
            }
        }
        
        // draw the start render and doc complete lines
        $section = 'first ' . $run;
        if($cached == 1) 
            $section = 'repeat ' . $run;
        $startRender = (int)((float)$test[$section][startRender] * 1000.0);
        $domElement = (int)((float)$test[$section][domElement] * 1000.0);
        $docComplete = (int)((float)$test[$section][docComplete] * 1000.0);
        $colStartRender = imagecolorallocate($im, 40, 188, 0);
        $colDomElement  = imagecolorallocate($im, 242, 131, 0);
        $colDocComplete = imagecolorallocate($im, 0, 0, 255);
        
        if( $startRender > 0 && $startRender <= $range )
        {
            $x = $left + (int)((double)$w * ((double)$startRender / $range));
            imageline($im, $x, 1, $x, $height - 1, $colStartRender);
            imageline($im, $x + 1, 1, $x + 1, $height - 2, $colStartRender);
        }

        if( $domElement > 0 && $domElement <= $range )
        {
            $x = $left + (int)((double)$w * ((double)$domElement / $range));
            imageline($im, $x, 1, $x, $height - 1, $colDomElement);
            imageline($im, $x + 1, 1, $x + 1, $height - 2, $colDomElement);
        }
        
        if( $docComplete > 0 )
        {
            if( $docComplete >= $range )
                $docComplete = $range - 1;
            $x = $left + (int)((double)$w * ((double)$docComplete / $range));
            imageline($im, $x, 1, $x, $height - 1, $colDocComplete);
            imageline($im, $x + 1, 1, $x + 1, $height - 2, $colDocComplete);
        }
        
        // put the url label in
        $x = 4;
        $y = 2 + $rowHeight;
        $label = $url;
        if( $x + (strlen($label) * $fontWidth) > $left )
        {
            $chars = ($left - $x) / $fontWidth;
            $label = substr($label, 0, $chars - 4) . '...';
        }
        imagestring($im, $font, $x, $y,  $label, $black);
    }

    // draw the connection information
    $i = 0;
    $y = 2 + ($rowHeight * 2);
    foreach($connections as $connection)
    {
        // draw the label
        $i++;
        
        if( !$imageMap )
        {
            $label = $i . ': ' . $connection['host'];
            
            // make sure the label fits
            if( $x + (strlen($label) * $fontWidth) > $left )
            {
                $chars = ($left - $x) / $fontWidth;
                $label = substr($label, 0, $chars - 4) . '...';
            }
            imagestring($im, $font, $x, $y,  $label, $black);
        }
        
        // draw each request
        $lastEnd = 0;
        foreach( $connection['requests'] as $request )
        {
            $start = $request['offset'];
            $end = $start + $request['loadTime'];
            if( $request['dnsTime'] > 0 )
                $end += $request['dnsTime'];
            if( $request['socketTime'] > 0 )
                $end += $request['socketTime'];
            if( $request['secure'] && $request['sslTime'] > 0 )
                $end += $request['sslTime'];
           
            $xStart = $left + (int)((double)$w * ((double)$start / $range));
            if( $xStart <= $lastEnd )
                $xStart = $lastEnd + 1;
            $xEnd = $left + (int)((double)$w * ((double)$end / $range));
            $xFullEnd = $xEnd;

            if( $imageMap )
            {
                $entry = array();
                $scheme = 'http://';
                if( $request['secure'] )
                    $scheme = 'https://';
                $entry['request'] = $request['index'];
                $entry['url'] = $scheme . $request['host'] . $request['url'];
                $entry['type'] = $request['mime'];
                $entry['left'] = $xStart;
                $entry['right'] = $xEnd;
                $entry['top'] = $y;
                $entry['bottom'] = $y + $rowHeight - 1;
                
                $map[] = $entry;
            }
            else
            {
                // see if we are drawing in request mode or content type mode
                if( $mime )
                {
                    $col = imagecolorallocate($im, $request['color'][0], $request['color'][1], $request['color'][2] );
                    imagefilledrectangle($im, $xStart, $y + 1, $xEnd, $y + $rowHeight - 4, $white);
                    imagerectangle($im, $xStart, $y + 1, $xEnd, $y + $rowHeight - 4, $col);
                    imagerectangle($im, $xStart + 1, $y + 2, $xEnd - 1, $y + $rowHeight - 5, $col);
                    if( $request['dnsTime'] > 0 )
                        $start += $request['dnsTime'];
                    if( $request['socketTime'] > 0 )
                        $start += $request['socketTime'];
                    if( $request['secure'] && $request['sslTime'] > 0 )
                        $start += $request['sslTime'];
                    if( $request['ttfb'] > 0 )
                        $start += $request['ttfb'];
                    $xStart = $left + (int)((double)$w * ((double)$start / $range));
                    imagefilledrectangle($im, $xStart, $y + 1, $xEnd, $y + $rowHeight - 4, $col);
                    imageline($im, $xEnd, $y + 1, $xEnd, $y + $rowHeight - 4, $black );
                }
                else
                {
                    // background fill the content download time
                    drawBar($im, $xStart, $y + 1, $xEnd, $y + $rowHeight - 4, $colContent);
                    
                    $end = $start;
                    // DNS
                    if( $request['dnsTime'] > 0 )
                    {
                        $end += $request['dnsTime'];
                        $xStart = $left + (int)((double)$w * ((double)$start / $range));
                        if( $xStart <= $lastEnd )
                            $xStart = $lastEnd + 1;
                        $xEnd = $left + (int)((double)$w * ((double)$end / $range));
                        drawBar($im, $xStart, $y + 1, $xEnd, $y + $rowHeight - 4, $colDNS);
                        $start = $end;
                    }
                    
                    // socket connect
                    if( $request['socketTime'] > 0 )
                    {
                        $end += $request['socketTime'];
                        $xStart = $left + (int)((double)$w * ((double)$start / $range));
                        if( $xStart <= $lastEnd )
                            $xStart = $lastEnd + 1;
                        $xEnd = $left + (int)((double)$w * ((double)$end / $range));
                        drawBar($im, $xStart, $y + 1, $xEnd, $y + $rowHeight - 4, $colConnect);
                        $start = $end;
                    }

                    // SSL
                    if( $request['secure'] && $request['sslTime'] > 0 )
                    {
                        $end += $request['sslTime'];
                        $xStart = $left + (int)((double)$w * ((double)$start / $range));
                        if( $xStart <= $lastEnd )
                            $xStart = $lastEnd + 1;
                        $xEnd = $left + (int)((double)$w * ((double)$end / $range));
                        drawBar($im, $xStart, $y + 1, $xEnd, $y + $rowHeight - 4, $colSSL);
                        $start = $end;
                    }

                    // TTFB
                    if( $request['ttfb'] > 0 )
                    {
                        $end += $request['ttfb'];
                        $xStart = $left + (int)((double)$w * ((double)$start / $range));
                        if( $xStart <= $lastEnd )
                            $xStart = $lastEnd + 1;
                        $xEnd = $left + (int)((double)$w * ((double)$end / $range));
                        if( $xEnd >= $xFullEnd )
                            $xEnd = $xFullEnd - 1;  // allow for a sliver of content download in between stacked requests
                        drawBar($im, $xStart, $y + 1, $xEnd, $y + $rowHeight - 4, $colRequest);
                        $start = $end;
                    }
                }
                $lastEnd = $xFullEnd;
            }
        }
        
        $y += $rowHeight;
    }
    
    // draw the legend
    if( $mime && !$imageMap )
    {
        $left = 100;
        $y = $height + $rowHeight;
        foreach( $mimeColors as $mimeType => $color )
        {
            $col = imagecolorallocate($im, $color[0], $color[1], $color[2]);
            imagefilledrectangle($im, 4, $y + 1, 94, $y + $rowHeight - 2, $col);
            imagefilledrectangle($im, 6, $y + 3, 44, $y + $rowHeight - 4, $white);
            imagestring($im, $font, $left, $y + 1, $mimeType, $black );
            $y += $rowHeight;
        }
    }
    
    if( $imageMap )
        return $map;
    else
        return $im;
}

/**
* Draw a 3D bar
* 
* @param mixed $im  Image
* @param mixed $x1
* @param mixed $y1
* @param mixed $x2
* @param mixed $y2
* @param array $color   RGB color
*/
function drawBar(&$im, $x1, $y1, $x2, $y2, &$color)
{
    $mid = (int)(((double)($y2 - $y1) * 0.25) + $y1);
    
    // build the highlight color
    $highlight = array();
    for( $i = 0; $i < 3; $i++ )
        $highlight[$i] = (int)((double)(255 - $color[$i]) * 0.3 + $color[$i]);

    // build the shadow color
    $shadow = array();
    for( $i = 0; $i < 3; $i++ )
        $shadow[$i] = (int)((double)$color[$i] * 0.8);
    
    drawGradient($im, $x1, $y1, $x2, $mid, $shadow, $highlight);
    drawGradient($im, $x1, $mid, $x2, $y2, $highlight, $shadow);
}

/**
* Draw a Gradient bar (1/2 of a 3D bar)
* 
* @param mixed $im
* @param mixed $x1
* @param mixed $y1
* @param mixed $x2
* @param mixed $y2
* @param mixed $color1
* @param mixed $color2
*/
function drawGradient(&$im, $x1, $y1, $x2, $y2, &$color1, &$color2)
{
    // draw the top and bottom lines
    $col = imagecolorallocate($im, $color1[0], $color1[1], $color1[2]);
    imageline($im, $x1, $y1, $x2, $y1, $col);
    $col = imagecolorallocate($im, $color2[0], $color2[1], $color2[2]);
    imageline($im, $x1, $y2, $x2, $y2, $col);
    
    // fill in any intermediate lines
    $colSteps = array();
    $steps = $y2 - $y1 - 1;
    for( $i = 0; $i < 3; $i++ )
        $colSteps[$i] = (double)($color2[$i] - $color1[$i]) / (double)$steps;
        
    for( $i = 1; $i <= $steps; $i++ )
    {
        $r = (int)((double)$color1[0] + $colSteps[0] * (double)$i);
        $g = (int)((double)$color1[1] + $colSteps[1] * (double)$i);
        $b = (int)((double)$color1[2] + $colSteps[2] * (double)$i);
        $col = imagecolorallocate($im, $r, $g, $b);
        $y = $y1 + $i;
        imageline($im, $x1, $y, $x2, $y, $col);
    }
}
?>