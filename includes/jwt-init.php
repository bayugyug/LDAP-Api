<?php
/**
|	@Filename	:	const.php
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


//framework
include_once('JWT/BeforeValidException.php');
include_once('JWT/ExpiredException.php');
include_once('JWT/SignatureInvalidException.php');
include_once('JWT/JWT.php');


define('JWT_ENCRYPT_KEY1',md5('#!/bin/rccl/ldap/@ap1!') );
define('JWT_ENCRYPT_KEY2',md5('#!/bin/heneral/l4n@!'  ) );
define('JWT_START_TS',   time());
define('JWT_MORE_TS',    60);
define('JWT_LEEWAT_TS',  120);
define('JWT_URL',        'http://10.8.0.54/api/console.php');
define('JWT_SECRET_KEY',  base64_encode(md5(sprintf("%s-%s",JWT_ENCRYPT_KEY1,JWT_ENCRYPT_KEY2))) );
	
//config
$_JWTConf = array(
		'issuedAt'    => JWT_START_TS,                                          // Issued at: time when the token was generated
		'tokenId'     => base64_encode(sprintf("%08x-%08x-%s-%08x-%08x-%s",
								mt_rand(),
								mt_rand(),
								md5(uniqid()),
								mt_rand(),
								mt_rand(),
								md5(uniqid())
								)),                                              
		'issuer'      => JWT_URL,                                               // Issuer
		'notBefore'   => (JWT_START_TS + JWT_MORE_TS) ,                         // Not before
		'expire'      => (JWT_START_TS + JWT_MORE_TS)+(JWT_MORE_TS*JWT_MORE_TS),// Expire
		'secretKey'   => JWT_SECRET_KEY,                                        // Secret Key
);
?>
