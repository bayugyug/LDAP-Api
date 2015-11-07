<?php
//gtalk
include_once('includes/init.php');

$dmp = API_HIT_ENTRY_LIST;
debug("api($dmp): Start!");

//run
$api = new LDAP_Api($dmp);
$api->hit();

debug("api($dmp): act -> $dmp");

//free
unset($api);

debug("api($dmp): Done !");
?>
