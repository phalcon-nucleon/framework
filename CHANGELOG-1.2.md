#Release Note

## v1.2.0

### Added
 - View extension : 
    - Configuring extensions, filters and functions to add to the compiler
    - StrExtension :
        - Allow to call all Support\Str function in volt. 
        Use `str_` will generate `Neutrino\Support\Str::`. 
    - SlugFilter :
        - Allow to add a filter who generate `Neutrino\Support\Str::slug`
    - LimitFilter :
        - Allow to add a filter who generate `Neutrino\Support\Str::limit`
    - WordsLimitFilter :
        - Allow to add a filter who generate `Neutrino\Support\Str::words`
 - View engines : 
    - Allow to configuring multiple engines.
 - Blueprint : Support foreign key with onDelete or/and onUpdate
### Change
 - Namespace `Neutrino\View\Engine` moved to `Neutrino\View\Engines`
 - Class `Neutrino\View\Engine\PhpFunction` moved to `Neutrino\View\Engines\Volt\Compiler\Extensions\PhpFunctionExtension`
