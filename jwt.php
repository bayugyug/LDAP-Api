<?php

//gtalk
include_once('includes/jwt-init.php');


use \Firebase\JWT\JWT;


global $_JWTConf;


echo "CONFIG:<hr>". @var_export($_JWTConf,1).'<hr>';
				$xdata = array(
						'iat'      => $_JWTConf['issuedAt']  ,     // Issued at: time when the token was generated
						'jti'      => $_JWTConf['tokenId']   ,     // Json Token Id: an unique identifier for the token
						'iss'      => $_JWTConf['issuer']    ,     // Issuer
						'nbf'      => $_JWTConf['notBefore'] ,     // Not before
						'exp'      => $_JWTConf['expire']    ,     // Expire
						'payload'  => array(                 // Data related to the signer user
							'userId'   => 'uisfsdf', // userid from the users table
							'userName' => 'sfsdfsdf', // User name
						)
				);
				/**
				
				
				**/
				try{
							@session_start();
							
							
							//set gracefully
							JWT::$leeway = JWT_LEEWAT_TS;
							
							//try to munge
							$jwt = JWT::encode($xdata, $_JWTConf['secretKey'] );
							$str = JWT::decode($jwt,   $_JWTConf['secretKey'],array('HS256'));
							
							
							$_SESSION['_JWT'] = $jwt;

							@header('Content-type: application/json');
							@header('X-WWW-Authenticate: Basic realm="Ldap-API Secured Area"');
							@header('X-Authorization: Bearer '.$jwt);

							echo '<hr><pre>JSON-JWT-ENCDODE:' .@var_export($jwt,1)  .'</pre><hr><hr><br>';
							echo '<hr><pre>JSON-JWT-DECODED:' .@var_export($str,1)  .'</pre><hr><hr><br>';
							echo '<hr><pre>JSON-JWT-payload:' .@var_export($str->payload,1)  .'</pre><hr><hr><br>';
							
							//remove
							JWT::$leeway = 0;
							
				}
				catch(\Firebase\JWT\BeforeValidException $e)
				{
					echo '<br>Caught BeforeValidException: ',  $e->getMessage(), "<br>";
					echo '<br>Caught BeforeValidException: ',  $e->getCode(),    "<br>";
				}
				catch(\Firebase\JWT\ExpiredException $e)
				{
					echo '<br>Caught ExpiredException: ',  $e->getMessage(), "<br>";
					echo '<br>Caught ExpiredException: ',  $e->getCode(),    "<br>";
				}
				catch(\Firebase\JWT\SignatureInvalidException $e)
				{
					echo '<br>Caught SignatureInvalidException: ',  $e->getMessage(), "<br>";
					echo '<br>Caught SignatureInvalidException: ',  $e->getCode(),    "<br>";
				}
				catch(Exception $e)
				{
					echo '<br>Caught exception: ',  $e->getMessage(), "<br>";
					echo '<br>Caught exception: ',  $e->getCode(),    "<br>";
				}
				
			 	
				$dmp = @var_export(apache_response_headers(),1);
				echo "dmp:<pre> $dmp </pre><br />\n";	
				
?>