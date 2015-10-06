<?php
/**
|	@Filename	:	com.utils.msqldb.class.php
|	@Description	:	PEAR MDB encapsulator
|
|	@Date		  :	2009-04-25
|	@Ver		  :	Ver 0.01
|	@Author		:	bayugyug@gmail.com
|
|
|       @Modified Date  :
|       @Modified By    :
|
**/


//misc includes
require_once ('MDB2.php');



class mySqlDb
{

	//vars
	private $_Mdb;
	private $_Dsn;
	private $_Opt;



	/**
	|
	|  @mySqlDb
	|
	|  @description
	|      - constructor
	|
	|  @parameters
	|      - options
	|
	|
	|  @return
	|      -
	|
	|
	**/
	public function mySqlDb($dsn="")
	{
		//fmt
		$opt = array(
				    'portability' => MDB2_PORTABILITY_NONE,
			    );
		self::$_Dsn = $dsn;
		self::$_Opt = $opt;
	}

      	//setter - getter
   	public function setOpt($opt=null) {  self::$_Opt = $opt;  }
   	public function setDsn($dsn=null) {  self::$_Dsn = $dsn;  }
	public function getOpt()          {  return self::$_Opt;  }
	public function getDsn()          {  return self::$_Dsn;  }

   	/**
	|
	|  @init
	|
	|  @description
	|      - init
	|
	|  @parameters
	|      -
	|
	|
	|  @return
	|      -
	|
	**/
	public function init()
	{
		//create connection
		debug("init() : start init [ self::$_Dsn ]");


		//try
		self::$_Mdb =& MDB2::factory(self::$_Dsn, self::$_Opt);

		//sanity-chk
		if (self::err(self::$_Mdb,"init::FAILED"))
			return null;


		//misc options set here
		self::$_Mdb->setFetchMode(MDB2_FETCHMODE_ASSOC);

		//more options
		self::$_Mdb->setOption('result_buffering', true);
		self::$_Mdb->setOption('multi_query',      true);

		//set here the utf-8
		$utf8[]     = " SET NAMES 'utf8' COLLATE utf8_unicode_ci ";
		$utf8[]     = " SET character_set_client='utf8'          ";
		$utf8[]     = " SET character_set_connection='utf8'      ";

		for($i=0; $i < @count($utf8) ; $i++)
		{
			$bfsql = trim($utf8[$i]);
			self::query($bfsql, "ERROR: $bfsql") ;
			debug("init() : utf-8 [ $bfsql ]");
		}


		debug("init() : done  init [ self::$_Mdb ]");

		return self::$_Mdb;

	}

   	/**
   	|
   	|  @get_dbh
   	|
   	|  @description
   	|      - get the link
   	|
   	|  @parameters
   	|      -
   	|
   	|
   	|  @return
   	|      -
   	|
	**/
	public function get_dbh()
	{

		//try
		return self::$_Mdb ;

	}

	/**
	|
	|  @err
	|
	|  @description
	|      -
	|
	|  @parameters
	|      - res
	|
	|
	|  @return
	|      - true/false
	|
	*/
	public function err($res,$more="")
	{

		//sanity check
		if (PEAR::isError($res))
		{
			//err-msg
			$buff =  $res->getMessage();

			debug("err() : ERROR [ $buff : $more ]");

			return true;

		}
		return false;
	}

	/**
	|
	|  @query
	|
	|  @description
	|      - execute the query
	|
	|  @parameters
	|      - sql
	|
	|
	|  @return
	|      - res
	|
	**/
   	public function query($sql, $err="")
   	{


   		// run the query and get a result handler
		$res =& self::$_Mdb->query($sql);

		// check if the query was executed properly
		if (self::err($res,"query::FAILED::$err"))
			return null;

		/**

		a. $res->fetchRow();
		b. $res->numRows();
		c. $res->getColumnNames();
		d. $res->numCols();

		**/
		return   $res;


   	}


	/**
	|
	| @exec
	|
	| @description
	|     - execute the query
	|
	| @parameters
	|     - sql
	|
	|
	| @return
	|     - res
	|
	**/
	public function exec($sql, $err="")
	{

		// run the query and get a result handler
		$res =& self::$_Mdb->exec($sql);

		// check if the query was executed properly
		if (self::err($res,"exec::FAILED::$err"))
			return null;

		//send result set ($res->numRows();)
		return  $res;

	}

	/**
	|
	|  @prepare
	|
	|  @description
	|      - execute the query + prepare
	|
	|  @parameters
	|      - sql
	|
	|
	|  @return
	|      - res
	|
	**/
	public function prepare($sql, $err="", $types=null, $data=null)
	{


		//prepare
		$sth =& self::$_Mdb->prepare($sql, $types);

		// check if the query was executed properly
		if (self::err($sth,"prepare::FAILED::$err"))
			return null;


		//rowse affected
		$affected  = $sth->execute($data);

		//affected
		return $affected;


	}

	/**
	|
	|  @free
	|
	|  @description
	|      - free up connection
	|
	|  @parameters
	|      - res
	|
	|
	|  @return
	|      -
	|
	**/
	public function free($res)
	{
		debug("free()");

		//free
		if($res)
		{
		   $res->free();
		}
	}

	/**
	|
	|  @close
	|
	|  @description
	|      - free up connection
	|
	|  @parameters
	|      - res
	|
	|
	|  @return
	|      -
	|
	**/
	public function close( )
	{
		debug("close()");

		//free
		if(self::$_Mdb)
		{
		   self::$_Mdb->disconnect();
		}
	}


	/**
	|
	|  @insert_id
	|
	|  @description
	|      - get the last auto-increment id
	|
	|  @parameters
	|      -
	|
	|
	|  @return
	|      -
	|
	**/
	public function insert_id($tab='',$col='id')
	{

		//prepare
		$sth =& self::$_Mdb->lastInsertID($tab, $col);

		// check if the query was executed properly
		if (self::err($sth,"insert_id::FAILED"))
			return null;

		//auto-increment id
		return $sth;

	}






   	/**
   	|
   	|  @release_lock
   	|
   	|  @description
   	|      - release lock tables
   	|
   	|  @parameters
   	|      - id
   	|
   	|
   	|  @return
   	|      -
   	|
	**/
	public function get_lock($val, $secs=10)
	{

		//fmt
		$val = addslashes($val);

	   	//exec
		self::query(
			"SELECT get_lock('$val', $secs) ",
			"get_lock() failed!"
		);
	}


	/**
	|
	|  @release_lock
	|
	|  @description
	|      - release lock tables
	|
	|  @parameters
	|      - id
	|
	|
	|  @return
	|      -
	|
	*/
	public function release_lock($val)
	{
		//fmt
		$val = addslashes($val);

	   	//exec
		self::query(
			 "SELECT release_lock('$val') ",
			 "release_lock() failed!"
		);
	}



	/**
	|
	|  @lock_table
	|
	|  @description
	|      - lock tables
	|
	|  @parameters
	|      - table
	|      - mode
	|
	|  @return
	|      -
	|
	**/
	public function lock_table($table, $mode="WRITE")
	{
		//exec
		self::query(
			 "LOCK TABLES $table $mode",
			 "lock_table() failed!"
		);
	}


	/**
	|
	|  @unlock_tables
	|
	|  @description
	|      - unlock tables
	|
	|  @parameters
	|      -
	|
	|  @return
	|      -
	|
	**/
	public function unlock_tables()
	{
		//exec
		self::query(
			 "UNLOCK TABLES",
			 "unlock_tables() failed!"
			);
	}



}

?>