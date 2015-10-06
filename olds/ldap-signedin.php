<?php

//gtalk
include_once('init.php');

//get params
$user   = trim($_REQUEST['user']);
$pass   = trim($_REQUEST['pass']);

//testing
if($gDev == 1)
{
		$user    = 'aplicant3';
		$pass    = 'abc12345';
		
		$user    = 'aplicant4';
		$pass    = 'abc123';
}
$reply = init_resp();

//sanity check -> LISTS
if( !strlen($user) or !strlen($pass))
{
	//fmt reply 500
	$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
	$reply['message']    = "Invalid parameters!";
	//give it back
	status_msg($reply);
	return;
}

//log
$res = try_ldap(LDAP_NORMAL_USER, $user,$pass);
if(!$res['ldapstat'])
{
	status_msg($res['ldapmesg']);
	return;
	
}


//fmt reply 200
$reply['status']     = true;
$reply['statuscode'] = HTTP_SUCCESS;
$reply['message']    = "Sign-in successful.";
$reply['result']     = array(
					 );

//give it back
status_msg($reply);

//free
if($ldapconn)
  @ldap_free_result($ldapconn);

?>

?>
