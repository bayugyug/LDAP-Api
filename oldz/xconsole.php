<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="language" content="en">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<script  src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script  src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	<script  type="text/javascript" src="js/tools.js"></script>
	<link    href="css/uploadfile.css" rel="stylesheet">
	<script  src="js/jquery.uploadfile.min.js"></script>
	<link    href="css/styles.css" rel="stylesheet">
    <script  src="js/SimpleAjaxUploader.js"></script>
   
   
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
					<option value="/api/index.php/ldap/restapi/session"    >08. LDAP User Session</option>
					<option value="/api/index.php/ldap/restapi/signout"    >09. LDAP Sign-out User</option>
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


                <tr>
            <td>
                          <strong>
                          <button type="button" class="btn btn-info" id='showdiv'>WHAT ?</button>
                          </strong>
                        </td>
            <td>
                           <span id="divUpload"  style="display: none" name="divUpload" >

							<h1>Dump CSV @ LDAP</h1>
							<form method="post" enctype="multipart/form-data"  action="/api/index.php/ldap/restapi/dumpcsv">
							 <div class="container">
							<div class="page-header">
							</div>
							  <div class="row" style="padding-top:10px;">
								<div class="col-xs-2">
								  <button id="uploadBtn" class="btn btn-large btn-primary">Choose File</button>
								</div>
								<div class="col-xs-10">
							  <div id="progressOuter" class="progress progress-striped active" style="display:none;">
								<div id="progressBar" class="progress-bar progress-bar-success"  role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
								</div>
							  </div>
								</div>
							  </div>
							  <div class="row" style="padding-top:10px;">
								<div class="col-xs-10">
								  <div id="msgBox">
								  </div>
								</div>
							  </div>
						  </div>
						  </form>
							<script>
							$(document).ready(function(){ 							  // Handler for .ready() called.
								$("#showdiv" ).click(function() {
										$("#divUpload").toggle();
								});
								
								$("#cmbPost" ).click(function() {
										$("#fileuploader").html("&nbsp;");
										$("#divUpload").hide();
								});
								
							});
							
							function escapeTags( str ) {
							  return String( str )
									   .replace( /&/g, '&amp;' )
									   .replace( /"/g, '&quot;' )
									   .replace( /'/g, '&#39;' )
									   .replace( /</g, '&lt;' )
									   .replace( />/g, '&gt;' );
							}
							window.onload = function() {
							  var btn = document.getElementById('uploadBtn'),
								  progressBar = document.getElementById('progressBar'),
								  progressOuter = document.getElementById('progressOuter'),
								  msgBox = document.getElementById('msgBox');
							  var uploader = new ss.SimpleUpload({
									button: btn,
									url: '/api/index.php/ldap/restapi/dumpcsv',
									name: 'filecsv',
									multipart: true,
									hoverClass: 'hover',
									focusClass: 'focus',
									responseType: 'json',
									startXHR: function() {
										progressOuter.style.display = 'block'; // make progress bar visible
										this.setProgressBar( progressBar );
									},
									onSubmit: function() {
										msgBox.innerHTML = ''; // empty the message box
										btn.innerHTML = 'Uploading...'; // change button text to "Uploading..."
									  },
									onComplete: function( filename, response ) {
										btn.innerHTML = 'Choose Another File';
										progressOuter.style.display = 'none'; // hide progress bar when upload is completed
										if ( !response ) {
											msgBox.innerHTML = 'Unable to upload file';
											return;
										}
										if ( response.success === true ) {
											msgBox.innerHTML = '<strong>' + escapeTags( filename ) + '</strong>' + ' successfully uploaded.';
										} else {
											if ( response.msg )  {
												msgBox.innerHTML = escapeTags( response.msg );
											} else {
												msgBox.innerHTML = 'An error occurred and the upload failed.';
											}
										}
									  },
									onError: function() {
										progressOuter.style.display = 'none';
										msgBox.innerHTML = 'Unable to upload file';
									  }
								});
							};
							</script>
							
                           </span>

                        </td>
        </tr>

		
		</table>
	</div>
</div>


</body>
</html>

