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
 * File Uploading Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Uploads
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/file_uploading.html
 */
class Upload
{

	public $maxSize				    = 0;
	public $maxWidth				= 0;
	public $maxHeight				= 0;
	public $maxFileName			    = 0;
	public $allowedTypes			= "";
	public $fileTemp				= "";
	public $fileName				= "";
	public $origName				= "";
	public $fileType				= "";
	public $fileSize				= "";
	public $fileExt				    = "";
	public $uploadPath				= "";
	public $overwrite				= FALSE;
	public $encryptName			    = FALSE;
	public $isImage				    = FALSE;
	public $imageWidth				= '';
	public $imageHeight			    = '';
	public $imageType				= '';
	public $imageSizeStr			= '';
	public $errorMsg				= array();
	public $mimes					= array();
	public $removeSpaces			= TRUE;
	public $xssClean				= FALSE;
	public $tempPrefix				= "temp_file_";
	public $clientName				= '';

	protected $_fileNameOverride	= '';

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct($props = array())
	{
		if (count($props) > 0) {
			$this->initialize($props);
		}

		Fly::log('debug', "Upload Class Initialized");
	}

	/**
	 * Initialize preferences
	 *
	 * @param	array
	 * @return	void
	 */
	public function initialize($config = array())
	{
		$defaults = array(
							'maxSize'			=> 0,
							'maxWidth'			=> 0,
							'maxHeight'		    => 0,
							'maxFileName'		=> 0,
							'allowedTypes'		=> "",
							'fileTemp'			=> "",
							'fileName'			=> "",
							'origName'			=> "",
							'fileType'			=> "",
							'fileSize'			=> "",
							'fileExt'			=> "",
							'uploadPath'		=> "",
							'overwrite'			=> FALSE,
							'encryptName'		=> FALSE,
							'isImage'			=> FALSE,
							'imageWidth'		=> '',
							'imageHeight'		=> '',
							'imageType'		    => '',
							'imageSizeStr'	    => '',
							'errorMsg'			=> array(),
							'mimes'				=> array(),
							'removeSpaces'		=> TRUE,
							'xssClean'			=> FALSE,
							'tempPrefix'		=> "temp_file_",
							'clientName'		=> ''
						);


		foreach ($defaults as $key => $val) {
			if (isset($config[$key])) {
				$method = 'set'.$key;
				if (method_exists($this, $method)) {
					$this->$method($config[$key]);
				} else {
					$this->$key = $config[$key];
				}
			} else {
				$this->$key = $val;
			}
		}

		// if a file_name was provided in the config, use it instead of the user input
		// supplied file name for all uploads until initialized again
		$this->_fileNameOverride = $this->fileName;
	}

	/**
	 * Perform the file upload
	 *
	 * @return	bool
	 */
	public function doUpload($field = 'userfile')
	{

	    // Is $_FILES[$field] set? If not, no reason to continue.
		if (!isset($_FILES[$field])) {
			$this->setError('upload_no_file_selected');
			return FALSE;
		}

		// Is the upload path valid?
		if (!$this->validateUploadPath()) {
			// errors will already be set by validate_upload_path() so just return FALSE
			return FALSE;
		}

		// Was the file able to be uploaded? If not, determine the reason why.
		if (!is_uploaded_file($_FILES[$field]['tmp_name'])) {
			$error = ( ! isset($_FILES[$field]['error'])) ? 4 : $_FILES[$field]['error'];

			switch($error) {
				case 1:	// UPLOAD_ERR_INI_SIZE
					$this->setError('upload_file_exceeds_limit');
					break;
				case 2: // UPLOAD_ERR_FORM_SIZE
					$this->setError('upload_file_exceeds_form_limit');
					break;
				case 3: // UPLOAD_ERR_PARTIAL
					$this->setError('upload_file_partial');
					break;
				case 4: // UPLOAD_ERR_NO_FILE
					$this->setError('upload_no_file_selected');
					break;
				case 6: // UPLOAD_ERR_NO_TMP_DIR
					$this->setError('upload_no_temp_directory');
					break;
				case 7: // UPLOAD_ERR_CANT_WRITE
					$this->setError('upload_unable_to_write_file');
					break;
				case 8: // UPLOAD_ERR_EXTENSION
					$this->setError('upload_stopped_by_extension');
					break;
				default :   $this->setError('upload_no_file_selected');
					break;
			}

			return FALSE;
		}


		// Set the uploaded data as class variables
		$this->fileTemp = $_FILES[$field]['tmp_name'];
		$this->fileSize = $_FILES[$field]['size'];
		$this->_getFileMimeType($_FILES[$field]);
		$this->fileType = preg_replace("/^(.+?);.*$/", "\\1", $this->fileType);
		$this->fileType = strtolower(trim(stripslashes($this->fileType), '"'));
		$this->fileName = $this->_prepFilename($_FILES[$field]['name']);
		$this->fileExt	 = $this->getExtension($this->fileName);
		$this->clientName = $this->fileName;

		// Is the file type allowed to be uploaded?
		if (!$this->isAllowedFileType()) {
			$this->setError('upload_invalid_filetype');
			return FALSE;
		}

		// if we're overriding, let's now make sure the new name and type is allowed
		if ($this->_fileNameOverride != '') {
			$this->fileName = $this->_prepFilename($this->_fileNameOverride);

			// If no extension was provided in the file_name config item, use the uploaded one
			if (strpos($this->_fileNameOverride, '.') === FALSE) {
				$this->fileName .= $this->fileExt;
			} else {
                // An extension was provided, lets have it!
				$this->fileExt	 = $this->getExtension($this->_fileNameOverride);
			}

			if (!$this->isAllowedFileType(TRUE)) {
				$this->setError('upload_invalid_filetype');
				return FALSE;
			}
		}

		// Convert the file size to kilobytes
		if ($this->fileSize > 0) {
			$this->fileSize = round($this->fileSize/1024, 2);
		}

		// Is the file size within the allowed maximum?
		if (!$this->isAllowedFileSize()) {
			$this->setError('upload_invalid_filesize');
			return FALSE;
		}

		// Are the image dimensions within the allowed size?
		// Note: This can fail if the server has an open_basdir restriction.
		if (!$this->isAllowedDimensions()) {
			$this->setError('upload_invalid_dimensions');
			return FALSE;
		}

		// Sanitize the file name for security
		$this->fileName = $this->cleanFileName($this->fileName);

		// Truncate the file name if it's too long
		if ($this->maxFileName > 0) {
			$this->fileName = $this->limitFileNameLength($this->fileName, $this->maxFileName);
		}

		// Remove white spaces in the name
		if ($this->removeSpaces == TRUE) {
			$this->fileName = preg_replace("/\s+/", "_", $this->fileName);
		}

		/*
		 * Validate the file name
		 * This function appends an number onto the end of
		 * the file if one with the same name already exists.
		 * If it returns false there was a problem.
		 */
		$this->origName = $this->fileName;

		if ($this->overwrite == FALSE) {
			$this->fileName = $this->_setFileName($this->uploadPath, $this->fileName);

			if ($this->fileName === FALSE) {
				return FALSE;
			}
		}

		/*
		 * Run the file through the XSS hacking filter
		 * This helps prevent malicious code from being
		 * embedded within a file.  Scripts can easily
		 * be disguised as images or other file types.
		 */
		if ($this->xssClean) {
			if ($this->doXssClean() === FALSE) {
				$this->setError('upload_unable_to_write_file');
				return FALSE;
			}
		}

		/*
		 * Move the file to the final destination
		 * To deal with different server configurations
		 * we'll attempt to use copy() first.  If that fails
		 * we'll use move_uploaded_file().  One of the two should
		 * reliably work in most environments
		 */
		if (!@copy($this->fileTemp, $this->uploadPath.$this->fileName)) {
			if (!@move_uploaded_file($this->fileTemp, $this->uploadPath.$this->fileName)) {
				$this->setError('upload_destination_error');
				return FALSE;
			}
		}

		/*
		 * Set the finalized image dimensions
		 * This sets the image width/height (assuming the
		 * file was an image).  We use this information
		 * in the "data" function.
		 */
		$this->setImageProperties($this->uploadPath.$this->fileName);

		return TRUE;
	}

	/**
	 * Finalized Data Array
	 *
	 * Returns an associative array containing all of the information
	 * related to the upload, allowing the developer easy access in one array.
	 *
	 * @return	array
	 */
	public function data()
	{
		return array (
						'fileName'			=> $this->fileName,
						'fileType'			=> $this->fileType,
						'filePath'			=> $this->uploadPath,
						'fullPath'			=> $this->uploadPath.$this->fileName,
						'rawName'			=> str_replace($this->fileExt, '', $this->fileName),
						'origName'			=> $this->origName,
						'clientName'		=> $this->clientName,
						'fileExt'			=> $this->fileExt,
						'fileSize'			=> $this->fileSize,
						'isImage'			=> $this->isImage(),
						'imageWidth'		=> $this->imageWidth,
						'imageHeight'		=> $this->imageHeight,
						'imageType'		    => $this->imageType,
						'imageSizeStr'	    => $this->imageSizeStr,
					);
	}

	/**
	 * Set Upload Path
	 *
	 * @param	string
	 * @return	void
	 */
	public function setUploadPath($path)
	{
		// Make sure it has a trailing slash
		$this->uploadPath = rtrim($path, '/').'/';
	}


	/**
	 * Set the file name
	 *
	 * This function takes a filename/path as input and looks for the
	 * existence of a file with the same name. If found, it will append a
	 * number to the end of the filename to avoid overwriting a pre-existing file.
	 *
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function _setFileName($path, $filename)
	{
		if ($this->encryptName == TRUE) {
			mt_srand();
			$filename = md5(uniqid(mt_rand())).$this->fileExt;
		}

		if ( ! file_exists($path.$filename)) {
			return $filename;
		}

		$filename = str_replace($this->fileExt, '', $filename);

		$new_filename = '';
		for ($i = 1; $i < 100; $i++) {
			if ( ! file_exists($path.$filename.$i.$this->fileExt)) {
				$new_filename = $filename.$i.$this->fileExt;
				break;
			}
		}

		if ($new_filename == '') {
			$this->setError('upload_bad_filename');
			return FALSE;
		} else {
			return $new_filename;
		}
	}

	/**
	 * Set Maximum File Size
	 *
	 * @param	integer
	 * @return	void
	 */
	public function setMaxFileSize($n)
	{
		$this->maxSize = ((int) $n < 0) ? 0: (int) $n;
	}

	/**
	 * Set Maximum File Name Length
	 *
	 * @param	integer
	 * @return	void
	 */
	public function setMaxFileName($n)
	{
		$this->maxFileName = ((int) $n < 0) ? 0: (int) $n;
	}

	/**
	 * Set Maximum Image Width
	 *
	 * @param	integer
	 * @return	void
	 */
	public function setMaxWidth($n)
	{
		$this->maxWidth = ((int) $n < 0) ? 0: (int) $n;
	}

	/**
	 * Set Maximum Image Height
	 *
	 * @param	integer
	 * @return	void
	 */
	public function setMaxHeight($n)
	{
		$this->maxHeight = ((int) $n < 0) ? 0: (int) $n;
	}

	/**
	 * Set Allowed File Types
	 *
	 * @param	string
	 * @return	void
	 */
	public function setAllowedTypes($types)
	{
		if (!is_array($types) && $types == '*') {
			$this->allowedTypes = '*';
			return;
		}
		$this->allowedTypes = explode('|', $types);
	}

	/**
	 * Set Image Properties
	 *
	 * Uses GD to determine the width/height/type of image
	 *
	 * @param	string
	 * @return	void
	 */
	public function setImageProperties($path = '')
	{
		if (!$this->isImage()) {
			return;
		}

		if (function_exists('getimagesize')) {
			if (FALSE !== ($D = @getimagesize($path))) {
				$types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');

				$this->imageWidth		= $D['0'];
				$this->imageHeight		= $D['1'];
				$this->imageType		= ( ! isset($types[$D['2']])) ? 'unknown' : $types[$D['2']];
				$this->imageSizeStr	= $D['3'];  // string containing height and width
			}
		}
	}

	/**
	 * Set XSS Clean
	 *
	 * Enables the XSS flag so that the file that was uploaded
	 * will be run through the XSS filter.
	 *
	 * @param	bool
	 * @return	void
	 */
	public function setXssClean($flag = FALSE)
	{
		$this->xssClean = ($flag == TRUE) ? TRUE : FALSE;
	}

	/**
	 * Validate the image
	 *
	 * @return	bool
	 */
	public function isImage()
	{
		// IE will sometimes return odd mime-types during upload, so here we just standardize all
		// jpegs or pngs to the same file type.

		$png_mimes  = array('image/x-png');
		$jpeg_mimes = array('image/jpg', 'image/jpe', 'image/jpeg', 'image/pjpeg');

		if (in_array($this->fileType, $png_mimes)) {
			$this->fileType = 'image/png';
		}

		if (in_array($this->fileType, $jpeg_mimes)) {
			$this->fileType = 'image/jpeg';
		}

		$img_mimes = array(
							'image/gif',
							'image/jpeg',
							'image/png',
						);

		return (in_array($this->fileType, $img_mimes, TRUE)) ? TRUE : FALSE;
	}

	/**
	 * Verify that the filetype is allowed
	 *
	 * @return	bool
	 */
	public function isAllowedFileType($ignore_mime = FALSE)
	{
		if ($this->allowedTypes == '*') {
			return TRUE;
		}

		if (count($this->allowedTypes) == 0 || ! is_array($this->allowedTypes)) {
			$this->setError('upload_no_file_types');
			return FALSE;
		}

		$ext = strtolower(ltrim($this->fileExt, '.'));

		if (!in_array($ext, $this->allowedTypes)) {
			return FALSE;
		}

		// Images get some additional checks
		$image_types = array('gif', 'jpg', 'jpeg', 'png', 'jpe');

		if (in_array($ext, $image_types)) {
			if (getimagesize($this->fileTemp) === FALSE) {
				return FALSE;
			}
		}

		if ($ignore_mime === TRUE) {
			return TRUE;
		}

		$mime = $this->getMimesTypes($ext);

		if (is_array($mime)) {
			if (in_array($this->fileType, $mime, TRUE)) {
				return TRUE;
			}
		} else if ($mime == $this->fileType) {
		    return TRUE;
		}

		return FALSE;
	}

	/**
	 * Verify that the file is within the allowed size
	 *
	 * @return	bool
	 */
	public function isAllowedFileSize()
	{
		if ($this->maxSize != 0  &&  $this->fileSize > $this->maxSize) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Verify that the image is within the allowed width/height
	 *
	 * @return	bool
	 */
	public function isAllowedDimensions()
	{
		if (!$this->isImage()) {
			return TRUE;
		}

		if (function_exists('getimagesize')) {
			$D = @getimagesize($this->fileTemp);

			if ($this->maxWidth > 0 AND $D['0'] > $this->maxWidth) {
				return FALSE;
			}

			if ($this->maxHeight > 0 AND $D['1'] > $this->maxHeight) {
				return FALSE;
			}

			return TRUE;
		}

		return TRUE;
	}

	/**
	 * Validate Upload Path
	 *
	 * Verifies that it is a valid upload path with proper permissions.
	 *
	 *
	 * @return	bool
	 */
	public function validateUploadPath()
	{
		if ($this->uploadPath == '') {
			$this->setError('upload_no_filepath');
			return FALSE;
		}

		if (function_exists('realpath') && @realpath($this->uploadPath) !== FALSE) {
			$this->uploadPath = str_replace("\\", "/", realpath($this->uploadPath));
		}

		if (!@is_dir($this->uploadPath)) {
			$this->setError('upload_no_filepath');
			return FALSE;
		}

		if (!FileHelper::isReallyWritable($this->uploadPath)) {
			$this->setError('upload_not_writable');
			return FALSE;
		}

		$this->uploadPath = preg_replace("/(.+?)\/*$/", "\\1/",  $this->uploadPath);
		return TRUE;
	}

	/**
	 * Extract the file extension
	 *
	 * @param	string
	 * @return	string
	 */
	public function getExtension($filename)
	{
		$x = explode('.', $filename);
		return '.'.end($x);
	}

	/**
	 * Clean the file name for security
	 *
	 * @param	string
	 * @return	string
	 */
	public function cleanFileName($filename)
	{
		$bad = array(
						"<!--",
						"-->",
						"'",
						"<",
						">",
						'"',
						'&',
						'$',
						'=',
						';',
						'?',
						'/',
						"%20",
						"%22",
						"%3c",		// <
						"%253c",	// <
						"%3e",		// >
						"%0e",		// >
						"%28",		// (
						"%29",		// )
						"%2528",	// (
						"%26",		// &
						"%24",		// $
						"%3f",		// ?
						"%3b",		// ;
						"%3d"		// =
					);

		$filename = str_replace($bad, '', $filename);

		return stripslashes($filename);
	}

	/**
	 * Limit the File Name Length
	 *
	 * @param	string
	 * @return	string
	 */
	public function limitFileNameLength($filename, $length)
	{
		if (strlen($filename) < $length) {
			return $filename;
		}

		$ext = '';
		if (strpos($filename, '.') !== FALSE) {
			$parts		= explode('.', $filename);
			$ext		= '.'.array_pop($parts);
			$filename	= implode('.', $parts);
		}

		return substr($filename, 0, ($length - strlen($ext))).$ext;
	}

	/**
	 * Runs the file through the XSS clean function
	 *
	 * This prevents people from embedding malicious code in their files.
	 * I'm not sure that it won't negatively affect certain files in unexpected ways,
	 * but so far I haven't found that it causes trouble.
	 *
	 * @return	void
	 */
	public function doXssClean()
	{
		$file = $this->fileTemp;

		if (filesize($file) == 0) {
			return FALSE;
		}

		if (function_exists('memory_get_usage') && memory_get_usage() && ini_get('memory_limit') != '') {
			$current = ini_get('memory_limit') * 1024 * 1024;

			// There was a bug/behavioural change in PHP 5.2, where numbers over one million get output
			// into scientific notation.  number_format() ensures this number is an integer
			// http://bugs.php.net/bug.php?id=43053

			$new_memory = number_format(ceil(filesize($file) + $current), 0, '.', '');

			ini_set('memory_limit', $new_memory); // When an integer is used, the value is measured in bytes. - PHP.net
		}

		// If the file being uploaded is an image, then we should have no problem with XSS attacks (in theory), but
		// IE can be fooled into mime-type detecting a malformed image as an html file, thus executing an XSS attack on anyone
		// using IE who looks at the image.  It does this by inspecting the first 255 bytes of an image.  To get around this
		// CI will itself look at the first 255 bytes of an image to determine its relative safety.  This can save a lot of
		// processor power and time if it is actually a clean image, as it will be in nearly all instances _except_ an
		// attempted XSS attack.

		if (function_exists('getimagesize') && @getimagesize($file) !== FALSE) {
			if (($file = @fopen($file, 'rb')) === FALSE) {
			    // "b" to force binary
				return FALSE; // Couldn't open the file, return FALSE
			}

			$opening_bytes = fread($file, 256);
			fclose($file);

			// These are known to throw IE into mime-type detection chaos
			// <a, <body, <head, <html, <img, <plaintext, <pre, <script, <table, <title
			// title is basically just in SVG, but we filter it anyhow

			if ( ! preg_match('/<(a|body|head|html|img|plaintext|pre|script|table|title)[\s>]/i', $opening_bytes)) {
				return TRUE; // its an image, no "triggers" detected in the first 256 bytes, we're good
			} else {
				return FALSE;
			}
		}

		if (($data = @file_get_contents($file)) === FALSE) {
			return FALSE;
		}

		return Fly::app()->Security->cleanXss($data, TRUE);
	}

	/**
	 * Set an error message
	 *
	 * @param	string
	 * @return	void
	 */
	public function setError($msg)
	{
		Fly::app()->Lang->load('upload');

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
	 * Display the error message
	 *
	 * @param	string
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

	/**
	 * List of Mime Types
	 *
	 * This is a list of mime types.  We use it to validate
	 * the "allowed types" set by the developer
	 *
	 * @param	string
	 * @return	string
	 */
	public function getMimesTypes($mime)
	{
        if (empty($this->mimes)) {
            Fly::loadConfig('config.mimes', true);
            $mimes = Fly::getConfig('mimes');
            if (empty($mimes)) {
                return false;
            }
            $this->mimes = $mimes;
            unset($mimes);
        }
		return (!isset($this->mimes[$mime])) ? FALSE : $this->mimes[$mime];
	}

	/**
	 * Prep Filename
	 *
	 * Prevents possible script execution from Apache's handling of files multiple extensions
	 * http://httpd.apache.org/docs/1.3/mod/mod_mime.html#multipleext
	 *
	 * @param	string
	 * @return	string
	 */
	protected function _prepFileName($filename)
	{
		if (strpos($filename, '.') === FALSE || $this->allowedTypes == '*') {
			return $filename;
		}

		$parts		= explode('.', $filename);
		$ext		= array_pop($parts);
		$filename	= array_shift($parts);

		foreach ($parts as $part) {
			if (!in_array(strtolower($part), $this->allowedTypes) || $this->getMimesTypes(strtolower($part)) === FALSE) {
				$filename .= '.'.$part.'_';
			} else {
				$filename .= '.'.$part;
			}
		}

		$filename .= '.'.$ext;

		return $filename;
	}

	/**
	 * File MIME type
	 *
	 * Detects the (actual) MIME type of the uploaded file, if possible.
	 * The input array is expected to be $_FILES[$field]
	 *
	 * @param	array
	 * @return	void
	 */
	protected function _getfileMimeType($file)
	{
		// We'll need this to validate the MIME info string (e.g. text/plain; charset=us-ascii)
		$regexp = '/^([a-z\-]+\/[a-z0-9\-\.\+]+)(;\s.+)?$/';

		/* Fileinfo extension - most reliable method
		 *
		 * Unfortunately, prior to PHP 5.3 - it's only available as a PECL extension and the
		 * more convenient FILEINFO_MIME_TYPE flag doesn't exist.
		 */
		if (function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME);
			if (is_resource($finfo)) {
			    // It is possible that a FALSE value is returned, if there is no magic MIME database file found on the system
				$mime = @finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);

				/* According to the comments section of the PHP manual page,
				 * it is possible that this function returns an empty string
				 * for some files (e.g. if they don't exist in the magic MIME database)
				 */
				if (is_string($mime) && preg_match($regexp, $mime, $matches)) {
					$this->fileType = $matches[1];
					return;
				}
			}
		}

		/* This is an ugly hack, but UNIX-type systems provide a "native" way to detect the file type,
		 * which is still more secure than depending on the value of $_FILES[$field]['type'], and as it
		 * was reported in issue #750 (https://github.com/EllisLab/CodeIgniter/issues/750) - it's better
		 * than mime_content_type() as well, hence the attempts to try calling the command line with
		 * three different functions.
		 *
		 * Notes:
		 *	- the DIRECTORY_SEPARATOR comparison ensures that we're not on a Windows system
		 *	- many system admins would disable the exec(), shell_exec(), popen() and similar functions
		 *	  due to security concerns, hence the function_exists() checks
		 */
		if (DIRECTORY_SEPARATOR !== '\\')
		{
			$cmd = 'file --brief --mime ' . escapeshellarg($file['tmp_name']) . ' 2>&1';

			if (function_exists('exec')) {
				/* This might look confusing, as $mime is being populated with all of the output when set in the second parameter.
				 * However, we only neeed the last line, which is the actual return value of exec(), and as such - it overwrites
				 * anything that could already be set for $mime previously. This effectively makes the second parameter a dummy
				 * value, which is only put to allow us to get the return status code.
				 */
				$mime = @exec($cmd, $mime, $return_status);
				if ($return_status === 0 && is_string($mime) && preg_match($regexp, $mime, $matches)) {
					$this->fileType = $matches[1];
					return;
				}
			}

			if ( (bool) @ini_get('safe_mode') === FALSE && function_exists('shell_exec')) {
				$mime = @shell_exec($cmd);
				if (strlen($mime) > 0) {
					$mime = explode("\n", trim($mime));
					if (preg_match($regexp, $mime[(count($mime) - 1)], $matches)) {
						$this->fileType = $matches[1];
						return;
					}
				}
			}

			if (function_exists('popen')) {
				$proc = @popen($cmd, 'r');
				if (is_resource($proc)) {
					$mime = @fread($proc, 512);
					@pclose($proc);
					if ($mime !== FALSE) {
						$mime = explode("\n", trim($mime));
						if (preg_match($regexp, $mime[(count($mime) - 1)], $matches)) {
							$this->fileType = $matches[1];
							return;
						}
					}
				}
			}
		}

		// Fall back to the deprecated mime_content_type(), if available (still better than $_FILES[$field]['type'])
		if (function_exists('mime_content_type')) {
			$this->fileType = @mime_content_type($file['tmp_name']);
			if (strlen($this->fileType) > 0) {
			    // It's possible that mime_content_type() returns FALSE or an empty string
				return;
			}
		}

		$this->fileType = $file['type'];
	}

}