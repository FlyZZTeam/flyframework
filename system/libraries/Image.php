<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Image Manipulation class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Image_lib
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/image_lib.html
 */
class Image
{

	var $imageLibrary		= 'gd2';	// Can be:  imagemagick, netpbm, gd, gd2
	var $libraryPath		= '';
	var $dynamicOutput		= FALSE;	// Whether to send to browser or write to disk
	var $sourceImage		= '';
	var $newImage			= '';
	var $width				= '';
	var $height				= '';
	var $quality			= '90';
	var $createThumb		= FALSE;
	var $thumbMarker		= '_thumb';
	var $maintainRatio		= TRUE;		// Whether to maintain aspect ratio when resizing or use hard values
	var $masterDim			= 'auto';	// auto, height, or width.  Determines what to use as the master dimension
	var $rotationAngle		= '';
	var $xAxis				= '';
	var	$yAxis				= '';

	// Watermark Vars
	var $wmText			    = '';			// Watermark text if graphic is not used
	var $wmType			    = 'text';		// Type of watermarking.  Options:  text/overlay
	var $wmXTransp		    = 4;
	var $wmYTransp		    = 4;
	var $wmOverlayPath	    = '';			// Watermark image path
	var $wmFontPath		    = '';			// TT font
	var $wmFontSize		    = 17;			// Font size (different versions of GD will either use points or pixels)
	var $wmVrtAlignment	    = 'B';			// Vertical alignment:   T M B
	var $wmHorAlignment	    = 'C';			// Horizontal alignment: L R C
	var $wmPadding			= 0;			// Padding around text
	var $wmHorOffset		= 0;			// Lets you push text to the right
	var $wmVrtOffset		= 0;			// Lets you push  text down
	var $wmFontColor		= '#ffffff';	// Text color
	var $wmShadowColor	    = '';			// Dropshadow color
	var $wmShadowDistance	= 2;			// Dropshadow distance
	var $wmOpacity			= 50;			// Image opacity: 1 - 100  Only works with image

	// Private Vars
	var $sourceFolder		= '';
	var $destFolder		    = '';
	var $mimeType			= '';
	var $origWidth			= '';
	var $origHeight		    = '';
	var $imageType			= '';
	var $sizeStr			= '';
	var $fullSrcPath		= '';
	var $fullDstPath		= '';
	var $createFnc			= 'imagecreatetruecolor';
	var $copyFnc			= 'imagecopyresampled';
	var $errorMsg			= array();
	var $wmUseDropShadow	= FALSE;
	var $wmUseTrueType	    = FALSE;
    
    private $destImage      = '';

	/**
	 * Constructor
	 *
	 * @param	string
	 * @return	void
	 */
	public function __construct($props = array())
	{
		if (count($props) > 0) {
			$this->initialize($props);
		}

		Fly::log('debug', "Image Lib Class Initialized");
	}

	/**
	 * Initialize image properties
	 *
	 * Resets values in case this class is used in a loop
	 *
	 * @access	public
	 * @return	void
	 */
	public function clear()
	{
		$props = array(
            'sourceFolder',
            'destFolder',
            'sourceImage',
            'fullSrcPath',
            'fullDstPath',
            'newImage',
            'imageType',
            'sizeStr',
            'quality',
            'origWidth',
            'origHeight',
            'width',
            'height',
            'rotationAngle',
            'xAxis',
            'yAxis',
            'createFnc',
            'copyFnc',
            'wmOverlayPath',
            'wmUseTrueType',
            'dynamicOutput',
            'wmFontSize',
            'wmText',
            'wmVrtAlignment',
            'wmHorAlignment',
            'wmPadding',
            'wmHorOffset',
            'wmVrtOffset',
            'wmFontColor',
            'wmUseDropShadow',
            'wmShadowColor',
            'wmShadowDistance',
            'wmOpacity');

		foreach ($props as $val) {
			$this->$val = '';
		}

		// special consideration for master_dim
		$this->masterDim = 'auto';
	}

	/**
	 * initialize image preferences
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	public function initialize($props = array())
	{
		/*
		 * Convert array elements into class variables
		 */
		if (count($props) > 0) {
			foreach ($props as $key => $val) {
				$this->$key = $val;
			}
		}

		/*
		 * Is there a source image?
		 *
		 * If not, there's no reason to continue
		 *
		 */
		if ($this->sourceImage == '') {
			$this->setError('imglib_source_image_required');
			return FALSE;	
		}

		/*
		 * Is getimagesize() Available?
		 *
		 * We use it to determine the image properties (width/height).
		 * Note:  We need to figure out how to determine image
		 * properties using ImageMagick and NetPBM
		 *
		 */
		if (!function_exists('getimagesize')) {
			$this->setError('imglib_gd_required_for_props');
			return FALSE;
		}

		$this->imageLibrary = strtolower($this->imageLibrary);

		/*
		 * Set the full server path
		 *
		 * The source image may or may not contain a path.
		 * Either way, we'll try use realpath to generate the
		 * full server path in order to more reliably read it.
		 *
		 */
		if (function_exists('realpath') AND @realpath($this->sourceImage) !== FALSE) {
			$full_source_path = str_replace("\\", "/", realpath($this->sourceImage));
		} else {
			$full_source_path = $this->sourceImage;
		}

		$x = explode('/', $full_source_path);
		$this->sourceImage = end($x);
		$this->sourceFolder = str_replace($this->sourceImage, '', $full_source_path);

		// Set the Image Properties
		if (!$this->getImageProperties($this->sourceFolder.$this->sourceImage)) {
			return FALSE;	
		}

		/*
		 * Assign the "new" image name/path
		 *
		 * If the user has set a "new_image" name it means
		 * we are making a copy of the source image. If not
		 * it means we are altering the original.  We'll
		 * set the destination filename and path accordingly.
		 *
		 */
		if ($this->newImage == '') {
			$this->destImage = $this->sourceImage;
			$this->destFolder = $this->sourceFolder;
		} else {
			if (strpos($this->newImage, '/') === FALSE && strpos($this->newImage, '\\') === FALSE) {
				$this->destFolder = $this->sourceFolder;
				$this->destImage = $this->newImage;
			} else {
				if (function_exists('realpath') && @realpath($this->newImage) !== FALSE) {
					$full_dest_path = str_replace("\\", "/", realpath($this->newImage));
				} else {
					$full_dest_path = $this->newImage;
				}

				// Is there a file name?
				if (!preg_match("#\.(jpg|jpeg|gif|png)$#i", $full_dest_path)) {
					$this->destFolder = $full_dest_path.'/';
					$this->destImage = $this->sourceImage;
				} else {
					$x = explode('/', $full_dest_path);
					$this->destImage = end($x);
					$this->destFolder = str_replace($this->destImage, '', $full_dest_path);
				}
			}
		}

		/*
		 * Compile the finalized filenames/paths
		 *
		 * We'll create two master strings containing the
		 * full server path to the source image and the
		 * full server path to the destination image.
		 * We'll also split the destination image name
		 * so we can insert the thumbnail marker if needed.
		 *
		 */
		if ($this->createThumb === FALSE || $this->thumbMarker == '') {
			$this->thumbMarker = '';
		}

		$xp	= $this->explodeName($this->destImage);

		$filename = $xp['name'];
		$file_ext = $xp['ext'];

		$this->fullSrcPath = $this->sourceFolder.$this->sourceImage;
		$this->fullDstPath = $this->destFolder.$filename.$this->thumbMarker.$file_ext;

		/*
		 * Should we maintain image proportions?
		 *
		 * When creating thumbs or copies, the target width/height
		 * might not be in correct proportion with the source
		 * image's width/height.  We'll recalculate it here.
		 *
		 */
		if ($this->maintainRatio === TRUE && ($this->width != '' && $this->height != '')) {
			$this->imageReproportion();
		}

		/*
		 * Was a width and height specified?
		 *
		 * If the destination width/height was
		 * not submitted we will use the values
		 * from the actual file
		 *
		 */
		if ($this->width == '')
			$this->width = $this->origWidth;

		if ($this->height == '')
			$this->height = $this->origHeight;

		// Set the quality
		$this->quality = trim(str_replace("%", "", $this->quality));

		if ($this->quality == '' || $this->quality == 0 || ! is_numeric($this->quality))
			$this->quality = 90;

		// Set the x/y coordinates
		$this->xAxis = ($this->xAxis == '' || ! is_numeric($this->xAxis)) ? 0 : $this->xAxis;
		$this->yAxis = ($this->yAxis == '' || ! is_numeric($this->yAxis)) ? 0 : $this->yAxis;

		// Watermark-related Stuff...
		if ($this->wmFontColor != '') {
			if (strlen($this->wmFontColor) == 6) {
				$this->wmFontColor = '#'.$this->wmFontColor;
			}
		}

		if ($this->wmShadowColor != '') {
			if (strlen($this->wmShadowColor) == 6) {
				$this->wmShadowColor = '#'.$this->wmShadowColor;
			}
		}

		if ($this->wmOverlayPath != '') {
			$this->wmOverlayPath = str_replace("\\", "/", realpath($this->wmOverlayPath));
		}

		if ($this->wmShadowColor != '') {
			$this->wmUseDropShadow = TRUE;
		}

		if ($this->wmFontPath != '') {
			$this->wmUseTrueType = TRUE;
		}

		return TRUE;
	}

	/**
	 * Image Resize
	 *
	 * This is a wrapper function that chooses the proper
	 * resize function based on the protocol specified
	 *
	 * @access	public
	 * @return	bool
	 */
	public function resize()
	{
		$protocol = 'image_process_'.$this->imageLibrary;

		if (preg_match('/gd2$/i', $protocol)) {
			$protocol = 'image_process_gd';
		}

		return $this->$protocol('resize');
	}

	/**
	 * Image Crop
	 *
	 * This is a wrapper function that chooses the proper
	 * cropping function based on the protocol specified
	 *
	 * @access	public
	 * @return	bool
	 */
	public function crop()
	{
		$protocol = 'image_process_'.$this->imageLibrary;

		if (preg_match('/gd2$/i', $protocol))
		{
			$protocol = 'image_process_gd';
		}

		return $this->$protocol('crop');
	}

	/**
	 * Image Rotate
	 *
	 * This is a wrapper function that chooses the proper
	 * rotation function based on the protocol specified
	 *
	 * @access	public
	 * @return	bool
	 */
	public function rotate()
	{
		// Allowed rotation values
		$degs = array(90, 180, 270, 'vrt', 'hor');

		if ($this->rotationAngle == '' OR ! in_array($this->rotationAngle, $degs)) {
			$this->setError('imglib_rotation_angle_required');
			return FALSE;	
		}

		// Reassign the width and height
		if ($this->rotationAngle == 90 OR $this->rotationAngle == 270) {
			$this->width	= $this->origHeight;
			$this->height	= $this->origWidth;
		} else {
			$this->width	= $this->origWidth;
			$this->height	= $this->origHeight;
		}


		// Choose resizing function
		if ($this->imageLibrary == 'imagemagick' OR $this->imageLibrary == 'netpbm') {
			$protocol = 'image_process_'.$this->imageLibrary;

			return $this->$protocol('rotate');
		}

		if ($this->rotationAngle == 'hor' OR $this->rotationAngle == 'vrt') {
			return $this->imageMirrorGD();
		} else {
			return $this->imageRotateGD();
		}
	}

	/**
	 * Image Process Using GD/GD2
	 *
	 * This function will resize or crop
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	protected function image_process_gd($action = 'resize')
	{
		$v2_override = FALSE;

		// If the target width/height match the source, AND if the new file name is not equal to the old file name
		// we'll simply make a copy of the original with the new name... assuming dynamic rendering is off.
		if ($this->dynamicOutput === FALSE) {
			if ($this->origWidth == $this->width AND $this->origHeight == $this->height) {
				if ($this->sourceImage != $this->newImage) {
					if (@copy($this->fullSrcPath, $this->fullDstPath)) {
						@chmod($this->fullDstPath, FILE_WRITE_MODE);
					}
				}

				return TRUE;
			}
		}

		// Let's set up our values based on the action
		if ($action == 'crop') {
			//  Reassign the source width/height if cropping
			$this->origWidth  = $this->width;
			$this->origHeight = $this->height;

			// GD 2.0 has a cropping bug so we'll test for it
			if ($this->getGDVersion() !== FALSE) {
				$gd_version = str_replace('0', '', $this->getGDVersion());
				$v2_override = ($gd_version == 2) ? TRUE : FALSE;
			}
		} else {
			// If resizing the x/y axis must be zero
			$this->xAxis = 0;
			$this->yAxis = 0;
		}

		//  Create the image handle
		if (!($src_img = $this->imageCreateGD())) {
			return FALSE;
		}

		//  Create The Image
		//
		//  old conditional which users report cause problems with shared GD libs who report themselves as "2.0 or greater"
		//  it appears that this is no longer the issue that it was in 2004, so we've removed it, retaining it in the comment
		//  below should that ever prove inaccurate.
		//
		//  if ($this->imageLibrary == 'gd2' AND function_exists('imagecreatetruecolor') AND $v2_override == FALSE)
		if ($this->imageLibrary == 'gd2' AND function_exists('imagecreatetruecolor')) {
			$create	= 'imagecreatetruecolor';
			$copy	= 'imagecopyresampled';
		} else {
			$create	= 'imagecreate';
			$copy	= 'imagecopyresized';
		}

		$dst_img = $create($this->width, $this->height);

		if ($this->imageType == 3) {
            // png we can actually preserve transparency
			imagealphablending($dst_img, FALSE);
			imagesavealpha($dst_img, TRUE);
		}

		$copy($dst_img, $src_img, 0, 0, $this->xAxis, $this->yAxis, $this->width, $this->height, $this->origWidth, $this->origHeight);

		//  Show the image
		if ($this->dynamicOutput == TRUE) {
			$this->imageDisplayGD($dst_img);
		} else {
			// Or save it
			if (!$this->imageSaveGD($dst_img)) {
				return FALSE;
			}
		}

		//  Kill the file handles
		imagedestroy($dst_img);
		imagedestroy($src_img);

		// Set the file to 777
		@chmod($this->fullDstPath, FILE_WRITE_MODE);

		return TRUE;
	}

	/**
	 * Image Process Using ImageMagick
	 *
	 * This function will resize, crop or rotate
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
    protected function image_process_imagemagick($action = 'resize')
	{
		//  Do we have a vaild library path?
		if ($this->libraryPath == '') {
			$this->setError('imglib_libpath_invalid');
			return FALSE;
		}

		if (!preg_match("/convert$/i", $this->libraryPath)) {
			$this->libraryPath = rtrim($this->libraryPath, '/').'/';
			$this->libraryPath .= 'convert';
		}

		// Execute the command
		$cmd = $this->libraryPath." -quality ".$this->quality;

		if ($action == 'crop') {
			$cmd .= " -crop ".$this->width."x".$this->height."+".$this->xAxis."+".$this->yAxis." \"$this->fullSrcPath\" \"$this->fullDstPath\" 2>&1";
		} else if ($action == 'rotate') {
			switch ($this->rotationAngle) {
				case 'hor'	: $angle = '-flop';
					break;
				case 'vrt'	: $angle = '-flip';
					break;
				default		: $angle = '-rotate '.$this->rotationAngle;
					break;
			}

			$cmd .= " ".$angle." \"$this->fullSrcPath\" \"$this->fullDstPath\" 2>&1";
		} else {
            // Resize
			$cmd .= " -resize ".$this->width."x".$this->height." \"$this->fullSrcPath\" \"$this->fullDstPath\" 2>&1";
		}

		$retval = 1;

		@exec($cmd, $output, $retval);

		//	Did it work?
		if ($retval > 0) {
			$this->setError('imglib_image_process_failed');
			return FALSE;
		}

		// Set the file to 777
		@chmod($this->fullDstPath, FILE_WRITE_MODE);

		return TRUE;
	}

	/**
	 * Image Process Using NetPBM
	 *
	 * This function will resize, crop or rotate
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	protected function image_process_netpbm($action = 'resize')
	{
		if ($this->libraryPath == '') {
			$this->setError('imglib_libpath_invalid');
			return FALSE;
		}

		//  Build the resizing command
		switch ($this->imageType) {
			case 1 :
						$cmd_in		= 'giftopnm';
						$cmd_out	= 'ppmtogif';
				break;
			case 2 :
						$cmd_in		= 'jpegtopnm';
						$cmd_out	= 'ppmtojpeg';
				break;
			case 3 :
						$cmd_in		= 'pngtopnm';
						$cmd_out	= 'ppmtopng';
				break;
		}

		if ($action == 'crop') {
			$cmd_inner = 'pnmcut -left '.$this->xAxis.' -top '.$this->yAxis.' -width '.$this->width.' -height '.$this->height;
		} else if ($action == 'rotate') {
			switch ($this->rotationAngle)
			{
				case 90		:	$angle = 'r270';
					break;
				case 180	:	$angle = 'r180';
					break;
				case 270	:	$angle = 'r90';
					break;
				case 'vrt'	:	$angle = 'tb';
					break;
				case 'hor'	:	$angle = 'lr';
					break;
			}

			$cmd_inner = 'pnmflip -'.$angle.' ';
		} else {
			$cmd_inner = 'pnmscale -xysize '.$this->width.' '.$this->height;
		}

		$cmd = $this->libraryPath.$cmd_in.' '.$this->fullSrcPath.' | '.$cmd_inner.' | '.$cmd_out.' > '.$this->destFolder.'netpbm.tmp';

		$retval = 1;

		@exec($cmd, $output, $retval);

		//  Did it work?
		if ($retval > 0) {
			$this->setError('imglib_image_process_failed');
			return FALSE;
		}

		// With NetPBM we have to create a temporary image.
		// If you try manipulating the original it fails so
		// we have to rename the temp file.
		copy ($this->destFolder.'netpbm.tmp', $this->fullDstPath);
		unlink ($this->destFolder.'netpbm.tmp');
		@chmod($this->fullDstPath, FILE_WRITE_MODE);

		return TRUE;
	}

	/**
	 * Image Rotate Using GD
	 *
	 * @access	public
	 * @return	bool
	 */
	function imageRotateGD()
	{
		//  Create the image handle
		if (!($src_img = $this->imageCreateGD())) {
			return FALSE;
		}

		// Set the background color
		// This won't work with transparent PNG files so we are
		// going to have to figure out how to determine the color
		// of the alpha channel in a future release.

		$white	= imagecolorallocate($src_img, 255, 255, 255);

		//  Rotate it!
		$dst_img = imagerotate($src_img, $this->rotationAngle, $white);

		//  Save the Image
		if ($this->dynamicOutput == TRUE) {
			$this->imageDisplayGD($dst_img);
		} else {
			// Or save it
			if (!$this->imageSaveGD($dst_img)) {
				return FALSE;
			}
		}

		//  Kill the file handles
		imagedestroy($dst_img);
		imagedestroy($src_img);

		// Set the file to 777

		@chmod($this->fullDstPath, FILE_WRITE_MODE);

		return TRUE;
	}

	/**
	 * Create Mirror Image using GD
	 *
	 * This function will flip horizontal or vertical
	 *
	 * @access	public
	 * @return	bool
	 */
	public function imageMirrorGD()
	{
		if (!$src_img = $this->imageCreateGD()) {
			return FALSE;
		}

		$width  = $this->origWidth;
		$height = $this->origHeight;

		if ($this->rotationAngle == 'hor') {
			for ($i = 0; $i < $height; $i++) {
				$left  = 0;
				$right = $width-1;

				while ($left < $right) {
					$cl = imagecolorat($src_img, $left, $i);
					$cr = imagecolorat($src_img, $right, $i);

					imagesetpixel($src_img, $left, $i, $cr);
					imagesetpixel($src_img, $right, $i, $cl);

					$left++;
					$right--;
				}
			}
		} else {
			for ($i = 0; $i < $width; $i++) {
				$top = 0;
				$bot = $height-1;

				while ($top < $bot) {
					$ct = imagecolorat($src_img, $i, $top);
					$cb = imagecolorat($src_img, $i, $bot);

					imagesetpixel($src_img, $i, $top, $cb);
					imagesetpixel($src_img, $i, $bot, $ct);

					$top++;
					$bot--;
				}
			}
		}

		//  Show the image
		if ($this->dynamicOutput == TRUE) {
			$this->imageDisplayGD($src_img);
		} else {
			// Or save it
			if (!$this->imageSaveGD($src_img)) {
				return FALSE;
			}
		}

		//  Kill the file handles
		imagedestroy($src_img);

		// Set the file to 777
		@chmod($this->fullDstPath, FILE_WRITE_MODE);

		return TRUE;
	}

	/**
	 * Image Watermark
	 *
	 * This is a wrapper function that chooses the type
	 * of watermarking based on the specified preference.
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function watermark()
	{
		if ($this->wmType == 'overlay') {
			return $this->overlayWatermark();
		} else {
			return $this->textWatermark();
		}
	}

	/**
	 * Watermark - Graphic Version
	 *
	 * @access	public
	 * @return	bool
	 */
	public function overlayWatermark()
	{
		if ( ! function_exists('imagecolortransparent')) {
			$this->setError('imglib_gd_required');
			return FALSE;
		}

		//  Fetch source image properties
		$this->getImageProperties();

		//  Fetch watermark image properties
		$props			= $this->getImageProperties($this->wmOverlayPath, TRUE);
		$wm_img_type	= $props['image_type'];
		$wm_width		= $props['width'];
		$wm_height		= $props['height'];

		//  Create two image resources
		$wm_img  = $this->imageCreateGD($this->wmOverlayPath, $wm_img_type);
		$src_img = $this->imageCreateGD($this->fullSrcPath);

		// Reverse the offset if necessary
		// When the image is positioned at the bottom
		// we don't want the vertical offset to push it
		// further down.  We want the reverse, so we'll
		// invert the offset.  Same with the horizontal
		// offset when the image is at the right

		$this->wmVrtAlignment = strtoupper(substr($this->wmVrtAlignment, 0, 1));
		$this->wmHorAlignment = strtoupper(substr($this->wmHorAlignment, 0, 1));

		if ($this->wmVrtAlignment == 'B')
			$this->wmVrtOffset = $this->wmVrtOffset * -1;

		if ($this->wmHorAlignment == 'R')
			$this->wmHorOffset = $this->wmHorOffset * -1;

		//  Set the base x and y axis values
		$x_axis = $this->wmHorOffset + $this->wmPadding;
		$y_axis = $this->wmVrtOffset + $this->wmPadding;

		//  Set the vertical position
		switch ($this->wmVrtAlignment) {
			case 'T':
				break;
			case 'M':	$y_axis += ($this->origHeight / 2) - ($wm_height / 2);
				break;
			case 'B':	$y_axis += $this->origHeight - $wm_height;
				break;
		}

		//  Set the horizontal position
		switch ($this->wmHorAlignment) {
			case 'L':
				break;
			case 'C':	$x_axis += ($this->origWidth / 2) - ($wm_width / 2);
				break;
			case 'R':	$x_axis += $this->origWidth - $wm_width;
				break;
		}

		//  Build the finalized image
		if ($wm_img_type == 3 AND function_exists('imagealphablending')) {
			@imagealphablending($src_img, TRUE);
		}

		// Set RGB values for text and shadow
		$rgba = imagecolorat($wm_img, $this->wmXTransp, $this->wmYTransp);
		$alpha = ($rgba & 0x7F000000) >> 24;

		// make a best guess as to whether we're dealing with an image with alpha transparency or no/binary transparency
		if ($alpha > 0) {
			// copy the image directly, the image's alpha transparency being the sole determinant of blending
			imagecopy($src_img, $wm_img, $x_axis, $y_axis, 0, 0, $wm_width, $wm_height);
		} else {
			// set our RGB value from above to be transparent and merge the images with the specified opacity
			imagecolortransparent($wm_img, imagecolorat($wm_img, $this->wmXTransp, $this->wmYTransp));
			imagecopymerge($src_img, $wm_img, $x_axis, $y_axis, 0, 0, $wm_width, $wm_height, $this->wmOpacity);
		}

		//  Output the image
		if ($this->dynamicOutput == TRUE) {
			$this->imageDisplayGD($src_img);
		} else {
			if (!$this->imageSaveGD($src_img)) {
				return FALSE;
			}
		}

		imagedestroy($src_img);
		imagedestroy($wm_img);

		return TRUE;
	}

	/**
	 * Watermark - Text Version
	 *
	 * @access	public
	 * @return	bool
	 */
	public function textWatermark()
	{
		if (!($src_img = $this->imageCreateGD())) {
			return FALSE;
		}

		if ($this->wmUseTrueType == TRUE AND ! file_exists($this->wmFontPath)) {
			$this->setError('imglib_missing_font');
			return FALSE;
		}

		//  Fetch source image properties
		$this->getImageProperties();

		// Set RGB values for text and shadow
		$this->wmFontColor	= str_replace('#', '', $this->wmFontColor);
		$this->wmShadowColor	= str_replace('#', '', $this->wmShadowColor);

		$R1 = hexdec(substr($this->wmFontColor, 0, 2));
		$G1 = hexdec(substr($this->wmFontColor, 2, 2));
		$B1 = hexdec(substr($this->wmFontColor, 4, 2));

		$R2 = hexdec(substr($this->wmShadowColor, 0, 2));
		$G2 = hexdec(substr($this->wmShadowColor, 2, 2));
		$B2 = hexdec(substr($this->wmShadowColor, 4, 2));

		$txt_color	= imagecolorclosest($src_img, $R1, $G1, $B1);
		$drp_color	= imagecolorclosest($src_img, $R2, $G2, $B2);

		// Reverse the vertical offset
		// When the image is positioned at the bottom
		// we don't want the vertical offset to push it
		// further down.  We want the reverse, so we'll
		// invert the offset.  Note: The horizontal
		// offset flips itself automatically

		if ($this->wmVrtAlignment == 'B')
			$this->wmVrtOffset = $this->wmVrtOffset * -1;

		if ($this->wmHorAlignment == 'R')
			$this->wmHorOffset = $this->wmHorOffset * -1;

		// Set font width and height
		// These are calculated differently depending on
		// whether we are using the true type font or not
		if ($this->wmUseTrueType == TRUE) {
			if ($this->wmFontSize == '')
				$this->wmFontSize = '17';

			$fontwidth  = $this->wmFontSize-($this->wmFontSize/4);
			$fontheight = $this->wmFontSize;
			$this->wmVrtOffset += $this->wmFontSize;
		} else {
			$fontwidth  = imagefontwidth($this->wmFontSize);
			$fontheight = imagefontheight($this->wmFontSize);
		}

		// Set base X and Y axis values
		$x_axis = $this->wmHorOffset + $this->wmPadding;
		$y_axis = $this->wmVrtOffset + $this->wmPadding;

		// Set verticle alignment
		if ($this->wmUseDropShadow == FALSE)
			$this->wmShadowDistance = 0;

		$this->wmVrtAlignment = strtoupper(substr($this->wmVrtAlignment, 0, 1));
		$this->wmHorAlignment = strtoupper(substr($this->wmHorAlignment, 0, 1));

		switch ($this->wmVrtAlignment) {
			case	 "T" :
				break;
			case "M":	$y_axis += ($this->origHeight/2)+($fontheight/2);
				break;
			case "B":	$y_axis += ($this->origHeight - $fontheight - $this->wmShadowDistance - ($fontheight/2));
				break;
		}

		$x_shad = $x_axis + $this->wmShadowDistance;
		$y_shad = $y_axis + $this->wmShadowDistance;

		// Set horizontal alignment
		switch ($this->wmHorAlignment) {
			case "L":
				break;
			case "R":
						if ($this->wmUseDropShadow)
							$x_shad += ($this->origWidth - $fontwidth*strlen($this->wmText));
							$x_axis += ($this->origWidth - $fontwidth*strlen($this->wmText));
				break;
			case "C":
						if ($this->wmUseDropShadow)
							$x_shad += floor(($this->origWidth - $fontwidth*strlen($this->wmText))/2);
							$x_axis += floor(($this->origWidth  -$fontwidth*strlen($this->wmText))/2);
				break;
		}

		//  Add the text to the source image
		if ($this->wmUseTrueType) {
			if ($this->wmUseDropShadow)
				imagettftext($src_img, $this->wmFontSize, 0, $x_shad, $y_shad, $drp_color, $this->wmFontPath, $this->wmText);
            imagettftext($src_img, $this->wmFontSize, 0, $x_axis, $y_axis, $txt_color, $this->wmFontPath, $this->wmText);
		} else {
			if ($this->wmUseDropShadow)
				imagestring($src_img, $this->wmFontSize, $x_shad, $y_shad, $this->wmText, $drp_color);
			imagestring($src_img, $this->wmFontSize, $x_axis, $y_axis, $this->wmText, $txt_color);
		}

		//  Output the final image
		if ($this->dynamicOutput == TRUE) {
			$this->imageDisplayGD($src_img);
		} else {
			$this->imageSaveGD($src_img);
		}

		imagedestroy($src_img);

		return TRUE;
	}

	/**
	 * Create Image - GD
	 *
	 * This simply creates an image resource handle
	 * based on the type of image being processed
	 *
	 * @access	public
	 * @param	string
	 * @return	resource
	 */
	function imageCreateGD($path = '', $image_type = '')
	{
		if ($path == '')
			$path = $this->fullSrcPath;

		if ($image_type == '')
			$image_type = $this->imageType;


		switch ($image_type) {
			case	 1 :
						if ( ! function_exists('imagecreatefromgif')) {
							$this->setError(array('imglib_unsupported_imagecreate', 'imglib_gif_not_supported'));
							return FALSE;
						}

						return imagecreatefromgif($path);
				break;
			case 2 :
						if ( ! function_exists('imagecreatefromjpeg')) {
							$this->setError(array('imglib_unsupported_imagecreate', 'imglib_jpg_not_supported'));
							return FALSE;
						}

						return imagecreatefromjpeg($path);
				break;
			case 3 :
						if ( ! function_exists('imagecreatefrompng')) {
							$this->setError(array('imglib_unsupported_imagecreate', 'imglib_png_not_supported'));
							return FALSE;
						}

						return imagecreatefrompng($path);
				break;

		}

		$this->setError(array('imglib_unsupported_imagecreate'));
		return FALSE;
	}

	/**
	 * Write image file to disk - GD
	 *
	 * Takes an image resource as input and writes the file
	 * to the specified destination
	 *
	 * @access	public
	 * @param	resource
	 * @return	bool
	 */
	public function imageSaveGD($resource)
	{
		switch ($this->imageType) {
			case 1 :
						if (!function_exists('imagegif')) {
							$this->setError(array('imglib_unsupported_imagecreate', 'imglib_gif_not_supported'));
							return FALSE;
						}

						if (!@imagegif($resource, $this->fullDstPath)) {
							$this->setError('imglib_save_failed');
							return FALSE;
						}
				break;
			case 2	:
						if (!function_exists('imagejpeg')) {
							$this->setError(array('imglib_unsupported_imagecreate', 'imglib_jpg_not_supported'));
							return FALSE;
						}

						if (!@imagejpeg($resource, $this->fullDstPath, $this->quality)) {
							$this->setError('imglib_save_failed');
							return FALSE;
						}
				break;
			case 3	:
						if (!function_exists('imagepng')) {
							$this->setError(array('imglib_unsupported_imagecreate', 'imglib_png_not_supported'));
							return FALSE;
						}

						if (!@imagepng($resource, $this->fullDstPath)) {
							$this->setError('imglib_save_failed');
							return FALSE;
						}
				break;
			default		:
							$this->setError(array('imglib_unsupported_imagecreate'));
							return FALSE;
				break;
		}

		return TRUE;
	}

	/**
	 * Dynamically outputs an image
	 *
	 * @access	public
	 * @param	resource
	 * @return	void
	 */
	public function imageDisplayGD($resource)
	{
		header("Content-Disposition: filename={$this->sourceImage};");
		header("Content-Type: {$this->mimeType}");
		header('Content-Transfer-Encoding: binary');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

		switch ($this->imageType)
		{
			case 1		:	imagegif($resource);
				break;
			case 2		:	imagejpeg($resource, '', $this->quality);
				break;
			case 3		:	imagepng($resource);
				break;
			default		:	echo 'Unable to display the image';
				break;
		}
	}

	/**
	 * Re-proportion Image Width/Height
	 *
	 * When creating thumbs, the desired width/height
	 * can end up warping the image due to an incorrect
	 * ratio between the full-sized image and the thumb.
	 *
	 * This function lets us re-proportion the width/height
	 * if users choose to maintain the aspect ratio when resizing.
	 *
	 * @access	public
	 * @return	void
	 */
	public function imageReproportion()
	{
		if ( ! is_numeric($this->width) OR ! is_numeric($this->height) OR $this->width == 0 OR $this->height == 0)
			return;

		if ( ! is_numeric($this->origWidth) OR ! is_numeric($this->origHeight) OR $this->origWidth == 0 OR $this->origHeight == 0)
			return;

		$new_width	= ceil($this->origWidth*$this->height/$this->origHeight);
		$new_height	= ceil($this->width*$this->origHeight/$this->origWidth);

		$ratio = (($this->origHeight/$this->origWidth) - ($this->height/$this->width));

		if ($this->masterDim != 'width' AND $this->masterDim != 'height')
		{
			$this->masterDim = ($ratio < 0) ? 'width' : 'height';
		}

		if (($this->width != $new_width) AND ($this->height != $new_height))
		{
			if ($this->masterDim == 'height')
			{
				$this->width = $new_width;
			}
			else
			{
				$this->height = $new_height;
			}
		}
	}

	/**
	 * Get image properties
	 *
	 * A helper function that gets info about the file
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 */
	public function getImageProperties($path = '', $return = FALSE)
	{
		// For now we require GD but we should
		// find a way to determine this using IM or NetPBM

		if ($path == '') {
			$path = $this->fullSrcPath;
        }

		if (!file_exists($path)) {
			$this->setError('imglib_invalid_path');
			return FALSE;
		}

		$vals = @getimagesize($path);

		$types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');

		$mime = (isset($types[$vals['2']])) ? 'image/'.$types[$vals['2']] : 'image/jpg';

		if ($return == TRUE) {
			$v['width']			= $vals['0'];
			$v['height']		= $vals['1'];
			$v['image_type']	= $vals['2'];
			$v['size_str']		= $vals['3'];
			$v['mime_type']		= $mime;

			return $v;
		}

		$this->origWidth	= $vals['0'];
		$this->origHeight	= $vals['1'];
		$this->imageType	= $vals['2'];
		$this->sizeStr		= $vals['3'];
		$this->mimeType	= $mime;

		return TRUE;
	}

	/**
	 * Size calculator
	 *
	 * This function takes a known width x height and
	 * recalculates it to a new size.  Only one
	 * new variable needs to be known
	 *
	 *	$props = array(
	 *					'width'			=> $width,
	 *					'height'		=> $height,
	 *					'new_width'		=> 40,
	 *					'new_height'	=> ''
	 *				  );
	 *
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	public function sizeCalculator($vals)
	{
		if (!is_array($vals)) {
			return;
		}

		$allowed = array('new_width', 'new_height', 'width', 'height');

		foreach ($allowed as $item) {
			if ( ! isset($vals[$item]) OR $vals[$item] == '')
				$vals[$item] = 0;
		}

		if ($vals['width'] == 0 OR $vals['height'] == 0) {
			return $vals;
		}

		if ($vals['new_width'] == 0) {
			$vals['new_width'] = ceil($vals['width']*$vals['new_height']/$vals['height']);
		} else if ($vals['new_height'] == 0) {
			$vals['new_height'] = ceil($vals['new_width']*$vals['height']/$vals['width']);
		}

		return $vals;
	}

	/**
	 * Explode source_image
	 *
	 * This is a helper function that extracts the extension
	 * from the source_image.  This function lets us deal with
	 * source_images with multiple periods, like:  my.cool.jpg
	 * It returns an associative array with two elements:
	 * $array['ext']  = '.jpg';
	 * $array['name'] = 'my.cool';
	 *
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	public function explodeName($source_image)
	{
		$ext = strrchr($source_image, '.');
		$name = ($ext === FALSE) ? $source_image : substr($source_image, 0, -strlen($ext));

		return array('ext' => $ext, 'name' => $name);
	}

	/**
	 * Is GD Installed?
	 *
	 * @access	public
	 * @return	bool
	 */
	public function gdLoaded()
	{
		if (!extension_loaded('gd')) {
			if (!dl('gd.so')) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Get GD version
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function getGDVersion()
	{
		if (function_exists('gd_info'))
		{
			$gd_version = @gd_info();
			$gd_version = preg_replace("/\D/", "", $gd_version['GD Version']);

			return $gd_version;
		}

		return FALSE;
	}

	/**
	 * Set error message
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setError($msg)
	{
		Fly::app()->Lang->load('imglib');
		if (is_array($msg)) {
			foreach ($msg as $val) {

				$msg = (Fly::app()->Lang->line($val) == FALSE) ? $val : Fly::app()->Lang->line($val);
				$this->errorMsg[] = $msg;
				Fly::log('error', $msg);
			}
		} else {
			$msg = (Fly::app()->Lang->line($msg) == FALSE) ? $msg : Fly::app()->Lang->line($msg);
			$this->errorMsg[] = $msg;
			Fly::log('error', $msg);
		}
	}

	/**
	 * Show error messages
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function displayErrors($open = '<p>', $close = '</p>')
	{
		$str = '';
		foreach ($this->errorMsg as $val) {
			$str .= $open.$val.$close;
		}
		return $str;
	}

}