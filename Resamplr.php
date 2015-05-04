<?php 
/**
 * Create a resampled version of an image with diffent modes of changing its dimensions
 * 
 * @Author Markus J Doetsch mdular.com
 * 
 * @Example 
 * $newImage = new Resamplr();
 * $newImage->setImage(PATH_TO_IMAGE, NAME_SUFFIX);
 * $newImage->fit(WIDTH, HEIGHT);
 * echo $newImage->getImage();
 */
class Resamplr 
{
  protected $_resource;
  protected $_width;
  protected $_height;
  protected $_filename;
  protected $_type;
  
  public function __construct(){
    $gd = gd_info();
    
    if(!$gd['PNG Support'] || !$gd['JPEG Support']){
      throw new Exception("No support for png and jpg found");
    }
  }
  
  public function __destruct(){
    if (is_resource($this->_resource)) {
      imagedestroy($this->_resource);
    }
  }
  
  public function setImage($resource, $versionName){
        
    // get image info, validate it's an image
    if(!list($width, $height, $type) = getimagesize($resource)) {
      throw new Exception("Must be an image");
    }
    
    // get file info
    $info = pathinfo($resource);
    
    // create image from resource
    switch($type){
      case IMAGETYPE_JPEG:  $img = imagecreatefromjpeg($resource); break;
      case IMAGETYPE_PNG:   $img = imagecreatefrompng($resource); break;
      case IMAGETYPE_GIF:   $img = imagecreatefromgif($resource); break;
      case IMAGETYPE_BMP:   $img = imagecreatefromwbmp($resource); break;
      default : throw new Exception("Supplied image must be bmp, gif, jpg or png");
    }
    
    // store image & data
    $this->_resource     = $img;
    $this->_width     = $width;
    $this->_height    = $height;
    $this->_filename  = $info['dirname'] . '/' . $info['filename'] . (!empty($versionName) ? '_' . $versionName : ''); // NOTE: without extension!
    $this->_type      = $type;
  }
  
  public function getImage($type = null){
    if(!empty($type) && $type !== $this->_type){
      if($type == IMAGETYPE_JPEG || $type == IMAGETYPE_PNG){
        $this->_type = $type;
      }else{
        throw new Exception("Image type for output must be png or jpg");
      }
    }
    
    $this->_saveImage();
    
    return $this->_filename . $this->_getExtension($this->_type);
  }
  
  public function getImageInfo(){
    return array(
      'path'    => $this->_filename . $this->_getExtension($this->_type),
      'width'   => $this->_width,
      'height'  => $this->_height,
      'type'    => $this->_type,
      'mime'    => image_type_to_mime_type($this->_type)
    );
  }
  
  public function getRawImage(){
    return $this->_resource;
  }
  
  /** @method resize
   * Resize to given dimensions regardless of proportions */
  public function resize($width, $height){
    
    $sourceDims = array(
      'x'       => 0,
      'y'       => 0,
      'width'   => $this->_width,
      'height'  => $this->_height,
    );
    
    $destDims = array(
      'x'       => 0,
      'y'       => 0,
      'width'   => $width, 
      'height'  => $height,
    );
    
    $this->_createImage($width, $height, $sourceDims, $destDims);
  }
  
  /** @method fit
   * Proportionally scale to the longest side within the given dimensions */
  public function fit($width, $height){
    
    $srcRatio = $this->_width / $this->_height;
    $destRatio = $width / $height;
    
    // source image is wider than requested
    if($srcRatio > $destRatio){
      $tmpWidth   = $width;
      $tmpHeight  = round($width / $srcRatio);
      $destX    = 0;
      $destY    = 0;
    }else{
      $tmpWidth   = round($height * $srcRatio);
      $tmpHeight  = $height;
      $destX    = 0;
      $destY    = 0;
    }
    
    $sourceDims = array(
      'x'       => 0,
      'y'       => 0,
      'width'   => $this->_width,
      'height'  => $this->_height
    );
    
    $destDims = array(
      'x'       => $destX,
      'y'       => $destY,
      'width'   => $tmpWidth,
      'height'  => $tmpHeight
    );
    
    $this->_createImage((int)$tmpWidth, (int)$tmpHeight, $sourceDims, $destDims);
  }
  
  /** @method fill
   * Completely fill the given dimensions proportionally,
   * overlap will be cropped from center */
  public function fill($width, $height){
    
    $srcRatio = $this->_width / $this->_height;
    $destRatio = $width / $height;
        
    // source image is wider than requested
    if($srcRatio > $destRatio){
      $tmpWidth   = round($this->_height * $destRatio);
      $tmpHeight  = $this->_height;
      $sourceX    = round(($this->_width - $tmpWidth) / 2);
      $sourceY    = 0;
    }else{
      $tmpWidth   = $this->_width;
      $tmpHeight  = round($this->_width / $destRatio);
      $sourceX    = 0;
      $sourceY    = round(($this->_height - $tmpHeight) / 2);
    }
    
    $sourceDims = array(
      'x'       => $sourceX,
      'y'       => $sourceY,
      'width'   => $tmpWidth,
      'height'  => $tmpHeight
    );
    
    $destDims = array(
      'x'       => 0,
      'y'       => 0,
      'width'   => $width,
      'height'  => $height
    );
    
    $this->_createImage($width, $height, $sourceDims, $destDims);
  }
  
  /** @method crop
   * Crop the given dimensions
   * if no offset coordinates are given, cropping will occur from center
   */
  public function crop($width, $height, $x = false, $y = false){
    $sourceDims = array(
      'x'       => ($x ? $x : $this->_width / 2 - $width / 2 ),
      'y'       => ($y ? $y : $this->_height / 2 - $height / 2),
      'width'   => $width,
      'height'  => $height
    );
    
    $destDims = array(
      'x'       => 0,
      'y'       => 0,
      'width'   => $width, 
      'height'  => $height
    );
    
    $this->_createImage($width, $height, $sourceDims, $destDims);
  }
  
  /** @method letterbox
   * Creates an image with the supplied dimensions into which
   * the original image is proportionally fitted and centered.
   * Missing pixels are filled with transparent (png) or white (jpg)
   * */
  public function letterBox($width, $height){
    
    $srcRatio = $this->_width / $this->_height;
    $destRatio = $width / $height;
    
    // source image is wider than requested
    if($srcRatio > $destRatio){
      $tmpWidth   = $width;
      $tmpHeight  = round($width / $srcRatio);
      $destX    = 0;
      $destY    = round(($height - $tmpHeight) / 2);
    }else{
      $tmpWidth   = round($height * $srcRatio);
      $tmpHeight  = $height;
      $destX    = round(($width - $tmpWidth) / 2);
      $destY    = 0;
    }
    
    $sourceDims = array(
      'x'       => 0,
      'y'       => 0,
      'width'   => $this->_width,
      'height'  => $this->_height
    );
    
    $destDims = array(
      'x'       => $destX,
      'y'       => $destY,
      'width'   => $tmpWidth,
      'height'  => $tmpHeight
    );
    
    $this->_createImage($width, $height, $sourceDims, $destDims);
  }
  
  private function _createImage($width, $height, $sourceDims, $destDims){
    // TODO: transparency flattening of semi-transparent areas on background in jpg mode
    
    // create new image
    $newImage = imagecreatetruecolor($width, $height);
    
    // initial alpha properties
    imagealphablending($newImage, false);
    imagesavealpha($newImage, true);
    
    // fill image with transparent color
    $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
    imagefilledrectangle($newImage, 0, 0, $width, $height, $transparent);
    
    // change blending before copy
    imagealphablending($newImage, true);
    
    // copy the resampled original into the new image
    imagecopyresampled(
      $newImage, $this->_resource, 
      $destDims['x'], $destDims['y'], $sourceDims['x'], $sourceDims['y'], 
      $destDims['width'], $destDims['height'], $sourceDims['width'], $sourceDims['height']
    );
    
    // apply results
    $this->_resource = $newImage;
    $this->_width = $width;
    $this->_height = $height;
  }
  
  private function _saveImage(){
    // TODO: error handling if write failed
    switch($this->_type){
      case IMAGETYPE_JPEG:  imagejpeg($this->_resource, $this->_filename . $this->_getExtension(IMAGETYPE_JPEG), 85); break;
      default:              imagepng($this->_resource, $this->_filename . $this->_getExtension(IMAGETYPE_PNG)); break;
    }
  }
  
  private function _getExtension($type){
    switch($type){
      case IMAGETYPE_JPEG: return '.jpg'; break;
      default: return image_type_to_extension($type);
    }
  }
}
