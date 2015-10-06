<?php


// using ldap bind
$ldaphost = "ldaps://ldap-server.shrss.domain";
$ldapport = 636;
$ldappass = 'abc123';  // associated password
$ldapuser = 'afaylona';
$ldapuser = 'aplicant1';
$ldaprdn  = sprintf("uid=%s,ou=People,dc=shrss,dc=domain",$ldapuser);  

echo "trying to connect $ldaphost:$ldapport\n";

// connect to ldap server
$ldapconn = ldap_connect($ldaphost, $ldapport)
or die("Could not connect to LDAP server. [$ldaphost -> $ldapport]");

// Set some ldap options for talking to 
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

if ($ldapconn) {

	// binding to ldap server
	//echo var_dump(ldap_bind($ldapconn,"$ldapuser@ldap-server.shrss.domain",$ldappass),1);
	$ldapbind = @ldap_bind($ldapconn, $ldaprdn, $ldappass);

	// verify binding
	if ($ldapbind) {
		echo "LDAP bind successful...\n";
	} else {
		echo "LDAP bind failed...\n";
	}

}
else
{
	echo "ERROR: Ldap connect failed!\n";
}


?>
