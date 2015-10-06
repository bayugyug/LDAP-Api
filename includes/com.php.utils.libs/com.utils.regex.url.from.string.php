<?php
/**
*
*  @filaname    : utils.regex.url.from.string.php
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
*  @utils_regex_url_from_string.php
*
*  @description
*      - get URL part from the string
*
*  @parameters
*      - url
*      - 
*      - 
*
*  @return
*      - true/false
*              
*/
function utils_regex_url_from_string($url='')
{


	//fmt
	debug("utils_regex_url_from_string():: params [ $url ] ");


	//VALID URL Pattern
	$url_pat="@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@ix";
	$url_pat= '@(https?://[\w\d:#\@%/;$()~_?\+-=\\\.&]*)@ix';
	//chk it
	if(@preg_match($url_pat , $url, $matches) )
	{
		//dmp
		$dmp = @var_export($matches,true);
		
		debug("utils_regex_url_from_string():: MATCHES! [ $dmp ] ");
		
		//save
		$actual    = trim($matches[1]);
		
		debug("utils_regex_url_from_string():: URL VALID FOUND! [ $actual ] ");
		
		//give it back
		return array('status' => true, 'url' => $actual, 'raw' => $url);

	}
	
	debug("utils_regex_url_from_string():: URL NOT  FOUND!");
	
	
	//give it back ;-)
	return array('status' => false, 'url' => $actual, 'raw' => $url);
	
}


?>