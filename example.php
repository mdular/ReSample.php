<?php 

// decomment if you need to debug
//ini_set('display_errors', 'On');

if(!empty($_FILES['imagefile']) && !empty($_POST['submit'])){
    
  $tmpFile = $_FILES['imagefile']['tmp_name'];
  $uploadedImage = 'images/' . preg_replace("/[^a-z0-9-_.]/", "", strtolower($_FILES['imagefile']['name']));
    
  if(move_uploaded_file($tmpFile, $uploadedImage)){
    
    /**
     * START of example
     * 
     * the code around is just a quick & dirty file upload. make sure the script process can write to /images
     */
     
    // 1. make sure the class is loaded, then create an instance
    require_once 'Resamplr.php';
    $newImage = new Resamplr();
    
    // 2. set the uploaded file and a version name
    $newImage->setImage($uploadedImage, 'thumb');
    
    // 3. resample the image (try out the different methods!)
    $newImage->fit(300, 300);
    //$newImage->letterBox(500, 500);
    //$newImage->resize(100, 200);
    //$newImage->fill(200, 200);
    //$newImage->crop(50, 50, 10, 10);
    
    // 4. get the image NOTE: the getImage() method automatically saves the file
    $newImageFile = $newImage->getImage();
    
    // (see how you can force the type)
    //$newImageFile = $newImage->getImage(IMAGETYPE_PNG);
    //$newImageFile = $newImage->getImage(IMAGETYPE_JPEG);
    
    // more stuff:
    $newImageInfo = $newImage->getImageInfo();
    $rawImage     = $newImage->getRawImage();
    
    /**
     * END of example
     */
  }
}

?>
<style>
  img {
    border:1px solid #000;
    background:gray;
  }
</style>

<form enctype="multipart/form-data" method="post">
  <input type="file" name="imagefile" />
  <input type="submit" value="submit" name="submit" />
</form>

<hr />

<h2>output:</h2>
<img src="<?php echo $newImageFile ?>" />
<pre>
  <?php print_r($newImageInfo); ?>
</pre>

<h2>original:</h2>
<img src="<?php echo $uploadedImage ?>" />
