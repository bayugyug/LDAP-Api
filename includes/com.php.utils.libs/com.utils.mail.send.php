<?php
/**
*
*  @filaname    : utils.mail.send.php
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
 
 

/**
*
*  @utils_mail_send
*
*  @description
*      - send mail
*
*  @parameters
*      - to
*      - subject
*      - msg
*
*  @return
*      - true/false
*              
*/
function utils_mail_send($email_from='', $email_to='', $subject='', $msg='')
{
	//fmt
	$crlf       = "\r\n";
	$token      = md5(uniqid(rand(), true));
	$svr        = "foosti.com";
	$email_to   = trim($email_to);
	
	// To send HTML mail, the Content-type header must be set
	$headers  = "MIME-Version: 1.0". $crlf;
	$headers .= "Content-type: text/html; charset=iso-8859-1". $crlf;
	$headers .= "To: $email_to". $crlf;
	$headers .= "From: $email_from" . $crlf;
	$headers .= "Reply-To: $email_from" . $crlf;
	$headers .= "Return-Path: $email_from" . $crlf;
	$headers .= "Message-ID: <$token@$svr>" . $crlf;
	$headers .= "X-Mailer: FoostiMailer v". phpversion() . $crlf;

	$ret      = @mail($email_to, $subject, $msg, $headers); 
	
	debug("utils_mail_send():: $ret [$email_to, $subject, $msg, $headers] ");

	//give it back ;-)
	return $ret;
	
}


?>