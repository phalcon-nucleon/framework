# Release Note

## v1.2.2

### Fixed 
 - Debugger : registering on Micro App

### Changed 
 - DebugToolbar : remove nucleon .const information

## v1.2.1

### Fixed 
 - DebugToolbar : View render time

## v1.2.0

### Added
 - Assets
   - Sass : Add Sass compiler
        Allow to compile sass/scss files from resources to public/css, or custom
   - Js : Add Closure compiler
        Allow to compile js files from resources to public/css, via closure api
 - Debug :
    - Add `Neutrino\Debug\Debugger` : Watch all events, Add db profiler, Add views profiler
    - Add `Neutrino\Debug\DebugToolbar` : Add debug/profiler toolbar
    - Add `Neutrino\Debug\VarDump` : Allow to dump a var 
        - .volt use : `{{ dump() }}`
 - HttpClient :
    - Add a lite HttpClient, using Curl or Stream.
 - Process :
    - Add `Neutrino\Process\Process`, allow to create Process via proc_open. 
 - View extension : 
    - Configuring extensions, filters and functions to add to the compiler
    - StrExtension :
        - Allow to call all Support\Str function in volt. 
        Use `str_{name}` will generate `Neutrino\Support\Str::{name}`. 
        - Add `slug` filter (call `Neutrino\Support\Str::slug`)
        - Add `limit` filter (call `Neutrino\Support\Str::limit`)
        - Add `words` filter (call `Neutrino\Support\Str::words`)
    - MergeFilter :
        - Add `merge` filter (call `array_merge`)
    - RoundFilter :
        - Add `round` filter (call `round` | `floor` | `ceil` [@see twig\round](https://twig.symfony.com/doc/2.x/filters/round.html))
    - SliceFilter :
        - Add `slice` filter (call `array_slice`)
    - SplitFilter :
        - Add `split` filter (call `str_split` | `explode` [@see twig\split](https://twig.symfony.com/doc/2.x/filters/split.html))
 - View engines : 
    - Allow to configuring multiple engines.
 - Blueprint : Support foreign key with onDelete or/and onUpdate
 - Blueprint : Allow to create index with custom type
 - Built-in Server
    - `php quark server:run` : run php built-in server
### Change
 - Namespace `Neutrino\View\Engine` moved to `Neutrino\View\Engines`
 - Class `Neutrino\View\Engine\PhpFunction` moved to `Neutrino\View\Engines\Volt\Compiler\Extensions\PhpFunctionExtension`
