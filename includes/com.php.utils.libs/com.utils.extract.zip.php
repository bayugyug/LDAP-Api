<?php
/**
|--------------------------------------------------------------------------
| @Filename: utils.extract.zip.php
|--------------------------------------------------------------------------
| @Desc    : extract zip file
| @Date    : 2010-04-15
| @Version : 1.0 
| @By      : 
|  
|
|
| @Modified By  :  
| @Modified Date: 
*/





/**
| @name
|      - utils_extract_zip
|
| @params
|      - 
|
| @return
|      -
|
| @description
|      - extract zip file
|
**/
if(!function_exists('utils_extract_zip') )
{

    function utils_extract_zip($filename, $extract_to_dir)
    {
        $zip = new ZipArchive;
        $res = $zip->open($filename);
        
        if ($res === TRUE) 
        {
          $result = $zip->extractTo($extract_to_dir);
          $zip->close();
          
          if(!$result) return false;
        } 
        else 
        {
          return false;
        }
        
        return true;
    }
}
?>