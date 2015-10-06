<?php
//gtalk
include_once('init.php');

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


class LDAP_Groups_Api{


	protected $group   = LDAP_RDN_GROUP;
	protected $mapping = null;

	//group names
	const LDAP_GRP_PEOPLE           = 'people';
	const LDAP_GRP_TRAVEL_MART      = 'travel_mart';
	const LDAP_GRP_RCLREW           = 'rclcrew';
	const LDAP_GRP_MSTR             = 'mstr';
	const LDAP_GRP_CTRACK_APPLICANT = 'ctrac_applicant';
	const LDAP_GRP_CTRACK_EMPLOYEE  = 'ctrac_employee';

	/**
	* main API
	*
	*/
	function __construct()
	{
		
		//init
		debug("LDAP_Groups_Api> ");
		$this->init();
	}	
	
	//chk
	public function is_group_valid($grp)
	{
		
		//sanity chk
		switch(strtolower($grp) )
		{
				case self::LDAP_GRP_PEOPLE           :
				case self::LDAP_GRP_TRAVEL_MART      :
				case self::LDAP_GRP_RCLREW           :
				case self::LDAP_GRP_MSTR             :
				case self::LDAP_GRP_CTRACK_APPLICANT :
				case self::LDAP_GRP_CTRACK_EMPLOYEE  :
					$valid = true;
					break;
				default:
					$valid = false;
					break;
					
		}
		//give it back
		return $valid;
		
	}
	//getter
	public function get($grp)
	{
			//get
			$this->group = $grp;
			
			debug("LDAP_Groups_Api::get> $grp");
			
			//just in case
			if(! @is_array($this->mapping))
				$this->init();
			
			//give it ;-)
			return ( ($this->mapping["$grp"]) ? ($this->mapping["$grp"]) : (null));
	}
	
	//filter
	protected function init()
	{


			/**
				People
				travel_mart
				rclcrew
				mstr
				ctrac_applicant
				ctrac_employee

				CN = Common Name
				OU = Organizational Unit
				DC = Domain Component
			**/

			//init all groupings here => ucfirst($key);   
			$this->mapping = array(
				'people'        => array(
									    'dn'          => LDAP_RDN_MAIN,
										'cn'          => 'people',
										'ou'          => 'people',
										'rdn'          => sprintf("ou=people,%s",LDAP_RDN_MAIN ),
										'objectClass' => array('top','person','organizationalPerson','inetorgperson'),
									 ),
				'travel_mart'   => array(
									    'dn'          => LDAP_RDN_MAIN,
										'cn'          => 'travel_mart',
										'ou'          => 'Groups',
										'rdn'         => sprintf("ou=Groups,%s",LDAP_RDN_MAIN ),
										'objectClass' => array('top','person','organizationalPerson','inetorgperson'),
									 ),
				'rclcrew'        => array(
									    'dn'          => LDAP_RDN_MAIN,
										'cn'          => 'rclcrew',
										'ou'          => 'Groups',
										'rdn'         => sprintf("ou=Groups,%s",LDAP_RDN_MAIN ),
										'objectClass' => array('top','person','organizationalPerson','inetorgperson'),
									 ),
				'mstr'          => array(
									    'dn'          => LDAP_RDN_MAIN,
										'cn'          => 'mstr',
										'ou'          => 'Groups',
										'rdn'         => sprintf("ou=Groups,%s",LDAP_RDN_MAIN ),
										'objectClass' => array('top','person','organizationalPerson','inetorgperson'),
									 ),
				'ctrac_applicant'=> array(
									    'dn'          => LDAP_RDN_MAIN,
										'cn'          => 'ctrac_applicant',
										'ou'          => 'Groups',
										'rdn'         => sprintf("ou=Groups,%s",LDAP_RDN_MAIN ),
										'objectClass' => array('top','person','organizationalPerson','inetorgperson'),
									 ),
				'ctrac_employee'=> array(
									    'dn'          => LDAP_RDN_MAIN,
										'cn'          => 'ctrac_employee',
										'ou'          => 'Groups',
										'rdn'         => sprintf("ou=Groups,%s",LDAP_RDN_MAIN ),
										'objectClass' => array('top','person','organizationalPerson','inetorgperson'),
									 ),
			);
		
	} //init

}//class	
?>
