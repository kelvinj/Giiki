<?php
/**
* A simple interface to sending email with multiple Mime Parts, e.g.
*
*  - with HTML and text
*  - with a attachments
*/
class Fu_MimeMail {

    private
        $sendto = array(),
        $sendcc = array(),
        $sendbcc = array(),
        $attachments = array(),
        $xheaders = array(), //list of message headers
        $priorities = array( '1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)' ),

        $charset = "UTF-8", // charset of plain text part of email
        $ctencoding = "8bit",
        $htmlcharset = "UTF-8", // character set of html message
        $htmlctencoding = "8bit",
        $receipt = 0, // Request receipt?
        $doencoding = 0, // Do we need to encode when we send?
        $apply_windows_bugfix = false, // apply a fix for sending from a windows machine
        $all_emails = array(), // if above is true, will contain all addies
        $check_address = true //whether to verify all email addresses (by regex)
        ;

    /*-----------------------------------------------------------------------------\
    |                           public functions                                   |
    \-----------------------------------------------------------------------------*/


    /**
     * Enables auto checking by default.
     * Also creates the unique boundary id for this message.
     *
     * @param bool $apply_windows_bugfix tells us whether to apply bug fix for windows Cc: and Bcc: headers.
     * @access private
     */
    function __construct ($apply_windows_bugfix=false) {
        $this->apply_windows_bugfix = ($apply_windows_bugfix);
        $this->boundary = "--" . md5(uniqid('seediganoofahilfaman'));
        $this->boundary_alt = "--" . md5(uniqid('seedvasten'));
    }

    /**
     * Sets auto checking to the value of $bool.
     *
     * @param bool $bool true/false
     * @access public
     */
    function auto_check ($bool) {
        $this->check_address = ($bool);
    }

    /**
     * Sets the To header for the message.
     *
     * @param string $toEmail email address
     * @param string $toName Name of recipient [optional]
     * @access public
     */
    function to ($email, $name=null) {
        return $this->_set_email('to', $email, $name);
    }

    /**
     * Clears all to emails set.
     *
     * @access public
     */
    function clear_to () {
        $this->sendto = array();
        $this->all_emails = array();
    }

    /**
     * Sets the Cc header for the message.
     *
     * @param string $ccEmail email address
     * @param string $ccName Name of recipient [optional]
     * @access public
     */
    function cc ($email, $name=null) {
        return $this->_set_email('cc', $email, $name);
    }

    /**
     * Clears all cc headers set.
     *
     * @access public
     */
    function clear_cc () {
        $this->sendcc = array();
        $this->all_emails = array();
    }

    /**
     * Sets the Bcc header for the message.
     *
     * @param string $bccEmail email address
     * @param string $bccName Name of recipient [optional]
     * @access public
     */
    function bcc ($email, $name=null) {
        return $this->_set_email('bcc', $email, $name);
    }

    /**
     * Clears all bcc headers set.
     *
     * @access public
     */
    function clear_bcc () {
        $this->sendbcc = array();
        $this->all_emails = array();
    }

    /**
     * Clears all To, Bcc and Cc headers set.
     *
     * @access public
     */
    function clear_all () {
        $this->clear_to();
        $this->clear_cc();
        $this->clear_bcc();
        $this->all_emails = array();
    }

    /**
     * Sets the From header for the message.
     *
     * @param string $fromEmail email address
     * @param string $fromName Name of recipient [optional]
     * @access public
     */
    function from ($email, $name=null) {
        if (!$email) return false;

        if ($this->check_address && !$this->validate_email($email)) {
            throw new Exception("Bad email address $stack: $email");
        }

        $this->xheaders['From'] = ($name == null) ? $email : '"'.$name.'" <'.$email.'>';
    }

    /**
     * Sets the ReplyTo header for the message.
     *
     * @param string $replytoEmail email address
     * @param string $replytoName Name of recipient [optional]
     * @access public
     */
    function reply_to ($email, $name=null) {
        if (!$email) return false;

        if ($this->check_address && !$this->validate_email($email)) {
            throw new Exception("Bad email address $stack: $email");
        }

        $this->xheaders['Reply-To'] = ($name == null) ? $email : '"'.$name.'" <'.$email.'>';
    }

    /**
     * Sets the subject for this message.
     *
     * @param string $subject
     * @access public
     */
    function subject ($subject) {
        $this->xheaders['Subject'] = strtr($subject, "\r\n", ' ');
    }

    /**
     * Sets the Body of the message.
     * If you're sending a mail with special characters, be sure to define the
     * charset.
     *  i.e. $mail->body('ce message est en français.', 'iso-8859-1');
     *
     * @param string $body plain text as the body
     * @param string $charset
     * @access public
     */
    function body ($body, $charset='') {
        $this->body = $body;

        if($charset != '') {
            $this->charset = strtolower($charset);
            if($this->charset != 'us-ascii') $this->ctencoding = '8bit';
        }
    }

    /**
     * Sets the HTML Body of the message.
     * You can you the body() function or html_body() or both just to be certain that
     * the user will be able to see the stuff you're sending.
     *
     * If you're sending a mail with special characters, be sure to define the
     * charset.
     *  i.e. $mail->html_body('ce message est en français.', 'iso-8859-1');
     *
     * @param string $htmlbody html text as the body
     * @param string $charset
     * @access public
     */
    function html_body ($htmlbody, $charset='') {
        $this->htmlbody = $htmlbody;

        if($charset != '') {
            $this->htmlcharset = strtolower($charset);
            if($this->htmlcharset != 'us-ascii') $this->htmlctencoding = '8bit';
        }
    }

    /**
     * Attach a file to the message. Defaults the disposition to 'attachment',
     * you can also use 'inline' which the client will try to show in the message.
     * Mime-types can be handled by vlibMimeMail by the list found in
     * vlibCommon/mime_types.php.
     *
     * @param string $filename full path of the file to attach
     * @param string $newname name to use as attathment name, will default to the basename of $filename
     * @param string $disposition inline or attachment
     * @param string $mimetype MIME-type of the file. defaults to 'application/octet-stream'
     * @access public
     */
    function attach ($filename, $newname = null, $disposition = 'attachment', $mimetype=null, $cid=null) {
        $name = ($newname) ? $newname : basename($filename);

        if ($mimetype == null) {
            $mimetype = $this->_get_mimetype($name);
        }

        if (!$cid) {
            srand((double)microtime()*96545624);
            $cid = md5(uniqid(rand())).'@fu_mimemail';
        }
        $this->contentIDs[] = $cid;

        $this->attachments[] = array(
            'path' => $filename,
            'name' => $name,
            'disposition' => $disposition,
            'mimetype' => $mimetype,
            'cid'   => $cid
        );

        return $cid;
    }

    /**
     * Sets the internal X-Mailer header as a way to identify that it cam from this script
     *
     * @param string name of mailer
     * @access public
     */
    function set_mailer ($name) {
        if(!empty($name)) $this->xheaders['X-Mailer'] = $name;
    }

    /**
     * Sets the Organization header for the message.
     *
     * @param string $org organization name
     * @access public
     */
    function organization ($org) {
        if(!empty($org)) $this->xheaders['Organization'] = $org;
    }

    /**
     * To request a receipt you must call this function with a true value.
     * And you must call $this->from() or $this->reply_to() before sending the mail.
     *
     * @param bool $bool true/false
     * @access public
     */
    function receipt ($bool=true) {
        $this->receipt = ($bool);
    }

    /**
     * Sets the Priority header for the message.
     * usage: $mail->priority(1); // highest setting
     *
     * @param string $priority
     * @access public
     */
    function priority ($priority) {
        if(!is_int($priority)) $priority = settype($priority, 'integer');
        if(!isset($this->priorities[$priority-1])) return false;

        $this->xheaders['X-Priority'] = $this->priorities[$priority-1];
        return true;
    }

    /**
     * Validates an email address and return true or false.
     *
     * @param string $address email address
     * @return bool true/false
     * @access public
     */
    function validate_email ($address) {
        return preg_match('/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i', $address);
    }

    /**
     * FUNCTION: send
     *
     * Sends the mail.
     *
     * @return boolean true on success, false on failure
     * @access public
     */
    function send () {
        $this->_build_mail();

        $to_str = ($this->apply_windows_bugfix) ? implode(',', $this->all_emails) : $this->xheaders['To'];
        $sendmail_from = ($this->validate_email($this->xheaders['From'])) ? "-f".$this->xheaders['From'] : null;
        return mail($to_str, $this->xheaders['Subject'], $this->full_body, $this->headers, "-f".$this->xheaders['From']);
    }

    /**
     * FUNCTION: get
     *
     * Returns the whole e-mail, headers and message. Can be used to display the
     * message in pain text or for debugging.
     *
     * @return string message
     * @access public
     */
    function get () {
        $this->_build_mail();

        $mail = 'To: '.$this->xheaders['To']."\n";
        $mail .= 'Subject: '.$this->xheaders['Subject']."\n";
        $mail .= $this->headers . "\n";
        $mail .= $this->full_body;
        return $mail;
    }


/*-----------------------------------------------------------------------------\
|                           private functions                                  |
\-----------------------------------------------------------------------------*/

    /**
     * Internal method for setting an email address
     *
     * @param string stack to add it to (to, cc, bcc)
     * @param string email address
     * @param string email name
    */
    private function _set_email ($stack, $email, $name=null) {
        if ($this->check_address && !$this->validate_email($email)) {
            throw new Exception("Bad email address $stack: $email");
        }

        if ($this->apply_windows_bugfix) {
            array_push($this->all_emails, $email);
        }

        $this->{"send$stack"}[] = ($name == null) ? $email : '"'.$name.'" <'.$email.'>';
    }

    /**
     * Proccesses all headers and attachments ready for sending.
     *
     * @access private
     */
    private function _build_mail () {
        if (empty($this->sendto) && (empty($this->body) && empty($this->htmlbody))) {
            throw new Exception("Cannot send, need more information");
        }

        // build the headers
        $this->headers = "";

        $this->xheaders['To']  = implode(',', $this->sendto);

        $cc_header_name  = ($this->apply_windows_bugfix) ? 'cc': 'Cc';
        if (!empty($this->sendcc))  $this->xheaders[$cc_header_name]  = implode(',', $this->sendcc);
        if (!empty($this->sendbcc)) $this->xheaders['Bcc'] = implode(',', $this->sendbcc);

        if($this->receipt) {
            if(isset($this->xheaders['Reply-To'])) {
                $this->xheaders['Disposition-Notification-To'] = $this->xheaders['Reply-To'];
            }
            elseif (isset($this->xheaders['From'])) {
                $this->xheaders['Disposition-Notification-To'] = $this->xheaders['From'];
            }
        }

        if($this->charset != '') {
            $this->xheaders['Mime-Version'] = '1.0';
            $this->xheaders['Content-Type'] = 'text/plain; charset='.$this->charset;
            $this->xheaders['Content-Transfer-Encoding'] = $this->ctencoding;
        }

        if (!$this->xheaders['X-Mailer']) {
            $this->xheaders['X-Mailer'] = 'King-Fu MimeMail';
        }

        // setup the body ready for sending
        $this->_set_body();

        foreach ($this->xheaders as $head => $value) {
            $rgx = ($this->apply_windows_bugfix) ? 'Subject' : 'Subject|To'; // don't strip out To header for bugfix
            if (!preg_match('/^'.$rgx.'$/i', $head)) $this->headers .= $head.': '.strtr($value, "\r\n", ' ')."\n";
        }
    }

    /**
     * sets the body to be used in a mail.
     *
     * @access private
     */
    private function _set_body () {
        // do we need to encode??
        $encode = (empty($this->htmlbody) && empty($this->attachments)) ? false : true;

        if ($encode) {
            $this->full_body = "This is a multi-part message in MIME format.\n\n";
            $this->full_body .= '--'.$this->boundary."\nContent-Type: multipart/alternative;\n\tboundary=\"".$this->boundary_alt."\"\n\n\n";
            $body_boundary = $this->boundary_alt;
            $this->xheaders['Content-Type'] = "multipart/mixed;\n\tboundary=\"".$this->boundary.'"';

            if (!empty($this->body)) {
                $this->full_body .= '--'.$body_boundary."\nContent-Type: text/plain; charset=".$this->charset."\nContent-Transfer-Encoding: ".$this->ctencoding."\n\n".$this->body."\n\n";
            }
            if (!empty($this->htmlbody)) {
                $this->full_body .= '--'.$body_boundary."\nContent-Type: text/html; charset=".$this->charset."\nContent-Transfer-Encoding: ".$this->ctencoding."\n\n".$this->htmlbody."\n\n";
            }

            $this->full_body .= '--'.$body_boundary."--\n\n";

            if (!empty($this->attachments)) {
                $this->_build_attachments();
            }
            $this->full_body .= '--'.$this->boundary.'--'; // ends the last boundary
        }
        // else we just send plain text.
        else {
            if (!empty($this->body)) {
                $this->full_body = $this->body;
            }
            else {
                throw new Exception("Cannot send, no body");
            }
        }
    }

    /**
     * FUNCTION: _build_attachments
     *
     * Checks and encodes all attachments.
     *
     * @access private
     */
    private function _build_attachments () {
        $sep = chr(13).chr(10);
        $ata = array();
        $k=0;

        foreach ((array) $this->attachments as $att) {
            $path = $att['path'];
            $name = $att['name'];
            $mimetype = $att['mimetype'];
            $disposition = $att['disposition'];
            $contentID = $att['cid'];
            $data = file_get_contents($path);

            $subhdr = '--'.$this->boundary."\nContent-type: ".$mimetype.";\n\tname=\"".$name."\"\nContent-Transfer-Encoding: base64\nContent-Disposition: ".$disposition.";\n\tfilename=\"".$name."\"\n";
            if ($contentID) $subhdr .= 'Content-ID: <'.$contentID.">\n";
            $ata[$k++] = $subhdr;
            $ata[$k++] = chunk_split(base64_encode($data))."\n\n";
        }
        $this->full_body .= "\n".implode($sep, $ata);
    }


    private private function _get_mimetype ($filename) {
        $mimetypes = array(
            'doc'     => 'application/msword',
            'xls'     => 'application/vnd.ms-excel',
            'ppt'     => 'application/vnd.ms-powerpoint',
            'docm'    => 'application/vnd.ms-word.document.macroEnabled.12',
            'docx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotm'    => 'application/vnd.ms-word.template.macroEnabled.12',
            'dotx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'potm'    => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
            'potx'    => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppam'    => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
            'ppsm'    => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'ppsx'    => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'pptm'    => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'pptx'    => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xlam'    => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'xlsb'    => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'xlsm'    => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xlsx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltm'    => 'application/vnd.ms-excel.template.macroEnabled.12',
            'xltx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'pdf'     => 'application/pdf',
            'smi'     => 'application/smil',
            'smil'    => 'application/smil',
            'bcpio'   => 'application/x-bcpio',
            'vcd'     => 'application/x-cdlink',
            'pgn'     => 'application/x-chess-pgn',
            'cpio'    => 'application/x-cpio',
            'csh'     => 'application/x-csh ',
            'dcr'     => 'application/x-director',
            'dir'     => 'application/x-director',
            'dxr'     => 'application/x-director',
            'dvi'     => 'application/x-dvi',
            'spl'     => 'application/x-futuresplash',
            'gtar'    => 'application/x-gtar',
            'gz'      => 'application/x-gzip',
            'hdf'     => 'application/x-hdf',
            'js'      => 'application/x-javascript',
            'skp'     => 'application/x-koan',
            'skd'     => 'application/x-koan',
            'skt'     => 'application/x-koan',
            'skm'     => 'application/x-koan',
            'latex'   => 'application/x-latex',
            'nc'      => 'application/x-netcdf',
            'cdf'     => 'application/x-netcdf',
            'sh'      => 'application/x-sh',
            'shar'    => 'application/x-shar',
            'swf'     => 'application/x-shockwave-flash',
            'sit'     => 'application/x-stuffit',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc'  => 'application/x-sv4crc',
            'tar'     => 'application/x-tar',
            'tcl'     => 'application/x-tcl',
            'tex'     => 'application/x-tex',
            'texinfo' => 'application/x-texinfo',
            'texi'    => 'application/x-texinfo',
            'man'     => 'application/x-troff-man',
            'me'      => 'application/x-troff-me',
            'ms'      => 'application/x-troff-ms',
            'ustar'   => 'application/x-ustar',
            'src'     => 'application/x-wais-source',
            'xml'     => 'application/xml',
            'zip'     => 'application/zip',
            'au'      => 'audio/basic',
            'snd'     => 'audio/basic',
            'mid'     => 'audio/midi',
            'midi'    => 'audio/midi',
            'kar'     => 'audio/midi',
            'mpga'    => 'audio/mpeg',
            'mp2'     => 'audio/mpeg',
            'mp3'     => 'audio/mpeg',
            'aif'     => 'audio/x-aiff',
            'aiff'    => 'audio/x-aiff',
            'aifc'    => 'audio/x-aiff',
            'ram'     => 'audio/x-pn-realaudio',
            'rm'      => 'audio/x-pn-realaudio',
            'rpm'     => 'audio/x-pn-realaudio-plugin',
            'ra'      => 'audio/x-realaudio',
            'wav'     => 'audio/x-wav',
            'pdb'     => 'chemical/x-pdb',
            'xyz'     => 'chemical/x-xyz',
            'bmp'     => 'image/bmp',
            'gif'     => 'image/gif',
            'ief'     => 'image/ief',
            'jpeg'    => 'image/jpeg',
            'jpg'     => 'image/jpeg',
            'jpe'     => 'image/jpeg',
            'png'     => 'image/png',
            'tiff'    => 'image/tiff',
            'tif'     => 'image/tiff',
            'wbmp'    => 'image/vnd.wap.wbmp',
            'ras'     => 'image/x-cmu-raster',
            'pnm'     => 'image/x-portable-anymap',
            'pbm'     => 'image/x-portable-bitmap',
            'pgm'     => 'image/x-portable-graymap',
            'ppm'     => 'image/x-portable-pixmap',
            'rgb'     => 'image/x-rgb',
            'xbm'     => 'image/x-xbitmap',
            'xpm'     => 'image/x-xpixmap',
            'xwd'     => 'image/x-xwindowdump',
            'igs'     => 'model/iges',
            'iges'    => 'model/iges',
            'msh'     => 'model/mesh',
            'mesh'    => 'model/mesh',
            'silo'    => 'model/mesh',
            'wrl'     => 'model/vrml',
            'vrml'    => 'model/vrml',
            'css'     => 'text/css',
            'html'    => 'text/html',
            'htm'     => 'text/html',
            'asc'     => 'text/plain',
            'txt'     => 'text/plain',
            'rtx'     => 'text/richtext',
            'rtf'     => 'text/rtf',
            'sgml'    => 'text/sgml',
            'sgm'     => 'text/sgml',
            'tsv'     => 'text/tab-separated-values',
            'wml'     => 'text/vnd.wap.wml',
            'wmls'    => 'text/vnd.wap.wmlscript',
            'etx'     => 'text/x-setext',
            'xml'     => 'text/xml',
            'mpeg'    => 'video/mpeg',
            'mpg'     => 'video/mpeg',
            'mpe'     => 'video/mpeg',
            'qt'      => 'video/quicktime',
            'mov'     => 'video/quicktime',
            'avi'     => 'video/x-msvideo',
            'movie'   => 'video/x-sgi-movie',
            'ice'     => 'x-conference/x-cooltalk',

            'default' => 'application/octet-stream' // this is default for extensions not in list
        );

        if (empty($filename)) return false;

        $extarr = explode('.', $filename);
        $ext = array_pop($extarr);

        if (!empty($mimetypes[$ext])) {
            return $mimetypes[$ext];
        }
        else {
            return $mimetypes['default'];
        }
    }
}