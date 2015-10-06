<?php
/**
*
*  @filaname    : utils.web.caller.php
*
*  @description : 
*
*  @version     : 0.001
*
*  @author      : bayugyug@gmail.com
*
*  @date        : 2003-01-01
*
*
*
*  @modified    :
*  @modified-by :
*  @modified-ver:
*
*              
*/
 
 
 include_once('Zend/Cache.php');
 include_once('Zend/Http/Client.php');
 
 define('HTTP_CLIENT_CACHE_DIR',        'tmp/');
 define('HTTP_CLIENT_MAX_REDIRECT',      5);
 define('HTTP_CLIENT_MAX_TIMEOUT',       600);
 define('HTTP_CLIENT_KEEP_ALIVE',        1);
 define('HTTP_CLIENT_CACHE_DURATION',    24 * 60 * 60); //24-hr
 define('HTTP_CLIENT_CACHE_SERIALIZED',  1);
 define('HTTP_CLIENT_CACHE_HASH1',       substr(@md5(__FILE__)       ,1,8)) ;
 define('HTTP_CLIENT_CACHE_HASH2',       substr(@md5(strrev(__FILE__)),1,4));
 define('HTTP_CLIENT_CACHE_SEPARATOR',   '<#|#|#>');
 define('HTTP_CLIENT_CACHE_PREFIX',      'http_request_cache');
 define('HTTP_CLIENT_CACHE_ETAG',        '_etag');
 define('HTTP_CLIENT_CACHE_LAST_MODIFIED','_last_modified');
 define('HTTP_CLIENT_CACHE_CODE',         304);
 define('HTTP_CLIENT_DEBUG',              1);



if(! function_exists('utils_web_caller') )
{

	function utils_web_caller($pdata=null)
	{
		//init handle
		$url = trim($pdata['url']);
		$usr = trim($pdata['usr']);
		$pwd = trim($pdata['pwd']);

		$ch  = @curl_init();

		//just checking ;-)
		if(!$ch)
		{	
			debug("utils_web_caller() : info => INIT FAILED [ $url ]");
			return null;
		}

		//opts
		@curl_setopt ($ch,  CURLOPT_URL, $url);

		//set usr/pwd
		if(strlen($usr) and strlen($pwd))
			curl_setopt($ch, CURLOPT_USERPWD, $usr.':'.$pwd); 

		//more options
		@curl_setopt ($ch , CURLOPT_HEADER        , 1);
		@curl_setopt ($ch , CURLOPT_FOLLOWLOCATION, 1);
		@curl_setopt ($ch , CURLOPT_RETURNTRANSFER, 1);        
		@curl_setopt ($ch , CURLOPT_AUTOREFERER   , 1);    
		@curl_setopt ($ch , CURLOPT_SSL_VERIFYPEER, FALSE); 
		@curl_setopt ($ch , CURLOPT_SSL_VERIFYHOST, 2); 
		@curl_setopt ($ch , CURLOPT_CONNECTTIMEOUT   , HTTP_CLIENT_MAX_TIMEOUT);
		@curl_setopt ($ch , CURLOPT_TIMEOUT          , HTTP_CLIENT_MAX_TIMEOUT);
		@curl_setopt ($ch , CURLE_OPERATION_TIMEOUTED, HTTP_CLIENT_MAX_TIMEOUT);// 5 mins

		//headers
		if(@is_array($pdata['header']))
		{
			@curl_setopt($ch,CURLOPT_HTTPHEADER,$pdata['header']);
		}
		//post
		if(isset($pdata['postdata']))
		{
			@curl_setopt($ch, CURLOPT_POST, 1);               
			@curl_setopt($ch, CURLOPT_POSTFIELDS, $pdata['postdata']); 
		}

		//proxy
		if($pdata['is_proxy'])
			@curl_setopt($ch, CURLOPT_PROXY, $pdata['proxy']);

		//u.a.
		if(strlen(trim($pdata['ua'])))
			@curl_setopt($ch, CURLOPT_USERAGENT, $pdata['ua']); 

		//set
		$res      = @curl_exec($ch);
		$cinfo    = @curl_getinfo($ch);
		$cerror   = @curl_error($ch);
		$hsize    = $cinfo["header_size"];
		$body     = @substr($res, $hsize );
		$req_code = $cinfo["http_code"];  


		//free
		@curl_close($ch);

		debug("utils_web_caller() : info => [ $req_code / $url / $body ]");

		//give it back ;-)
		return array(
				'status' => (false === $body or 200 != $req_code) ? (0) : (1),
				'code'   => $req_code,
				'res'    => $body,
				'type'   => $cinfo['content_type'],
				'size'   => $cinfo['size_download'],
			    );
	}


}
 
if(! function_exists('utils_http_client') )
{

	function utils_http_client($pdata=null)
	{

		//init handle
		$url     = trim($pdata['url']);
		$usr     = trim($pdata['usr']);
		$pwd     = trim($pdata['pwd']);

		//init
		$client  = new Zend_Http_Client();

		try 
		{
			//init
			$client->resetParameters();
			$client->setUri($url);
			
			//settings					
			$client->setConfig( array( 
				'maxredirects'    => $pdata['no-redirect'] ? 0    : HTTP_CLIENT_MAX_REDIRECT,
				'strictredirects' => $pdata['no-redirect'] ? true : false,
				'adapter'         => 'Zend_Http_Client_Adapter_Socket',
				'timeout'         => HTTP_CLIENT_MAX_TIMEOUT, 
				'keepalive'       => true) );
			//header?
			if(@is_array($pdata['header']))
				$client->setHeaders($pdata['header']);

			//fixed-hdrs
			$client->setHeaders('If-None-Match',     null);
			$client->setHeaders('If-Modified-Since', null);

			//usr + pwd
			if(strlen($usr) and strlen($pwd))
				$client->setAuth($usr, $pwd);



			//post
			if($pdata['postdata'])
			{
				$client->setHeaders('Content-Type',     'application/x-www-form-urlencoded');
				$client->setParameterPost($pdata['postdata']);				  

			}	

			//upload
			if(@file_exists($pdata['uploadfile']) and strlen($pdata['uploadname']) )
			{
				// Uploading an existing file
				$client->setFileUpload($pdata['uploadfile'], $pdata['uploadname']);
			}

			//exec
			$response   = $client->request($pdata['postdata'] ? 'POST' : 'GET');
			$req_code   = $response->getStatus();
			$req_status = $response->isSuccessful();
			$req_hdrs   = $response->getHeaders();
			$req_body   = $response->getBody();

		}
		catch (Zend_Http_Client_Exception $e) 
		{
			$err  = 'Zend_Http_Client_Exception: An error occurred (' .$e->getMessage().')';
			
			debug("utils_http_client() : EXCEPTION => [ $err ]");
		}


		if(HTTP_CLIENT_DEBUG)
			debug("utils_http_client() : info => [ $req_code / $url / $body ]");

		//give it back ;-)
		return array(
				'status' => (!$req_status or 200 != $req_code) ? (0) : (1),
				'code'   => $req_code,
				'res'    => $req_body,
				'hdr'    => $req_hdrs,
				'err'    => $err,
				'cached' => $is_cached ,
				'cacheid'=> $cacheid ,
			    );
	}
}

?>