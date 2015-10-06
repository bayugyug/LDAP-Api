<?php
/**
*
*  @filaname    : utils.ucs2.utf8.converter.php
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
*  @utils_ucs2_utf8_convert
*
*  @description
*      - convert hex to utf8 chars
*
*  @parameters
*      - hex
*
*  @return
*      - result
*              
*/
function utils_ucs2_utf8_convert($hex='')
{
 	 
 	
 	//--------
 	//
	// @sample: 00480065006C006C006F0020007B00200057006F0072006C0064002062C900200021 
	//          will be converted to Hello  World 
	//          <chinese character here> !
	//--------
	
	//pack-it
	$hex  = @pack('H*', $hex);                                                                                                                           
	$res  = @iconv('UCS-2', 'UTF-8', $hex);                                                                                                           
	 
	 
	debug("utils_ucs2_utf8_convert()::raw = [ $hex => $res ]");
	 
	 
	 //give it back ;-)
	 return $res;
	 
}


/**
*
*  @utils_ucs2be_utf8_convert
*
*  @description
*      - convert hex to utf8 chars
*
*  @parameters
*      - hex
*
*  @return
*      - result
*              
*/
function utils_ucs2be_utf8_convert($hex='')
{
 	 
 	
 	//--------
 	//
	// @sample: 00480065006C006C006F0020007B00200057006F0072006C0064002062C900200021 
	//          will be converted to Hello  World 
	//          <chinese character here> !
	//--------
	
	//pack-it
	$hex  = @pack('H*', $hex);                                                                                                                           
	$res  = @iconv('UCS-2BE', 'UTF-8', $hex);                                                                                                           
	 
	 
	debug("utils_ucs2be_utf8_convert()::raw = [ $hex => $res ]");
	 
	 
	 //give it back ;-)
	 return $res;
	 
}


/**
*
*  @utils_utf8_ucs2be_convert
*
*  @description
*      - convert utf8 to hex
*
*  @parameters
*      - utf8
*
*  @return
*      - result
*              
*/
function utils_utf8_ucs2be_convert($utf8='')
{
 	 
 	//convert it
	$res = @iconv('UTF-8', 'UCS-2BE', $utf8);
	 
	debug("utils_utf8_ucs2be_convert()::raw = [ $utf8 => $res ]");
	
	//give it back ;-)
	return $res;
	 
}


/**
*
*  @utils_utf8_ucs2_convert
*
*  @description
*      - convert utf8 to hex
*
*  @parameters
*      - utf8
*
*  @return
*      - result
*              
*/
function utils_utf8_ucs2_convert($utf8='')
{
 	 
 	//convert it
	$res = @iconv('UTF-8', 'UCS-2', $utf8);
	 
	debug("utils_utf8_ucs2_convert()::raw = [ $utf8 => $res ]");
	
	//give it back ;-)
	return $res;
	 
}

?>