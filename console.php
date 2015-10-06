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
	    width: 450px; 
	}
</style>
</head>

<body>

<div class="container">
  
    
	<div class="jumbotron">
    <h1>LDAP API Console</h1>
    </div>
	<div class="table-responsive">
    <table style="width:587px;" class='table table-hover'>
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
            <td align="center" valign="top" class="success">
					
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

