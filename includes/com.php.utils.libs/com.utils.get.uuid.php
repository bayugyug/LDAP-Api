<?php
/**
*
*  @filaname    : utils.get.uuid.php
*
*  @description : 
*
*  @version     : 0.001
*
*  @author      : bayugyug@gmail.com
*
*  @date        : 2003-01-01
*
*
*
*  @modified    :
*  @modified-by :
*  @modified-ver:
*
*              
*/
 

/**
*
*  @utils_get_uuid
*
*  @description
*      - calculate the uniq id
*        
*
*  @parameters
*      - prefix
*
*  @return
*      - uniq-id
*              
*/
function utils_get_uuid($pfx='')
{

	//fmt
	//sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	$s1        = uniqid(rand(), true);
    	$s2        = uniqid(rand(), true);
    	$tok1      = hash('sha1',$s1);
    	$tok2      = hash('sha1',$s2);
    	$tok3      = md5($s1);
    	$tok4      = md5($s2);
    	$tok5      = md5($tok1.$tok2);
    	$ref_id    = @strtoupper(trim($pfx)). 
    			sprintf("%s-%s-%s-%s-%s",
    			substr($tok1,0,8),
    			substr($tok2,-4),
    			substr($tok3,0,4),
    			substr($tok4,-4),
    			substr($tok5,0,12));
	
        debug("utils_get_uuid() :  [ uniq=$ref_id ]");
        
        //give it back ;-)
        return $ref_id;
}


/**
*
*  @utils_get_rand_str
*
*  @description
*      - generate random string
*
*  @parameters
*      - max
*
*  @return
*      - uniq-id
*              
*/
function utils_get_rand_str($more=5)
{

	//lits of chars
	$bfr    = 'abcdefghijklmnopqrstuvwxyz0123456789';
	
	//get buffer
	$ref_id = str_shuffle( substr(str_shuffle($bfr), 0, $more-1 ) );

	debug("utils_get_rand_str() :  [ str=$ref_id ]");

	//give it back ;-)
	return $ref_id;
}
?>