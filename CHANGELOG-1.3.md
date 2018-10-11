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
 - Console : Allow force enable / disable color.
 - Debug : add syntax highlight with ark4ne/highlight.
 - Migration : add pretend option Dump the SQL queries that would be run.
 - Migration : implement rename table function.
 - Crypt : Configurable cipher algorithm.
 - Session : Multiple backend adapter configuration
 - Improved debug tools (File highlight, improve exception/error format, improve variable verbose, ...)
 - App : Allow to configure base_url & static_url.

### Changed 
 - Config::Cache : Aggregate now all config files into one file, and keep file content.
 - Dotconst::PhpDir : Compile now relative path to basePath, from compilePath.
 - Str::normalizePath trigger E_USER_DEPRECATED.
 - Str::random : Use lib sodium.
 - Arr::isAssoc : Improve performance.
 - Arr::sortRecursive : Add $sort_flags
 - Middlewares : All middlewares define response insteadof throw Exception.
 - Config::Session : Allow to describe multiple store for session
 - Debug: Clean output before render error
 - Auth: Fixed session remember, and remember cookie deletion on logout.
 - HttpClient: Fixed methods arguments for post, put, patch.
 - HttpClient: Remove default timeout.
 - ListTask : Display an error if a command has a nonexistent method.

### Removed
 - classpreloader/classpreloader