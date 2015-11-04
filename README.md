# Dirty REST

A dirt simple REST API framework.

Phil Aylesworth
Version 1.1

WARNING: This software uses file storage and has no authentication. Do not use this software for a real API. It is for testing and educational purposes only. *Really*.

## API Configuration

The `config.json` file is used to describe the API. It is an object, each property is the name of the items part of the API. The key is the property name and the value is the PHP [sanitize filter](http://php.net/manual/en/filter.filters.sanitize.php) used for the data type.

For example:

	{
	  "songs":{
	    "id":"FILTER_SANITIZE_NUMBER_INT",
	    "title":"FILTER_SANITIZE_STRING",
	    "year":"FILTER_SANITIZE_NUMBER_INT",
	    "artist":"FILTER_SANITIZE_STRING",
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

 - check filename extension for data type (html/json). Right now, filename extensions are not supported.
 
## Version History

 - 1.0 2015-10-Initial release
 - 1.1 2015-11-04 Implement PUT to update items