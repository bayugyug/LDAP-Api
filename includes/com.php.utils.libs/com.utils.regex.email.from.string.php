<?php
/**
*
*  @filaname    : utils.regex.email.from.string.php
*
*  @description : 
*
*  @version     : 0.001
*
*  @author      : bayugyug@gmail.com
*
*  @date        : 2010-01-18
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
*  @utils_regex_email_from_string
*
*  @description
*      - get MAIL part from the string
*
*  @parameters
*      - str
*      - 
*      - 
*
*  @return
*      - true/false
*              
*/
function utils_regex_email_from_string($raw='')
{

	//fmt
	debug("utils_regex_email_from_string():: params [ $raw ] ");


	//VALID EMAIL Pattern
	$mail_pat = '/(\b)?([A-Z0-9._%+-]+)@([A-Z0-9.-]+\.[A-Z]{2,6})(\b)?/i';

	//init
	$actual   = null;
	$mstatus  = false;

	//chk it
	if( @preg_match($mail_pat , $raw, $matches) )
	{
		//dmp
		$dmp     = @var_export($matches,true);
		
		//save
		$usr     = trim($matches[2]);
		$dom     = trim($matches[3]);
		
		//actual email address
		$actual  = $usr.'@'.$dom;
		$mstatus = true;
		
		debug("utils_regex_email_from_string():: MAIL VALID [ $actual => $dmp ] ");
		
		
	}
	 
	//give it back ;-)
	return array('status' => $mstatus, 
		     'mail'   => $actual, 
		     'raw'    => $raw
		     );

	
}
 

/**
*
*  @utils_regex_email_chk
*
*  @description
*      - chk MAIL part from the string
*
*  @parameters
*      - str
*      - 
*      - 
*
*  @return
*      - true/false
*              
*/
function utils_regex_email_chk($raw='')
{

	//fmt
	debug("utils_regex_email_chk():: params [ $raw ] ");


	//VALID EMAIL Pattern
	$mail_pat = "/^([A-Z0-9._%-]+)@([A-Z0-9][A-Z0-9.-]{0,255}[A-Z0-9]\.[A-Z]{2,6})$/i";

	//chk it
	if( @preg_match($mail_pat , $raw, $matches) )
	{
		//dmp
		$dmp = @var_export($matches,true);
		
		debug("utils_regex_email_chk():: MAIL MATCHES! [ $dmp ] ");

		//give it back
		return true;

	}
	
	
	debug("utils_regex_email_chk():: MAIL NOT  FOUND!");
	
	
	//give it back ;-)
	return false;
	
}


?>