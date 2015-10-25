<?php




//more
const LDAP_REQ_METHOD_GET     = 'GET';
const LDAP_REQ_METHOD_POST    = 'POST';
const LDAP_REQ_METHOD_DELETE  = 'DELETE';
const LDAP_REQ_METHOD_PUT     = 'PUT';
const LDAP_REQ_METHOD_OPTIONS = 'OPTIONS';
const LDAP_REQ_METHOD_HEAD    = 'HEAD';


// 200 OK
const REST_RESP_200 = 200;
// 201 Created
const REST_RESP_201 = 201;
// 202 Accepted
const REST_RESP_202 = 202;
// 203 Non-Authoritative Information (since HTTP/1.1)
const REST_RESP_203 = 203;
// 204 No Content
const REST_RESP_204 = 204;
// 205 Reset Content
const REST_RESP_205 = 205;
// 206 Partial Content (RFC 7233)
const REST_RESP_206 = 206;
// 207 Multi-Status (WebDAV; RFC 4918)
const REST_RESP_207 = 207;
// 208 Already Reported (WebDAV; RFC 5842)
const REST_RESP_208 = 208;
// 226 IM Used (RFC 3229)
const REST_RESP_226 = 226;
// 300 Multiple Choices
const REST_RESP_300 = 300;
// 301 Moved Permanently
const REST_RESP_301 = 301;
// 302 Found
const REST_RESP_302 = 302;
// 303 See Other (since HTTP/1.1)
const REST_RESP_303 = 303;
// 304 Not Modified (RFC 7232)
const REST_RESP_304 = 304;
// 305 Use Proxy (since HTTP/1.1)
const REST_RESP_305 = 305;
// 306 Switch Proxy
const REST_RESP_306 = 306;
// 307 Temporary Redirect (since HTTP/1.1)
const REST_RESP_307 = 307;
// 308 Permanent Redirect (RFC 7538)
const REST_RESP_308 = 308;
// 400 Bad Request
const REST_RESP_400 = 400;
// 401 Unauthorized (RFC 7235)
const REST_RESP_401 = 401;
// 402 Payment Required
const REST_RESP_402 = 402;
// 403 Forbidden
const REST_RESP_403 = 403;
// 404 Not Found
const REST_RESP_404 = 404;
// 405 Method Not Allowed
const REST_RESP_405 = 405;
// 406 Not Acceptable
const REST_RESP_406 = 406;
// 407 Proxy Authentication Required (RFC 7235)
const REST_RESP_407 = 407;
// 408 Request Timeout
const REST_RESP_408 = 408;
// 409 Conflict
const REST_RESP_409 = 409;
// 410 Gone
const REST_RESP_410 = 410;
// 411 Length Required
const REST_RESP_411 = 411;
// 412 Precondition Failed (RFC 7232)
const REST_RESP_412 = 412;
// 413 Payload Too Large (RFC 7231)
const REST_RESP_413 = 413;
// 414 Request-URI Too Long
const REST_RESP_414 = 414;
// 415 Unsupported Media Type
const REST_RESP_415 = 415;
// 416 Requested Range Not Satisfiable (RFC 7233)
const REST_RESP_416 = 416;
// 417 Expectation Failed
const REST_RESP_417 = 417;
// 418 I'm a teapot (RFC 2324)
const REST_RESP_418 = 418;
// 419 Authentication Timeout (not in RFC 2616)
const REST_RESP_419 = 419;
// 420 Method Failure (Spring Framework)
const REST_RESP_420 = 420;
// 421 Misdirected Request (RFC 7540)
const REST_RESP_421 = 421;
// 422 Unprocessable Entity (WebDAV; RFC 4918)
const REST_RESP_422 = 422;
// 423 Locked (WebDAV; RFC 4918)
const REST_RESP_423 = 423;
// 424 Failed Dependency (WebDAV; RFC 4918)
const REST_RESP_424 = 424;
// 426 Upgrade Required
const REST_RESP_426 = 426;
// 428 Precondition Required (RFC 6585)
const REST_RESP_428 = 428;
// 429 Too Many Requests (RFC 6585)
const REST_RESP_429 = 429;
// 431 Request Header Fields Too Large (RFC 6585)
const REST_RESP_431 = 431;
// 440 Login Timeout (Microsoft)
const REST_RESP_440 = 440;
// 444 No Response (Nginx)
const REST_RESP_444 = 444;
// 449 Retry With (Microsoft)
const REST_RESP_449 = 449;
// 450 Blocked by Windows Parental Controls (Microsoft)
const REST_RESP_450 = 450;
// 451 Unavailable For Legal Reasons (Internet draft)
const REST_RESP_451 = 451;
// 494 Request Header Too Large (Nginx)
const REST_RESP_494 = 494;
// 495 Cert Error (Nginx)
const REST_RESP_495 = 495;
// 496 No Cert (Nginx)
const REST_RESP_496 = 496;
// 497 HTTP to HTTPS (Nginx)
const REST_RESP_497 = 497;
// 498 Token expired/invalid (Esri)
const REST_RESP_498 = 498;
// 499 Token required (Esri)
const REST_RESP_499 = 499;
// 500 Internal Server Error
const REST_RESP_500 = 500;
// 501 Not Implemented
const REST_RESP_501 = 501;
// 502 Bad Gateway
const REST_RESP_502 = 502;
// 503 Service Unavailable
const REST_RESP_503 = 503;
// 504 Gateway Timeout
const REST_RESP_504 = 504;
// 505 HTTP Version Not Supported
const REST_RESP_505 = 505;
// 506 Variant Also Negotiates (RFC 2295)
const REST_RESP_506 = 506;
// 507 Insufficient Storage (WebDAV; RFC 4918)
const REST_RESP_507 = 507;
// 508 Loop Detected (WebDAV; RFC 5842)
const REST_RESP_508 = 508;
// 509 Bandwidth Limit Exceeded (Apache bw/limited extension)[32]
const REST_RESP_509 = 509;
// 510 Not Extended (RFC 2774)
const REST_RESP_510 = 510;
// 511 Network Authentication Required (RFC 6585)
const REST_RESP_511 = 511;
// 520 Unknown Error
const REST_RESP_520 = 520;
// 522 Origin Connection Time-out
const REST_RESP_522 = 522;
// 598 Network read timeout error (Unknown)
const REST_RESP_598 = 598;
// 599 Network connect timeout error (Unknown)
const REST_RESP_599 = 599;



//just in case;-)
if (!function_exists('http_response_code')) {
        function http_response_code($code = NULL) 
		{
            if ($code !== NULL) 
			{

                switch ($code) {
                    case 100: $text = 'Continue'; break;
                    case 101: $text = 'Switching Protocols'; break;
                    case 200: $text = 'OK'; break;
                    case 201: $text = 'Created'; break;
                    case 202: $text = 'Accepted'; break;
                    case 203: $text = 'Non-Authoritative Information'; break;
                    case 204: $text = 'No Content'; break;
                    case 205: $text = 'Reset Content'; break;
                    case 206: $text = 'Partial Content'; break;
                    case 300: $text = 'Multiple Choices'; break;
                    case 301: $text = 'Moved Permanently'; break;
                    case 302: $text = 'Moved Temporarily'; break;
                    case 303: $text = 'See Other'; break;
                    case 304: $text = 'Not Modified'; break;
                    case 305: $text = 'Use Proxy'; break;
                    case 400: $text = 'Bad Request'; break;
                    case 401: $text = 'Unauthorized'; break;
                    case 402: $text = 'Payment Required'; break;
                    case 403: $text = 'Forbidden'; break;
                    case 404: $text = 'Not Found'; break;
                    case 405: $text = 'Method Not Allowed'; break;
                    case 406: $text = 'Not Acceptable'; break;
                    case 407: $text = 'Proxy Authentication Required'; break;
                    case 408: $text = 'Request Time-out'; break;
                    case 409: $text = 'Conflict'; break;
                    case 410: $text = 'Gone'; break;
                    case 411: $text = 'Length Required'; break;
                    case 412: $text = 'Precondition Failed'; break;
                    case 413: $text = 'Request Entity Too Large'; break;
                    case 414: $text = 'Request-URI Too Large'; break;
                    case 415: $text = 'Unsupported Media Type'; break;
                    case 500: $text = 'Internal Server Error'; break;
                    case 501: $text = 'Not Implemented'; break;
                    case 502: $text = 'Bad Gateway'; break;
                    case 503: $text = 'Service Unavailable'; break;
                    case 504: $text = 'Gateway Time-out'; break;
                    case 505: $text = 'HTTP Version not supported'; break;
                    default:
						$text = 'Unknown Error'; 
						break;
                } // switch
            } // CODE
			else 
			{
				// 599 Network connect timeout error (Unknown)
                $code = 598;
				$text = 'Unknown Error'; 
            }
			$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

			header($protocol . ' ' . $code . ' ' . $text);

			//set code
			$GLOBALS['http_response_code'] = $code;
			//give it back
			return $code;

        }//function
}// if func