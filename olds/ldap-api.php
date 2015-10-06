<?php
//gtalk
include_once('init.php');

/**
|	@Filename	:	ldap.api.php
|	@Description:	entry point
|                               
|	@Date		:	2015-10-03
|	@Ver		:	Ver 0.01
|	@Author		:	bayugyug@gmail.com
|
|
|   @Modified Date:
|   @Modified By  :
|    
**/


class LDAP_Api{

	protected $action = API_HIT_ENTRY_SEARCH;

	/**
	* main API
	*
	*/
	function __construct($action = API_HIT_ENTRY_SEARCH)
	{
		//init
		$this->action = $action;
		$dmp = @var_export($_REQUEST,1);
		debug("PARAMS> $dmp");
	}	
		
	//filter
	protected function try_filter($ldapconn=null,$ldapfilter=null)
	{
			$reply    = $this->init_resp();
			//run
			$search  = @ldap_search($ldapconn, LDAP_RDN_USERS, $ldapfilter);  
			$found   = @ldap_count_entries($ldapconn, $search);
			$info    = @ldap_get_entries($ldapconn, $search);
			$totctr  = @intval($info["count"]);        
			$entries = array();
			for ($i = 0; $i<$info["count"]; $i++) 
			{
				  for ($ii=0; $ii<$info[$i]["count"]; $ii++)
				  {
					 $data = $info[$i][$ii];
					 $rec  = array();
					 for ($iii=0; $iii<$info[$i][$data]["count"]; $iii++) 
					 {
						 //ignore
						 if($data == "userpassword")
							 continue;
						 //log
						 debug("try_filter(): [$i] entry: $data -->: ". $info[$i][$data][$iii] );
						 $entries[]["$data"] = trim($info[$i][$data][$iii]);
					 }
				  }
			}

			//dump
			debug("try_filter(): Data for " . @var_export($entries,1). " items returned:");

			if(@count($entries) > 0)
			{
				    //fmt reply 200
					$reply['status']     = true;
					$reply['statuscode'] = HTTP_SUCCESS;
					$reply['message']    = "Lists results found.";
					$reply['result']     = array(
											"entries" => $entries,
											"found"   => $found,
											"total"   => $totctr,
										 );
			}
			else
			{
				    //fmt reply 404
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_NOT_FOUND;
					$reply['message']    = "Lists no-results found.";
					$reply['result']     = array(
										    );
			}
			//give it back pls ;-)
			return $reply;
	}


	//connection
	protected function try_ldap($flag = 1,$user='',$pass='')
	{
			$reply    = $this->init_resp();
		
			// using ldap bind
			$ldaphost = LDAP_HOST;
			$ldapport = LDAP_PORT;

			debug("try_ldap(): USER=$user; flag=$flag;");
			
			if( intval($flag) == LDAP_ADMIN_USER)
			{
				//manager-user
				$ldapuser   = LDAP_ENTRY_ROOT_USER;
				$ldappass   = LDAP_ENTRY_ROOT_PWD;
				$ldaprdn    = LDAP_ENTRY_ROOT_DN;

			}
			else
			{
				//normal user
				$ldapuser   = $user;
				$ldappass   = $pass;     // associated password
				$ldaprdn    = sprintf("uid=%s,%s",$ldapuser,LDAP_RDN_USERS);  
			}
			
			debug("try_ldap(): USER=$ldapuser; flag=$flag;");
			
			// connect to ldap server
			$ldapconn   = ldap_connect($ldaphost, $ldapport);
			if(!$ldapconn)
			{
				//fmt reply 502
				$reply['status']     = 0;
				$reply['statuscode'] = HTTP_BAD_GATEWAY;
				$reply['message']    = "Could not connect to LDAP server. [$ldaphost -> $ldapport]";
				//give it back
				return array(
					'ldapstat' => false,
					'ldapconn' => $ldapconn,
					'ldapbind' => $ldapbind,
					'ldapmesg' => $reply,
				);
			}

			// Set some ldap options for talking to 
			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

			
			//its good + binding to ldap server
			$ldapbind = @ldap_bind($ldapconn, $ldaprdn, $ldappass);
		
			// verify binding
			if (!$ldapbind) {
				//fmt reply 502
				$reply['status']     = 0;
				$reply['statuscode'] = HTTP_BAD_GATEWAY;
				$reply['message']    = "LDAP bind failed!";
				
				//give it back
				return array(
					'ldapstat' => false,
					'ldapconn' => $ldapconn,
					'ldapbind' => $ldapbind,
					'ldapmesg' => $reply,
				);
			}
			
			//give it back
			return array(
			   'ldapstat' => true,
			   'ldapconn' => $ldapconn,
			   'ldapbind' => $ldapbind,
			   'ldapmesg' => null,
			);
		
	}
	
	protected function init_resp()
	{
		return array(
				'status'      => false,
				'statuscode'  => HTTP_NOT_FOUND,
				'result'      => array(),
				'message'     => null,
				);
		
	}
	
	//msg
	protected function send_reply($reply=array())
	{
		$dmp = @var_export($reply,1);
		debug("send_reply() : INFO : [ $dmp; ]");
		echo json_encode($reply);
	}

	//chk
	protected function is_valid_email($email='')
	{
		$patt  = "/^[_A-Za-z0-9-\\+]+(\\.[_A-Za-z0-9-]+)*@[A-Za-z0-9-]+(\\.[A-Za-z0-9]+)*(\\.[A-Za-z]{2,})$/i";
		return @preg_match($patt,$email);
	}	

	
	//do=it
	public function hit()
	{
		//chk
		$dmp    = $this->action;
		debug("hit() : INFO : [ ACTION=$dmp; ]");
		
		//chk it
		switch($this->action)
		{
			case API_HIT_SIGN_IN:
				$this->do_sign_in();
				break;
			case API_HIT_ENTRY_SEARCH:
				$this->do_entry_search();
				break;
			case API_HIT_ENTRY_LIST:
				$this->do_entry_list();
				break;
			case API_HIT_ENTRY_UPDATE:
			    $this->do_entry_update();
				break;
			case API_HIT_ENTRY_ADD:
				$this->do_entry_add();
				break;
			case API_HIT_ENTRY_CHPASS:
				$this->do_entry_change_pass();
				break;				
			//notfound
			default:	
				$this->send_reply($this->notfound());
		}
	}	



	//login
	protected function do_sign_in()
	{
			//get params
			$user   = trim($_REQUEST['user']);
			$pass   = trim($_REQUEST['pass']);

			//testing
			if(1 == API_ENVT)
			{
					$user    = 'aplicant3';
					$pass    = 'abc12345';
					
					$user    = 'aplicant4';
					$pass    = 'abc123';
					
					$user    = 'aplicant5';
					$pass    = 'abc123';
			}
			
			$reply = $this->init_resp();

			//sanity check -> LISTS
			if( !strlen($user) or !strlen($pass))
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
			}

			//log
			$res = $this->try_ldap(LDAP_NORMAL_USER, $user,$pass);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
				
			}


			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = "Sign-in successful.";
			$reply['result']     = array(
								 );

			//give it back
			$this->send_reply($reply);

			//free
			if($ldapconn)
			  @ldap_free_result($ldapconn);
	
	}
	//search
	protected function do_entry_search()
	{
			//get params
			$uid   = trim($_REQUEST['user']);
			$reply = $this->init_resp();

			//testing
			if(1 == API_ENVT)
			{
				$uid   = 'aplicant5';
			}

			//sanity check -> LISTS
			if( !strlen($uid))
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
			}

			$res = $this->try_ldap(LDAP_ADMIN_USER);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
				
			}


			//get conn
			$ldapconn = $res['ldapconn'];

			//use for filtering
			$ldapfilter = sprintf("uid=%s",$uid); 

			//chk it
			$resp       = $this->try_filter($ldapconn,$ldapfilter);		
			
			//give it back
			$this->send_reply($resp);

			//free
			if($ldapconn)
			  @ldap_free_result($ldapconn);
		
	}
	
	
	//list
	protected function do_entry_list()
	{
			//get params
			$uid   = trim($_REQUEST['uid']);
			$reply = $this->init_resp();

			//testing
			if(1 == API_ENVT)
			{
				$uid   = LDAP_LIST_ALL;
			}

			//sanity check -> LISTS
			if(0)
			{
				if( $uid !== LDAP_LIST_ALL)
				{
					//fmt reply 500
					$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
					$reply['message']    = "Invalid parameters!";
					//give it back
					$this->send_reply($reply);
					return;
				}
			}


			//sign
			$res = $this->try_ldap(LDAP_ADMIN_USER);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
				
			}


			//get conn
			$ldapconn = $res['ldapconn'];

			//use for filtering
			$ldapfilter = "(cn=*)";  

			//chk it
			$resp       = $this->try_filter($ldapconn,$ldapfilter);		
			
			//give it back
			$this->send_reply($resp);

			//free
			if($ldapconn)
			  @ldap_free_result($ldapconn);
	
	}
	
	//update
	protected function do_entry_update()
	{
			//get params
			$user   = trim($_REQUEST['user']);
			$pass   = trim($_REQUEST['pass']);
			$email  = trim($_REQUEST["email"]        );
			$desc   = trim($_REQUEST["description"] );

			//init
			$reply = $this->init_resp();


			//testing
			if(1 == API_ENVT)
			{
					$user  = 'aplicant3';
					$pass  = 'abc123';
					$email = "bayugyug@gmail.com";
					$desc  = "bayugs dabis updated hehehehe,".@date('Y-m-d_H:i:s');
			}

			//sanity check -> LISTS
			if(
				!strlen($user)          or !strlen($pass)  or
				!$this->is_valid_email($email) or !strlen($desc) 
			)
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
			}

			//conn
			$res = $this->try_ldap(LDAP_NORMAL_USER,$user,$pass);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
				
			}



			//get conn
			$ldapconn                = $res['ldapconn'];
			$ldapuser                = $user;
			$ldappass                = $pass;

			// prepare data
			$info["mail"]            = $email;
			$info["description"]     = $desc;
			$ldaprdn                 = sprintf("uid=%s,%s",$ldapuser,LDAP_RDN_USERS);  

			//update entry
			$update  = ldap_modify($ldapconn, $ldaprdn, $info);
			if(!$update)
			{
					//fmt reply 403
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_FORBIDDEN;
					$reply['message']    = "Update entry failed.";
					//give it back
					$this->send_reply($reply);
					return;
			}


			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = "Update entry successful.";
			$reply['result']     = array(
								 );

			//give it back
			$this->send_reply($reply);

			//free
			if($ldapconn)
			  @ldap_free_result($ldapconn);

	
	}
	
	
	
	//update password
	protected function do_entry_change_pass()
	{
			//get params
			$user   = trim($_REQUEST['user']);
			$pass   = trim($_REQUEST['pass']);
			$newpass= trim($_REQUEST["newpass"]);

			//init
			$reply  = $this->init_resp();

			//testing
			if(1 == API_ENVT)
			{
					$user    = 'aplicant3';
					$pass    = 'abc12345';
					$newpass = 'abc123';

			}

			//sanity check -> LISTS
			if(
				!strlen($user)    or !strlen($pass)  or
				!strlen($newpass) or ($pass === $newpass)
			)
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				
				//give it back
				$this->send_reply($reply);
				return;
			}

			//conn
			$res = $this->try_ldap(LDAP_NORMAL_USER,$user,$pass);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
			}



			//get conn
			$ldapconn                = $res['ldapconn'];
			
			// prepare data
			$info["userPassword"]    = '{md5}' . base64_encode(pack('H*', md5($newpass)));
			$ldaprdn                 = sprintf("uid=%s,%s",$user,LDAP_RDN_USERS);  

			//update entry
			$update  = ldap_modify($ldapconn, $ldaprdn, $info);
			if(!$update)
			{
					//fmt reply 403
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_FORBIDDEN;
					$reply['message']    = "Update password failed.";
					
					//give it back
					$this->send_reply($reply);
					return;
			}


			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = "Update password successful.";
			$reply['result']     = array(
								 );

			//give it back
			$this->send_reply($reply);

			//free
			if($ldapconn)
			  @ldap_free_result($ldapconn);
	}
	
	//add
	protected function do_entry_add()
	{
			//get params
			$user      = trim($_REQUEST['user']);
			$pass      = trim($_REQUEST['pass']);
			$firstname = trim($_REQUEST["firstname" ]);
			$middlename= trim($_REQUEST["middlename"]);
			$lastname  = trim($_REQUEST["lastname"  ]);
			$email     = trim($_REQUEST["email"]        );
			$desc      = trim($_REQUEST["description"] );



			//init
			$reply     = $this->init_resp();


			//testing
			if(1 == API_ENVT)
			{
					$uidx      = 6;
					$user      = "aplicant$uidx";
					$pass      = "abc123";
					$firstname = "fname$uidx";
					$middlename= "m$uidx";
					$lastname  = "lname$uidx";
					$email     = "$user@ldap-test.com";
					$desc      = "desc of user $user";
			}

			//sanity check -> LISTS
			if(
				!strlen($user)    or 
				!strlen($pass)    or
				!strlen($firstname) or 
				!strlen($lastname)  or 
				!strlen($email)  or 
				!strlen($desc) 
			)
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
			}

			//conn
			$res = $this->try_ldap(LDAP_ADMIN_USER);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
				
			}



			//get conn
			$ldapconn                = $res['ldapconn'];

			/**
			objectClass: top
			objectClass: person
			objectClass: organizationalPerson
			objectClass: inetorgperson
			sn: Aplicant3
			cn: Aplicant3 Complet3
			givenName
			**/

			// prepare data
			$info["uid"]             = $user;
			$info["mail"]            = $email;
			$info["givenName"]       = $firstname;
			$info["sn"]              = $lastname;
			$info["cn"]              = trim(sprintf("%s %s %s",$firstname, $middlename,$lastname));
			$info["objectClass"][]   = USER_OBJ_CLASS_1;
			$info["objectClass"][]   = USER_OBJ_CLASS_2;
			$info["objectClass"][]   = USER_OBJ_CLASS_3;
			$info["objectClass"][]   = USER_OBJ_CLASS_4;
			$info["description"]     = $desc;
			$info["userPassword"]    = '{md5}' . base64_encode(pack('H*', md5($pass)));
			$ldaprdn                 = sprintf("uid=%s,%s",$user,LDAP_RDN_USERS);  

			//update entry
			$update  = ldap_add($ldapconn, $ldaprdn, $info);
			if(!$update)
			{
					//fmt reply 403
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_FORBIDDEN;
					$reply['message']    = "LDAP user add failed.";
					//give it back
					$this->send_reply($reply);
					return;
			}


			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = "LDAP user add successful.";
			$reply['result']     = array(
								 );

			//give it back
			$this->send_reply($reply);

			//free
			if($ldapconn)
			  @ldap_free_result($ldapconn);
	
	}

	//error
	protected function notfound()
	{
			//HTTP_UNAUTHORIZED
			return array(
					'status'      => false,
					'statuscode'  => HTTP_UNAUTHORIZED,
					'result'      => array(),
					'message'     => 'Method not found!',
			);

	}

}//class	
?>
