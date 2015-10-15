<?php
echo @var_export(mcrypt_list_algorithms(),1);

define('LDAP_API_ENC_METHOD','aes-128-cbc');
define('LDAP_API_ENC_IV',    md5(sprintf("%s-%s",LDAP_API_ENC_METHOD,'#!/ldap/restapi/rccl/0123455@')));
define('LDAP_API_ENC_PASS',  md5(sprintf("%s-%s",LDAP_API_ENC_METHOD,'#!/ldap/restapi/rccl/9876543$')));

$raw  = 'It works ? Or not it works ?';
$pass   = '1234';
$method = 'aes128';
$method = 'aes-128-cbc';
$iv     = "01234567890!";
$enc    = base64_encode(openssl_encrypt(base64_encode($raw),       LDAP_API_ENC_METHOD, LDAP_API_ENC_PASS, false, LDAP_API_ENC_IV));
$dec    = rtrim(base64_decode(openssl_decrypt(base64_decode($enc), LDAP_API_ENC_METHOD, LDAP_API_ENC_PASS, false, LDAP_API_ENC_IV)),"\0");



echo "
<hr>STR:<hr>
'$raw'
<hr>
$method
<hr>
$pass
<hr>
ENC: $enc
<hr>
DEC: '$dec'
";


if(1)
{
	$str1 = enc_str('mark-tejero');
$str2 = dec_str($str1);

echo "
$str1
<hr>
$str2
<hr>
";
function enc_str($str,$key='1234567890!')
{
			$iv = mcrypt_create_iv(
			    mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC),
			    MCRYPT_DEV_URANDOM
			);
			
			$encrypted = base64_encode(
			    $iv .
			    mcrypt_encrypt(
			        MCRYPT_RIJNDAEL_128,
			        hash('sha256', $key, true),
			        $str,
			        MCRYPT_MODE_CBC,
			        $iv
			    )
			);
			
			echo "ENCRYPT> $str -> $$encrypted\n";
			return $encrypted;
}


function dec_str($str,$key='1234567890!')
{
		$data      = base64_decode($str);
		$iv        = substr($data, 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC));
		$decrypted = rtrim(
		    mcrypt_decrypt(
		        MCRYPT_RIJNDAEL_128,
		        hash('sha256', $key, true),
		        substr($data, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC)),
		        MCRYPT_MODE_CBC,
		        $iv
		    ),
		    "\0"
		);
		echo "DECRYPT> $str -> $$decrypted\n";
		return $decrypted;
}

}

phpinfo();
?>