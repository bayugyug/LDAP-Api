<?php
/**
*
*  @filaname    : utils.io.php
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
*  @utils_io_file_save
*
*  @description
*      - save a file
*
*  @parameters
*      - file
*      - contents
*      - mode of saving
*
*  @return
*      - result
*              
*/
function utils_io_file_save($fname='', $body='', $mode = 'w')
{
 	 
 	 
 	
	debug("utils_io_file_save():: save a file = [ $fname / $body / $mode ]");

	//mode of fopen
	$mode  = @preg_match("/^(a|append)$/i", $mode) ? ('a') :  ('w');
	
	//open it
	$fh = @fopen($fname, $mode);
	if($fh)
	{
		@fwrite($fh, $body);
		@fclose($fh); 
		$is_ok  = true;
	}

	debug("utils_io_file_save():: save OK ! [ '$is_ok' ]");
	
	//give it back ;-)
	return $is_ok;
	 
}

?>