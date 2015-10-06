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


include_once('MDB2.php');


class mySqlDbh
{
	//class vars
	protected $dbh;
	public    static $dsn;
	public    static $opt;
	public    static $res;


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
	public function __construct($dsn=null)
	{

		$this->dsn  = $dsn;
		$this->opt  = array(
				    'result_buffering' => false,
				    'portability' 	   => MDB2_PORTABILITY_NONE,
				   );
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
	public function dbh($dsn=null, $opt=null)
	{
		//init
		if(null != $dsn) $this->dsn = $dsn;
		if(null != $opt) $this->opt = $opt;

		//connect => $dsn = 'pgsql://someuser:apasswd@localhost/thedb';
		$this->dbh =& MDB2::factory($this->dsn, $this->opt);

		//sanity
		if (self::err($this->dbh,"dbh() : init-db"))
		    return null;

		//re-init
		$this->dbh->setFetchMode(MDB2_FETCHMODE_ASSOC);

		//more options
		$this->dbh->setOption('result_buffering', true);
		$this->dbh->setOption('multi_query',      true);

		//set here the utf-8
		$utf8[]     = " SET NAMES 'utf8' COLLATE utf8_unicode_ci ";
		$utf8[]     = " SET character_set_client='utf8'          ";
		$utf8[]     = " SET character_set_connection='utf8'      ";

		for($i=0; $i < @count($utf8) ; $i++)
		{
			$bfsql = trim($utf8[$i]);
			self::query($bfsql, "ERROR: $bfsql") ;
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
		$res =& $this->dbh->query($sql);

		//sanity chk
		if (self::err($res,"query() : $err"))
		    return null;


		//hint
		if(0)
		{
			//get data
			$row = $res->fetchRow();
			//total-rows
			$res->rowCount();

			//last-insert
			$id = $this->dbh->lastInsertID($table,$field);

			/**
			a. $res->fetchRow();
			b. $res->numRows();
			c. $res->getColumnNames();
			d. $res->numCols();
			**/

		}

		//give it back ;-)
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
		// Proceed with getting some data...
		$res =& $this->dbh->exec($sql);

		//sanity chk
		if (self::err($res,"exec() : $err"))
		    return null;

		//give it back ;-)
		return $res;

	}

	/**
	*
	*  @prepare
	*
	*  @description
	*      - execute the query + prepare
	*
	*  @parameters
	*      - sql
	*
	*
	*  @return
	*      - res
	*
	*/
	function prepare($sql, $err="", $types=null, $data=null)
	{

		//prepare
		$sth =& $this->dbh->prepare($sql, $types);

		// check if the query was executed properly
		if (self::err($sth,"prepare() : $err"))
			return null;


		//rowse affected
		$affected  = $sth->execute($data);

		//affected
		return $affected;


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
	public function setDsn($dsn=null)
	{
		$this->dsn = $dsn;
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
	public function setOpts($opt=null)
	{
		$this->opt = $opt;
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
			$this->dbh->disconnect();
	}


	public function err($res=null, $err='')
	{
		//sanity chk
		if (PEAR::isError($res))
		{
			//fmt-msg
			$buff = sprintf("%s-%s-%s" , $res->getMessage() , $res->getUserinfo(), $err) ;

			trigger_error($buff);
			
			//log
			debug(__CLASS_."::err() : $buff");
			
			//ok
			return true;
		}

		//give it back ;-)
		return false;

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
	function insert_id($tab='',$col='id')
	{
		//prepare
		$sth =& $this->dbh->lastInsertID($tab, $col);

		//sanity chk
		if (self::err($sth,"insert_id() : [ $tab => $col ]"))
			return null;

		//auto-increment id
		return $sth;

	}


}
?>