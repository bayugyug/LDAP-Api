<?php

//gtalk
include_once('init.php');

//get params
$user   = trim($_REQUEST['user']);
$pass   = trim($_REQUEST['pass']);
$newpass= trim($_REQUEST["newpass"]);

//init
$reply = init_resp();


//testing
if($gDev == 1)
{
		$user    = 'aplicant3';
		$pass    = 'abc123';
		$newpass = 'abc12345';
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
	status_msg($reply);
	return;
}

//conn
$res = try_ldap(LDAP_NORMAL_USER,$user,$pass);
if(!$res['ldapstat'])
{
	status_msg($res['ldapmesg']);
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
		status_msg($reply);
		return;
}


//fmt reply 200
$reply['status']     = true;
$reply['statuscode'] = HTTP_SUCCESS;
$reply['message']    = "Update password successful.";
$reply['result']     = array(
					 );

//give it back
status_msg($reply);

//free
if($ldapconn)
  @ldap_free_result($ldapconn);

?>
