<?php
/**
*
*  @filaname    : utils.get.content.type.php
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
*  @utils_get_content_type
*
*  @description
*      - get manually the mime type
*
*  @parameters
*      - filename
*      
*      
*      
*
*  @return
*      - true/false
*              
*/
function utils_get_content_type($filename) 
{
	//manual
	$mime_types = array(

            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'htm' => 'text/html',
            'html'=> 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js'  => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg'=> 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff'=> 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz'=> 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt'  => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai'  => 'application/postscript',
            'eps' => 'application/postscript',
            'ps'  => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

	//ext
	$parts = @pathinfo($filename);
        $ext   = strtolower(trim($parts['extension']));
        if (array_key_exists($ext, $mime_types)) 
        {
           $mime = $mime_types[$ext];
        }
        elseif (function_exists('finfo_open')) 
        {
            $finfo    = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            $mime     = $mimetype;
        }
        else 
        {
            $mime = 'application/octet-stream';
        }
        
        debug("utils_get_content_type() : [ $filename : $mime ]");
        
        return $mime ;
}


?>