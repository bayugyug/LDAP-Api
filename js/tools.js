$(function() {

	var DOMAIN = 'http://10.8.0.54';
	var params = {}


	//list remote urls
	params["/api/index.php/ldap/restapi/signin"]     = ["user", "pass","company"]; 
	params["/api/index.php/ldap/restapi/search"]     = ["user", "company"]; 
	params["/api/index.php/ldap/restapi/list"]       = ["company"]; 
	params["/api/index.php/ldap/restapi/modify"]     = ["user", "pass","email","description","company"]; 
	params["/api/index.php/ldap/restapi/changepass"] = ["user", "pass", "newpass","company"]; 
	params["/api/index.php/ldap/restapi/add"]        = ["user", "pass", "firstname", "middlename", "lastname","email","description","company"]; 
	params["/api/index.php/ldap/restapi/memberof"]   = ["user"]; 
	
	
	$('#cmbAPIType').val(0);
	$('#tdParams').html('&nbsp');
	$('#tdResponse').html('&nbsp');
	$('#txtResponse').html('&nbsp');

	
	function generateParams(key)
	{
		var html = "<table cellspacing='5' cellpadding='5' style='width:90%'>";
		for (var i = 0; i < params[key].length; i++)
		{

			html += "<tr>";
			html += "<td style='width:50%;'>" + params[key][i] + "</td>";
			html += "<td><input type='text' value='' style='width:95%;' class='form-control'  /></td>";
			html += "</tr>";

		}
		html += '</table>';
		return html;
	}

    function getData()
    {
        var data = {}
        $('#tdParams tr').each(function (idx, elem) {
            var key = '';
            var val = '';
            $('td', this).each (function (indx, el2) {
                if (indx == 0)
                    key = $(el2).html();
                else
                    val = $('input', el2).val();
            });

            data[key] = val;
        });

        return data;
    }

    $('#cmbAPIType').change(function (e) {

		$('#tdResponse').html('&nbsp');
		$('#txtResponse').html('&nbsp');

        if ($(this).val() != "")
        {
            $('#tdParams').html(generateParams($(this).val()));
            $('#txtURL').val(DOMAIN + $(this).val());
            //display the post params
            $('#tdResponse').html('&nbsp');
        }
        else
        {
            $('#tdParams').html('&nbsp;');
            $('#txtResponse').html('&nbsp');
            $('#txtURL').val("");
        }
    });

    $('#cmbPost').click(function (e) {
		
		$('#tdResponse').html('&nbsp');
		$('#txtResponse').html('&nbsp');
        if ($('#txtURL').val() != "")
        {
            var params = getData();
            $.post( $('#txtURL').val(), params)
              .done(function( resp ) {
                  $('#txtResponse').html('<p class="wbreak">'+resp+'</p>');
            });
        }

    });
});