<?php
// load the object data file
function getRequests($id, $testPath, $run, $cached, &$secure, &$haveLocations, $checkLocations)
{
	$requests = array();
    $start = 100000;
	
	$geoip;
    if( $checkLocations == true )
    {
	    try
	    {
		    include('./Net/GeoIP.php');
		    $geoip = Net_GeoIP::getInstance('./Net/GeoLiteCity.dat', Net_GeoIP::MEMORY_CACHE);
	    }catch(Exception $e) { }
    }
	
	// load the object file into an array of lines
	$cachedText='';
	if((int)$cached == 1)
		$cachedText ='_Cached';
	$fileName = $testPath . '/' . $run . $cachedText . '_IEWTR.txt';
	$lines = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if( $lines)
	{
		$dnsTime;
		$socketTime;
		
		// loop through each line in the file
		foreach($lines as $linenum => $line) 
		{
			// skip the header line
			if( $linenum > 0)
			{
				$event = array();
				
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
							case 4: $event['ipAddr'] = $token; break;
							case 6: $event['host'] = $token; break;
							case 7: $event['url'] = $token; break;
							case 8: $event['responseCode'] = $token; break;
							case 9: $event['loadTime'] = (int)$token; break;
							case 10: $event['ttfb'] = (int)$token; break;
							case 11: $event['offset'] = (int)$token; break;
							case 12: $event['bytesOut'] = (int)$token; break;
							case 13: $event['bytesIn'] = (int)$token; break;
							case 14: $event['objectSize'] = (int)$token; break;
							case 17: $event['expires'] = $token; break;
							case 18: $event['cacheControl'] = $token; break;
							case 19: $event['contentType'] = $token; break;
							case 20: $event['contentEncoding'] = $token; break;
							case 21: $event['type'] = (int)$token; break;
							case 22: $event['socket'] = (int)$token; break;
							case 47: $event['secure'] = (int)$token; break;
							case 48: $event['dnsTime'] = (int)$token; break;
							case 49: $event['socketTime'] = (int)$token; break;
							case 50: $event['sslTime'] = (int)$token; break;
						}
					}
					
					// on to the next token
					$token = strtok("\t");
				}
				
				// Track the individual requests only (roll-up the connections and DNS lookups)
				if( (int)$event[type] == 3 )
				{
					if( $dnsTime && !$event['dnsTime'] )
						$event['dnsTime'] = $dnsTime;
                    if( $event['dnsTime'] > 0 )
                        $event['offset'] -= $dnsTime;
					
					if( $socketTime && !$event['socketTime'] )
						$event['socketTime'] = $socketTime;
                    if( $event['socketTime'] > 0 )
						$event['offset'] -= $socketTime;
                    
                    if( $event['offset'] < $start )
                        $start = $event['offset'];
					
					unset($dnsTime);
					unset($socketTime);
					
					if( $event['secure'] && $event['secure'] > 0)
						$secure = true;
					
					// do the IP lookup
					if( $geoip && $event['ipAddr'])
					{
						try
						{
							$location = $geoip->lookupLocation($event['ipAddr']);
							if( $location)
							{
								$haveLocations = true;
								$event['country'] = $location->countryName;
								$event['countryCode'] = $location->countryCode3;
								$event['city'] = $location->city;
								$event['region'] = $location->region;
								$event['lat'] = $location->latitude;
								$event['long'] = $location->longitude;

								$loc = '-';
								if( $event['city'])
								{
									if ( $event['region'] && $event['countryCode'] == 'USA' )
										$loc = $event['city'] . ', ' . $event['region'];
									elseif( $event['country'])
										$loc = $event['city'] . ', ' . $event['country'];
								}
								elseif ( $event['region'] )
								{
									if( $event['country'])
										$loc = $event['region'] . ', ' . $event['country'];
								}
								elseif ( $event['country'] )
									$loc = $event['country'];
								$event['location'] = $loc;
							}
						}catch(Exception $e) {}
					}
					
					// add this event to the requests list
                    $event['index'] = count($requests);
					$requests[] = $event;
				}
				else if( (int)$event[type] == 1 )
					$dnsTime = $event['loadTime'];
				else if( (int)$event[type] == 2 )
					$socketTime = $event['loadTime'];
			}
		}
	}
    
    // go through all of the requests and shift the offset to 0
    foreach($requests as &$request)
        $request['offset'] -= $start;
	
	return $requests;
}
?>
