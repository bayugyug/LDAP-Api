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
	
	function api_signin($user,$pass,$company='')
	{
		    $api   = array(
							'data' => array(
									'user'    => $user,
									'pass'    => $pass,
									'api-uuid'=> md5(uniqid(time()))),
									'url'  => 'http://10.8.0.54/api/index.php/ldap/restapi/signin',
							);
			//run
		    $ret   = send2Api($api);
			//sanity-chk
		//	return ($ret['status'] and $ret['statuscode'] == 200) ? (true) : (false);
		return json_encode($ret);
	}	
	
	function api_search($user,$company='')
	{
		    $api   = array(
							'data' => array(
									'user'    => $user,
									'company' => $company,
									'api-uuid'=> md5(uniqid(time()))),
							'url'  => 'http://10.8.0.54/api/api-entry-search.php',
							);
			//run
		    $ret   = send2Api($api);
			
			//sanity-chk
			return $ret;
	}	

	function api_list($company)
	{
		    $api   = array(
							'data' => array(
									'company' => $company,
									'api-uuid'=> md5(uniqid(time()))),
							'url'  => 'http://10.8.0.54/api/api-entry-list.php',
							);
			//run
		    $ret   = send2Api($api);
			
			//sanity-chk
			return $ret;
	}	
	
	function api_entry_add($pdata=array())
	{
		    $api   = array(
							'data' => array(
									'user'          => $pdata['user'       ],
									'pass'	        => $pdata['pass'       ],
									'firstname'	    => $pdata['firstname'  ],
									'middlename'	=> $pdata['middlename' ],
									'lastname'	    => $pdata['lastname'   ],
									'email'	        => $pdata['email'      ],
									'description'	=> $pdata['description'],
									'company'       => $pdata['company'    ],
									'api-uuid'      => md5(uniqid(time()))),
							'url'  => 'http://10.8.0.54/api/api-entry-add.php',
							);
			//run
		    $ret   = send2Api($api);
			
			//sanity-chk
			return $ret;
	}	
	
	function api_entry_modify($pdata=array())
	{
		    $api   = array(
							'data' => array(
									'user'          => $pdata['user'       ],
									'pass'	        => $pdata['pass'       ],
									'email'	        => $pdata['email'      ],
									'description'	=> $pdata['description'],
									'company'       => $pdata['company'    ],
									'api-uuid'      => md5(uniqid(time()))),
							'url'  => 'http://10.8.0.54/api/api-entry-modify.php',
							);
			//run
		    $ret   = send2Api($api);
			
			//sanity-chk
			return $ret;
	}	

	function api_entry_change_pwd($pdata=array())
	{
		    $api   = array(
							'data' => array(
									'user'          => $pdata['user'       ],
									'pass'	        => $pdata['pass'       ],
									'newpass'	    => $pdata['newpass'    ],
									'api-uuid'      => md5(uniqid(time()))),
							'url'  => 'http://10.8.0.54/api/index.php/ldap/restapi/changepass',
							);
			//run
		    $ret   = send2Api($api);
			
			//sanity-chk
			return json_encode($ret);
	}	
	
    function api_session($user,$company='')
	{
		    $api   = array(
							'data' => array(
									'user'    => $user,
									'company' => $company,
									'api-uuid'=> md5(uniqid(time()))),
							'url'  => 'http://10.8.0.54/api/index.php/ldap/restapi/session',
							);
			//run
		    $ret   = send2Api($api);
			
			//sanity-chk
			return $ret;
	}	
	
	function api_session_sid($sid='')
	{
		    $api   = array(
							'data' => array(
									'sid'     => $sid,
									'api-uuid'=> md5(uniqid(time()))),
							'url'  => 'http://10.8.0.54/api/index.php/ldap/restapi/sid',
							);
			//run
		    $ret   = send2Api($api);
			
			//sanity-chk
			return $ret;
	}	

    function api_signout($user,$company='')
	{
		    $api   = array(
							'data' => array(
									'user'    => $user,
									'company' => $company,
									'api-uuid'=> md5(uniqid(time()))),
									'url'  => 'http://10.8.0.54/api/index.php/ldap/restapi/signout',
							);
			//run
		    $ret   = send2Api($api);
			
			//sanity-chk
			return ($ret['status'] and $ret['statuscode'] == 200) ? (true) : (false);
	}	
	
    function api_member_of($user)
	{
		    $api   = array(
							'data' => array(
									'user'    => $user,
									'api-uuid'=> md5(uniqid(time()))),
									'url'  => 'http://10.8.0.54/api/index.php/ldap/restapi/memberof',
							);
			//run
		    $ret   = send2Api($api);
			
			//sanity-chk
			return ($ret['status'] and $ret['statuscode'] == 200) ? (true) : (false);
	}	

	
	
?>
