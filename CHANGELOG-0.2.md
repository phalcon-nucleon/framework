#Release Note

## v0.2.0

### Added
- Neutrino\Dotenv. Dotenv loading from `.env.php` file, with configuration of different environment by `.env.{environment}.php`.

- Neutrino\Optimizer. Composer optimizer for Phalcon. Recreate autoloader by using Phalcon\Loader.

- Neutrino\Config\Loader. Configuration loader.
  - `config:cache` command.
  
- Neutrino\Repositories\Repository. 
  - Repository Design pattern. (inspired from Laravel)
  - Repository::each() : Allows to process large amounts of data, without overloading the php memory, by making several calls.
  
- Neutrino\Providers\BasicProvider. 
  - Allow to load a Service with the minimum of code.
  
- Neutrino\Providers\Provider. 
   - Add $aliases. 
   
- Kernels : Micro
   - Implement phalcon micro application.
   
### Change
- Performance : 
  - Support [ Arr, Str, Obj ] has replaced by functions. `(Arr|Str|Obj):: => (arr|str|obj)_` `Arr:: => arr_` 