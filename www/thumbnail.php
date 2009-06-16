<?php
include 'common.inc';
$file = $_GET['file'];

// make sure nobody is trying to use us to pull down external images from somewhere else
if( strpos($file, ':') === FALSE &&
    strpos($file, '/') === FALSE &&
    strpos($file, '\\') === FALSE )
{
    $fileParts = explode('.', $file);
    $thumbFile = "$testPath/" . $fileParts[0] . '_thumb.' . $fileParts[1];
    $parts = pathinfo($file);
    $type = $parts['extension'];
    if( !stat($thumbFile) )
    {
        $newWidth = 250;
        $img = null;
        if( !strcasecmp( $type, 'jpg') )
            $img = imagecreatefromjpeg("$testPath/$file");
        elseif( !strcasecmp( $type, 'gif') )
            $img = imagecreatefromgif("$testPath/$file");
        else
            $img = imagecreatefrompng("$testPath/$file");

        if( $img )
        {
            // figure out what the height needs to be
            $width = imagesx($img);
            $height = imagesy($img);
            $scale = $newWidth / $width;
            $newHeight = (int)($height * $scale);
            
            # Create a new temporary image
            $tmp = imagecreatetruecolor($newWidth, $newHeight);

            # Copy and resize old image into new image
            imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($img);
            $img = $tmp;    
        }

        if( !$img )
        {
            // create a blank error image
            $img = imagecreatetruecolor($newWidth, $newWidth);
            $black = imagecolorallocate($img, 0, 0, 0);
            imagefilledrectangle($img, 0, 0, $newWidth, $newWidth, $black);
        }

        // output the image
        if( !strcasecmp( $type, 'jpg') )
            imagejpeg($img, $thumbFile);
        else
            imagepng($img, $thumbFile);
    }
    
    // output the thumbnail file
    if( !strcasecmp( $type, 'jpg') )
        header ('Content-type: image/jpeg');
    else
        header ('Content-type: image/png');
    echo file_get_contents($thumbFile);
}
?>
