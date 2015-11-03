<?php
/**
 * Dirty REST
 *
 * A dirt simple REST API framework.
 * Phil Aylesworth 2015-10-29
 *
 * WARNING: This software uses file storage and has no authentication.
 * Do not use this software for a real API. It is for testing and 
 * educational purposes only. Really.
 *
 **/

/******************** Start Config ********************/
// set the location of your API
$api_base = '/web595/api/';

// where to store the data. This directory must be writable by the webserver.
// if it is relative, it is relative to this file.
$storage = 'storage/';

// Check out the README.md to configure the API in config.json.
/******************** End Config ********************/

// set the output format from the Accept: header
// (should also look at filename extension)
if((isset($_SERVER['HTTP_ACCEPT'])) && false !== strpos($_SERVER['HTTP_ACCEPT'], "html")) {
	$format = "html";
} else {
	$format = "json";
}

/**
 * Polyfil for PHP less than 5.4
 * define the function http_response_code if it does not exist
 * contributed on http://php.net/manual/en/function.http-response-code.php by "craig at craigfrancis dot co dot uk"
 */
if (!function_exists('http_response_code')) {
    function http_response_code($code = NULL) {
        if ($code !== NULL) {
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
                    exit('Unknown http status code "' . htmlentities($code) . '"');
                break;
            }
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            header($protocol . ' ' . $code . ' ' . $text);
            $GLOBALS['http_response_code'] = $code;
        } else {
            $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
        }
        return $code;
    }
}


set_exception_handler(function ($e) use ($format) {
	http_response_code($e->getCode());
	send_output($e->getMessage(), $format);
});

// separate parts of url that we need making sure everything is okay
$api_noun = NULL;
$id = NULL;
$pieces = explode("/", str_replace($api_base,'',$_SERVER['REQUEST_URI']));
if(isset($pieces[0])) {
	$api_noun = filter_var($pieces[0], FILTER_SANITIZE_STRING);
	if($api_noun === '') {
		throw new Exception("404, Not Found (105)", 404);
	}
} else {
	throw new Exception("404, Not Found (108)", 404);
}
if(isset($pieces[1])) {
	$id = filter_var($pieces[1], FILTER_SANITIZE_NUMBER_INT);
	if($id < 1) {
		throw new Exception("404, Not Found (113)", 404);  // bad id provided
	}
}

// get API config info
$api = json_decode(file_get_contents("config.json"));

// HTTP verb
$http_method = $_SERVER['REQUEST_METHOD'];



/************************ Routing ************************/
if(function_exists($http_method)) {
	$data = $http_method($api_noun);
} else {
	throw new Exception("Method Not Allowed (129)", 405);
}
send_output($data, $format);



/************************ Utility functions ************************/
/**
 * Very primitive html/json output handler
 **/
function send_output($data, $format) {
	if($format == "html") {
		header("Content-Type: text/plain");
		print_r($data);
	} else {
		header("Content-Type: application/json");
		echo json_encode($data);
	}
}

/**
 * Read data file for the requested items
 **/
function get_api_data($api_noun){
	global $storage;
	return json_decode(file_get_contents("${storage}${api_noun}.json"));
}
 
/**
 * Get index for a given id
 **/
function get_index($data, $id) {
	for($i=0; $i < count($data); $i++) {
		if($data[$i]->id == $id) {
			return $i;
		}
	}
	return NULL;
}

/**
 * Get id for a given index
 **/
function get_id($data, $index) {
	$id = 0;
	for($i=0; $i < count($data); $i++) {
		if($data[$i]->id == $id) {
			$id = $data[$i]->id;
		}
	}
	return $id;
}


/************************ HTTP Methods ************************/

/************
 * POST - Store a new item
 **/
function POST($api_noun) {
	global $id, $storage;
	$api = $GLOBALS['api']->{$api_noun};
	$data = get_api_data($api_noun);
	
	if($id > 0) {
		$index = get_index($data, $id);
		if($index !== NULL) {
			throw new Exception("409 (Conflict) item already exists (196)", 409);
		} else {
			throw new Exception("Not Found (198)", 404);
		}
	}
	
	$id = 0;
	for($i=0; $i < count($data); $i++) {
		if($data[$i]->id > $id) {
			$id = $data[$i]->id;
		}
	}
	$id++;
	
	// create a new item for any POST params that match the API
	$new = array();
	$new['id'] = $id;
	foreach($api as $key => $data_type) {
		if(isset($_POST[$key]) and $key != 'id') {
			$new[$key] = filter_var(trim($_POST[$key]), constant($data_type));
		}
	}
	
	$data[] = $new;

	file_put_contents("${storage}${api_noun}.json", json_encode($data));
	http_response_code(201); // Created
	return $new;
}

/************
 * GET all items or just one
 **/
function GET($api_noun) {
	global $id;
	$data = get_api_data($api_noun);
	
	if($id !== NULL) {
		$index = get_index($data, $id);
		for($i=0; $i < count($data); $i++) {
			if($data[$i]->id == $id) {
				return $data[$i];
			}
		}
		throw new Exception("404, Not Found (240)", 404);
	} else {
		return $data;
	}
}

/************
 * PUT - update an item
 **/
// Coming soon ...


/************
 * DELETE an item
 **/
function DELETE($api_noun) {
	global $id, $storage;
	if($id !== NULL) {
		$api = $GLOBALS['api']->{$api_noun};
		$data = get_api_data($api_noun);
		$index = get_index($data, $id);
		if($index !== NULL) {
			array_splice($data, $index, 1);
			file_put_contents("${storage}${api_noun}.json", json_encode($data));
			return "";
		} else {
			throw new Exception('Selected item does not exist. (266)', 404);
		}
	}
	throw new Exception('DELETE method must specify which item to delete. (269)', 404);
}

