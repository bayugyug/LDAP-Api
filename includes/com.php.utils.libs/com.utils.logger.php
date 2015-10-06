<?php
/*_____________________________________________________________________________
|
| @com.utils.logger.php
|______________________________________________________________________________
|
| @desc  :
|          simple logger
|
| @author: bayugyug@gmail.com
| @date  :
| @ver   : 0.01
|
|
| @Modified
|
| @author:
| @date  :
| @ver   :
| @desc  :
|
**/


define('_LOG_INFO', 1);
define('_LOG_WARN', 2);
define('_LOG_FATAL',3);
define('_LOG_ERROR',4);

class Logger
{
	//class vars
	private static $fh      ;
	public  static $logname ;

	//constructor
	private function __construct($fn)
	{
		self::$logname = $fn;
	}

	private function __clone()
	{
	}

	public function __toString()
	{
		return __CLASS__;
	}

	//instance
	public static function log($mod=0 , $msg="")
	{
		//chk filename
		if(null === self::$logname or !strlen(self::$logname))
			self::$logname = WEB_LOG;

		//chk handle
		if(null === self::$fh or false === self::$fh)
			self::$fh = @fopen(self::$logname, "a+");

		//chk mode
		switch($mod)
		{
			case _LOG_INFO:
			$mode = "INFO";
			break;

			case _LOG_WARN:
			$mode = "WARN";
			break;

			case _LOG_FATAL:
			$mode = "FATAL";
			break;

			case _LOG_ERROR:
			$mode = "ERROR";
			break;

			default:
			$mode = "DEBUG";
			break;
		}
		//fmt
		$dt  = date("[Y-m-d H:i:s]");
		$fmt = sprintf("%s[%10s]-%s\n", $dt, $mode, $msg);
		@fwrite(self::$fh , "$fmt");
		
		//free
		@fclose(self::$fh);
		self::$fh = null;
	}


	public static function info($msg="")
	{
		self::log(_LOG_INFO, $msg) ;
	}


	public static function error($msg="")
	{
		self::log(_LOG_ERROR, $msg) ;
	}

	public static function fatal($msg="")
	{
		self::log(_LOG_FATAL, $msg) ;
	}

	public static function warn($msg="")
	{
		self::log(_LOG_WARN, $msg) ;
	}

	public static function debug($msg="")
	{
		self::log(_LOG_DEBUG, $msg) ;
	}

}
?>
