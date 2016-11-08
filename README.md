# Dirty REST

A dirt simple REST API framework.

Phil Aylesworth
Version 1.1.3

WARNING: This software uses file storage and has no authentication. Do not use this software for a real API. It is for testing and educational purposes only. *Really*.

## API Configuration

The `config.json` file is used to describe the API and for configuration settings. It is an object, one property is called `dirtyRest` and each of the others is the name of an API collection. 

For the configuration, `dirtyRest->apiBase` is the URL path to the `index.php` file and `dirtyRest->storage` is the relative path to the directory that contains the API data files.

For the collections, the key is the property name and the value is the PHP [validate](http://php.net/manual/en/filter.filters.validate.php) and [sanitize](http://php.net/manual/en/filter.filters.sanitize.php) filters used for the data type. The `id` property is not required and will be ignored. Be sure to use validate filters for non-string types to achive the type conversion.

For example:

	{
		"dirtyRest":{
			"apiBase":"/web595/api/",
			"storage":"storage/"
		},
		"pets":{
			"id":"FILTER_VALIDATE_INT",
			"species":"FILTER_SANITIZE_STRING",
			"breed":"FILTER_SANITIZE_STRING",
			"age":"FILTER_VALIDATE_INT"
		}
	}

## The `id` Property

The `id` property is mandatory and will be created even if it is not specified in the `config.json` file. In fact, the specification for `id` is ignored. The `id` property is a positive integer > 0. New records created with POST will have the `id` of the highest `id+1` even if a value is specified in the POST request.

## Webserver Configuration

In order for the routing to work, any request for a file that does not exist must be redirected to the `index.php` file. The following lines added to the `.htaccess` file in the DocumentRoot directory will do the trick. (These could also be put in the main configuration file, but why would you do that?) This is for Apache. If you are using a different web server you will need to configure similar directives.

	RewriteEngine on
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule . index.php [L]

Also, the `storage` directory, and any preexisting file in it, must be writable by the web server.

Most Apache configurations won't allow PUT requests, so they need to be enabled in the `.htaccess` file.

	<Limit GET POST PUT DELETE HEAD OPTIONS>
	    Order allow,deny
	    Allow from all
	</Limit>
	<LimitExcept GET POST PUT DELETE HEAD OPTIONS>
	    Order deny,allow
	    Deny from all
	</LimitExcept>

Any kind of URL rewriting might mess things up. For example, UserDir will need an extra directive right after the `RewriteEngine on` line:

	RewriteBase /~johndoe/somedir/api/

Here, the path will match the path assigned to `apiBase` in the `config.json` file.

## CRUD Actions
 
| CRUD    |    HTTP Verb  |    /items         |    /items/:id         |
|---------|---------------|-------------------|-----------------------|
| Create  |    POST       |    add item       |    --                 |
| Read    |    GET        |    get all items  |    get single item    |
| Update  |    PUT        |    --             |    update item        |
| Delete  |    DELETE     |    --             |    delete item        |

##  Return Status Codes
 
 These are the status codes that will be returned. If `id` is present, but does not resolve to an integer greater than zero, a 404 (Not Found) will be returned. This includes a trailing slash.
 
 - POST /items
     - 201 (Created), Location: header with link to /item/:id containing new ID.
 - POST /items/:id
     - 404 (Not Found)
     - 409 (Conflict) if item already exists.
 
 - GET /items
     - 200 (OK), list of items. Use pagination, sorting and filtering to navigate big lists.
 - GET /items/:id
     - 200 (OK), single item.
     - 404 (Not Found), if ID not found or invalid.
 
 - PUT /items
     - 404 (Not Found)
 - PUT /items/:id
     - 200 (OK) or 204 (No Content)
     - 404 (Not Found), if ID not found or invalid.
 
 - DELETE /items
     - 404 (Not Found)
 - DELETE /items/:id
     - 200 (OK)
     - 404 (Not Found), if ID not found or invalid.
 
 - Any other method
     - 405 (Method Not Allowed)

## To Do

 - check filename extension for data type (.html or .json). Right now, filename extensions are not supported.
 - change config to data type rather than PHP filter
 
## Version History

 - 1.0   2015-10-29  Initial release
 - 1.0.1 2015-11-03  Added a polyfil function for http_response_code to support PHP < 5.4
 - 1.1   2015-11-04  Implement PUT to update items
 - 1.1.1 2015-11-05  Moved PHP config variables to `config.json`
 - 1.1.2 2015-11-12  Fixed POST error. Check to make sure that a collection is configured. Add error message if it can't write to the storage file.
 - 1.1.3 2015-11-12  Added ability to accept JSON data as well as Form data for POST and PUT