<?php
/**
*
*  @filaname    : utils.bignum.convert.php
*
*  @description : 
*
*  @version     : 0.001
*
*  @author      : bayugyug@gmail.com
*
*  @date        : 2009-10-07
*
*
*
*  @modified    :
*  @modified-by :
*  @modified-ver:
*
*              
*/
 
 
define('UBIG_NUM_HEX_STRING',     '0123456789abcdef');
define('UBIG_NUM_DEC_STRING',     '0123456789');
define('UBIG_NUM_CONVERT_DEC2HEX','DEC2HEX');
define('UBIG_NUM_CONVERT_HEX2DEC','HEX2DEC');




/**
*
*  @utils_bignum_convert
*
*  @description
*      - base converter from DEC -> HEX or vice-versa
*
*  @parameters
*      -numstring
*      -base from
*      -base to
*
*  @return
*      - num
*              
*/
function utils_bignum_convert($numstring , $mode="DEC2HEX")
{
    //re-init
    $numstring      = trim("$numstring");//must be a string
    //set constants    
    if(!strcasecmp($mode,UBIG_NUM_CONVERT_DEC2HEX))
    {
    	$frombase     = UBIG_NUM_DEC_STRING;
    	$tobase       = UBIG_NUM_HEX_STRING;
    }
    else if(!strcasecmp($mode,UBIG_NUM_CONVERT_HEX2DEC))
    {
    	$frombase   = UBIG_NUM_HEX_STRING;
    	$tobase     = UBIG_NUM_DEC_STRING;
    }
    else
    {
    	debug("utils_bignum_convert() : ( INVALID MODE: $numstring => $mode ) ");
    }
    
    //init
    $from_count = strlen($frombase);
    $to_count   = strlen($tobase);
    $length     = strlen($numstring);
    $result     = '';
    for ($i = 0; $i < $length; $i++)
    {
        $number[$i] = strpos($frombase, $numstring{$i});
    }
    do // Loop until whole number is converted
    {
        $divide = 0;
        $newlen = 0;
        for ($i = 0; $i < $length; $i++) // Perform division manually (which is why this works with big numbers)
        {
            $divide = $divide * $from_count + $number[$i];
            if ($divide >= $to_count)
            {
                $number[$newlen++] = (int)($divide / $to_count);
                $divide = $divide % $to_count;
            }
            elseif ($newlen > 0)
            {
                $number[$newlen++] = 0;
            }
        }
        $length = $newlen;
        $result = $tobase{$divide} . $result; // Divide is basically $numstring % $to_count (i.e. the new character)
    }
    while ($newlen != 0);
    
    debug("utils_bignum_convert() : ( param=$numstring : res=$result) ");
    
    return $result;
}





?>