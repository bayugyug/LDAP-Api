<?php
/**
|	@Filename	:	cfg.php
|	@Description	:	all global vars
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




// prod or sandbox
$gDev       = 0;
$gToday     = date("Y-m-d");

$gDebug     = 0;
$gLogDebug  = 1;
$gIPAddress = strlen(trim($_SERVER["REMOTE_ADDR"]))>0?($_SERVER["REMOTE_ADDR"]):('127.0.0.1');
$gAppName   = sprintf("%s-YUICON-LDAP-API-%s",gethostname(),$gIPAddress);
/*
|    1 = sandbox    or devel
|    0 = production or live
*/

/*

//local
$db['default']['hostname'] = "localhost";
$db['default']['username'] = "chwens_uno";
$db['default']['password'] = "uno888";
$db['default']['database'] = "chwens_uno";
*/

if(1 == $gDev)
{
	//sandbox
	define('WEBLOG' , "log/$gToday.$gAppName.log");	
	$DBOPTS['dbhost'] = "10.8.0.52";
	$DBOPTS['dbuser'] = "rccl_api";
	$DBOPTS['dbpass'] = "rCcl@110415";
	$DBOPTS['dbname'] = "prd_ldp_auth";
}
else
{
	//prod
	define('WEBLOG' , "log/$gToday.$gAppName.log");	
	$DBOPTS['dbhost'] = "10.8.0.52";
	$DBOPTS['dbuser'] = "rccl_api";
	$DBOPTS['dbpass'] = "rCcl@110415";
	$DBOPTS['dbname'] = "prd_ldp_auth";

}


//more
define('LDAP_HOST',       "ldaps://ldap-server.shrss.domain"   );
define('LDAP_PORT',       636                                  );
define('LDAP_RDN_GROUP',  "People"                             );
define('LDAP_RDN_MAIN' ,  "dc=shrss,dc=domain"                 );  
define('LDAP_RDN_USERS' , sprintf("ou=%s,%s",LDAP_RDN_GROUP,LDAP_RDN_MAIN ));  
define('LDAP_RDN_GROUPS' ,sprintf("ou=Groups,%s",LDAP_RDN_MAIN ));  
define('LDAP_LIST_ALL',   "LISTS"                              );
define('LDAP_ADMIN_USER',   201);
define('LDAP_NORMAL_USER',  200);

//classes
define('USER_OBJ_CLASS_1', 'top'                 );
define('USER_OBJ_CLASS_2', 'person'              );
define('USER_OBJ_CLASS_3', 'organizationalPerson');
define('USER_OBJ_CLASS_4', 'inetorgperson'       );

//root
define('LDAP_ENTRY_ROOT_USER',   'root');
define('LDAP_ENTRY_ROOT_PWD',    '!shrss!@#$%');
define('LDAP_ENTRY_ROOT_DN',     ''); //"cn=Directory Manager");
define('LDAP_ENTRY_ROOT_DN_ADD', "cn=Directory Manager");
define('LDAP_ENTRY_ROOT_DN_UPD', "cn=Directory Manager");

//actions
define('API_HIT_SIGN_IN',       'signin');
define('API_HIT_LOG_IN',        API_HIT_SIGN_IN);
define('API_HIT_ENTRY_SEARCH',  'search');
define('API_HIT_ENTRY_LIST',    'list');
define('API_HIT_ENTRY_UPDATE',  'update');
define('API_HIT_ENTRY_ADD',     'add');
define('API_HIT_ENTRY_CHPASS',  'changepass');
define('API_HIT_ENTRY_MEMBER',  'member');
define('API_HIT_ENTRY_RESTAPI', 'restapi');
define('API_HIT_ENTRY_SESSION', 'session');
define('API_HIT_ENTRY_SID',     'sid');
define('API_HIT_SIGN_OUT',      'signout');
define('API_HIT_CSV_DUMP',      'csvdump');

//session
define('API_SID_NAME',          'LDAPApi');


define('API_CSV_FILEFORM',      'filecsv');
define('API_CSV_DIR',           'uploads/');

//environment
define('API_ENVT',$gDev);

//JWT
$_JWT = array(
    'jwt' => array(
        'key'       => base64_encode(openssl_random_pseudo_bytes(64)),     // Key for signing the JWT's, I suggest generate it with base64_encode(openssl_random_pseudo_bytes(64))
        'algorithm' => 'HS512'                                             // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        ),
    'serverName' => 'http://10.8.0.54/api/',
);
//valid API KEYS               
$_API_KEYS = array(            
				sprintf("rccl-%s",md5('#!/rccl/api/k1')),
				sprintf("rccl-%s",md5('#!/rccl/api/k2')),
				sprintf("rccl-%s",md5('#!/rccl/api/k3')),
				sprintf("rccl-%s",md5('#!/rccl/api/k4')),
				sprintf("rccl-%s",md5('#!/rccl/api/k5')),
);                             

?>