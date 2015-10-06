<?php
/*_____________________________________________________________________________
|
| @com.utils.mySqlDbh.php
|______________________________________________________________________________
|
| @desc  :
|          simple mdb2 wrapper
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


class mySqlDbh2
{
	//class vars
	protected $dbh;
	
	public    $opts;
	public    $errmsg;
	
	

	/*--
	| @name    :
	| @desc    :
	|
	| @params  :
	|
	|
	| @return  :
	|
	 --*/
	public function __construct($opts=null)
	{

		$this->opts  = $opts;
	}

	/*--
	| @name    : dbh
	| @desc    : db connector
	|
	| @params  :
	|          - dsn
	|          - options
	|
	| @return  :
	|          - db connection otherwise NULL
	|
	 --*/
	public function dbh()
	{

		//connect => $dsn = 'pgsql://someuser:apasswd@localhost/thedb';
		$this->dbh = @mysql_connect($this->opts['dbhost'], 
					    $this->opts['dbuser'], 
					    $this->opts['dbpass']
					   );

		//sanity
		if (!$this->dbh)
		{
		    $this->errmsg = @mysql_error();
		    $this->err();
		    return null;
		}
		
		//chk again
		if (!@mysql_select_db($this->opts['dbname'], $this->dbh)) 
		{
		    $this->errmsg = sprintf("Could not select database [ %s ]",@mysql_error());
		    $this->err();
		    return null;
		}

		//set here the utf-8
		$utf8[]     = " SET NAMES 'utf8' COLLATE utf8_unicode_ci ";
		$utf8[]     = " SET character_set_client='utf8'          ";
		$utf8[]     = " SET character_set_connection='utf8'      ";

		for($i=0; $i < @count($utf8) ; $i++)
		{
			$bfsql = trim($utf8[$i]);
			$this->query($bfsql, "ERROR: $bfsql") ;
		}

		//give it back ;-)
		return $this->dbh;

	}


	/*--
	| @name    : query
	| @desc    : select handler
	|
	| @params  :
	|          - sql
	|          -
	|
	| @return  :
	|          - resultset otherwise NULL
	|
	 --*/
	public function query($sql=null, $err='')
	{
		// Proceed with getting some data...
		$res = @mysql_query($sql, $this->dbh);

		//give sth		
		return $res;
	}

	
	/*--
	| @name    : exec
	| @desc    : query handler that has an affected rows
	|
	| @params  :
	|          - sql
	|          -
	|
	| @return  :
	|          - resultset otherwise NULL
	|
	--*/
	public function exec($sql=null, $err='')
	{
		return $this->query($sql, $err);
	}



	/*--
	| @name    : setDsn
	|
	| @desc    : setter
	|
	| @params  :
	|          - dsn
	|          -
	|
	| @return  :
	|          -
	|
	--*/
	public function setOpts($k=null,$v=null)
	{
		$this->opts["$k"] = $v;
	}


	public function __toString()
	{
		return __CLASS__;
	}


	/*--
	| @name    : free
	|
	| @desc    : resultset free
	|
	| @params  :
	|          -
	|          -
	|
	| @return  :
	|          -
	|
	--*/
	public function free($res=null)
	{
		if($res)
		   $res = null;
	}


	/*--
	| @name    : close
	|
	| @desc    : dbh free
	|
	| @params  :
	|          -
	|          -
	|
	| @return  :
	|          -
	|
	--*/
	public function close()
	{
		
		if($this->dbh)
			@mysql_close($this->dbh);
		
		//free
		$this->dbh = false;
	}


	public function err($err=null)
	{
		//fmt-msg
		$buff = trim(sprintf("info: %s > %s" , $this->errmsg, $err)) ;

		trigger_error($buff);

		//log
		debug(__CLASS_."::err() : $buff");

		//ok
		return true;

	}


	/**
	*
	*  @insert_id
	*
	*  @description
	*      - get the last auto-increment id
	*
	*  @parameters
	*      -
	*
	*
	*  @return
	*      -
	*
	*/
	function insertId()
	{
		//prepare
		$sth = @mysql_query("SELECT LAST_INSERT_ID()", $this->dbh);

		//sanity chk
		$row = @mysql_fetch_row($sth);

		//auto-increment id
		return $row[0];

	}

	function numRows($res)
	{
		//prepare
		return @mysql_num_rows($res);
	}
	
	function updRows($res)
	{
		return @mysql_affected_rows($res);
	}

	function getArray($res)
	{
		return @mysql_fetch_array($res, MYSQL_NUM);
	}
	
	function getAssoc($res)
	{
		return @mysql_fetch_array($res, MYSQL_ASSOC);
	}
	
	function getObject($res)
	{
		return @mysql_fetch_object($res);
	}

}
?>