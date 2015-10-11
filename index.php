<?php

//gtalk
include_once('includes/init.php');




/**
|	@Filename	:	ldap.api.php
|	@Description:	entry point
|                               
|	@Date		:	2015-10-03
|	@Ver		:	Ver 0.01
|	@Author		:	bayugyug@gmail.com
|
|
|   @Modified Date:
|   @Modified By  :
|    
**/

/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */



//auto-load
\Slim\Slim::registerAutoloader();

//instance 
$app = new \Slim\Slim(array(
));
 
//more settings
const MY_APP_NAME = 'LDAPApi';

$app->setName(MY_APP_NAME);

//cfg here
$app->config('debug', false);

//just in-case
if(0)
{
		$app->config('cookies.lifetime', '720 minutes');
		$app->config('cookies.path',     '/');
		$app->config('cookies.encrypt',    true);
		$app->config('cookies.secret_key', md5( sprintf("%s-%s",MY_APP_NAME,@date('Ymd') ) ) );
		$app->config('http.version', '1.1');
}



//instantiate it here
debug("api(): Start!");

//run
$api = new LDAP_Api(API_HIT_ENTRY_RESTAPI);
$api->hit();

debug("api(): VIA REST API > ");
 
 
//@ MAPPING of ROUTES


//ldap group
$app->group('/ldap', function () use ($app,&$api) 
{

	$app->group('/restapi', function () use ($app,&$api) 
	{
		//sign-in
		$app->map('/signin', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_SIGN_IN);
			return true;
		})->via('GET', 'POST');  
	 
        //add entry
		$app->map('/add', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_ENTRY_ADD);
			return true;
		})->via('POST', 'PUT');  
        
		//update entry
		$app->map('/modify', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_ENTRY_UPDATE);
			return true;
		})->via('POST', 'PUT');
		
		//search
		$app->map('/search', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_ENTRY_SEARCH);
			return true;
		})->via('GET', 'POST');
		
		//list
		$app->map('/list', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_ENTRY_LIST);
			return true;
		})->via('GET', 'POST');
    	
		//change-password
		$app->map('/changepass', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_ENTRY_CHPASS);
			return true;
		})->via('GET', 'POST');
		
		//member-of
		$app->map('/memberof', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_ENTRY_MEMBER);
			return true;
		})->via('GET', 'POST');  
		
		//session
		$app->map('/session', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_ENTRY_SESSION);
			return true;
		})->via('GET', 'POST');  
		
		//session id
		$app->map('/sid', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_ENTRY_SID);
			return true;
		})->via('GET', 'POST');  
		
		//sign-out
		$app->map('/signout', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_SIGN_OUT);
			return true;
		})->via('GET', 'POST'); 
		
		//csv upload
		$app->map('/dumpcsv', function () use ($app,&$api) 
		{
			$api->hit(API_HIT_CSV_DUMP);
			return true;
		})->via('GET', 'POST'); 
		
	}); //MAP REST-API
	
}); //MAP LDAP-GROUP

 
 
//404
$app->notFound(function () use ($app,&$api) 
{
    $api->send_reply(
					$api->notfound(REST_RESP_404,
							       "LDAP-API: Method not found!")
					);
});

 
 
/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();

debug("api(): Done!");




/*
//GET variable
$paramValue = $app->request->get('paramName');

//POST variable
$paramValue = $app->request->post('paramName');

//PUT variable
$paramValue = $app->request->put('paramName');
*/