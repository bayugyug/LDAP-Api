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
		global $_JWT;
		
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
			case API_HIT_ENTRY_SESSION:
				$this->do_entry_session();
				break;					
			case API_HIT_ENTRY_SID:
				$this->do_entry_sid();
				break;					
			case API_HIT_SIGN_OUT:
				$this->do_sign_out();
				break;	
			case API_HIT_CSV_DUMP:
				$this->do_csv_dump();
				break;	
			case API_HIT_WORD_ENC:
				$this->do_word_shuffle(1);
				break;
			case API_HIT_WORD_DEC:
				$this->do_word_shuffle(0);
				break;	
			case API_HIT_RESET_PASS:
				$this->do_entry_reset_pass();
				break;				
			//notfound
			default:	
				$this->send_reply($this->notfound());
		}
		
	}	


	
	//filter
	protected function try_filter($ldapconn=null,$ldapfilter=null,$ldaprdn,$allcn=0,$savepwd=false)
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
			$passwds = array();
			$uidx    = '';
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
						 if($data == "uid")
						 	 $uidx = trim($info[$i][$data][$iii]);;
						 
						 if($data == "userpassword")
						 {
							 if($savepwd)
							 {
								$passwds["$uidx"] = trim($info[$i][$data][$iii]); 
								$rec["$data"]     = trim($info[$i][$data][$iii]);
								debug("try_filter(): [$i] entry: $data -->: ". $info[$i][$data][$iii] );
							 }
							 continue;
					     }
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
					//list of passwd
					if($savepwd)						 
						$reply['result']["xtras"] = $passwds;
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
		global $_API_KEYS;
		
			//get params
			$user   = trim($_REQUEST['user']);
			$pass   = trim($_REQUEST['pass']);
			
			$reply = $this->init_resp();

			//sanity check -> LISTS
			if( !strlen($user) or !strlen($pass) )
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				
				return;
			}

			

			if('dont' == 'use')
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
					debug("map> $tmf");
			}
			
			
			//chk email
			$bdata = $this->ldap_user_get_by_userid($user);
			if(!$bdata['exists'])
			{
					//fmt reply 404
					$reply['statuscode'] = HTTP_NOT_FOUND;
					$reply['message']    = "User not found in db!";
					//give it back
					$this->send_reply($reply);
					return;
			}
			$dmp = @var_export($bdata,1);				
			debug("DB-RROW> $dmp");
			
			//get list
			$uidlist   = array();
			if(strlen($bdata['data']['tm'])>0      )
			{
				$uidlist[$this->MapGroup->LDAP_GRP_TRAVEL_MART] = trim($bdata['data']['tm']);
			}
			if(strlen($bdata['data']['mstr'])>0    )
			{
				$uidlist[$this->MapGroup->LDAP_GRP_MSTR] = trim($bdata['data']['mstr']);
			}
			if(strlen($bdata['data']['rclcrew'])>0 )
			{
				$uidlist[$this->MapGroup->LDAP_GRP_RCLREW] = trim($bdata['data']['rclcrew']);
			}
			if(strlen($bdata['data']['ctrac'])>0   )
			{
				$uidlist[$this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE]  = trim($bdata['data']['ctrac']);
			}
			if(strlen($bdata['data']['ctrac_app'])>0   )
			{
				$uidlist[$this->MapGroup->LDAP_GRP_CTRACK_APPLICANT] = trim($bdata['data']['ctrac_app']);
			}
			$dmp = @var_export($uidlist,1);				
			debug("UIDLIST> $dmp");

			
			//log
			$res = $this->try_ldap(LDAP_NORMAL_USER, $user,$pass,LDAP_RDN_GROUPS);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				
				return;
				
			}
			
			//chk the CN
			$ldapfilter = sprintf("(mail=%s)",$bdata['data']['email']);  
			
			//get member of
			debug("filter> $ldapfilter");
			$srch   = $this->try_ldap(LDAP_ADMIN_USER);
			$resp   = $this->try_filter($srch['ldapconn'],$ldapfilter,null);		
			$member = $resp['result']['member'];
			$dmp    = @var_export($resp,1);
			debug("memberof> $dmp");

			//unset all active
			for($i=0; $i < @count($member); $i++)
			{
				 $cn   = $member[$i];
				 $xuser= (strlen($uidlist[$cn])>0) ? ($uidlist[$cn]) : ($user);
				 $this->unset_session_db_by($xuser,$cn);
				 $this->unset_sess_id();	
			}

			//set new
			$sids   = array();
			$dbsess = array();
			for($i=0; $i < @count($member); $i++)
			{
				 $cn   = $member[$i];
				 $xuser= (strlen($uidlist[$cn])>0) ? ($uidlist[$cn]) : ($user);
				 
				 debug("$i.#sid> $sessk => $sid -> $user/user=$xuser;");
				
				 $this->set_session_db(array(          
									'user'  => $xuser,
									'cn'    => $cn,
									'sid'   => base64_encode(openssl_random_pseudo_bytes(64)), 
									));
				 $dbsess["$cn"]  = $this->get_session_db_by($xuser,$cn);														
			}
			
			//get db query
			$dtls   = $this->get_user_details($user);		

			//free memory
			$this->set_sess_id();	
			
			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = "Sign-in successful.";
			$reply['result']     = array();
			$reply['sessionid']  = $dbsess;
			$reply['cns']        = $member;
			$reply['migrated']   = $dtls;
			$reply['token']      = $this->get_sess_id();
			$reply['email']      = $bdata['data']['email'];
			$reply['apikey']     = $_API_KEYS[0];

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

			//sanity check -> LISTS
			if( !strlen($uid) )
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
			}


			if(strlen($cn) > 0)
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
			}

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
			
			//groups != people
			if($cn !== strtolower(LDAP_RDN_GROUP))
			{
				//Groups
				$rdn = LDAP_RDN_GROUPS;
				
				debug("filter>GROUPS = $rdn");
			}
			else
			{
				//People
				$rdn = LDAP_RDN_USERS;
				
				debug("filter>PEOPLE = $rdn");
			}
			
			//all
			$cnf = (strlen($cn)>0) ? ($cn) : ('*');
			$ldapfilter = sprintf("(&(cn=%s)(uid=%s))",$cnf,$uid); 
			

			debug("filter> $ldapfilter");
			
			//chk it
			$resp       = $this->try_filter($ldapconn,$ldapfilter,$rdn);		

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
			$user      = trim($_REQUEST['user']);
			$firstname = trim($_REQUEST["firstname" ]);
			$middlename= trim($_REQUEST["middlename"]);
			$lastname  = trim($_REQUEST["lastname"  ]);
			$email     = trim($_REQUEST["email"]        );
			$desc      = trim($_REQUEST["description"] );
		    $cn        = strtolower(trim($_REQUEST["company"]));
			
			//init
			$reply = $this->init_resp();


			//sanity check -> LISTS
			if(
				!strlen($user)        or 
				!strlen($firstname)   or 
				!strlen($middlename)  or 
				!strlen($lastname)    or 
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

			
			if('dont' == 'run')
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
					debug("map> $tmf");
			}


			
			//chk email
			$bdata = $this->ldap_user_get_by_userid($user);
			if(!$bdata['exists'])
			{
					//fmt reply 404
					$reply['statuscode'] = HTTP_NOT_FOUND;
					$reply['message']    = "User not found in db!";
					//give it back
					$this->send_reply($reply);
					return;
			}
			$dmp = @var_export($bdata,1);				
			debug("DB-RROW> $dmp");

			
			//conn
			//$res = $this->try_ldap(LDAP_NORMAL_USER,$user,$pass,LDAP_RDN_GROUPS);
			$res = $this->try_ldap(LDAP_ADMIN_USER,null,null,LDAP_ENTRY_ROOT_DN_UPD);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
				
			}

			
			//get conn
			$ldapconn                = $res['ldapconn'];
			$ldapuser                = $user;
			
			// prepare data
			$info["description"]     = $desc;
			
			//sn
			if( strlen($lastname) ) 
				$info["sn"]              = $lastname;
			
			//givenName
			if( strlen($firstname) and strlen($lastname) )
				$info["givenName"]  = sprintf("%s %s",$firstname,$lastname);
			if( strlen($firstname) and strlen($lastname) and strlen($middlename) )
				$info["givenName"]  = sprintf("%s %s %s",$firstname, $middlename,$lastname);
	

			//update the db
			$ndata["firstname"]   = $firstname ;
			$ndata["middlename"]  = $middlename;
			$ndata["lastname"]    = $lastname  ;
			$ndata["email"]       = $bdata['data']['email'];
			$updret               = $this->ldap_user_upd_db($ndata);


			//get list
			$uidlist   = array();
			$uidlist[] = $user;
			if(strlen($bdata['data']['tm'])>0      and ($bdata['data']['tm']      !== $user))
					$uidlist[] = trim($bdata['data']['tm']);
			if(strlen($bdata['data']['mstr'])>0    and ($bdata['data']['mstr']    !== $user))
					$uidlist[] = trim($bdata['data']['mstr']);
			if(strlen($bdata['data']['rclcrew'])>0 and ($bdata['data']['rclcrew'] !== $user))
					$uidlist[] = trim($bdata['data']['rclcrew']);
			if(strlen($bdata['data']['ctrac'])>0   and ($bdata['data']['ctrac']   !== $user))
					$uidlist[] = trim($bdata['data']['ctrac']);
			if(strlen($bdata['data']['ctrac_app'])>0   and ($bdata['data']['ctrac_app']   !== $user))
					$uidlist[] = trim($bdata['data']['ctrac_app']);

			
			$dmp = @var_export($uidlist,1);
			debug("LIST-OF-CNS-UID>$dmp;");
			foreach($uidlist as $kk => $vv)
			{
						//GROUPS
						$ldaprdn                 = sprintf("uid=%s,%s",$vv,LDAP_RDN_GROUPS);  
						debug("[$kk] UPDATE/MODIFY> DN=$ldaprdn;");		
						
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

			//sanity check -> LISTS
			if(
				!strlen($user)    or 
				!strlen($pass)    or
				!strlen($newpass) or ($pass === $newpass)
			)
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				if(strlen($newpass) and ($pass === $newpass))
				{
					$reply['message']    = "Invalid parameters! New password is the same as old one.";	
				}
				//give it back
				$this->send_reply($reply);
				return;
			}

			if('dont' == 'run')
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
					debug("map> $tmf");
			}

			//chk email
			$bdata = $this->ldap_user_get_by_userid($user);
			if(!$bdata['exists'])
			{
					//fmt reply 404
					$reply['statuscode'] = HTTP_NOT_FOUND;
					$reply['message']    = "User not found in db!";
					//give it back
					$this->send_reply($reply);
					return;
			}
			
			//conn
			$res = $this->try_ldap(LDAP_ADMIN_USER,null,null,LDAP_ENTRY_ROOT_DN_UPD);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				
				return;
				
			}

			//get conn
			$ldapconn                = $res['ldapconn'];
			
			// prepare data
			$info["userPassword"]    = '{md5}' . base64_encode(pack('H*', md5($newpass)));
			
			
			$dmp = @var_export($bdata,1);				
			debug("DB-RROW> $dmp");

			//get list
			$uidlist   = array();
			$uidlist[] = $user;
			if(strlen($bdata['data']['tm'])>0      and ($bdata['data']['tm']      !== $user))
					$uidlist[] = trim($bdata['data']['tm']);
			if(strlen($bdata['data']['mstr'])>0    and ($bdata['data']['mstr']    !== $user))
					$uidlist[] = trim($bdata['data']['mstr']);
			if(strlen($bdata['data']['rclcrew'])>0 and ($bdata['data']['rclcrew'] !== $user))
					$uidlist[] = trim($bdata['data']['rclcrew']);
			if(strlen($bdata['data']['ctrac'])>0   and ($bdata['data']['ctrac']   !== $user))
					$uidlist[] = trim($bdata['data']['ctrac']);
			if(strlen($bdata['data']['ctrac_app'])>0   and ($bdata['data']['ctrac_app']   !== $user))
					$uidlist[] = trim($bdata['data']['ctrac_app']);

			$dmp = @var_export($uidlist,1);
			debug("LIST-OF-CNS-UID>$dmp;");
	
			foreach($uidlist as $kk => $vv)
			{
				    //GROUPS
					$ldaprdn  = sprintf("uid=%s,%s",$vv,LDAP_RDN_GROUPS);  
					
					$dmp = @var_export($info,1);				
					debug("$kk> CHANGE-PASS > DN=$ldaprdn; [ $dmp ]");
			
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
					
			}

			//update password, use the parameter
			$ndata['email' ] = $bdata['data']['email'] ;
			$ndata['pass']   = base64_encode(pack('H*', md5($newpass)));
			$rawstr          = $this->str_enc($newpass);
			$ndata['pass']   = "$rawstr";
			$pret            = $this->ldap_user_upd_pwd_db($ndata);

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

			//db dip here
			$chkmail = $this->ldap_user_get_email($email);

			//sanity check -> LISTS
			if(
				!strlen($user)      or 
				!strlen($firstname) or 
				!strlen($lastname)  or 
				!$this->is_valid_email($email) or 
				( !strlen($pass) and (!$chkmail['exists']) ) or 
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
			
			//get all CN from DB-RROW
			$CNlist    = array();
			$UIDlist   = array();
			if($chkmail['exists']>0)
			{
				$rawstr                  = $this->str_dec($chkmail['data']['passwd']);
				$info["userPassword"]    = '{md5}'. $chkmail['data']['passwd'];
				$info["userPassword"]    = "$rawstr";
				$dmp = @var_export($info["userPassword"],1);
				debug("WILL USE THE OLD PWD> $dmp");
				
				if(strlen($chkmail['data']['tm'])>0      )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_TRAVEL_MART;
					if($user !== $chkmail['data']['tm'])
						$UIDlist[] = $chkmail['data']['tm'];
				}
				if(strlen($chkmail['data']['mstr'])>0    )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_MSTR;
					if($user !== $chkmail['data']['mstr'])
						$UIDlist[] = $chkmail['data']['mstr'];

				}
				if(strlen($chkmail['data']['rclcrew'])>0 )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_RCLREW;
					if($user !== $chkmail['data']['rclcrew'])
						$UIDlist[] = $chkmail['data']['rclcrew'];
				}
				if(strlen($chkmail['data']['ctrac'])>0   )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE;
					if($user !== $chkmail['data']['ctrac'])
						$UIDlist[] = $chkmail['data']['ctrac'];
				}
				if(strlen($chkmail['data']['ctrac_app'])>0   )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_APPLICANT;
					if($user !== $chkmail['data']['ctrac_app'])
						$UIDlist[] = $chkmail['data']['ctrac_app'];
				}
				
				$dmp = @var_export($UIDlist,1);
				debug("DB UID List> $dmp");
				
				$dmp = @var_export($CNlist,1);
				debug("DB CN List> $dmp");
			}
			
			//chk it
			//chk the CN
			$ldapfilter = sprintf("(uid=%s)",$user);
			debug("filter> $ldapfilter");
			$srch = $this->try_ldap(LDAP_ADMIN_USER);
			$resp = $this->try_filter($srch['ldapconn'],$ldapfilter,null);
			$dmp  = @var_export($resp,1);
			debug("memberof> $dmp");

			//not found
			if(!@count($resp['result']['member']))
			{
				    $dmp = @var_export($info,1);
					debug("THIS IS A NEW USER from LDAP> $dmp");
					
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
						
						//uidlist must be same password
						foreach($UIDlist as $kk => $vv)
						{
								//GROUPS
								$ldaprdn  = sprintf("uid=%s,%s",$vv,LDAP_RDN_GROUPS);  
								//only change the LDAP attribute.passwd
								$pinfo["userPassword"] = $info["userPassword"] ;

								$dmp = @var_export($pinfo,1);				
								debug("$kk> CHANGE-PASS > DN=$ldaprdn; [ $dmp ]");

								if('no' == 'need this')
								{
										
										//update entry
										$update  = ldap_modify($ldapconn, $ldaprdn, $pinfo);
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
								}

						}
				}
			}

			//db dip here
			$ndata['firstname' ] = $firstname ; 
			$ndata['middlename'] = $middlename;
			$ndata['lastname'  ] = $lastname  ;
			$ndata['email'     ] = $email     ;
			
			//sanity check
			if($chkmail['exists']>0)
			{
				//update details?
				$nret = $this->ldap_user_upd_db($ndata);
				
				//update CN from db
				$uret = $this->ldap_user_upd_cn_db(array( 'cn' => $cn, 'uid' => $user,'email' => $email ));

				//add
				$reply['message']    = "LDAP user update successful.";
			}
			else
			{
				//ADD record
				switch(strtolower($cn) )
				{
						case $this->MapGroup->LDAP_GRP_TRAVEL_MART      :
							$ndata['tm'] = $user;
							break;
						case $this->MapGroup->LDAP_GRP_RCLREW           :
							$ndata['rclcrew'] = $user;
							break;
						case $this->MapGroup->LDAP_GRP_MSTR             :
							$ndata['mstr'] = $user;
							break;
						case $this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE  :
							$ndata['ctrac'] = $user;
							break;
						case $this->MapGroup->LDAP_GRP_CTRACK_APPLICANT :
							$ndata['ctrac_app'] = $user;						
							break;
				}
				
				//run
				$aret = $this->ldap_user_add_db($ndata);

				//update password, use the parameter
				$ndata['email' ] = $email     ;
				$ndata['pass']   = base64_encode(pack('H*', md5($pass)));
				$rawstr          = $this->str_enc($pass);
				$ndata['pass']   = "$rawstr";
				$pret            = $this->ldap_user_upd_pwd_db($ndata);
				
				//add
				$reply['message'] = "LDAP user add successful.";
			}

			
			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
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
	
	//session
	protected function do_entry_session()
	{
			//get params
			$user   = trim($_REQUEST['user']);
			$cn     = strtolower(trim($_REQUEST["company"]));
			
			$reply = $this->init_resp();

			//sanity check -> LISTS
			if( !strlen($user) )
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				
				return;
			}

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
					debug("map> $tmf");
			}
			
			$dbsess= $this->get_session_db_by($user,$cn);														
			 
			if(@count($dbsess['data'])>0 )
			{
					//fmt reply 200
					$reply['status']     = true;
					$reply['statuscode'] = HTTP_SUCCESS;
					$reply['message']    = "Session is valid.";
					$reply['result']     = array(
										 );
					$reply['sessionid']  = $dbsess; 
			}
			else
			{
					//fmt reply 410
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_GONE;
					$reply['message']    = "Session is gone.";
					$reply['result']     = array(
										 );
					$reply['sessionid']  = null; 	
			}
			
			
			//give it back
			$this->send_reply($reply);
			
	
	}
	
	//session
	protected function do_entry_sid()
	{
		global $_API_KEYS;
		
			//get params
			$sid   = @str_replace("\\","", trim($_REQUEST['sid']));
			$apikey= trim($_REQUEST['apikey']);
			
			$reply = $this->init_resp();

			//sanity check -> LISTS
			if( !strlen($sid) )
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				
				return;
			}
			
			if(0){
				//api-key
				if( !@in_array($apikey, $_API_KEYS) )	
				{
					//fmt reply 404
					$reply['statuscode'] = HTTP_NOT_FOUND;
					$reply['message']    = "API key is invalid!";
					//give it back
					$this->send_reply($reply);
					return;
				}
			}

			$dbsess= $this->get_session_db_sid($sid);														
			 
			if(@count($dbsess['data'])>0 )
			{
					//fmt reply 200
					$reply['status']     = true;
					$reply['statuscode'] = HTTP_SUCCESS;
					$reply['message']    = "Session is valid.";
					$reply['result']     = array(
										 );
					$reply['sessionid']  = $dbsess; 
			}
			else
			{
					//fmt reply 410
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_GONE;
					$reply['message']    = "Session is gone.";
					$reply['result']     = array(
										 );
					$reply['sessionid']  = null; 	
			}
			
			
			//give it back
			$this->send_reply($reply);
			
	
	}
	
	
	//sign-out
	protected function do_sign_out()
	{
			//get params
			$user   = trim($_REQUEST['user']);
			$cn     = strtolower(trim($_REQUEST["company"]));
			
			$reply = $this->init_resp();

			//sanity check -> LISTS
			if( !strlen($user) )
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				
				return;
			}

			
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
					debug("map> $tmf");
			}
			
				
			//unset all active
			$this->unset_session_db_by($user,$cn);
			
			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = "Session unset is successful.";
			$reply['result']     = array(
								 );
			$reply['sessionid']  = null; 

			//free memory
			$this->unset_sess_id();	
			
			//give it back
			$this->send_reply($reply);
	}
	
		
	//sign-out
	protected function do_csv_dump()
	{
			//get params
			$output_dir = API_CSV_DIR;
			
			
			$reply = $this->init_resp();

			//sanity check -> LISTS
			if(! isset($_FILES[API_CSV_FILEFORM]))
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters! File is empty!";
				//give it back
				$this->send_reply($reply);
				
				return;
			}

			//chk
			$error = $_FILES["myfile"]["error"];
			
			if(isset($error))
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Upload failed. [$error]!";
				//give it back
				$this->send_reply($reply);
				
				return;
			}

			//save
			$ret = array();
			
			//If Any browser does not support serializing of multiple files using FormData() 
			if(!is_array($_FILES[API_CSV_FILEFORM]["name"])) //single file
			{
				    $fn       = $_FILES[API_CSV_FILEFORM]["name"];
					$ext      = @end((@explode(".", $fn)));
					$fileName = sprintf("upload-%s-%s-%s",@date('Ymd'), md5(uniqid(time())) , $fn);
					
					if(@preg_match("/^(xls|csv)$/i",$ext))
					{
						@move_uploaded_file($_FILES[API_CSV_FILEFORM]["tmp_name"],$output_dir.$fileName);
						$ret[]= $fileName;
					}
					else
					{
						debug("file:$ext> $output_dir/$fileName... IGNORED");
					}
					debug("file:$ext> $output_dir/$fileName");
			}
			else  //Multiple files, file[]
			{
				$fileCount = count($_FILES[API_CSV_FILEFORM]["name"]);
				for($i=0; $i < $fileCount; $i++)
				{
					$fn       = $_FILES[API_CSV_FILEFORM]["name"][$i];
					$ext      = @end((@explode(".", $fn)));
					$fileName = sprintf("upload-%s-%s-%s",@date('Ymd'), md5(uniqid(time())) , $fn);
					if(@preg_match("/^(xls|csv)$/i",$ext))
					{
						@move_uploaded_file($_FILES[API_CSV_FILEFORM]["tmp_name"][$i],$output_dir.$fileName);
						$ret[]= $fileName;
					}
					else
					{
						debug("file:$ext> $output_dir/$fileName... IGNORED");
					}
					debug("file:$ext> $output_dir/$fileName");
				}//for
			}
			
			
			if(@count($ret))
			{
					//fmt reply 200
					$reply['status']     = true;
					$reply['success']    = true;
					
					$reply['statuscode'] = HTTP_SUCCESS;
					$reply['message']    = "File upload is successful.";
					$reply['result']     = array(
										 );
					$reply['filelist']   = $ret; 
					$reply['msg']        = $reply['message']; 
			}
			else
			{
					//fmt reply 410
					$reply['status']     = false;
					$reply['success']    = false;
					$reply['statuscode'] = HTTP_GONE;
					$reply['message']    = "File upload failed.";
					$reply['result']     = array(
										 );
					$reply['msg']        = $reply['message']; 
			}	
			//give it back
			$this->send_reply($reply);
	}
	
	//reset password
	protected function do_entry_reset_pass()
	{
			//get params
			$user   = trim($_REQUEST['user']);
			$pass   = trim($_REQUEST['pass']);
			
			//init
			$reply  = $this->init_resp();

			//sanity check -> LISTS
			if(
				!strlen($user)    or 
				!strlen($pass)    
			)
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
			}

			//chk email
			$bdata = $this->ldap_user_get_by_userid($user);
			if(!$bdata['exists'])
			{
					//fmt reply 404
					$reply['statuscode'] = HTTP_NOT_FOUND;
					$reply['message']    = "User not found in db!";
					//give it back
					$this->send_reply($reply);
					return;
			}
			
			//conn
			$res = $this->try_ldap(LDAP_ADMIN_USER,null,null,LDAP_ENTRY_ROOT_DN_UPD);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				return;
			}

			//get conn
			$ldapconn                = $res['ldapconn'];
			
			// prepare data
			$info["userPassword"]    = '{md5}' . base64_encode(pack('H*', md5($pass)));
			
			$dmp = @var_export($bdata,1);				
			debug("DB-RROW> $dmp");

			//get list
			$uidlist   = array();
			$uidlist[] = $user;
			if(strlen($bdata['data']['tm'])>0      and ($bdata['data']['tm']      !== $user))
					$uidlist[] = trim($bdata['data']['tm']);
			if(strlen($bdata['data']['mstr'])>0    and ($bdata['data']['mstr']    !== $user))
					$uidlist[] = trim($bdata['data']['mstr']);
			if(strlen($bdata['data']['rclcrew'])>0 and ($bdata['data']['rclcrew'] !== $user))
					$uidlist[] = trim($bdata['data']['rclcrew']);
			if(strlen($bdata['data']['ctrac'])>0   and ($bdata['data']['ctrac']   !== $user))
					$uidlist[] = trim($bdata['data']['ctrac']);
			if(strlen($bdata['data']['ctrac_app'])>0   and ($bdata['data']['ctrac_app']   !== $user))
					$uidlist[] = trim($bdata['data']['ctrac_app']);

			$dmp = @var_export($uidlist,1);
			debug("LIST-OF-CNS-UID>$dmp;");
	
			foreach($uidlist as $kk => $vv)
			{
				    //GROUPS
					$ldaprdn  = sprintf("uid=%s,%s",$vv,LDAP_RDN_GROUPS);  
					
					$dmp = @var_export($info,1);				
					debug("$kk> RESET-PASS > DN=$ldaprdn; [ $dmp ]");
			
					//update entry
					$update  = ldap_modify($ldapconn, $ldaprdn, $info);
					if(!$update)
					{
							//fmt reply 403
							$reply['status']     = false;
							$reply['statuscode'] = HTTP_FORBIDDEN;
							$reply['message']    = "Reset password failed.";
							$reply['error']      = $this->fmt_err_msg($ldapconn);
							
							//give it back
							$this->send_reply($reply);
							return;
					}
					
			}

			//reset password, use the parameter
			$ndata['email' ] = $bdata['data']['email'] ;
			$ndata['pass']   = base64_encode(pack('H*', md5($pass)));
			$rawstr          = $this->str_enc($pass);
			$ndata['pass']   = "$rawstr";
			$pret            = $this->ldap_user_upd_pwd_db($ndata);

			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = "Reset password successful.";
			$reply['result']     = array(
								 );
			//give it back
			$this->send_reply($reply);

			//free
			if($ldapconn)
			  @ldap_free_result($ldapconn);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//------------------------------------------------------------------
	//error
	public function notfound($code=HTTP_UNAUTHORIZED, $msg='Method not found!')
	{
			//HTTP_UNAUTHORIZED
			return array(
					'status'      => false,
					'statuscode'  => $code,
					'result'      => array(),
					'message'     => $msg,
					'authsid'     => $this->get_sess_id(),
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
	
	protected function unset_sess_id()
	{
		//null
		$_SESSION[API_SID_NAME] = array();
		unset($_SESSION[API_SID_NAME]);
		session_destroy();
	}
	
	
	protected function set_sess_id()
	{
		//ensure
		if('' == session_id()) 
		   session_start();
		
		//save
		$sid = sprintf("%s-%s",session_id(),md5(uniqid()));
		$_SESSION[API_SID_NAME] = $sid;

		//give it
		return $sid;
	}
	
	
	protected function get_sess_id()
	{
		//give it back
		return $_SESSION[API_SID_NAME];
	}
		
		
	//get data
	protected function get_user_details($usr)
	{
		//globals here
		global $gSqlDb;

		debug("get_user_details() : INFO : [ USER=$usr; ]");
		
		//fmt-params
		$usr      = addslashes(trim($usr));
		

		//select
		$sql = "SELECT SQL_CALC_FOUND_ROWS 
					* 
				FROM sso_users 
				WHERE 
					ouid = '$usr'
				LIMIT 1	
		       ";
		
		$res   = $gSqlDb->query($sql, "get_user_details() : ERROR : $sql");

		//total-rows
		$is_ok = $gSqlDb->numRows($res);
		$data  = array();
		$sdata = array('exists' => intval($is_ok));
		
		//get data
		if($is_ok>0)
		{
			$data = $gSqlDb->getAssoc($res);
		}
		
		//save
		$sdata['data'] = $data;
		
		debug("get_user_details() : INFO : [ $sql => $is_ok ]");
		
		//free-up
		if($res) $gSqlDb->free($res);
		
		//give it back ;-)
		return $sdata;
		
	}

	//save
	protected function set_session_db($data=null)
	{
		//globals here
		global $gSqlDb;

		//fmt-params
		$user     = addslashes(trim($data["user" ] ));
		$cn       = addslashes(trim($data["cn"   ] ));
		$sid      = addslashes(trim($data["sid"  ] ));
		
		//exec
		$sql = "INSERT INTO ldap_session
				(user    ,
				 cn      ,
				 sid     , 
				 created ,
				 expiry  
				)
				VALUES(
				 '$user',
				 '$cn'  ,
				 '$sid' ,
				 Now()  ,
				 DATE_ADD(Now(),INTERVAL 90 MINUTE)
				)
			";
			   
		//run		  
		$res   = $gSqlDb->exec($sql, "set_session_db() : ERROR : $sql");
		$ref   = $gSqlDb->insertId();
		
		debug("set_session_db() : INFO : [ ref=$ref; ]");

		//free-up
		if($res) $gSqlDb->free($res);

		
		//give it back ;-)
		return $ref;
		
	}
	
		
	//get data
	protected function get_session_db($sid='')
	{
		//globals here
		global $gSqlDb;

		debug("get_session_db() : INFO : [ sessionid=$sid; ]");
		
		//fmt-params
		$sid      = addslashes(trim($sid));
		

		//select
		$sql = "SELECT SQL_CALC_FOUND_ROWS 
					user,cn,sid,created,expiry
				FROM ldap_session 
				WHERE 
					sid = '$sid'
				LIMIT 1	
		       ";
		
		$res   = $gSqlDb->query($sql, "get_session_db() : ERROR : $sql");

		//total-rows
		$is_ok = $gSqlDb->numRows($res);
		$data  = array();
		$sdata = array('exists' => intval($is_ok));
		
		//get data
		if($is_ok>0)
		{
			$data = $gSqlDb->getAssoc($res);
		}
		
		//save
		$sdata['data'] = $data;
		
		debug("get_session_db() : INFO : [ $sql => $is_ok ]");
		
		//free-up
		if($res) $gSqlDb->free($res);
		
		//give it back ;-)
		return $sdata;
		
	}

	//get data
	protected function get_session_db_by($usr,$cn='')
	{
		//globals here
		global $gSqlDb;

		debug("get_session_db_by() : INFO : [ USER=$usr; $cn;]");
		
		//fmt-params
		$usr      = addslashes(trim($usr));
		$cn       = addslashes(trim($cn));
		$cnwhere  = (strlen($cn)>0) ? ( " AND cn = '$cn' " ) : ('');
		
		//select
		$sql = "SELECT SQL_CALC_FOUND_ROWS 
					user,cn,sid,created,expiry
				FROM ldap_session 
				WHERE 
					user = '$usr' $cnwhere
					AND now() < expiry
		       ";
		
		$res   = $gSqlDb->query($sql, "get_session_db_by() : ERROR : $sql");

		//total-rows
		$is_ok = $gSqlDb->numRows($res);
		$data  = array();
		$sdata = array('exists' => intval($is_ok));
		
		//get data
		if($is_ok>0)
		{
			while($strow = $gSqlDb->getAssoc($res))
			{
				$data[] = $strow;
			}
		}
		
		//save
		$sdata['data'] = $data;
		
		debug("get_session_db_by() : INFO : [ $sql => $is_ok ]");
		
		//free-up
		if($res) $gSqlDb->free($res);
		
		//give it back ;-)
		return $sdata;
		
	}

	//get data
	protected function get_session_db_sid($sid='')
	{
		//globals here
		global $gSqlDb;

		debug("get_session_db_sid() : INFO : [ SID=$sid;]");
		
		//fmt-params
		$sid      = addslashes(trim($sid));
		
		//select
		$sql = "SELECT SQL_CALC_FOUND_ROWS 
					user,cn,sid,created,expiry
				FROM ldap_session 
				WHERE 
					sid = '$sid'
					AND now() < expiry
		       ";
		
		$res   = $gSqlDb->query($sql, "get_session_db_sid() : ERROR : $sql");

		//total-rows
		$is_ok = $gSqlDb->numRows($res);
		$data  = array();
		$sdata = array('exists' => intval($is_ok));
		
		//get data
		if($is_ok>0)
		{
			while($strow = $gSqlDb->getAssoc($res))
			{
				$data[] = $strow;
			}
		}
		
		//save
		$sdata['data'] = $data;
		
		debug("get_session_db_sid() : INFO : [ $sql => $is_ok ]");
		
		//free-up
		if($res) $gSqlDb->free($res);
		
		//give it back ;-)
		return $sdata;
		
	}

	
	
	//get data
	protected function unset_session_db_by($usr,$cn='')
	{
		//globals here
		global $gSqlDb;

		debug("unset_session_db_by() : INFO : [ USER=$usr; $cn;]");
		
		//fmt-params
		$usr      = addslashes(trim($usr));
		$cn       = addslashes(trim($cn));
		$cnwhere  = (strlen($cn)>0) ? ( " AND cn = '$cn' " ) : ('');
		

		//exec
		$sql = "UPDATE ldap_session 
			SET 
				expiry    = DATE_SUB(Now(),INTERVAL 5 HOUR)
			WHERE 
				user      = '$usr' $cnwhere
			";
			  
		  
		$res   = $gSqlDb->exec($sql, "unset_session_db_by() : ERROR : $sql");
		$is_ok = $gSqlDb->updRows($res);

		debug("unset_session_db_by() : INFO : [ $sql => $res => $is_ok ]");

		//free-up
		if($res) $gSqlDb->free($res);

		
		//give it back ;-)
		return $is_ok;
		
	}

	
	//save
	protected function ldap_user_add_db($pdata=array())
	{
		//globals here
		global $gSqlDb;

		//fmt-params
		$firstname    = addslashes(trim($pdata['firstname' ]  ));
		$middlename   = addslashes(trim($pdata['middlename']  ));
		$lastname     = addslashes(trim($pdata['lastname'  ]  ));
		$email        = addslashes(trim($pdata['email'     ]  ));
		$status       = '1';
		$group_name   = addslashes(trim($pdata['group_name']  ));
		$tm           = addslashes(trim($pdata['tm'        ]  ));   
		$mstr         = addslashes(trim($pdata['mstr'      ]  )); 
		$rclcrew      = addslashes(trim($pdata['rclcrew'   ]  ));
		$ctrac        = addslashes(trim($pdata['ctrac'     ]  ));
		$ctrac_app    = addslashes(trim($pdata['ctrac_app' ]  ));

		//exec
		$sql = "INSERT INTO sso_users(
					 firstname,      
					 middlename,     
					 lastname,       
					 email,          
					 group_name,     
					 tm,             
					 mstr,           
					 rclcrew,        
					 ctrac,          
					 ctrac_app,
					 creation_date
				)
				VALUES(
					'$firstname',      
					'$middlename',     
					'$lastname',       
					'$email',          
					'$group_name',     
					'$tm',             
					'$mstr',           
					'$rclcrew',        
					'$ctrac',          
					'$ctrac_app',  
					now()
				)  
			   ";
			   
		//run		  
		$res   = $gSqlDb->exec($sql, "ldap_user_add_db() : ERROR : $sql");
		$ref   = $gSqlDb->insertId();

		debug("SQL Statement for insert sso_users:: $sql");
		
		debug("ldap_user_add_db() : INFO : [ ref=$ref; ]");

		//free-up
		if($res) $gSqlDb->free($res);

		
		//give it back ;-)
		return $ref;
		
	}

	//get data
	protected function ldap_user_upd_db($pdata=array())
	{
		//globals here
		global $gSqlDb;

		debug("ldap_user_upd_db() : INFO");
		
		//fmt-params
		$firstname    = addslashes(trim($pdata['firstname' ]  ));
		$middlename   = addslashes(trim($pdata['middlename']  ));
		$lastname     = addslashes(trim($pdata['lastname'  ]  ));
		$email        = addslashes(trim($pdata['email'     ]  ));
		
		//exec
		$sql = "UPDATE sso_users 
			SET 
				firstname    = '$firstname' ,
				middlename   = '$middlename',
				lastname     = '$lastname'
			WHERE 
				email        = '$email'
			";
			  
		  
		$res   = $gSqlDb->exec($sql, "ldap_user_upd_db() : ERROR : $sql");
		$is_ok = $gSqlDb->updRows($res);

		debug("ldap_user_upd_db() : INFO : [ $sql => $res => $is_ok ]");

		//free-up
		if($res) $gSqlDb->free($res);

		
		//give it back ;-)
		return $is_ok;
		
	}

	//get data
	protected function ldap_user_upd_cn_db($pdata=array())
	{
		//globals here
		global $gSqlDb;

		debug("ldap_user_upd_cn_db() : INFO");
		
		//fmt-params
		$cn           = addslashes(trim($pdata['cn'        ]  ));
		$email        = addslashes(trim($pdata['email'     ]  ));
		$uid          = addslashes(trim($pdata['uid'       ]  ));
		
		debug("ldap_user_upd_cn_db() : try to check CN GROUP> $cn; $uid;#");
		
		switch(strtolower($cn) )
		{
				case $this->MapGroup->LDAP_GRP_TRAVEL_MART      :
					$sql   = "UPDATE sso_users SET tm      = '$uid'	WHERE email = '$email' LIMIT 1";
					break;
				case $this->MapGroup->LDAP_GRP_RCLREW           :
					$sql   = "UPDATE sso_users SET rclcrew = '$uid'	WHERE email = '$email' LIMIT 1";
					break;
				case $this->MapGroup->LDAP_GRP_MSTR             :
					$sql   = "UPDATE sso_users SET mstr    = '$uid'	WHERE email = '$email' LIMIT 1";
					break;
				case $this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE  :
					$sql   = "UPDATE sso_users SET ctrac   = '$uid'	WHERE email = '$email' LIMIT 1";
					break;
				case $this->MapGroup->LDAP_GRP_CTRACK_APPLICANT :
					$sql   = "UPDATE sso_users SET ctrac_app = '$uid'	WHERE email = '$email' LIMIT 1";
					break;					
				default:
					debug("ldap_user_upd_cn_db() : hahaha, oops, invalid CN GROUP> $cn; $uid;#");
					return null;
		}
		
		//exec
		$res   = $gSqlDb->exec($sql, "ldap_user_upd_cn_db() : ERROR : $sql");
		$is_ok = $gSqlDb->updRows($res);

		debug("ldap_user_upd_cn_db() : INFO : [ $sql => $res => $is_ok ]");

		//free-up
		if($res) $gSqlDb->free($res);

		//give it back ;-)
		return $is_ok;
	}

	//get data
	protected function ldap_user_upd_pwd_db($pdata=array())
	{
		//globals here
		global $gSqlDb;

		debug("ldap_user_upd_pwd_db() : INFO");
		
		//fmt-params
		$email = addslashes(trim($pdata['email'     ]  ));
		$pass  = addslashes(trim($pdata['pass'      ]  ));
		
		//
		$sql   = "UPDATE sso_users SET passwd = '$pass' WHERE email = '$email' LIMIT 1";

		//exec
		$res   = $gSqlDb->exec($sql, "ldap_user_upd_pwd_db() : ERROR : $sql");
		$is_ok = $gSqlDb->updRows($res);

		debug("ldap_user_upd_pwd_db() : INFO : [ $sql => $res => $is_ok ]");

		//free-up
		if($res) $gSqlDb->free($res);

		//give it back ;-)
		return $is_ok;
	}

	//get data
	protected function ldap_user_get_email($email='')
	{
		//globals here
		global $gSqlDb;

		debug("ldap_user_get_email() : INFO : [ email=$email; ]");
		
		//fmt-params
		$email   = addslashes(trim($email));
		

		//select
		$sql = "SELECT *
				FROM sso_users 
				WHERE 
					email = '$email'
				LIMIT 1	
		       ";
		
		$res   = $gSqlDb->query($sql, "ldap_user_get_email() : ERROR : $sql");

		//total-rows
		$is_ok = $gSqlDb->numRows($res);
		$data  = array();
		$sdata = array('exists' => intval($is_ok));
		
		//get data
		if($is_ok>0)
		{
			$data = $gSqlDb->getAssoc($res);
		}
		
		//save
		$sdata['data'] = $data;
		
		debug("ldap_user_get_email() : INFO : [ $sql => $is_ok ]");
		
		//free-up
		if($res) $gSqlDb->free($res);
		
		//give it back ;-)
		return $sdata;
		
	}

	

	//get data
	protected function ldap_user_get_by_userid($user='')
	{
		//globals here
		global $gSqlDb;

		debug("ldap_user_get_by_userid() : INFO : [ user=$user; ]");
		
		//fmt-params
		$user   = addslashes(trim($user));

		//select
		$sql = "SELECT * 
				FROM 
					sso_users 
				WHERE 
					( tm         = '$user' or 
					  rclcrew    = '$user' or 
					  mstr       = '$user' or
					  ctrac      = '$user' or
					  ctrac_app  = '$user' 
					  )
					LIMIT 1 ";
		
		$res   = $gSqlDb->query($sql, "ldap_user_get_by_userid() : ERROR : $sql");

		//total-rows
		$is_ok = $gSqlDb->numRows($res);
		$data  = array();
		$sdata = array('exists' => intval($is_ok));
		
		//get data
		if($is_ok>0)
		{
			$data = $gSqlDb->getAssoc($res);
		}
		
		//save
		$sdata['data'] = $data;
		
		debug("ldap_user_get_by_userid() : INFO : [ $sql => $is_ok ]");
		
		//free-up
		if($res) $gSqlDb->free($res);
		
		//give it back ;-)
		return $sdata;
		
	}
	

	//encrypt decrypt
	protected function do_word_shuffle($shuffle=1)
	{
			//get params
			$word  = trim($_REQUEST['word']);
			
			$reply = $this->init_resp();

			//sanity check -> LISTS
			if( !strlen($word) )
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				
				return;
			}

			//encrypt
			if(1 == $shuffle)
			{
				//encrypt the word
				$res    			 = $this->str_enc($word);
				$reply['message']    = "Word successfully shuffled.";
				$reply['word']        = $res; 
			}
			else
			{
				//decrypt the word
				$res    			 = $this->str_dec($word);
				$reply['message']    = "Word successfully un-shuffled.";
				$reply['word']       = $res; 
			}
			
			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['result']     = array(
								 );
			

			//free memory
			$this->unset_sess_id();	
			
			//give it back
			$this->send_reply($reply);
	}
	
	//encrypt
	protected function str_enc($word='')
	{
		//give it back
		return  base64_encode(openssl_encrypt(
					base64_encode($word),       
					LDAP_API_ENC_METHOD, 
					LDAP_API_ENC_PASS, 
					false, 
					LDAP_API_ENC_IV) );
	}
	
	//decrypt
	protected function str_dec($word='')
	{
		//give it back
		return rtrim( base64_decode( openssl_decrypt(
				base64_decode($word), 
				LDAP_API_ENC_METHOD, 
				LDAP_API_ENC_PASS, 
				false, 
				LDAP_API_ENC_IV ) ), "\0" );
	
	}

	
}//class	
?>
