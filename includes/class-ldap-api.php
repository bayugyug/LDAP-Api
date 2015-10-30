<?php
//gtalk
include_once('init.php');

//gtalk
include_once('jwt-init.php');

use \Firebase\JWT\JWT;

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
	var    $MapGroup     = null;
	var    $SLIM         = null;
	var    $ShowResults  = true;
	var    $ShowStatCode = false;
	var    $LIMIT        = BATCH_LIST_MAX;
	/**
	* main API
	*
	*/
	function __construct($action = API_HIT_ENTRY_SEARCH,$showres=true,$status=false)
	{
		//init
		$this->action = $action;
		$dmp = @var_export($_REQUEST,1);
		debug("PARAMS> $dmp");
		
		//group
		$this->MapGroup = new LDAP_Groups_Api;
		
		//flag
		$this->ShowResults = $showres;
		$this->ShowStatCode= $status;
		
		//slim
		$this->SLIM = new \Slim\Slim(array(
			));
	}	

	//do=it
	public function hit($act=null,$slim=null)
	{
		global $_JWT;
		
		if($act != null)
		{
			$this->action = $act;
		}
		if($slim != null)
		{
			$this->SLIM = $slim;
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

		try{
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
					case API_HIT_ENTRY_CHMAIL:
						$this->do_entry_change_email();
						break;	
					//notfound
					default:	
						$this->send_reply($this->notfound());
				}
		} 
		catch (Exception $e) 
		{
			$dmp = @var_export($e->getMessage(),1);
			debug("hit() : EXCEPTION : [ $dmp; ]");
			
			$this->send_reply($this->notfound(
						HTTP_SERVICE_UNAVAILABLE,
						"LDAP-API: Method not found ($dmp)!"
					));
			
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
		
		//header status
		if($this->ShowStatCode)
		{
			//slim
			$code = ($reply['statuscode'] > 0) ? ($reply['statuscode']) :(REST_RESP_510);

			//send header
			$this->SLIM->response->setStatus($code);
			
		}
		
		//show message
		if($this->ShowResults)
		{
			echo json_encode($reply);
		}
			
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				
				return;
			}

			
			//chk email
			$bdata = $this->ldap_user_get_by_userid($user,true);
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

			//disabled
			if(@intval($bdata['data']['status']) <= 0)
			{
					//fmt reply 404
					$reply['statuscode'] = HTTP_NOT_FOUND;
					$reply['message']    = "User is found in db but is disabled!";
					//give it back
					$this->send_reply($reply);
					return;
			}
			
			//get list
			$uidlist   = array();
			$CNlist    = array();
			if(strlen($bdata['data']['tm'])>0      )
			{
				$uidlist[$this->MapGroup->LDAP_GRP_TRAVEL_MART] = trim($bdata['data']['tm']);
				$CNlist[] = $this->MapGroup->LDAP_GRP_TRAVEL_MART;
			}
			if(strlen($bdata['data']['mstr'])>0    )
			{
				$uidlist[$this->MapGroup->LDAP_GRP_MSTR] = trim($bdata['data']['mstr']);
				$CNlist[] = $this->MapGroup->LDAP_GRP_MSTR;
			}
			if(strlen($bdata['data']['rclcrew'])>0 )
			{
				$uidlist[$this->MapGroup->LDAP_GRP_RCLREW] = trim($bdata['data']['rclcrew']);
				$CNlist[] = $this->MapGroup->LDAP_GRP_RCLREW;
			}
			if(strlen($bdata['data']['ctrac'])>0   )
			{
				$uidlist[$this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE]  = trim($bdata['data']['ctrac']);
				$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE;
			}
			if(strlen($bdata['data']['ctrac_app'])>0   )
			{
				$uidlist[$this->MapGroup->LDAP_GRP_CTRACK_APPLICANT] = trim($bdata['data']['ctrac_app']);
				$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_APPLICANT;
			}
			
			$dmp = @var_export($uidlist,1);				
			debug("UIDLIST> $dmp");

			$dmp = @var_export($CNlist,1);				
			debug("CNlist> $dmp");

			//log
			$rw_id = $bdata['data']['rw_id'];
			$res   = $this->try_ldap(LDAP_NORMAL_USER, $rw_id,$pass,LDAP_RDN_GROUPS);
			if(!$res['ldapstat'])
			{
				$this->send_reply($res['ldapmesg']);
				
				return;
				
			}
	
			//unset all active
			for($i=0; $i < @count($CNlist); $i++)
			{
				 $cn   = $CNlist[$i];
				 $xuser= (strlen($uidlist[$cn])>0) ? ($uidlist[$cn]) : ($user);
				 $this->unset_session_db_by($xuser,$cn);
				 $this->unset_sess_id();	
			}

			//set new
			$sids   = array();
			$dbsess = array();
			for($i=0; $i < @count($CNlist); $i++)
			{
				 $cn   = $CNlist[$i];
				 $xuser= (strlen($uidlist[$cn])>0) ? ($uidlist[$cn]) : ($user);
				 
				 debug("$i.#sid> $sessk => $sid -> $user/user=$xuser;");
				
				 $this->set_session_db(array(          
									'user'  => $xuser,
									'cn'    => $cn,
									'sid'   => sprintf("%x-%s-%s-%x-%s",
															  mt_rand(),
															  md5(base64_encode(openssl_random_pseudo_bytes(512))),
															  md5(base64_encode(openssl_random_pseudo_bytes(512))),
															  mt_rand(),
															  substr(md5(uniqid()),0,16) ), 
									));
				 $dbsess["$cn"]  = $this->get_session_db_by($xuser,$cn);														
			}
			
			//get db query
			$dtls   = $this->get_user_details($user);		

			//free memory
			$this->unset_sess_id();
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
			
			if('jwt' == 'jwt')
			{
					//encode
					$reply['jwt']        = $this->jwt(array(
												'user'	 =>  $user,
												'email'	 =>  $reply['email'],
												'apikey' =>  $reply['apikey'],
												'session'=>  $reply['sessionid'],
											) );

					
					
					//oops:HTTP_SERVICE_UNAVAILABLE
					if(null == $reply['jwt'])
					{
						$this->send_reply(
								$this->notfound(
									HTTP_SERVICE_UNAVAILABLE,
									'Service Unavailable')
									);
						return;
					}				
			}
			
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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

			
			//chk email
			$bdata = $this->ldap_user_get_by_userid($uid,true);
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

			//get UID from DB
			if($bdata['data']['rw_id'] <= 0)
			{
					//fmt reply 404
					$reply['statuscode'] = HTTP_NOT_FOUND;
					$reply['message']    = "User not found in db!";
					//give it back
					$this->send_reply($reply);
					return;
			}
			
			if(1)
			{
				if(strlen($bdata['data']['tm'])>0      )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_TRAVEL_MART;
				}
				if(strlen($bdata['data']['mstr'])>0    )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_MSTR;
				}
				if(strlen($bdata['data']['rclcrew'])>0 )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_RCLREW;
				}
				if(strlen($bdata['data']['ctrac'])>0   )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE;
				}
				if(strlen($bdata['data']['ctrac_app'])>0   )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_APPLICANT;
				}
			}
			
			//return
			$result = array(
				'entries' => array(
						'uid'       => $uid,
						'cns'       => $CNlist,
						'rwid'      => $bdata['data']['rw_id'],
						'mail'      => $bdata['data']['email'],
						'sn'        => sprintf("%s",$bdata['data']['lastname']),
						'givenname' => sprintf("%s %s %s",$bdata['data']['firstname'],$bdata['data']['middlename'],$bdata['data']['lastname']),
						'is_migrated' => sprintf("%d",$bdata['data']['is_migrated']),
				),
				'found'   => (@count($CNlist)>0)?(1):(0),
				'member'  => $CNlist,
			);				
			
			//fmt reply 200
			$reply['statuscode'] = HTTP_NOT_FOUND;
			

			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = (@count($CNlist)>0)?("Lists results found."):("Lists results NOT found.");
			$reply['result']     = $result;

			//give it back
			$this->send_reply($reply);
			
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
				$reply['message']    = "Invalid parameters!";
				//give it back
				$this->send_reply($reply);
				return;
			}

			//get paging
			$paging  = $this->paging();
			
			
			//get the list from db
			$res    = $this->ldap_cn_user_list(
						array(
							'page'  => $paging['pagex'],
							'batch' => $paging['batch'],
							'cn'    => $cn,
							)
						);
			if(!$res['exists'])
			{
				//fmt reply 404
				$reply['statuscode'] = HTTP_NOT_FOUND;
				$reply['message']    = "No List found!";
				//give it back
				$this->send_reply($reply);
				return;
			}
			
			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = "List(s) found!";
			$reply['result']     = $res['data'];
			$reply['batch']      = @intval(@count($res['data']));
			
			//paging
			$reply['totalrows']  = $res['totalrows']['total'];
			$reply['currpage']   = (@count($res['data'])) ? ( $paging['page']    ) : 1;
			$reply['nextpage']   = (@count($res['data'])) ? ( $paging['page'] + 1) : 1;
			
			$this->send_reply($reply);
			
	
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
			$active    = trim($_REQUEST["active"] );
			
			//init
			$reply = $this->init_resp();


			//sanity check -> LISTS
			if(
				!strlen($user)        or 
				!strlen($firstname)   or 
				!strlen($lastname)    or 
				!strlen($desc) 
			)
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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
			$dmp = @var_export($bdata,1);				
			debug("DB-RROW> $dmp");

			
			//conn
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
			
			//get userid
			$user = $bdata['data']['rw_id'];
			if($user <= 0)
			{
					//fmt reply 403
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_FORBIDDEN;
					$reply['message']    = "Update db entry failed.";
					$reply['error']      = $this->fmt_err_msg($ldapconn);
					//give it back
					$this->send_reply($reply);
					return;
			}
			
			//modify ldap
			if(1)
			{
						//GROUPS
						$ldaprdn                 = sprintf("uid=%s,%s",$user,LDAP_RDN_GROUPS);  
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
			//active-status-flag
			if(@preg_match("/^(0|1)$/", $active))
			{
					$upd = $this->ldap_user_upd_activeflag_db(
										array(
											'status' => @intval($active),
											'rw_id'  => $bdata['data']['rw_id'],
											)
									);
					debug("[ACTIVE-FLAG] > set = $active;#$upd");	
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
				$reply['message']    = "Invalid parameters!";
				if(strlen($newpass) and ($pass === $newpass))
				{
					$reply['message']    = "Invalid parameters! New password is the same as old one.";	
				}
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
			
			//passed pwd
			$encpass = $this->str_enc($pass);
			if($bdata['data']['passwd'] !== $encpass)
			{
					//fmt reply 404
					$reply['statuscode'] = HTTP_NOT_FOUND;
					$reply['message']    = "Wrong Current password!";
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

			//get userid
			$user = $bdata['data']['rw_id'];
			
			if(1)
			{
				    //GROUPS
					$ldaprdn  = sprintf("uid=%s,%s",$user,LDAP_RDN_GROUPS);  
					
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
				!strlen($email)     or
				( !strlen($pass) and (!$chkmail['exists']) ) or 
				!strlen($desc) or
				!strlen($cn)
			)
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_BAD_REQUEST;
				$reply['message']    = "Invalid parameters!";
				
				//give it back
				$this->send_reply($reply);
				return;
			}

			
			//chk email + and userid
			$bdata = $this->ldap_user_get_by_userid($user);
			if( ( $bdata['exists'] )
				and
			    ( $email !== $bdata['data']['email'] )
			)
			{
					//fmt reply 406
					$reply['statuscode'] = REST_RESP_406;
					$reply['message']    = "User exists already in db!";
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


			//get all CN from DB-RROW
			$CNlist    = array();
			$UIDlist   = array();
			if($chkmail['exists']>0)
			{
				if(strlen($chkmail['data']['tm'])>0      )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_TRAVEL_MART;
				}
				if(strlen($chkmail['data']['mstr'])>0    )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_MSTR;
				}
				if(strlen($chkmail['data']['rclcrew'])>0 )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_RCLREW;
				}
				if(strlen($chkmail['data']['ctrac'])>0   )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE;
				}
				if(strlen($chkmail['data']['ctrac_app'])>0   )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_APPLICANT;
				}
				
			}
			
			$dmp = @var_export($CNlist,1);
			debug("DB CN List> $dmp");
			
			//exists already
			if(@in_array($cn,$CNlist) and @count($CNlist))
			{
				//fmt reply 405
				$reply['status']     = false;
				$reply['statuscode'] = HTTP_METHOD_NOT_ALLOWED;
				$reply['message']    = "LDAP add failed (CN Already Exists).";
				$reply['error']      = null;
				//give it back
				$this->send_reply($reply);
				return;
			}
			
				
			//db dip here
			$ndata['firstname' ] = $firstname ; 
			$ndata['middlename'] = $middlename;
			$ndata['lastname'  ] = $lastname  ;
			$ndata['email'     ] = $email     ;
			
			//just in case
			if(! strlen($middlename))
			{
				$middlename          = 'M';
				$ndata['middlename'] = $middlename;
			}
			
			//sanity check
			if($chkmail['exists']>0)
			{
				//auto-inc-id
				$rawuid = $chkmail['data']['raw_id'];
				
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
				$RAW_UID = $this->ldap_user_add_db($ndata);
				
				//oops
				if($RAW_UID <= 0 )
				{
						//fmt reply 405
						$reply['status']     = false;
						$reply['statuscode'] = HTTP_METHOD_NOT_ALLOWED;
						$reply['message']    = "LDAP DB add failed.";
						$reply['error']      = null;
						//give it back
						$this->send_reply($reply);
						return;
				}
				
				//update password, use the parameter
				$ndata['email' ] = $email     ;
				$ndata['pass']   = base64_encode(pack('H*', md5($pass)));
				$rawstr          = $this->str_enc($pass);
				$ndata['pass']   = "$rawstr";
				$pret            = $this->ldap_user_upd_pwd_db($ndata);
				
				//add
				$reply['message'] = "LDAP user add successful.";
				
				
				//LDAP-ADD here
				if('LDAP-ADD' == 'LDAP-ADD')
				{
						//get all mapping
						$mapx = $this->MapGroup->get($this->MapGroup->LDAP_GRP_RCCL);
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
						$user                    = $RAW_UID;
						$info["uid"]             = $user;
						$info["mail"]            = $email;
						$info["sn"]              = $lastname;
						$info["givenName"]       = sprintf("%s %s %s",$firstname, $middlename,$lastname);
						$info["cn"]              = $mapx['cn'];
						$info["objectClass"][]   = $mapx['objectClass'][0];
						$info["objectClass"][]   = $mapx['objectClass'][1];
						$info["objectClass"][]   = $mapx['objectClass'][2];
						$info["objectClass"][]   = $mapx['objectClass'][3];
						$info["description"]     = $desc;
						$info["userPassword"]    = '{md5}' . base64_encode(pack('H*', md5($pass)));
						$ldaprdn                 = sprintf("uid=%s,%s",$user,$mapx['rdn']);  

						debug("ldaprdn> $ldaprdn");
						
						//chk the CN
						$ldapfilter = sprintf("(uid=%s)",$user);
						debug("filter> $ldapfilter");
						$srch = $this->try_ldap(LDAP_ADMIN_USER);
						$resp = $this->try_filter($srch['ldapconn'],$ldapfilter,null);
						$dmp  = @var_export($resp,1);
						debug("memberof> $dmp");

						//is found
						if(@count($resp['result']['member']) > 0 )
						{
								//fmt reply 405
								$reply['status']     = false;
								$reply['statuscode'] = HTTP_METHOD_NOT_ALLOWED;
								$reply['message']    = "LDAP add failed (UID Already Exists in LDAP Server).";
								$reply['error']      = null;
								//give it back
								$this->send_reply($reply);
								return;
						}
						
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

				}//LDAP-ADD
				
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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
			
			$dmp = @var_export($bdata,1);				
			debug("DB-RROW> $dmp");

			//get UID from DB
			if($bdata['data']['rw_id'] <= 0)
			{
					//fmt reply 404
					$reply['statuscode'] = HTTP_NOT_FOUND;
					$reply['message']    = "User not found in db!";
					//give it back
					$this->send_reply($reply);
					return;
			}
			
			if(1)
			{
				if(strlen($bdata['data']['tm'])>0      )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_TRAVEL_MART;
				}
				if(strlen($bdata['data']['mstr'])>0    )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_MSTR;
				}
				if(strlen($bdata['data']['rclcrew'])>0 )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_RCLREW;
				}
				if(strlen($bdata['data']['ctrac'])>0   )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE;
				}
				if(strlen($bdata['data']['ctrac_app'])>0   )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_APPLICANT;
				}
			}
			
			//return
			$result = array(
				'entries' => array(
						'uid'       => $user,
						'cns'       => $CNlist,
						'rwid'      => $bdata['data']['rw_id'],
						'mail'      => $bdata['data']['email'],
						'sn'        => sprintf("%s",$bdata['data']['lastname']),
						'givenname' => sprintf("%s %s %s",$bdata['data']['firstname'],$bdata['data']['middlename'],$bdata['data']['lastname']),
				),
				'found'   => (@count($CNlist)>0)?(1):(0),
				'member'  => $CNlist,
			);				
			
			//fmt reply 200
			$reply['statuscode'] = HTTP_NOT_FOUND;
			

			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = (@count($CNlist)>0)?("Member list results found."):("Member list results NOT found.");
			$reply['result']     = $result;

			//give it back
			$this->send_reply($reply);
			
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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

			
			//get the UID from DB
			$user = $bdata['data']['rw_id'];
			
			//update ldap password
			if(1)
			{
				    //GROUPS
					$ldaprdn  = sprintf("uid=%s,%s",$user,LDAP_RDN_GROUPS);  
					
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
	
	//reset email
	protected function do_entry_change_email()
	{
			//get params
			$user   = trim($_REQUEST['user']);
			$email   = trim($_REQUEST['email']);
			
			//init
			$reply  = $this->init_resp();

			//sanity check -> LISTS
			if(
				!strlen($user)    or 
				!strlen($email)    
			)
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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
			
			//db dip here
			$chkmail = $this->ldap_user_get_email($email);
			
			$CNlist = array();
			if($chkmail['exists'])
			{
				if(strlen($chkmail['data']['tm'])>0  and strlen($user)    )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_TRAVEL_MART;
				}
				if(strlen($chkmail['data']['mstr'])>0  and strlen($user)    )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_MSTR;
				}
				if(strlen($chkmail['data']['rclcrew'])>0 and strlen($user)  )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_RCLREW;
				}
				if(strlen($chkmail['data']['ctrac'])>0   and strlen($user)  )
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE;
				}
				if(strlen($chkmail['data']['ctrac_app'])>0  and strlen($user))
				{
					$CNlist[] = $this->MapGroup->LDAP_GRP_CTRACK_APPLICANT;
				}
			}
			
			//same EMAIL as OLD 
			if($chkmail['data']['email'] === $bdata['data']['email'] and @count($CNlist))
			{
					//fmt reply 403
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_FORBIDDEN;
					$reply['message']    = "Change email failed. ( Old email is the same as the New 1)";
					

					//give it back
					$this->send_reply($reply);
					return;
			}
			
			//maybe its other's email
			if($chkmail['exists'])
			{
					//fmt reply 403
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_FORBIDDEN;
					$reply['message']    = "Change email failed. ( Email already exists)";
					
					//give it back
					$this->send_reply($reply);
					return;
				
				
			}
			
			//update db
			$updret = $this->ldap_user_upd_email_db(array('email' => $email, 
														  'rw_id' => $bdata['data']['rw_id'] ));
			//db fail
			if(!$updret)
			{
					//fmt reply 403
					$reply['status']     = false;
					$reply['statuscode'] = HTTP_FORBIDDEN;
					$reply['message']    = "Change email failed. ( Db update failed )";
					//give it back
					$this->send_reply($reply);
					return;
			}														  

			//update ldap email
			if('ldap-change-mail' == 'ldap-change-mail-X')
			{
					// prepare data
					$info["mail"]  = $email;

				    //GROUPS
					$ldaprdn  = sprintf("uid=%s,%s",$user,LDAP_RDN_GROUPS);  
					
					$dmp = @var_export($info,1);				
					debug("$kk> RESET-PASS > DN=$ldaprdn; [ $dmp ]");
			
					//update entry
					$update  = ldap_modify($ldapconn, $ldaprdn, $info);
					if(!$update)
					{
							//fmt reply 403
							$reply['status']     = false;
							$reply['statuscode'] = HTTP_FORBIDDEN;
							$reply['message']    = "Change email failed (LDAP server update).";
							$reply['error']      = $this->fmt_err_msg($ldapconn);
							
							//give it back
							$this->send_reply($reply);
							return;
					}
					
			}


			//fmt reply 200
			$reply['status']     = true;
			$reply['statuscode'] = HTTP_SUCCESS;
			$reply['message']    = "Email change successful.";
			$reply['result']     = array(
								 );
			//give it back
			$this->send_reply($reply);

			//free
			if($ldapconn)
			  @ldap_free_result($ldapconn);
	}
	
	//encrypt decrypt
	protected function do_word_shuffle($shuffle=1)
	{
			//get params
			$word  = trim($_REQUEST['word']);
			$user  = trim($_REQUEST['user']);
			
			$reply = $this->init_resp();

			//sanity check -> LISTS
			if( !strlen($word) and !strlen($user) )
			{
				//fmt reply 500
				$reply['statuscode'] = HTTP_BAD_REQUEST;
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
				//get hash from db
				if(strlen($user)>0)
				{
					//chk email
					$bdata = $this->ldap_user_get_by_userid($user);
					if(!$bdata['exists'])
					{
							//fmt reply 404
							$reply['statuscode'] = HTTP_NOT_FOUND;
							$reply['message']    = "User not found in db! Cannot decrypt it.";
							//give it back
							$this->send_reply($reply);
							return;
					}
					$dmp = @var_export($bdata,1);				
					debug("DB-RROW> $dmp");
					
					//decrypt the word
					$res    			 = $this->str_dec($bdata['data']['passwd']);
				}
				else
				{
					//decrypt the word
					$res    			 = $this->str_dec($word);
				}

				if(!strlen($res))
				{
						//fmt reply 404
						$reply['statuscode'] = HTTP_NOT_FOUND;
						$reply['message']    = "Cannot decrypt it.";
						$reply['word']       = $res; 
						
						//give it back
						$this->send_reply($reply);
						return;
				}
				
				//reply
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
	protected function ldap_user_get_by_userid($user='',$emailtoo=false)
	{
		//globals here
		global $gSqlDb;

		debug("ldap_user_get_by_userid() : INFO : [ user=$user; flag=$emailtoo; ]");
		
		//fmt-params
		$user     = addslashes(trim($user));
		$incwhere = ($emailtoo) ? (" email = '$user' or ") : ('');
		
		//select
		$sql = "SELECT * 
				FROM 
					sso_users 
				WHERE 
					( $incwhere
					  tm         = '$user' or 
					  rclcrew    = '$user' or 
					  mstr       = '$user' or
					  ctrac      = '$user' or
					  ctrac_app  = '$user' 
					  ) AND LENGTH(IFNULL('$user','')) > 0
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

	//get data
	protected function ldap_cn_user_list($pdata=array())
	{
		//globals here
		global $gSqlDb;

		debug("ldap_user_upd_cn_db() : INFO");
		
		//fmt-params
		$cn    = addslashes(trim($pdata['cn'   ]));
		$pg    = addslashes(trim($pdata['page' ]));
		$mx    = addslashes(trim($pdata['batch']));
		$limit = " LIMIT $pg, $mx ";
		
		debug("ldap_cn_user_list() : CN GROUP> $cn; $limit;#");
		
		switch(strtolower($cn) )
		{
				case $this->MapGroup->LDAP_GRP_TRAVEL_MART      :
					$sql   = "SELECT SQL_CALC_FOUND_ROWS 
								rw_id          ,			
								firstname      ,
								middlename     ,
								lastname       ,
								email          ,
								status         ,
								group_name     ,
								creation_date  ,
								tm             ,
								is_migrated    ,
								login_attempts ,
								user_role       
							FROM sso_users  
							WHERE 
								LENGTH(IFNULL(tm,'')) > 0 
							ORDER BY rw_id
							$limit
							";
					break;
				case $this->MapGroup->LDAP_GRP_RCLREW           :
					$sql   = "SELECT SQL_CALC_FOUND_ROWS 
								rw_id          ,			
								firstname      ,
								middlename     ,
								lastname       ,
								email          ,
								status         ,
								group_name     ,
								creation_date  ,
								rclcrew        ,
								is_migrated    ,
								login_attempts ,
								user_role       
							FROM sso_users  
							WHERE 
								LENGTH(IFNULL(rclcrew,'')) > 0 
							ORDER BY rw_id
							$limit
							";
					
					break;
				case $this->MapGroup->LDAP_GRP_MSTR             :
					$sql   = "SELECT SQL_CALC_FOUND_ROWS 
								rw_id          ,			
								firstname      ,
								middlename     ,
								lastname       ,
								email          ,
								status         ,
								group_name     ,
								creation_date  ,
								mstr           ,
								is_migrated    ,
								login_attempts ,
								user_role       
							FROM sso_users  
							WHERE 
								LENGTH(IFNULL(mstr,'')) > 0 
							ORDER BY rw_id
							$limit
							";
					
					break;
				case $this->MapGroup->LDAP_GRP_CTRACK_EMPLOYEE  :
					$sql   = "SELECT SQL_CALC_FOUND_ROWS 
								rw_id          ,			
								firstname      ,
								middlename     ,
								lastname       ,
								email          ,
								status         ,
								group_name     ,
								creation_date  ,
								ctrac          ,
								is_migrated    ,
								login_attempts ,
								user_role      
							FROM sso_users  
							WHERE 
								LENGTH(IFNULL(ctrac,'')) > 0 
							ORDER BY rw_id
							$limit
							";
					
					break;
				case $this->MapGroup->LDAP_GRP_CTRACK_APPLICANT :
					$sql   = "SELECT SQL_CALC_FOUND_ROWS 
								rw_id          ,			
								firstname      ,
								middlename     ,
								lastname       ,
								email          ,
								status         ,
								group_name     ,
								creation_date  ,
								ctrac_app      ,
								is_migrated    ,
								login_attempts ,
								user_role       
							FROM sso_users  
							WHERE 
								LENGTH(IFNULL(ctrac_app,'')) > 0 
							ORDER BY rw_id
							$limit
							";
					
					break;					
				default:
					debug("ldap_cn_user_list() : hahaha, oops, invalid CN GROUP> $cn; $uid;#");
					return null;
		}
		
			$res   = $gSqlDb->query($sql, "ldap_cn_user_list() : ERROR : $sql");

		//total-rows
		$is_ok = $gSqlDb->numRows($res);
		$data  = array();
		$sdata = array('exists' => intval($is_ok));
		
		//get data
		if($is_ok>0)
		{
			while($xrow = $gSqlDb->getAssoc($res))
			{
					$data[] = $xrow;
			}
		}
		
		//save
		$sdata['data']      = $data;
		$sdata['totalrows'] = $this->get_total_rows();
		
		debug("ldap_cn_user_list() : INFO : [ $sql => $is_ok ]");
		
		//free-up
		if($res) $gSqlDb->free($res);
		
		//give it back ;-)
		return $sdata;

	}

	//get data
	protected function get_total_rows()
	{
		//globals here
		global $gSqlDb;

		debug("get_total_rows() : INFO : ");
		
		//select
		$sql = "SELECT FOUND_ROWS() as rows";
		$res = $gSqlDb->query($sql, "get_total_rows() : ERROR : $sql");

		//total-rows
		$is_ok = $gSqlDb->numRows($res);
		$data  = array();
		$sdata = array('exists' => intval($is_ok));
		
		//get data
		if($is_ok>0)
		{
			$strow = $gSqlDb->getAssoc($res);
			$total = intval($strow['rows']);
		}
		
		//save
		$sdata['total'] = $total;
		
		debug("get_total_rows() : INFO : [ $sql => $is_ok / $total;]");
		
		//free-up
		if($res) $gSqlDb->free($res);
		
		//give it back ;-)
		return $sdata;
		
	}
	
	//get data
	protected function ldap_user_upd_email_db($pdata=array())
	{
		//globals here
		global $gSqlDb;

		debug("ldap_user_upd_email_db() : INFO");
		
		//fmt-params
		$email = addslashes(trim($pdata['email'     ]  ));
		$rw_id = addslashes(trim($pdata['rw_id'     ]  ));
		
		//
		$sql   = "UPDATE sso_users SET email = '$email', creation_date=Now() WHERE rw_id = '$rw_id' LIMIT 1";

		//exec
		$res   = $gSqlDb->exec($sql, "ldap_user_upd_email_db() : ERROR : $sql");
		$is_ok = $gSqlDb->updRows($res);

		debug("ldap_user_upd_email_db() : INFO : [ $sql => $res => $is_ok ]");

		//free-up
		if($res) $gSqlDb->free($res);

		//give it back ;-)
		return ($res)?(1):(0);
	}

	//get data
	protected function ldap_user_upd_activeflag_db($pdata=array())
	{
		//globals here
		global $gSqlDb;

		debug("ldap_user_upd_email_db() : INFO");
		
		//fmt-params
		$status= addslashes(@intval(trim($pdata['status'])));
		$rw_id = addslashes(trim($pdata['rw_id' ]         ));
		
		//
		$sql   = "UPDATE sso_users SET status = '$status', creation_date=Now() WHERE rw_id = '$rw_id' LIMIT 1";

		//exec
		$res   = $gSqlDb->exec($sql, "ldap_user_upd_activeflag_db() : ERROR : $sql");
		$is_ok = $gSqlDb->updRows($res);

		debug("ldap_user_upd_activeflag_db() : INFO : [ $sql => $res => $is_ok ]");

		//free-up
		if($res) $gSqlDb->free($res);

		//give it back ;-)
		return ($res)?(1):(0);
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

	protected function jwt($payload=array())
	{
		global $_JWTConf;
		
		//fmt
		$jwt   = null;
		$jdata = array(
						'iat'      => $_JWTConf['issuedAt']  ,     // Issued at: time when the token was generated
						'jti'      => $_JWTConf['tokenId']   ,     // Json Token Id: an unique identifier for the token
						'iss'      => $_JWTConf['issuer']    ,     // Issuer
						'nbf'      => $_JWTConf['notBefore'] ,     // Not before
						'exp'      => $_JWTConf['expire']    ,     // Expire
						'payload'  => $payload               ,
 				);
				
				try{
							//set gracefully
							JWT::$leeway = JWT_LEEWAT_TS;
							
							//try to munge
							$jwt = JWT::encode($jdata, $_JWTConf['secretKey'] );
							@header('X-WWW-Authenticate: Basic realm="Ldap-API Secured Area"');
							@header('X-Authorization: Bearer '.$jwt);
							//remove
							JWT::$leeway = 0;
							debug("jwt() : [INFO] $jwt;");
				}
				catch(\Firebase\JWT\BeforeValidException $e)
				{
					debug("jwt() : [BeforeValidException]". $e->getMessage());
				}
				catch(\Firebase\JWT\ExpiredException $e)
				{
					debug("jwt() : [ExpiredException]". $e->getMessage());
				}
				catch(\Firebase\JWT\SignatureInvalidException $e)
				{
					debug("jwt() : [SignatureInvalidException]". $e->getMessage());
				}
				catch(Exception $e)
				{
					debug("jwt() : [Exception]". $e->getMessage());
				}
				
				//give it back
				return $jwt;
		
	}
	
	//paging
	public function paging()
	{
		
		$batch   = @intval(trim($_REQUEST['batch']));
		$page    = @intval(trim($_REQUEST['page' ]));

		//default
		if($batch  < 1)
			$batch = $this->LIMIT;

		//calc
		$pagex = ($page <= 1) ?  (0) : (($page-1)*$batch);

		return array(
			'page'  => (($page < 1) ? (1) : ($page)),
			'batch' => $batch,
			'pagex' => $pagex,
		);
	}	
}//class	
?>


