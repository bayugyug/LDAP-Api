<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="language" content="en">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/tools.js"></script>
    <title>API Console</title>
<style>
	.wbreak {
	    word-wrap: break-word;
	    -moz-hyphens:auto; 
	    -webkit-hyphens:auto; 
	    -o-hyphens:auto; 
	    word-wrap: break-word;
    	    overflow-wrap: break-word;
	    text-overflow: ellipsis;
	    width: 780px; 
	}
</style>
</head>

<body>

<div class="container">
  
    
	<div class="jumbotron">
    <h1>LDAP API Console</h1>
    </div>
	<div class="table-responsive">
    <table style="width:840px;" class='table table-hover'>
        <tr>
            <td style="width:35%;" class="success">
				<strong>
				Choose API:
				  </strong>
				</td>
            <td style="width:65%;" class="success">
                <select name="cmbAPIType" id="cmbAPIType" style="width:100%;" class="form-control" >
                    <option value="">Select API</option>
					<option value="/api/index.php/ldap/restapi/signin"     >01. LDAP Sign-in User</option>
					<option value="/api/index.php/ldap/restapi/search"     >02. LDAP Search User</option>
					<option value="/api/index.php/ldap/restapi/list"       >03. LDAP List User</option>
					<option value="/api/index.php/ldap/restapi/modify"     >04. LDAP Modify/Update User</option>
					<option value="/api/index.php/ldap/restapi/changepass" >05. LDAP User Change Password</option>
					<option value="/api/index.php/ldap/restapi/add"        >06. LDAP User Add/Register</option>
					<option value="/api/index.php/ldap/restapi/memberof"   >07. LDAP User Is Member-Of</option>
					<option value="/api/index.php/ldap/restapi/session"    >08. LDAP User Session [by User/CN]</option>
					<option value="/api/index.php/ldap/restapi/sid"        >09. LDAP User Session [by SID (SessionId]</option>
					<option value="/api/index.php/ldap/restapi/signout"    >10. LDAP Sign-out User</option>
					<option value="/api/index.php/ldap/restapi/resetpass"  >11. LDAP User Reset Password</option>
					<option value="/api/index.php/ldap/restapi/encryptword">12. LDAP Utils (Encrypt)</option>
					<option value="/api/index.php/ldap/restapi/decryptword">13. LDAP Utils (Decrypt)</option>
					<option value="/api/index.php/ldap/restapi/changemail" >14. LDAP User Change Email</option>
					<option value="/api/index.php/ldap/restapi/deactivate" >15. LDAP User De-Activate</option>
					
			   </select>
            </td>
        </tr>

        <tr>
            <td class="success">
			  <strong>
			API URL:
			  </strong>
			</td>
            <td class="success"><input type="text" id="txtURL" value="" readonly="true" style="width:100%;" class="form-control" /></td>
        </tr>

        <tr>
            <td class="success">
				  <strong>
				POST Params (Key / Value)
				  </strong>
				</td>
            <td id="tdParams" class="success">
                &nbsp;
            </td>
        </tr>

        <tr>
            <td class="success">&nbsp;</td>
            <td align="left" valign="top" class="success">
					
					    <a href="#" class="btn btn-info btn-lg" id="cmbPost">
							<span class="glyphicon glyphicon-search">
							</span> POST API CALL
						</a>
				
				</td>
        </tr>
        <tr>
            <th class="success" colspan="2" align="center" valign="top">
				  <strong>
					Response:
				  </strong>
				  </th>
        </tr>
        <tr>
            <td colspan="2" align="left" valign="top" class="bg-info">
				<div id="txtResponse"  class="wbreak" >
               &nbsp;
			   </div>
            </td>
        </tr>


		</table>
	</div>
</div>
</body>
</html>


