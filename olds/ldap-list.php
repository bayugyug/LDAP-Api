<?php

//gtalk
include_once('init.php');

//get params
$uid   = trim($_REQUEST['uid']);
$reply = init_resp();

//testing
if($gDev == 1)
{
	$uid   = LDAP_LIST_ALL;
}

//sanity check -> LISTS
if( $uid !== LDAP_LIST_ALL)
{
	//fmt reply 500
	$reply['statuscode'] = HTTP_INTERNAL_SERVER_ERROR;
	$reply['message']    = "Invalid parameters!";
	//give it back
	status_msg($reply);
	return;
}


//sign
$res = try_ldap(LDAP_ADMIN_USER);
if(!$res['ldapstat'])
{
	status_msg($res['ldapmesg']);
	return;
	
}


//get conn
$ldapconn = $res['ldapconn'];

//use for filtering
$ldapfilter = "(cn=*)";  

//chk it
$resp       = try_filter($ldapconn,$ldapfilter);		
//give it back
status_msg($resp);

//free
if($ldapconn)
  @ldap_free_result($ldapconn);

?>
