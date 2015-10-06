<?php
/**
|	@Filename	:	com.utils.portlocker.class.php
|	@Description	:	inet socket port locker
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

 
class PortLocker
{

	//vars
	private $_Port     = 34567;
	private $_Sock;
	private $_Res;
	
	//new
	public function  PortLocker($port=34567)
	{
		//init
		$this->_Port    = (! $port ) ?  (34567)    : ($port);
		$this->_Sock    = null;
		$this->_Res     = null;
		
		
		debug("PortLocker() ::  Try to lock the port# [$this->_Port]");
		
	}

	/**
	*
	*  @lock
	*
	*  @description
	*      - lock the port
	*
	*  @parameters
	*      - 
	*
	*  @return
	*      - true/false
	*              
	*/
	public function  lock( )
	{ 
		debug("lock() :: Try to set the port! [$this->_Port] ");


		// create socket
		$this->_Sock = @socket_create(AF_INET, SOCK_STREAM, 0); 
		
		$bind        = @socket_bind($this->_Sock, '127.0.0.1', $this->_Port) ;
		
		// Start listening for connections 
		@socket_listen($this->_Sock); 
		
		
		$res = (! $this->_Sock  or ! $bind ) ? (false) : (true);


	   	return $res;
	}


	/**
	*
	*  @unlock
	*
	*  @description
	*      - unlock the port
	*
	*  @parameters
	*      - 
	*
	*  @return
	*      -  
	*              
	*/
	public function  unlock( )
	{
	
		//free
		@socket_shutdown($this->_Sock, 2);
		@socket_close($this->_Sock);
		
		debug("unlock() :: Try to reset the port! [$this->_Port] ");
 	} 
}



?>