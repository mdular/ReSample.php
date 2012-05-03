<?php 

require_once 'ReSample.php';
$newImage = new ReSample();
$newImage->setImage('YOUR_IMAGE_FILE', 'thumb'); // NOTE: set image path

$newImage->letterBox(200, 100);

// get the raw image
$image = $newImage->getRawImage();

// output to the browser
header('Content-Type: image/png');
imagepng($image['image']);