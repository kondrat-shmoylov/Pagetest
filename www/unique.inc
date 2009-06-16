<?php
/**
*   Generate a unique Id
*/
function uniqueId()
{
    $id = NULL;
    
    // try locking the context file
    $filename = "./settings/uniqueId.dat";
    $file = fopen( $filename, "a+b",  false);
    if( $file )
    {
        // TODO: add a timeout to the locking loop
        while( !flock($file, LOCK_EX) )
            sleep(1);

        fseek($file, 0, SEEK_SET);
        $num = (int)fread($file, 100);
        $num++;
        
        // go back to the beginning of the file and write out the new value
        fseek($file, 0, SEEK_SET);
        ftruncate($file, 0);
        fwrite($file, $num);
            
        fclose($file);
        
        // convert the number to a base-32 string for shorter text
        $id = '';
        $digits = "0123456789ABCDEFGHJKMNPQRSTVWXYZ";
        while($num > 0)
        {
            $digitValue = $num % 32;
            $num = (int)($num / 32);
            $id .= $digits[$digitValue];
        }
        $id = strrev($id);
    }
    
    return $id;
} 
?>
