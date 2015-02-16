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
 * CodeIgniter Email Class
 *
 * Permits email to be sent using Mail, Sendmail, or SMTP.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/email.html
 */
class Email
{

	public $userAgent		= "FlyFramework";
	public $mailPath		= "/usr/sbin/sendmail";	    // Sendmail path
	public $protocol		= "mail";	                // mail/sendmail/smtp
	public $smtpHost		= "";		                // SMTP Server.  Example: mail.earthlink.net
	public $smtpUser		= "";		                // SMTP Username
	public $smtpPass		= "";		                // SMTP Password
	public $smtpPort		= "25";		                // SMTP Port
	public $smtpTimeout	    = 5;		                // SMTP Timeout in seconds
	public $smtpCrypto	    = "";		                // SMTP Encryption. Can be null, tls or ssl.
	public $wordWrap		= TRUE;		                // TRUE/FALSE  Turns word-wrap on/off
    public $wrapchars		= "76";		                // Number of characters to wrap at.
	public $mailType		= "text";	                // text/html  Defines email formatting
    public $charset		    = "utf-8";	                // Default char set: iso-8859-1 or us-ascii
    public $multipart		= "mixed";	                // "mixed" (in the body) or "related" (separate)
	public $altMessage	    = '';		                // Alternative message for HTML emails
	public $validate		= FALSE;	                // TRUE/FALSE.  Enables email validation
	public $priority		= "3";		                // Default priority (1 - 5)
	public $newLine		    = "\n";		                // Default newline. "\r\n" or "\n" (Use "\r\n" to comply with RFC 822)
	public $crlf			= "\n";		                // The RFC 2045 compliant CRLF for quoted-printable is "\r\n".  Apparently some servers,
									                    // even on the receiving end think they need to muck with CRLFs, so using "\n", while
									                    // distasteful, is the only thing that seems to work for all environments.
	public $sendMultipart	= TRUE;		                // TRUE/FALSE - Yahoo does not like multipart alternative, so this is an override.  Set to FALSE for Yahoo.
	public $bccBatchMode	= FALSE;	                // TRUE/FALSE  Turns on/off Bcc batch feature
	public $bccBatchSize	= 200;		                // If bccBatchMode = TRUE, sets max number of Bccs in each batch

    private $_safeMode		= FALSE;
    private	$_subject		= "";
    private	$_body			= "";
    private	$_finalbody		= "";
	private	$_altBoundary	= "";
	private	$_atcBoundary	= "";
	private $_headerStr	    = "";
	private	$_smtpConn	    = "";
	private	$_encoding		= "8bit";
	private $_IP			= FALSE;
    private	$_smtpAuth		= FALSE;
	private $_replytoFlag	= FALSE;
    private	$_debugMsg		= array();
    private	$_recipients	= array();
    private	$_ccArray		= array();
    private	$_bccArray		= array();
	private $_headers		= array();
    private	$_attachName	= array();
    private	$_attachType	= array();
    private	$_attachDisp	= array();
    private	$_protocols		= array('mail', 'sendmail', 'smtp');
    private	$_baseCharsets	= array('us-ascii', 'iso-2022-');	// 7-bit charsets (excluding language suffix)
    private	$_bitDepths	    = array('7bit', '8bit');
    private	$_priorities	= array('1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)');


	/**
	 * Constructor - Sets Email Preferences
	 *
	 * The constructor can be passed an array of config values
	 */
	public function __construct($config = array())
	{
		if (count($config) > 0) {
			$this->initialize($config);
		} else {
			$this->_smtpAuth = ($this->smtpUser == '' && $this->smtpPass == '') ? FALSE : TRUE;
			$this->_safeMode = ((boolean)@ini_get("safe_mode") === FALSE) ? FALSE : TRUE;
		}

		Fly::log('debug', "Email Class Initialized");
	}

	/**
	 * Initialize preferences
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	public function initialize($config = array())
	{
		foreach ($config as $key => $val)
		{
			if (isset($this->$key)) {
				$method = 'set'.$key;

				if (method_exists($this, $method)) {
					$this->$method($val);
				} else {
					$this->$key = $val;
				}
			}
		}
		$this->clear();

		$this->_smtpAuth = ($this->smtpUser == '' && $this->smtpPass == '') ? FALSE : TRUE;
		$this->_safeMode = ((boolean)@ini_get("safe_mode") === FALSE) ? FALSE : TRUE;

		return $this;
	}

	/**
	 * Initialize the Email Data
	 *
	 * @access	public
	 * @return	void
	 */
	public function clear($clear_attachments = FALSE)
	{
		$this->_subject		= "";
		$this->_body		= "";
		$this->_finalbody	= "";
		$this->_headerStr	= "";
		$this->_replytoFlag = FALSE;
		$this->_recipients	= array();
		$this->_ccArray	= array();
		$this->_bccArray	= array();
		$this->_headers		= array();
		$this->_debugMsg	= array();

		$this->_setHeader('User-Agent', $this->userAgent);
		$this->_setHeader('Date', $this->_setDate());

		if ($clear_attachments !== FALSE) {
			$this->_attachName = array();
			$this->_attachType = array();
			$this->_attachDisp = array();
		}

		return $this;
	}

	/**
	 * Set FROM
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function from($from, $name = '')
	{
		if (preg_match( '/\<(.*)\>/', $from, $match)) {
			$from = $match['1'];
		}

		if ($this->validate) {
			$this->validateEmail($this->strToArray($from));
		}

		// prepare the display name
		if ($name != '') {
			// only use Q encoding if there are characters that would require it
			if (!preg_match('/[\200-\377]/', $name)) {
				// add slashes for non-printing characters, slashes, and double quotes, and surround it in double quotes
				$name = '"'.addcslashes($name, "\0..\37\177'\"\\").'"';
			} else {
				$name = $this->prepQEncoding($name, TRUE);
			}
		}

		$this->_setHeader('From', $name.' <'.$from.'>');
		$this->_setHeader('Return-Path', '<'.$from.'>');

		return $this;
	}

	/**
	 * Set Reply-to
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function replyTo($replyto, $name = '')
	{
		if (preg_match( '/\<(.*)\>/', $replyto, $match)) {
			$replyto = $match['1'];
		}

		if ($this->validate) {
			$this->validateEmail($this->strToArray($replyto));
		}

		if ($name == '') {
			$name = $replyto;
		}

		if (strncmp($name, '"', 1) != 0) {
			$name = '"'.$name.'"';
		}

		$this->_setHeader('Reply-To', $name.' <'.$replyto.'>');
		$this->_replytoFlag = TRUE;

		return $this;
	}

	/**
	 * Set Recipients
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function to($to)
	{
		$to = $this->strToArray($to);
		$to = $this->cleanEmail($to);

		if ($this->validate) {
			$this->validateEmail($to);
		}

		if ($this->getProtocol() != 'mail')
		{
			$this->_setHeader('To', implode(", ", $to));
		}

		switch ($this->getProtocol())
		{
			case 'smtp'		:
				$this->_recipients = $to;
			    break;
			case 'sendmail'	:
			case 'mail'		:
				$this->_recipients = implode(", ", $to);
			    break;
		}

		return $this;
	}

	/**
	 * Set CC
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function cc($cc)
	{
		$cc = $this->strToArray($cc);
		$cc = $this->cleanEmail($cc);

		if ($this->validate) {
			$this->validateEmail($cc);
		}

		$this->_setHeader('Cc', implode(", ", $cc));

		if ($this->getProtocol() == "smtp") {
			$this->_ccArray = $cc;
		}

		return $this;
	}

	/**
	 * Set BCC
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function bcc($bcc, $limit = '')
	{
		if ($limit != '' && is_numeric($limit)) {
			$this->bccBatchMode = TRUE;
			$this->bccBatchSize = $limit;
		}

		$bcc = $this->strToArray($bcc);
		$bcc = $this->cleanEmail($bcc);

		if ($this->validate) {
			$this->validateEmail($bcc);
		}

		if (($this->getProtocol() == "smtp") OR ($this->bccBatchMode && count($bcc) > $this->bccBatchSize)) {
			$this->_bccArray = $bcc;
		} else {
			$this->_setHeader('Bcc', implode(", ", $bcc));
		}

		return $this;
	}

	/**
	 * Set Email Subject
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function subject($subject)
	{
		$subject = $this->prepQEncoding($subject);
		$this->_setHeader('Subject', $subject);
		return $this;
	}

	/**
	 * Set Body
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function message($body)
	{
		$this->_body = rtrim(str_replace("\r", "", $body));

		/* strip slashes only if magic quotes is ON
		   if we do it with magic quotes OFF, it strips real, user-inputted chars.

		   NOTE: In PHP 5.4 get_magic_quotes_gpc() will always return 0 and
			 it will probably not exist in future versions at all.
		*/
		if (!Fly::isPhp('5.4') && get_magic_quotes_gpc()) {
			$this->_body = stripslashes($this->_body);
		}

		return $this;
	}

	/**
	 * Assign file attachments
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function attach($filename, $disposition = 'attachment')
	{
		$this->_attachName[] = $filename;
		$this->_attachType[] = $this->getMimeTypes(pathinfo($filename, PATHINFO_EXTENSION));
		$this->_attachDisp[] = $disposition; // Can also be 'inline'  Not sure if it matters
		return $this;
	}

	/**
	 * Add a Header Item
	 *
	 * @access	protected
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	protected function _setHeader($header, $value)
	{
		$this->_headers[$header] = $value;
	}

	/**
	 * Convert a String to an Array
	 *
	 * @access	protected
	 * @param	string
	 * @return	array
	 */
	protected function strToArray($email)
	{
		if (!is_array($email)) {
			if (strpos($email, ',') !== FALSE) {
				$email = preg_split('/[\s,]/', $email, -1, PREG_SPLIT_NO_EMPTY);
			} else {
				$email = trim($email);
				settype($email, "array");
			}
		}
		return $email;
	}

	/**
	 * Set Multipart Value
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setAltMessage($str = '')
	{
		$this->altMessage = $str;
		return $this;
	}

	/**
	 * Set Mailtype
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setMailType($type = 'text')
	{
		$this->mailType = ($type == 'html') ? 'html' : 'text';
		return $this;
	}

	/**
	 * Set Wordwrap
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setWordWrap($wordwrap = TRUE)
	{
		$this->wordWrap = ($wordwrap === FALSE) ? FALSE : TRUE;
		return $this;
	}

	/**
	 * Set Protocol
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setProtocol($protocol = 'mail')
	{
		$this->protocol = ( ! in_array($protocol, $this->_protocols, TRUE)) ? 'mail' : strtolower($protocol);
		return $this;
	}

	/**
	 * Set Priority
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */
	public function setPriority($n = 3)
	{
		if (!is_numeric($n)) {
			$this->priority = 3;
			return;
		}

		if ($n < 1 || $n > 5) {
			$this->priority = 3;
			return;
		}

		$this->priority = $n;
		return $this;
	}

	/**
	 * Set Newline Character
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setNewLine($newline = "\n")
	{
		if ($newline != "\n" && $newline != "\r\n" && $newline != "\r") {
			$this->newLine	= "\n";
			return;
		}

		$this->newLine	= $newline;

		return $this;
	}

	/**
	 * Set CRLF
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setCrlf($crlf = "\n")
	{
		if ($crlf != "\n" && $crlf != "\r\n" && $crlf != "\r") {
			$this->crlf	= "\n";
			return;
		}

		$this->crlf	= $crlf;

		return $this;
	}

	/**
	 * Set Message Boundary
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _setBoundaries()
	{
		$this->_altBoundary = "B_ALT_".uniqid(''); // multipart/alternative
		$this->_atcBoundary = "B_ATC_".uniqid(''); // attachment boundary
	}

	/**
	 * Get the Message ID
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _getMessageId()
	{
		$from = $this->_headers['Return-Path'];
		$from = str_replace(">", "", $from);
		$from = str_replace("<", "", $from);

		return  "<".uniqid('').strstr($from, '@').">";
	}

	/**
	 * Get Mail Protocol
	 *
	 * @access	protected
	 * @param	bool
	 * @return	string
	 */
	protected function getProtocol($return = TRUE)
	{
		$this->protocol = strtolower($this->protocol);
		$this->protocol = ( ! in_array($this->protocol, $this->_protocols, TRUE)) ? 'mail' : $this->protocol;

		if ($return == TRUE) {
			return $this->protocol;
		}
	}

	/**
	 * Get Mail Encoding
	 *
	 * @access	protected
	 * @param	bool
	 * @return	string
	 */
	protected function _getEncoding($return = TRUE)
	{
		$this->_encoding = ( ! in_array($this->_encoding, $this->_bitDepths)) ? '8bit' : $this->_encoding;

		foreach ($this->_baseCharsets as $charset) {
			if (strncmp($charset, $this->charset, strlen($charset)) == 0) {
				$this->_encoding = '7bit';
			}
		}

		if ($return == TRUE) {
			return $this->_encoding;
		}
    }

	/**
	 * Get content type (text/html/attachment)
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _getContentType()
	{
		if ($this->mailType == 'html' &&  count($this->_attachName) == 0) {
			return 'html';
		} else if	($this->mailType == 'html' &&  count($this->_attachName)  > 0) {
			return 'html-attach';
		} else if ($this->mailType == 'text' &&  count($this->_attachName)  > 0) {
			return 'plain-attach';
		} else {
			return 'plain';
		}
	}

	/**
	 * Set RFC 822 Date
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _setDate()
	{
		$timezone = date("Z");
		$operator = (strncmp($timezone, '-', 1) == 0) ? '-' : '+';
		$timezone = abs($timezone);
		$timezone = floor($timezone/3600) * 100 + ($timezone % 3600 ) / 60;

		return sprintf("%s %s%04d", date("D, j M Y H:i:s"), $operator, $timezone);
	}

	/**
	 * Mime message
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _getMimeMessage()
	{
		return "This is a multi-part message in MIME format.".$this->newLine."Your email application may not support this format.";
	}

	/**
	 * Validate Email Address
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function validateEmail($email)
	{
		if (!is_array($email)) {
			$this->_setErrorMessage('lang:email_must_be_array');
			return FALSE;
		}

		foreach ($email as $val) {
			if (!$this->validEmail($val)) {
				$this->_setErrorMessage('lang:email_invalid_address', $val);
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Email Validation
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function validEmail($address)
	{
		return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? FALSE : TRUE;
	}

	/**
	 * Clean Extended Email Address: Joe Smith <joe@smith.com>
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function cleanEmail($email)
	{
		if (!is_array($email)) {
			if (preg_match('/\<(.*)\>/', $email, $match)) {
				return $match['1'];
			} else {
				return $email;
			}
		}

		$clean_email = array();

		foreach ($email as $addy) {
			if (preg_match( '/\<(.*)\>/', $addy, $match)) {
				$clean_email[] = $match['1'];
			} else {
				$clean_email[] = $addy;
			}
		}

		return $clean_email;
	}

	/**
	 * Build alternative plain text message
	 *
	 * This public function provides the raw message for use
	 * in plain-text headers of HTML-formatted emails.
	 * If the user hasn't specified his own alternative message
	 * it creates one by stripping the HTML
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _getAltMessage()
	{
		if ($this->altMessage != "")
		{
			return $this->handleWordWrap($this->altMessage, '76');
		}

		if (preg_match('/\<body.*?\>(.*)\<\/body\>/si', $this->_body, $match))
		{
			$body = $match['1'];
		}
		else
		{
			$body = $this->_body;
		}

		$body = trim(strip_tags($body));
		$body = preg_replace( '#<!--(.*)--\>#', "", $body);
		$body = str_replace("\t", "", $body);

		for ($i = 20; $i >= 3; $i--)
		{
			$n = "";

			for ($x = 1; $x <= $i; $x ++)
			{
				$n .= "\n";
			}

			$body = str_replace($n, "\n\n", $body);
		}

		return $this->handleWordWrap($body, '76');
	}

	/**
	 * Word Wrap
	 *
	 * @access	public
	 * @param	string
	 * @param	integer
	 * @return	string
	 */
	public function handleWordWrap($str, $charlim = '')
	{
		// Se the character limit
		if ($charlim == '') {
			$charlim = ($this->wrapchars == "") ? "76" : $this->wrapchars;
		}

		// Reduce multiple spaces
		$str = preg_replace("| +|", " ", $str);

		// Standardize newlines
		if (strpos($str, "\r") !== FALSE) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		// If the current word is surrounded by {unwrap} tags we'll
		// strip the entire chunk and replace it with a marker.
		$unwrap = array();
		if (preg_match_all("|(\{unwrap\}.+?\{/unwrap\})|s", $str, $matches)) {
			for ($i = 0; $i < count($matches['0']); $i++) {
				$unwrap[] = $matches['1'][$i];
				$str = str_replace($matches['1'][$i], "{{unwrapped".$i."}}", $str);
			}
		}

		// Use PHP's native public function to do the initial wordwrap.
		// We set the cut flag to FALSE so that any individual words that are
		// too long get left alone.  In the next step we'll deal with them.
		$str = wordwrap($str, $charlim, "\n", FALSE);

		// Split the string into individual lines of text and cycle through them
		$output = "";
		foreach (explode("\n", $str) as $line) {
			// Is the line within the allowed character count?
			// If so we'll join it to the output and continue
			if (strlen($line) <= $charlim) {
				$output .= $line.$this->newLine;
				continue;
			}

			$temp = '';
			while ((strlen($line)) > $charlim) {
				// If the over-length word is a URL we won't wrap it
				if (preg_match("!\[url.+\]|://|wwww.!", $line)) {
					break;
				}

				// Trim the word down
				$temp .= substr($line, 0, $charlim-1);
				$line = substr($line, $charlim-1);
			}

			// If $temp contains data it means we had to split up an over-length
			// word into smaller chunks so we'll add it back to our current line
			if ($temp != '') {
				$output .= $temp.$this->newLine.$line;
			} else {
				$output .= $line;
			}

			$output .= $this->newLine;
		}

		// Put our markers back
		if (count($unwrap) > 0) {
			foreach ($unwrap as $key => $val) {
				$output = str_replace("{{unwrapped".$key."}}", $val, $output);
			}
		}

		return $output;
	}

	/**
	 * Build final headers
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function _buildHeaders()
	{
		$this->_setHeader('X-Sender', $this->cleanEmail($this->_headers['From']));
		$this->_setHeader('X-Mailer', $this->userAgent);
		$this->_setHeader('X-Priority', $this->_priorities[$this->priority - 1]);
		$this->_setHeader('Message-ID', $this->_getMessageId());
		$this->_setHeader('Mime-Version', '1.0');
	}

	/**
	 * Write Headers as a string
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _writeHeaders()
	{
		if ($this->protocol == 'mail') {
			$this->_subject = $this->_headers['Subject'];
			unset($this->_headers['Subject']);
		}

		reset($this->_headers);
		$this->_headerStr = "";

		foreach ($this->_headers as $key => $val) {
			$val = trim($val);

			if ($val != "") {
				$this->_headerStr .= $key.": ".$val.$this->newLine;
			}
		}

		if ($this->getProtocol() == 'mail') {
			$this->_headerStr = rtrim($this->_headerStr);
		}
	}

	/**
	 * Build Final Body and attachments
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _buildMessage()
	{
		if ($this->wordWrap === TRUE && $this->mailType != 'html') {
			$this->_body = $this->handleWordWrap($this->_body);
		}

		$this->_setBoundaries();
		$this->_writeHeaders();

		$hdr = ($this->getProtocol() == 'mail') ? $this->newLine : '';
		$body = '';

		switch ($this->_getContentType()) {
			case 'plain' :

				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newLine;
				$hdr .= "Content-Transfer-Encoding: " . $this->_getEncoding();

				if ($this->getProtocol() == 'mail') {
					$this->_headerStr .= rtrim($hdr);
					$this->_finalbody = $this->_body;
				} else {
					$this->_finalbody = $hdr . $this->newLine . $this->newLine . $this->_body;
				}

				return;

			break;
			case 'html' :

				if ($this->sendMultipart === FALSE) {
					$hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newLine;
					$hdr .= "Content-Transfer-Encoding: quoted-printable";
				} else {
					$hdr .= "Content-Type: multipart/alternative; boundary=\"" . $this->_altBoundary . "\"" . $this->newLine . $this->newLine;

					$body .= $this->_getMimeMessage() . $this->newLine . $this->newLine;
					$body .= "--" . $this->_altBoundary . $this->newLine;

					$body .= "Content-Type: text/plain; charset=" . $this->charset . $this->newLine;
					$body .= "Content-Transfer-Encoding: " . $this->_getEncoding() . $this->newLine . $this->newLine;
					$body .= $this->_getAltMessage() . $this->newLine . $this->newLine . "--" . $this->_altBoundary . $this->newLine;

					$body .= "Content-Type: text/html; charset=" . $this->charset . $this->newLine;
					$body .= "Content-Transfer-Encoding: quoted-printable" . $this->newLine . $this->newLine;
				}

				$this->_finalbody = $body . $this->_prepQuotedPrintable($this->_body) . $this->newLine . $this->newLine;


				if ($this->getProtocol() == 'mail') {
					$this->_headerStr .= rtrim($hdr);
				} else {
					$this->_finalbody = $hdr . $this->_finalbody;
				}


				if ($this->sendMultipart !== FALSE) {
					$this->_finalbody .= "--" . $this->_altBoundary . "--";
				}

				return;

			break;
			case 'plain-attach' :

				$hdr .= "Content-Type: multipart/".$this->multipart."; boundary=\"" . $this->_atcBoundary."\"" . $this->newLine . $this->newLine;

				if ($this->getProtocol() == 'mail') {
					$this->_headerStr .= rtrim($hdr);
				}

				$body .= $this->_getMimeMessage() . $this->newLine . $this->newLine;
				$body .= "--" . $this->_atcBoundary . $this->newLine;

				$body .= "Content-Type: text/plain; charset=" . $this->charset . $this->newLine;
				$body .= "Content-Transfer-Encoding: " . $this->_getEncoding() . $this->newLine . $this->newLine;

				$body .= $this->_body . $this->newLine . $this->newLine;

			break;
			case 'html-attach' :

				$hdr .= "Content-Type: multipart/".$this->multipart."; boundary=\"" . $this->_atcBoundary."\"" . $this->newLine . $this->newLine;

				if ($this->getProtocol() == 'mail') {
					$this->_headerStr .= rtrim($hdr);
				}

				$body .= $this->_getMimeMessage() . $this->newLine . $this->newLine;
				$body .= "--" . $this->_atcBoundary . $this->newLine;

				$body .= "Content-Type: multipart/alternative; boundary=\"" . $this->_altBoundary . "\"" . $this->newLine .$this->newLine;
				$body .= "--" . $this->_altBoundary . $this->newLine;

				$body .= "Content-Type: text/plain; charset=" . $this->charset . $this->newLine;
				$body .= "Content-Transfer-Encoding: " . $this->_getEncoding() . $this->newLine . $this->newLine;
				$body .= $this->_getAltMessage() . $this->newLine . $this->newLine . "--" . $this->_altBoundary . $this->newLine;

				$body .= "Content-Type: text/html; charset=" . $this->charset . $this->newLine;
				$body .= "Content-Transfer-Encoding: quoted-printable" . $this->newLine . $this->newLine;

				$body .= $this->_prepQuotedPrintable($this->_body) . $this->newLine . $this->newLine;
				$body .= "--" . $this->_altBoundary . "--" . $this->newLine . $this->newLine;

			break;
		}

		$attachment = array();

		$z = 0;

		for ($i=0; $i < count($this->_attachName); $i++) {
			$filename = $this->_attachName[$i];
			$basename = basename($filename);
			$ctype = $this->_attachType[$i];

			if (!file_exists($filename)) {
				$this->_setErrorMessage('lang:email_attachment_missing', $filename);
				return FALSE;
			}

			$h  = "--".$this->_atcBoundary.$this->newLine;
			$h .= "Content-type: ".$ctype."; ";
			$h .= "name=\"".$basename."\"".$this->newLine;
			$h .= "Content-Disposition: ".$this->_attachDisp[$i].";".$this->newLine;
			$h .= "Content-Transfer-Encoding: base64".$this->newLine;

			$attachment[$z++] = $h;
			$file = filesize($filename) +1;

			if (!$fp = fopen($filename, FOPEN_READ)) {
				$this->_setErrorMessage('lang:email_attachment_unreadable', $filename);
				return FALSE;
			}

			$attachment[$z++] = chunk_split(base64_encode(fread($fp, $file)));
			fclose($fp);
		}

		$body .= implode($this->newLine, $attachment).$this->newLine."--".$this->_atcBoundary."--";


		if ($this->getProtocol() == 'mail') {
			$this->_finalbody = $body;
		} else {
			$this->_finalbody = $hdr . $body;
		}

		return;
	}

	/**
	 * Prep Quoted Printable
	 *
	 * Prepares string for Quoted-Printable Content-Transfer-Encoding
	 * Refer to RFC 2045 http://www.ietf.org/rfc/rfc2045.txt
	 *
	 * @access	protected
	 * @param	string
	 * @param	integer
	 * @return	string
	 */
	protected function _prepQuotedPrintable($str, $charlim = '')
	{
		// Set the character limit
		// Don't allow over 76, as that will make servers and MUAs barf
		// all over quoted-printable data
		if ($charlim == '' || $charlim > '76') {
			$charlim = '76';
		}

		// Reduce multiple spaces
		$str = preg_replace("| +|", " ", $str);

		// kill nulls
		$str = preg_replace('/\x00+/', '', $str);

		// Standardize newlines
		if (strpos($str, "\r") !== FALSE) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		// We are intentionally wrapping so mail servers will encode characters
		// properly and MUAs will behave, so {unwrap} must go!
		$str = str_replace(array('{unwrap}', '{/unwrap}'), '', $str);

		// Break into an array of lines
		$lines = explode("\n", $str);

		$escape = '=';
		$output = '';

		foreach ($lines as $line) {
			$length = strlen($line);
			$temp = '';

			// Loop through each character in the line to add soft-wrap
			// characters at the end of a line " =\r\n" and add the newly
			// processed line(s) to the output (see comment on $crlf class property)
			for ($i = 0; $i < $length; $i++) {
				// Grab the next character
				$char = substr($line, $i, 1);
				$ascii = ord($char);

				// Convert spaces and tabs but only if it's the end of the line
				if ($i == ($length - 1)) {
					$char = ($ascii == '32' || $ascii == '9') ? $escape.sprintf('%02s', dechex($ascii)) : $char;
				}

				// encode = signs
				if ($ascii == '61') {
					$char = $escape.strtoupper(sprintf('%02s', dechex($ascii)));  // =3D
				}

				// If we're at the character limit, add the line to the output,
				// reset our temp variable, and keep on chuggin'
				if ((strlen($temp) + strlen($char)) >= $charlim) {
					$output .= $temp.$escape.$this->crlf;
					$temp = '';
				}

				// Add the character to our temporary line
				$temp .= $char;
			}

			// Add our completed line to the output
			$output .= $temp.$this->crlf;
		}

		// get rid of extra CRLF tacked onto the end
		$output = substr($output, 0, strlen($this->crlf) * -1);

		return $output;
	}

	/**
	 * Prep Q Encoding
	 *
	 * Performs "Q Encoding" on a string for use in email headers.  It's related
	 * but not identical to quoted-printable, so it has its own method
	 *
	 * @access	public
	 * @param	str
	 * @param	bool	// set to TRUE for processing From: headers
	 * @return	str
	 */
	protected function prepQEncoding($str, $from = FALSE)
	{
		$str = str_replace(array("\r", "\n"), array('', ''), $str);

		// Line length must not exceed 76 characters, so we adjust for
		// a space, 7 extra characters =??Q??=, and the charset that we will add to each line
		$limit = 75 - 7 - strlen($this->charset);

		// these special characters must be converted too
		$convert = array('_', '=', '?');

		if ($from === TRUE) {
			$convert[] = ',';
			$convert[] = ';';
		}

		$output = '';
		$temp = '';

		for ($i = 0, $length = strlen($str); $i < $length; $i++) {
			// Grab the next character
			$char = substr($str, $i, 1);
			$ascii = ord($char);

			// convert ALL non-printable ASCII characters and our specials
			if ($ascii < 32 || $ascii > 126 || in_array($char, $convert)) {
				$char = '='.dechex($ascii);
			}

			// handle regular spaces a bit more compactly than =20
			if ($ascii == 32) {
				$char = '_';
			}

			// If we're at the character limit, add the line to the output,
			// reset our temp variable, and keep on chuggin'
			if ((strlen($temp) + strlen($char)) >= $limit) {
				$output .= $temp.$this->crlf;
				$temp = '';
			}

			// Add the character to our temporary line
			$temp .= $char;
		}

		$str = $output.$temp;

		// wrap each line with the shebang, charset, and transfer encoding
		// the preceding space on successive lines is required for header "folding"
		$str = trim(preg_replace('/^(.*)$/m', ' =?'.$this->charset.'?Q?$1?=', $str));

		return $str;
	}

	/**
	 * Send Email
	 *
	 * @access	public
	 * @return	bool
	 */
	public function send()
	{
		if ($this->_replytoFlag == FALSE) {
			$this->replyTo($this->_headers['From']);
		}

		if (( ! isset($this->_recipients) && ! isset($this->_headers['To']))  &&
			( ! isset($this->_bccArray) && ! isset($this->_headers['Bcc'])) &&
			( ! isset($this->_headers['Cc'])))
		{
			$this->_setErrorMessage('lang:email_no_recipients');
			return FALSE;
		}

		$this->_buildHeaders();

		if ($this->bccBatchMode && count($this->_bccArray) > 0) {
			if (count($this->_bccArray) > $this->bccBatchSize)
				return $this->batchBccSend();
		}

		$this->_buildMessage();

		if (!$this->_spoolEmail()) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Batch Bcc Send.  Sends groups of BCCs in batches
	 *
	 * @access	public
	 * @return	bool
	 */
	public function batchBccSend()
	{
		$float = $this->bccBatchSize -1;

		$set = "";

		$chunk = array();

		for ($i = 0; $i < count($this->_bccArray); $i++)
		{
			if (isset($this->_bccArray[$i]))
			{
				$set .= ", ".$this->_bccArray[$i];
			}

			if ($i == $float)
			{
				$chunk[] = substr($set, 1);
				$float = $float + $this->bccBatchSize;
				$set = "";
			}

			if ($i == count($this->_bccArray)-1)
			{
				$chunk[] = substr($set, 1);
			}
		}

		for ($i = 0; $i < count($chunk); $i++)
		{
			unset($this->_headers['Bcc']);
			unset($bcc);

			$bcc = $this->strToArray($chunk[$i]);
			$bcc = $this->cleanEmail($bcc);

			if ($this->protocol != 'smtp')
			{
				$this->_setHeader('Bcc', implode(", ", $bcc));
			}
			else
			{
				$this->_bccArray = $bcc;
			}

			$this->_buildMessage();
			$this->_spoolEmail();
		}
	}

	/**
	 * Unwrap special elements
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _unwrapSpecials()
	{
		$this->_finalbody = preg_replace_callback("/\{unwrap\}(.*?)\{\/unwrap\}/si", array($this, '_removeNlCallback'), $this->_finalbody);
	}

	/**
	 * Strip line-breaks via callback
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _removeNlCallback($matches)
	{
		if (strpos($matches[1], "\r") !== FALSE || strpos($matches[1], "\n") !== FALSE) {
			$matches[1] = str_replace(array("\r\n", "\r", "\n"), '', $matches[1]);
		}

		return $matches[1];
	}

	/**
	 * Spool mail to the mail server
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _spoolEmail()
	{
		$this->_unwrapSpecials();

		switch ($this->getProtocol())
		{
			case 'mail'	:

					if ( ! $this->_sendWithMail())
					{
						$this->_setErrorMessage('lang:email_send_failure_phpmail');
						return FALSE;
					}
			break;
			case 'sendmail'	:

					if ( ! $this->_sendWithSendmail())
					{
						$this->_setErrorMessage('lang:email_send_failure_sendmail');
						return FALSE;
					}
			break;
			case 'smtp'	:

					if ( ! $this->_sendWithSmtp())
					{
						$this->_setErrorMessage('lang:email_send_failure_smtp');
						return FALSE;
					}
			break;

		}

		$this->_setErrorMessage('lang:email_sent', $this->getProtocol());
		return TRUE;
	}

	/**
	 * Send using mail()
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _sendWithMail()
	{
		if ($this->_safeMode == TRUE) {
			if (!mail($this->_recipients, $this->_subject, $this->_finalbody, $this->_headerStr)) {
				return FALSE;
			} else {
				return TRUE;
			}
		} else {
			// most documentation of sendmail using the "-f" flag lacks a space after it, however
			// we've encountered servers that seem to require it to be in place.

			if (!mail($this->_recipients, $this->_subject, $this->_finalbody, $this->_headerStr, "-f ".$this->cleanEmail($this->_headers['From']))) {
				return FALSE;
			} else {
				return TRUE;
			}
		}
	}

	/**
	 * Send using Sendmail
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _sendWithSendmail()
	{
		$fp = @popen($this->mailPath . " -oi -f ".$this->cleanEmail($this->_headers['From'])." -t", 'w');

		if ($fp === FALSE || $fp === NULL) {
			// server probably has popen disabled, so nothing we can do to get a verbose error.
			return FALSE;
		}

		fputs($fp, $this->_headerStr);
		fputs($fp, $this->_finalbody);

		$status = pclose($fp);

		if (version_compare(PHP_VERSION, '4.2.3') == -1) {
			$status = $status >> 8 & 0xFF;
		}

		if ($status != 0) {
			$this->_setErrorMessage('lang:email_exit_status', $status);
			$this->_setErrorMessage('lang:email_no_socket');
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Send using SMTP
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _sendWithSmtp()
	{
		if ($this->smtpHost == '')
		{
			$this->_setErrorMessage('lang:email_no_hostname');
			return FALSE;
		}

		$this->_smtpConnect();
		$this->_smtpAuthenticate();

		$this->_sendCommand('from', $this->cleanEmail($this->_headers['From']));

		foreach ($this->_recipients as $val)
		{
			$this->_sendCommand('to', $val);
		}

		if (count($this->_ccArray) > 0)
		{
			foreach ($this->_ccArray as $val)
			{
				if ($val != "")
				{
					$this->_sendCommand('to', $val);
				}
			}
		}

		if (count($this->_bccArray) > 0)
		{
			foreach ($this->_bccArray as $val)
			{
				if ($val != "")
				{
					$this->_sendCommand('to', $val);
				}
			}
		}

		$this->_sendCommand('data');

		// perform dot transformation on any lines that begin with a dot
		$this->_sendData($this->_headerStr . preg_replace('/^\./m', '..$1', $this->_finalbody));

		$this->_sendData('.');

		$reply = $this->_getSmtpData();

		$this->_setErrorMessage($reply);

		if (strncmp($reply, '250', 3) != 0)
		{
			$this->_setErrorMessage('lang:email_smtp_error', $reply);
			return FALSE;
		}

		$this->_sendCommand('quit');
		return TRUE;
	}

	/**
	 * SMTP Connect
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function _smtpConnect()
	{
		$ssl = NULL;
		if ($this->smtpCrypto == 'ssl')
			$ssl = 'ssl://';
		$this->_smtpConn = fsockopen($ssl.$this->smtpHost,
										$this->smtpPort,
										$errno,
										$errstr,
										$this->smtpTimeout);

		if ( ! is_resource($this->_smtpConn))
		{
			$this->_setErrorMessage('lang:email_smtp_error', $errno." ".$errstr);
			return FALSE;
		}

		$this->_setErrorMessage($this->_getSmtpData());

		if ($this->smtpCrypto == 'tls')
		{
			$this->_sendCommand('hello');
			$this->_sendCommand('starttls');
			stream_socket_enable_crypto($this->_smtpConn, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT);
		}

		return $this->_sendCommand('hello');
	}

	/**
	 * Send SMTP command
	 *
	 * @access	protected
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	protected function _sendCommand($cmd, $data = '')
	{
		switch ($cmd) {
			case 'hello' :

					if ($this->_smtpAuth OR $this->_getEncoding() == '8bit') {
						$this->_sendData('EHLO '.$this->_getHostName());
                    } else {
						$this->_sendData('HELO '.$this->_getHostName());
                    }

						$resp = 250;
			break;
			case 'starttls'	:

						$this->_sendData('STARTTLS');

						$resp = 220;
			break;
			case 'from' :

						$this->_sendData('MAIL FROM:<'.$data.'>');

						$resp = 250;
			break;
			case 'to'	:

						$this->_sendData('RCPT TO:<'.$data.'>');

						$resp = 250;
			break;
			case 'data'	:

						$this->_sendData('DATA');

						$resp = 354;
			break;
			case 'quit'	:

						$this->_sendData('QUIT');

						$resp = 221;
			break;
		}

		$reply = $this->_getSmtpData();

		$this->_debugMsg[] = "<pre>".$cmd.": ".$reply."</pre>";

		if (substr($reply, 0, 3) != $resp) {
			$this->_setErrorMessage('lang:email_smtp_error', $reply);
			return FALSE;
		}

		if ($cmd == 'quit') {
			fclose($this->_smtpConn);
		}

		return TRUE;
	}

	/**
	 *  SMTP Authenticate
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _smtpAuthenticate()
	{
		if ( ! $this->_smtpAuth) {
			return TRUE;
		}

		if ($this->smtpUser == ""  AND  $this->smtpPass == "") {
			$this->_setErrorMessage('lang:email_no_smtp_unpw');
			return FALSE;
		}

		$this->_sendData('AUTH LOGIN');

		$reply = $this->_getSmtpData();

		if (strncmp($reply, '334', 3) != 0) {
			$this->_setErrorMessage('lang:email_failed_smtp_login', $reply);
			return FALSE;
		}

		$this->_sendData(base64_encode($this->smtpUser));

		$reply = $this->_getSmtpData();

		if (strncmp($reply, '334', 3) != 0) {
			$this->_setErrorMessage('lang:email_smtp_auth_un', $reply);
			return FALSE;
		}

		$this->_sendData(base64_encode($this->smtpPass));

		$reply = $this->_getSmtpData();

		if (strncmp($reply, '235', 3) != 0) {
			$this->_setErrorMessage('lang:email_smtp_auth_pw', $reply);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Send SMTP data
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _sendData($data)
	{
		if ( ! fwrite($this->_smtpConn, $data . $this->newLine)) {
			$this->_setErrorMessage('lang:email_smtp_data_failure', $data);
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Get SMTP data
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _getSmtpData()
	{
		$data = "";

		while ($str = fgets($this->_smtpConn, 512)) {
			$data .= $str;

			if (substr($str, 3, 1) == " ") {
				break;
			}
		}

		return $data;
	}

	/**
	 * Get Hostname
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _getHostName()
	{
		return (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'localhost.localdomain';
	}

	/**
	 * Get IP
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _getIp()
	{
		if ($this->_IP !== FALSE) {
			return $this->_IP;
		}

		$cip = (isset($_SERVER['HTTP_CLIENT_IP']) AND $_SERVER['HTTP_CLIENT_IP'] != "") ? $_SERVER['HTTP_CLIENT_IP'] : FALSE;
		$rip = (isset($_SERVER['REMOTE_ADDR']) AND $_SERVER['REMOTE_ADDR'] != "") ? $_SERVER['REMOTE_ADDR'] : FALSE;
		$fip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND $_SERVER['HTTP_X_FORWARDED_FOR'] != "") ? $_SERVER['HTTP_X_FORWARDED_FOR'] : FALSE;

		if ($cip && $rip)	$this->_IP = $cip;
		elseif ($rip)		$this->_IP = $rip;
		elseif ($cip)		$this->_IP = $cip;
		elseif ($fip)		$this->_IP = $fip;

		if (strpos($this->_IP, ',') !== FALSE) {
			$x = explode(',', $this->_IP);
			$this->_IP = end($x);
		}

		if ( ! preg_match( "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $this->_IP)) {
			$this->_IP = '0.0.0.0';
		}

		unset($cip);
		unset($rip);
		unset($fip);

		return $this->_IP;
	}

	/**
	 * Get Debug Message
	 *
	 * @access	public
	 * @return	string
	 */
	public function printDebugger()
	{
		$msg = '';

		if (count($this->_debugMsg) > 0) {
			foreach ($this->_debugMsg as $val) {
				$msg .= $val;
			}
		}

		$msg .= "<pre>".htmlspecialchars($this->_headerStr)."\n".htmlspecialchars($this->_subject)."\n".htmlspecialchars($this->_finalbody).'</pre>';
		return $msg;
	}

	/**
	 * Set Message
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function _setErrorMessage($msg, $val = '')
	{
		Fly::app()->Lang->load('email');

		if (substr($msg, 0, 5) != 'lang:' || FALSE === ($line = Fly::app()->Lang->line(substr($msg, 5)))) {
			$this->_debugMsg[] = str_replace('%s', $val, $msg)."<br />";
		} else {
			$this->_debugMsg[] = str_replace('%s', $val, $line)."<br />";
		}
	}

	/**
	 * Mime Types
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function getMimeTypes($ext = "")
	{
		$mimes = array(	'hqx'	=>	'application/mac-binhex40',
						'cpt'	=>	'application/mac-compactpro',
						'doc'	=>	'application/msword',
						'bin'	=>	'application/macbinary',
						'dms'	=>	'application/octet-stream',
						'lha'	=>	'application/octet-stream',
						'lzh'	=>	'application/octet-stream',
						'exe'	=>	'application/octet-stream',
						'class'	=>	'application/octet-stream',
						'psd'	=>	'application/octet-stream',
						'so'	=>	'application/octet-stream',
						'sea'	=>	'application/octet-stream',
						'dll'	=>	'application/octet-stream',
						'oda'	=>	'application/oda',
						'pdf'	=>	'application/pdf',
						'ai'	=>	'application/postscript',
						'eps'	=>	'application/postscript',
						'ps'	=>	'application/postscript',
						'smi'	=>	'application/smil',
						'smil'	=>	'application/smil',
						'mif'	=>	'application/vnd.mif',
						'xls'	=>	'application/vnd.ms-excel',
						'ppt'	=>	'application/vnd.ms-powerpoint',
						'wbxml'	=>	'application/vnd.wap.wbxml',
						'wmlc'	=>	'application/vnd.wap.wmlc',
						'dcr'	=>	'application/x-director',
						'dir'	=>	'application/x-director',
						'dxr'	=>	'application/x-director',
						'dvi'	=>	'application/x-dvi',
						'gtar'	=>	'application/x-gtar',
						'php'	=>	'application/x-httpd-php',
						'php4'	=>	'application/x-httpd-php',
						'php3'	=>	'application/x-httpd-php',
						'phtml'	=>	'application/x-httpd-php',
						'phps'	=>	'application/x-httpd-php-source',
						'js'	=>	'application/x-javascript',
						'swf'	=>	'application/x-shockwave-flash',
						'sit'	=>	'application/x-stuffit',
						'tar'	=>	'application/x-tar',
						'tgz'	=>	'application/x-tar',
						'xhtml'	=>	'application/xhtml+xml',
						'xht'	=>	'application/xhtml+xml',
						'zip'	=>	'application/zip',
						'mid'	=>	'audio/midi',
						'midi'	=>	'audio/midi',
						'mpga'	=>	'audio/mpeg',
						'mp2'	=>	'audio/mpeg',
						'mp3'	=>	'audio/mpeg',
						'aif'	=>	'audio/x-aiff',
						'aiff'	=>	'audio/x-aiff',
						'aifc'	=>	'audio/x-aiff',
						'ram'	=>	'audio/x-pn-realaudio',
						'rm'	=>	'audio/x-pn-realaudio',
						'rpm'	=>	'audio/x-pn-realaudio-plugin',
						'ra'	=>	'audio/x-realaudio',
						'rv'	=>	'video/vnd.rn-realvideo',
						'wav'	=>	'audio/x-wav',
						'bmp'	=>	'image/bmp',
						'gif'	=>	'image/gif',
						'jpeg'	=>	'image/jpeg',
						'jpg'	=>	'image/jpeg',
						'jpe'	=>	'image/jpeg',
						'png'	=>	'image/png',
						'tiff'	=>	'image/tiff',
						'tif'	=>	'image/tiff',
						'css'	=>	'text/css',
						'html'	=>	'text/html',
						'htm'	=>	'text/html',
						'shtml'	=>	'text/html',
						'txt'	=>	'text/plain',
						'text'	=>	'text/plain',
						'log'	=>	'text/plain',
						'rtx'	=>	'text/richtext',
						'rtf'	=>	'text/rtf',
						'xml'	=>	'text/xml',
						'xsl'	=>	'text/xml',
						'mpeg'	=>	'video/mpeg',
						'mpg'	=>	'video/mpeg',
						'mpe'	=>	'video/mpeg',
						'qt'	=>	'video/quicktime',
						'mov'	=>	'video/quicktime',
						'avi'	=>	'video/x-msvideo',
						'movie'	=>	'video/x-sgi-movie',
						'doc'	=>	'application/msword',
						'word'	=>	'application/msword',
						'xl'	=>	'application/excel',
						'eml'	=>	'message/rfc822'
					);

		return ( ! isset($mimes[strtolower($ext)])) ? "application/x-unknown-content-type" : $mimes[strtolower($ext)];
	}

}