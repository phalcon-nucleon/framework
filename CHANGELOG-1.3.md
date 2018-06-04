# Release Note

## v1.3.0

### Added 
 - Own Preloader.  
 Improved class compilation, removing "use", converting the long syntax "array" to the short syntax.  
 Thanks to [nikic/php-parser](https://github.com/nikic/PHP-Parser).
 - Kernel HTTP : Routes cache
 - Volt :
    - Csrf Extension, add functions : csrf_field, csrf_token. 
    - Function route(name, arguments, query)
 - Middleware : Auth\Middleware\Authenticate
 - Config : app->static_base_uri : configure url->static_base_uri

### Changed 
 - Config::Cache : Aggregate now all config files into one file, and keep file content.
 - Dotconst::PhpDir : Compile now relative path to basePath, from compilePath.
 - Str::normalizePath trigger E_USER_DEPRECATED.
 - Str::random : Use lib sodium.
 - Arr::isAssoc : Improve performance.
 - Arr::sortRecursive : Add $sort_flags
 - Middlewares : All middlewares define response insteadof throw Exception.

### Removed
 - classpreloader/classpreloader