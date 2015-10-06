<?php
/**
*
*  @filaname    : utils.encrypt.decrypt.php
*
*  @description : 
*
*  @version     : 0.001
*
*  @author      : bayugyug@gmail.com
*
*  @date        : 2003-01-01
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
*  @utils_encrypt_convert
*
*  @description
*      - encrypt
*
*  @parameters
*      - str
*
*  @return
*      - result
*              
*/
function utils_encrypt_convert($str='',$bfr=2,$aftr=2)
{
 	 
 	//get token
	$tok1      = substr(md5(uniqid(rand(), true)),0,$bfr );
	$tok2      = substr(md5(uniqid(rand(), true)), -$aftr);
	
	//simple rot-13
	$res        = strtr(@base64_encode($str), '+/=', '-_,');
 	$res        = $tok1.@str_rot13($res).$tok2;
	
	
	debug("utils_encrypt_convert()::raw = [ $str => $res ]");
	 
	 
	 //give it back ;-)
	 return $res;
	 
}


/**
*
*  @utils_decrypt_convert
*
*  @description
*      - decrypt
*
*  @parameters
*      - str
*
*  @return
*      - result
*              
*/
function utils_decrypt_convert($str='',$bfr=2,$aftr=2)
{

  	
  	//simple rot-13
	$raw = substr($str, $bfr, @strlen($str)-$aftr);
	$raw = strtr($raw, '-_,', '+/=');
  	$res = @base64_decode(@str_rot13($raw));
 	 
 	debug("utils_decrypt_convert()::raw = [ $str => $res ]");
 	 
 	 //give it back ;-)
 	 return $res;
 	 
}

?>