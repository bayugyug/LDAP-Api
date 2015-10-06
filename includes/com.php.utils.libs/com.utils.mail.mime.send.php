<?php
/**
*
*  @filaname    : utils.mail.mime.send.php
*
*  @description : 
*
*  @version     : 0.001
*
*  @author      : bayugyug@gmail.com
*
*  @date        : 2009-10-07
*
*
*
*  @modified    :
*  @modified-by :
*  @modified-ver:
*
*              
*/

//needed ;-)
include_once('Mail.php');
include_once('Mail/mime.php');


/**
*
*  @utils_mail_mime_send
*
*  @description
*      - send mime email
*
*  @parameters
*      - data
*      -  
*      -  
*
*  @return
*      - result
*              
*/
function utils_mail_mime_send($pdata=null)
{
 	 
 	//dmp
 	$dmp  = @var_export($pdata, true);
	debug("utils_mail_mime_send():: DMP [ $dmp ]");

	//fmt
	$mfrom      = trim($pdata['from']);
	$mto        = trim($pdata['to'  ]);
	$msubject   = trim($pdata['subject'  ]);
	$text       = trim($pdata['text']);
	$html       = trim($pdata['html']);
	
	//attachment
	$attach     = $pdata['attach'];
	
	//misc hdrs
	$crlf       = "\n";
	$hdrs       = array(
			      'From'    => $mfrom,
			      'Subject' => $msubject
			   );

	//mime
	$mime = new Mail_mime($crlf);
	
	$mime->setTXTBody($text);
	$mime->setHTMLBody($html);
	
	//attach if can
	for($i=0; $i < @count($attach); $i++)
	{
		//save
		$afile= $attach[$i];
		
		//chk
		$aret = $mime->addAttachment($afile);
		
		debug("utils_mail_mime_send():: Attach! [ #$afile => $aret ]");
	}
	
	
	//do not ever try to call these lines in reverse order
	$body = $mime->get();
	$hdrs = $mime->headers($hdrs);
	
	$mail =& Mail::factory('mail');
	$is_ok= $mail->send($mto, $hdrs, $body);
	
	debug("utils_mail_mime_send():: Sent! [ #$is_ok ]");
	
	//give it back ;-)
	return $is_ok;
	 
}

?>