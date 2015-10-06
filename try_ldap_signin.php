<?php
	function send2Api($param=array())
	{

		$url     = trim($param['url']);
		$data    = $param['data'];
		$opts    = array(
						'http' => array(
							'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
							'method'  => 'POST',
							'content' => http_build_query($data),
						),
					);
		//send it
		$context  = stream_context_create($opts);
		$result   = file_get_contents($url, false, $context);
		$retv     = json_decode($result, true);

		//give it back
		return $retv;
	}
	
	function signin($user,$pass,$company)
	{
		    $api   = array(
							'data' => array(
									'user'    => $user,
									'pass'    => $pass,
									'company' => $company,
									'api-uuid'=> md5(uniqid(time()))),
							'url'  => 'http://10.8.0.54/api/api-entry-login.php',
							);
			//run
		    $ret   = send2Api($api);
			
			//sanity-chk
			return ($ret['status'] and $ret['statuscode'] == 200) ? (true) : (false);
	}
?>	