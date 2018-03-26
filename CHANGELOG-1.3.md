# Release Note

## v1.3.0

### Added 
 - Own Preloader.  
 Improved class compilation, removing "use", converting the long syntax "array" to the short syntax.  
 Thanks to [nikic/php-parser](https://github.com/nikic/PHP-Parser).

### Changed 
 - Config::Cache : Aggregate now all config files into one file, and keep file content.
 - Dotconst::PhpDir : Compile now relative path to basePath, from compilePath.
 - Str::normalizePath trigger E_USER_DEPRECATED.

### Removed
 - classpreloader/classpreloader