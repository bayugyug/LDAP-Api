<?php
/**
|	@Filename	:	com.utils.misc.php
|	@Description	:	set of util methods
|                               
|	@Date		:	2009-04-25
|	@Ver		:	Ver 0.01
|	@Author		:	bayugyug@gmail.com
|
|
|       @Modified Date  :
|       @Modified By    :
|    
**/





/**
|
|  @debug
|
|  @description
|      - log the msg
|
|  @parameters
|      - msg
|
|  @return
|      - 
|              
*/
if(!function_exists("debug"))
{
	function debug($msg="") 
	{
		include_once('Logger/Log.php');
		//must be initialized somewhere ;=)
		global $gLoggerConf,$gDebug,$gLogDebug;

		//fmt
		$tm   = date("[Y-m-d H:i:s]");
		$buff = sprintf("DEBUG: [%15s] : %s : %s", "---MAIN---" , $tm, $msg); 

		//if want to echo on the window
		if($gDebug)
		{
			echo "$buff<hr>\n";	

		}
		//log to a file
		if($gLogDebug)
		{
			$logger = &Log::singleton('file', WEBLOG, __CLASS__, $gLoggerConf);
			$logger->log($msg);
		}  
	} 
}


?>