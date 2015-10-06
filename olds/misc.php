<?php
/**
|	@Filename	:	misc.php
|	@Description	:	all important methods/subs
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



//filter
function try_filter($ldapconn=null,$ldapfilter=null)
{
		$reply    = init_resp();
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

		//fmt reply 200
		$reply['status']     = true;
		$reply['statuscode'] = HTTP_SUCCESS;
		$reply['message']    = "Lists results found.";
		$reply['result']     = array(
								"entries" => $entries,
								"found"   => $found,
								"total"   => $totctr,
							 );
		//give it back pls ;-)
		return $reply;
}


//connection
function try_ldap($flag = 1,$user='',$pass='')
{
		$reply      = init_resp();
	
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
			$reply['message'] = "Could not connect to LDAP server. [$ldaphost -> $ldapport]";
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
function init_resp()
{
	return array(
			'status'      => false,
			'statuscode'  => HTTP_NOT_FOUND,
			'result'      => array(),
			'message'     => null,
			);
	
}
//free
function free_up()
{
	//globals here
	global $gSqlDb;
	
	//free
	if($gSqlDb)     $gSqlDb->close();
	
	debug("free_up() : INFO : [ free! ]");
	
	//give it back ;-)
}

//msg
function status_msg($reply=array())
{
	$dmp = @var_export($reply,1);
	debug("status_msg() : INFO : [ $dmp; ]");
	echo json_encode($reply);
}

//chk
function is_valid_email($email='')
{
	$patt  = "/^[_A-Za-z0-9-\\+]+(\\.[_A-Za-z0-9-]+)*@[A-Za-z0-9-]+(\\.[A-Za-z0-9]+)*(\\.[A-Za-z]{2,})$/i";
	return @preg_match($patt,$email);
}
?>