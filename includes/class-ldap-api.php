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
	
	//mapping
	var    $MapGroup = null;
	
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
		
		//group
		$this->MapGroup = new LDAP_Groups_Api;
	}	

	//do=it
	public function hit($act=null)
	{
		if($act != null)
		{
			$this->action = $act;
		}
		//chk
		$dmp    = $this->action;
		
		debug("hit() : INFO : [ ACTION=$dmp; ]");
		
		//can filter the _POST here ;-)
		if(!isset($_POST))
		{
			debug("hit() : INFO : Warning no POST found!");
			$this->send_reply($this->notfound());
			return;
		}
		
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
			case API_HIT_ENTRY_MEMBER:
				$this->do_entry_member();
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
			case API_HIT_ENTRY_RESTAPI:
			    debug("hit() : INFO : will use the REST-API-ROUTING!");
				break;
			//notfound
			default:	
				$this->send_reply($this->notfound());
		}
	}	


	
	//filter
	protected function try_filter($ldapconn=null,$ldapfilter=null,$ldaprdn,$allcn=0)
	{
			$reply    = $this->init_resp();
			
			//member
			$memberof = array();
			
			//run
			$search  = @ldap_search($ldapconn,$ldaprdn, $ldapfilter);  
			$found   = @ldap_count_entries($ldapconn, $search);
			$info    = @ldap_get_entries($ldapconn, $search);
			$totctr  = @intval($info["count"]);        
			$entries = array();
			for ($i = 0; $i<$info["count"]; $i++) 
			{
				  $row = array();
				  $cns = array();
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
						 $rec["$data"] = trim($info[$i][$data][$iii]);
						 
						 //all-cns
						 if($data == 'cn')
						 {
							$memberof[] = trim($info[$i][$data][$iii]);
							$cns[]      = trim($info[$i][$data][$iii]);
						 }
					 }
					 
					 //add
					 $row[]        = $rec;
				  }
				
				  //main
				  if(@count($cns))
				    $row[]     = array('cns' => $cns );
				
				  $entries[] = $row;
				 debug("try_filter(): [$i] =>  " . @var_export($rec,1));
			}

			//dump
			if(@count($entries) > 0)
			{
				    //fmt reply 200
					$reply['status']     = true;
					$reply['statuscode'] = HTTP_SUCCESS;
					$reply['message']    = "Lists results found.";
					$reply['result']     = array(
											"entries" => $entries,
											"found"   => $totctr,
											"member"  => $memberof,
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
	protected function try_ldap($flag = LDAP_NORMAL_USER,$user='',$pass='',$rdn='')
	{
			$reply    = $this->init_resp();
		
			// using ldap bind
			$ldaphost = LDAP_HOST;
			$ldapport = LDAP_PORT;

			debug("try_ldap(): USER=$user; flag=$flag; rdn=$rdn;");
			
			if( intval($flag) == LDAP_ADMIN_USER)
			{
				//manager-user
				$ldapuser   = LDAP_ENTRY_ROOT_USER;
				$ldappass   = LDAP_ENTRY_ROOT_PWD;
				$ldaprdn    = LDAP_ENTRY_ROOT_DN;
				if(strlen($rdn))
						$ldaprdn = $rdn;
			}
			else
			{
				//normal user
				$ldapuser   = $user;
				$ldappass   = $pass;     // associated password
				$ldaprdn    = sprintf("uid=%s,%s",$ldapuser,$rdn);  
			}
			
			
			debug("try_ldap(): USER=$user; flag=$flag; USER-RDN=$ldaprdn;");
			
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
			debug("try_ldap(): ldap_bind(CONN=$ldapconn, DN=$ldaprdn, UID=$ldapuser) ..........");
			
			// verify binding
			if (!$ldapbind) {
				//fmt reply 502
				$reply['status']     = 0;
				$reply['statuscode'] = HTTP_BAD_GATEWAY;
				$reply['message']    = "LDAP bind failed!";
				$reply['error']      = $this->fmt_err_msg($ldapconn);
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
	public function send_reply($reply=array())
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

	
	
	//login
	protected function do_sign_in()
	{
			//get params
			$user   = trim($_REQUEST['user']);
			$pass   = trim($_REQUEST['pass']);
			$cn     = strtolower(trim($_REQUEST["company"]));
			
			$reply = $this->init_resp();

			//sanity check -> LISTS
			if( !strlen($user) or !strlen($pass) or ! strlen($cn))
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
			}

					
			//get map
			$map = $this->MapGroup->get($cn);
			
			//chk if invalid group -> 404::HTTP_NOT_FOUND
			if(! $this->MapGroup->is_group_valid($cn) or null == $map)
			{
				//fmt reply 404
				$reply['statuscode'] = HTTP_NOT_FOUND;
				$reply['message']    = "CN not found!";
				//give it back
				$this->send_reply($reply);
				return;
				
			}

			//log
			$tmf = @var_export($map,1);
			debug("map> $tmf");
			
			//log
			$res = $this->try_ldap(LDAP_NORMAL_USER, $user,$pass,$map['rdn']);
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
			$cn     = strtolower(trim($_REQUEST["company"]));
			$reply = $this->init_resp();

			//testing
			if(1 == API_ENVT)
			{
				$uid   = 'aplicant5';
			}

			//sanity check -> LISTS
			if( !strlen($uid) or !strlen($cn))
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
			}


			//get map
			$map = $this->MapGroup->get($cn);

			//chk if invalid group -> 404::HTTP_NOT_FOUND
			if(! $this->MapGroup->is_group_valid($cn) or null == $map)
			{
				//fmt reply 404
				$reply['statuscode'] = HTTP_NOT_FOUND;
				$reply['message']    = "CN not found!";
				//give it back
				$this->send_reply($reply);
				return;
				
			}

			//log
			$tmf = @var_export($map,1);
			$cn  = $map['cn'];
			

			//default
			$cnstr = LDAP_ENTRY_ROOT_DN;
			
			debug("map> $tmf ; CN=$cn -> $cnstr;");
			
			//connect
			$res = $this->try_ldap(LDAP_ADMIN_USER);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
				
			}


			//get conn
			$ldapconn = $res['ldapconn'];
			//use for filtering
			$ldapfilter = sprintf("(&(uid=%s))",$uid); 
			if($map['cn'] !== strtolower(LDAP_RDN_GROUP))
			{
				$ldapfilter = sprintf("(&(cn=%s)(uid=%s))",$map['cn'],$uid); 
			}
			

			debug("filter> $ldapfilter");
			
			//chk it
			$resp       = $this->try_filter($ldapconn,$ldapfilter,$map['rdn']);		
			
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
			$cn    = trim($_REQUEST['company']);
			$reply = $this->init_resp();

			
			//sanity check -> must be valid CN
			if( !strlen($cn))
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
			}

			//sign
			$res = $this->try_ldap(LDAP_ADMIN_USER);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
				
			}

			//chk it
			if(strlen($cn))
			{
					//get map
					$map = $this->MapGroup->get($cn);
					
					//chk if invalid group -> 404::HTTP_NOT_FOUND
					if(! $this->MapGroup->is_group_valid($cn) or null == $map)
					{
						//fmt reply 404
						$reply['statuscode'] = HTTP_NOT_FOUND;
						$reply['message']    = "CN not found!";
						//give it back
						$this->send_reply($reply);
						return;
						
					}

					//log
					$tmf = @var_export($map,1);
					$cn  = $map['cn'];
					debug("map> $tmf ; CN=$cn");
			}
			else
			{
					$cn = '*';
					debug("map> CN=$cn");
			}

			//get conn
			$ldapconn = $res['ldapconn'];

			//use for filtering
			$ldapfilter = sprintf("(cn=%s)",$cn);  

			//chk it
			$resp       = $this->try_filter($ldapconn,$ldapfilter,$map['rdn'],1);		
			
			//overwrite
			$resp['result']['member'] = array();
			
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
		    $cn     = strtolower(trim($_REQUEST["company"]));
			
			//init
			$reply = $this->init_resp();


			//sanity check -> LISTS
			if(
				!strlen($user)  or 
				!strlen($pass)  or
				!$this->is_valid_email($email) or 
				!strlen($cn)  or
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

			//get map
			$map = $this->MapGroup->get($cn);
			
			//chk if invalid group -> 404::HTTP_NOT_FOUND
			if(! $this->MapGroup->is_group_valid($cn) or null == $map)
			{
				//fmt reply 404
				$reply['statuscode'] = HTTP_NOT_FOUND;
				$reply['message']    = "CN not found!";
				//give it back
				$this->send_reply($reply);
				return;
				
			}

			//log
			$tmf = @var_export($map,1);
			debug("map> $tmf");
			
			//conn
			$res = $this->try_ldap(LDAP_NORMAL_USER,$user,$pass,$map['rdn']);
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

			//not the default
			if($map['cn'] !== strtolower(LDAP_RDN_GROUP))
			{
				$ldaprdn             = sprintf("uid=%s,%s",$user,$map['rdn']);  
			}

			//update entry
			$update  = ldap_modify($ldapconn, $ldaprdn, $info);
			if(!$update)
			{
					//fmt reply 403
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_FORBIDDEN;
					$reply['message']    = "Update entry failed.";
					$reply['error']      = $this->fmt_err_msg($ldapconn);
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
			$cn     = strtolower(trim($_REQUEST["company"]));
			
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
				!strlen($user)    or 
				!strlen($pass)    or
				!strlen($cn)      or
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

			//get map
			$map = $this->MapGroup->get($cn);
			
			//chk if invalid group -> 404::HTTP_NOT_FOUND
			if(! $this->MapGroup->is_group_valid($cn) or null == $map)
			{
				//fmt reply 404
				$reply['statuscode'] = HTTP_NOT_FOUND;
				$reply['message']    = "CN not found!";
				//give it back
				$this->send_reply($reply);
				return;
				
			}

			//log
			$tmf = @var_export($map,1);
			debug("map> $tmf");
			

			
			//conn
			$res = $this->try_ldap(LDAP_NORMAL_USER,$user,$pass,$map['rdn']);
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
			
			//not the default
			if($map['cn'] !== strtolower(LDAP_RDN_GROUP))
			{
				$ldaprdn            = sprintf("uid=%s,%s",$user,$map['rdn']);  
			}
			
			debug("CHANGE-PASS> DN=$ldaprdn;");
			
			//update entry
			$update  = ldap_modify($ldapconn, $ldaprdn, $info);
			if(!$update)
			{
					//fmt reply 403
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_FORBIDDEN;
					$reply['message']    = "Update password failed.";
					$reply['error']      = $this->fmt_err_msg($ldapconn);
					
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
			$cn        = strtolower(trim($_REQUEST["company"]));



			//init
			$reply     = $this->init_resp();

			//sanity check -> LISTS
			if(
				!strlen($user)    or 
				!strlen($pass)    or
				!strlen($firstname) or 
				!strlen($lastname)  or 
				!strlen($email)  or 
				!strlen($desc) or
				!strlen($cn)
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
			$res = $this->try_ldap(LDAP_ADMIN_USER,null,null,LDAP_ENTRY_ROOT_DN_ADD);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
				
			}
			
			//get map
			$map = $this->MapGroup->get($cn);
			
			//chk if invalid group -> 404::HTTP_NOT_FOUND
			if(! $this->MapGroup->is_group_valid($cn) or null == $map)
			{
				//fmt reply 404
				$reply['statuscode'] = HTTP_NOT_FOUND;
				$reply['message']    = "CN not found!";
				//give it back
				$this->send_reply($reply);
				return;
				
			}
			
			//log
			$tmf = @var_export($map,1);
			debug("map> $tmf");
			
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
			$info["sn"]              = $lastname;
			$info["givenName"]       = sprintf("%s %s %s",$firstname, $middlename,$lastname);
			$info["cn"]              = $map['cn'];
			$info["objectClass"][]   = $map['objectClass'][0];
			$info["objectClass"][]   = $map['objectClass'][1];
			$info["objectClass"][]   = $map['objectClass'][2];
			$info["objectClass"][]   = $map['objectClass'][3];
			$info["description"]     = $desc;
			$info["userPassword"]    = '{md5}' . base64_encode(pack('H*', md5($pass)));
			$ldaprdn                 = sprintf("uid=%s,%s",$user,$map['rdn']);  
			
			debug("ldaprdn> $ldaprdn");

			//chk the CN
			$ldapfilter = sprintf("(uid=%s)",$user);  

			//chk it
			debug("filter> $ldapfilter");
			$srch = $this->try_ldap(LDAP_ADMIN_USER);
			$resp = $this->try_filter($srch['ldapconn'],$ldapfilter,null);		
			$dmp  = @var_export($resp,1);
			debug("memberof> $dmp");
			
			//not found
			if(!@count($resp['result']['member']))
			{
					//new entry
					$update  = ldap_add($ldapconn, $ldaprdn, $info);
					if(!$update)
					{
							//fmt reply 403
							$reply['status']     = false;
							$reply['statuscode'] = HTTP_FORBIDDEN;
							$reply['message']    = "LDAP user add failed.";
							$reply['error']      = $this->fmt_err_msg($ldapconn);
							//give it back
							$this->send_reply($reply);
							return;
					}
			}
			else
			{
				//chk if its there?
				if (in_array($map['cn'], $resp['result']['member']) ) 
				{
						//fmt reply 405
						$reply['status']     = false;
						$reply['statuscode'] = HTTP_METHOD_NOT_ALLOWED;
						$reply['message']    = "LDAP user/modify add failed (CN Already Exists).";
						$reply['error']      = null;
						//give it back
						$this->send_reply($reply);
						return;
				}
				else
				{
						//only-add the CN
						$cnlist     = array_merge(array($map['cn']),$resp['result']['member']);
						$info["cn"] = $cnlist;
						$dmp = @var_export($cnlist,1);
						debug("CNS> $dmp");
						
						//modify
						$update  = ldap_modify($ldapconn, $ldaprdn, $info);
						if(!$update)
						{
								//fmt reply 403
								$reply['status']     = false;
								$reply['statuscode'] = HTTP_FORBIDDEN;
								$reply['message']    = "LDAP user/modify add failed.";
								$reply['error']      = $this->fmt_err_msg($ldapconn);
								//give it back
								$this->send_reply($reply);
								return;
						}
				}
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

	//member
	protected function do_entry_member()
	{
			//get params
			$user  = trim($_REQUEST['user']);
			$reply = $this->init_resp();

			
			//sanity check -> must be valid CN
			if( !strlen($user))
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
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
			$ldapfilter = sprintf("(uid=%s)",$user);  

			//chk it
			$resp       = $this->try_filter($ldapconn,$ldapfilter,$map['rdn']);		
			
			//give it back
			$this->send_reply($resp);

			//free
			if($ldapconn)
			  @ldap_free_result($ldapconn);
	
	}
	
	//error
	public function notfound($code=HTTP_UNAUTHORIZED, $msg='Method not found!')
	{
			//HTTP_UNAUTHORIZED
			return array(
					'status'      => false,
					'statuscode'  => $code,
					'result'      => array(),
					'message'     => $msg,
			);

	}

	protected function fmt_err_msg($ldapconn)
	{
			$errmsg = array(
					'LDAP-Error' => null
			);
			if($ldapconn !=null)
			{
				
				$errmsg = array(
						'error-msg' => sprintf("Errno: %s, Message:%s ",
													ldap_errno($ldapconn),
													ldap_error($ldapconn)),
									);
			}
			//give it back
			return $errmsg;
	}
}//class	
?>
