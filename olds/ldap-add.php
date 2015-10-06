<?php

//gtalk
include_once('init.php');

//get params
$user      = trim($_REQUEST['user']);
$pass      = trim($_REQUEST['pass']);
$firstname = trim($_REQUEST["firstname" ]);
$middlename= trim($_REQUEST["middlename"]);
$lastname  = trim($_REQUEST["lastname"  ]);
$email     = trim($_REQUEST["mail"]        );
$desc      = trim($_REQUEST["description"] );



//init
$reply = init_resp();


//testing
if($gDev == 1)
{
		$user      = 'aplicant4';
		$pass      = 'abc123';
		$firstname = "fname-4";
		$middlename= "mname-4";
		$lastname  = "lname-4";
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
	status_msg($reply);
	return;
}

//conn
$res = try_ldap(LDAP_ADMIN_USER);
if(!$res['ldapstat'])
{
	status_msg($res['ldapmesg']);
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
		status_msg($reply);
		return;
}


//fmt reply 200
$reply['status']     = true;
$reply['statuscode'] = HTTP_SUCCESS;
$reply['message']    = "LDAP user add successful.";
$reply['result']     = array(
					 );

//give it back
status_msg($reply);

//free
if($ldapconn)
  @ldap_free_result($ldapconn);

?>
