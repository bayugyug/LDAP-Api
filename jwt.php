<?php

//gtalk
include_once('includes/init.php');
include_once('JWT/JWT.php');

use \Firebase\JWT\JWT;

echo @var_export(format_jwt('mark','mstr'),1);
echo "===============\n";
echo "\n";

function format_jwt($user,$cn)
{
		global $_JWT;

		
		//instance 
		$app = new \JWT\JWT;
		 
			$tokenId    = base64_encode(openssl_random_pseudo_bytes(32));
			$issuedAt   = time();
			$notBefore  = $issuedAt  + 60;    //Adding 10 seconds
			$expire     = $notBefore + 60*60; // Adding 60 seconds X 60 minutes
			$serverName = $_JWT['serverName'];

			/*
			 * Create the token as an array
			 */
			$data = array(
				'iat'  => $issuedAt,         // Issued at: time when the token was generated
				'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
				'iss'  => $serverName,       // Issuer
				'nbf'  => $notBefore,        // Not before
				'exp'  => $expire,           // Expire
				'data' => array(                  // Data related to the signer user
					'cn'       => $cn,   
					'userName' => $user, 
				)
			);
			print_r($data)	;
			$secretKey = base64_decode( $_JWT['jwt']['key']);

			/*
			 * Extract the algorithm from the config file too
			 */
			$algorithm = $_JWT['jwt']['algorithm'];

			/*
			 * Encode the array to a JWT string.
			 * Second parameter is the key to encode the token.
			 * 
			 * The output string can be validated at http://jwt.io/
			 */
			$jwt = $app::encode(
				$data,      //Data to be encoded in the JWT
				$secretKey, // The signing key
				$algorithm  // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
				);
			
			//give it back
			return array (
					'jwt' => $jwt,
			);
		
	}