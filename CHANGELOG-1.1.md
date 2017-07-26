#Release Note

## v1.1.0

### Added
 - \Neutrino\Support\Fluent
 - \Neutrino\Support\Fluent\Fluentable
 - \Neutrino\Support\Fluent\Fluentize
    - @see [laravel/fluent](https://github.com/laravel/framework/blob/5.4/src/Illuminate/Support/Fluent.php)
 - \Neutrino\Support\Func::tap :
    - @see [laravel/helpers::tap](https://github.com/laravel/framework/blob/5.4/src/Illuminate/Support/helpers.php#L944)
 - Event "kernel:terminate" :
    - Raised on any kernel termination, only one time.
### Change
 - \Neutrino\Support\Arr::read & \Neutrino\Support\Arr::fetch :
    - Harmonize read & fetch function.
    - Use Obj::value for get default value. Allow lazy recovering the value.
