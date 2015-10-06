<?php
/**
|	@Filename	:	com.php.init.php
|	@Description	:	all app specific initialization must start here
|                               
|	@Date		:	2003-01-01
|	@Ver		:	Ver 0.01
|	@Author		:	bayugyug@gmail.com
|
|
|       @Modified Date  :
|       @Modified By    :
*/


//http


//utils
include_once('com.utils.debug.php');
include_once('com.utils.bignum.convert.php');
include_once('com.utils.encrypt.decrypt.php');
include_once('com.utils.extract.zip.php');
include_once('com.utils.get.content.type.php');
include_once('com.utils.get.uuid.php');
include_once('com.utils.gsm.chars.php');
include_once('com.utils.io.php');
include_once('com.utils.portlocker.class.php');
include_once('com.utils.logger.php');

//include_once('com.utils.http.client.php');

//include_once('com.utils.mail.mime.send.php');
//include_once('com.utils.mail.send.php');
//include_once('com.utils.msqldb.class.php');

include_once('com.utils.mysqldbh2.class.php');
include_once('com.utils.regex.email.from.string.php');
include_once('com.utils.regex.url.from.string.php');
include_once('com.utils.ucs2.utf8.converter.php');


//user


?>