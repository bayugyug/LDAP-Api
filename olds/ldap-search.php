<?php

//gtalk
include_once('init.php');

//get params
$uid   = trim($_REQUEST['uid']);
$reply = init_resp();

//testing
if($gDev == 1)
{
	$uid   = 'aplicant3';
}

//sanity check -> LISTS
if( !strlen($uid))
{
	//fmt reply 500
	$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
	$reply['message']    = "Invalid parameters!";
	//give it back
	status_msg($reply);
	return;
}

$res = try_ldap(LDAP_ADMIN_USER);
if(!$res['ldapstat'])
{
	status_msg($res['ldapmesg']);
	return;
	
}


//get conn
$ldapconn = $res['ldapconn'];

//use for filtering
$ldapfilter = sprintf("uid=%s",$uid); 

//chk it
$resp       = try_filter($ldapconn,$ldapfilter);		
//give it back
status_msg($resp);

//free
if($ldapconn)
  @ldap_free_result($ldapconn);

?>
