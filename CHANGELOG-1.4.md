# Release Note

## v1.4.0

### Added 
 - Review handle of exceptions/errors : use `Neutrino\Debug\Exceptions`
    - add `Neutrino\Debug\Exceptions\ExceptionHandlerInterface`
    - add `Neutrino\Debug\Exceptions\Helper`
    - add `Neutrino\Debug\Exceptions\Handler`
    - add `Neutrino\Debug\Exceptions\Errors\FatalErrorException`
    - add `Neutrino\Debug\Exceptions\Errors\WariningErrorException`
    - add `Neutrino\Debug\Exceptions\Errors\NoticeErrorException`
    - add `Neutrino\Debug\Exceptions\Errors\DeprecatedErrorException`
    - add `Neutrino\Debug\Exceptions\Errors\StrictErrorException`
    - add `Neutrino\Debug\Exceptions\Errors\CustomErrorException`
    - add `Neutrino\Foundation\Debug\Exceptions\ExceptionHandler`
    - add `Neutrino\Foundation\Debug\Exceptions\ReporterInterface`
    - add `Neutrino\Foundation\Debug\Exceptions\RenderInterface`
    - add `Neutrino\Foundation\Debug\Exceptions\Reporters\DebugReporter`
    - add `Neutrino\Foundation\Debug\Exceptions\Reporters\LoggerReporter`
    - add `Neutrino\Foundation\Debug\Exceptions\Reporters\FlashReporter`
    - add `Neutrino\Foundation\Debug\Exceptions\Renders\CliRender`
    - add `Neutrino\Foundation\Debug\Exceptions\Renders\WebRender`

### Changed 
 - `Neutrino\Error\**` : is now deprecated, and will be remove in v2.0.
 - `Neutrino\Error\Helper` replace by `Neutrino\Debug\Exceptions\Helper`
 - `Neutrino\Error\Handler` replace by `Neutrino\Debug\Exceptions\Handler`
 - `Neutrino\Debug\Debbuger` moved in `Neutrino\Foundation\Debug\Debbuger`
 - `Neutrino\Debug\DebugToolbar` moved in `Neutrino\Foundation\Debug\DebugToolbar`
 - `Neutrino\Debug\DebugEventsManagerWrapper` moved in `Neutrino\Foundation\Debug\DebugEventsManagerWrapper`
 - `Neutrino\Debug\resources` moved in `Neutrino\Foundation\Debug\resources`
 - `Neutrino\Debug\helpers\functions.php` moved in `Neutrino\Foundation\Debug\resources\helpers.php`

