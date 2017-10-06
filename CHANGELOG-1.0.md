#Release Note

## v1.0.2
### Change
- Support Phalcon =>3.0.4

## v1.0.1
### Added
 - Error\Helper::verboseArgs function.
### Change
 - Improve \Neutrino\Model ::timestampable & ::softDelete behavior implementation :
    - Timestampable :
        - Allow null column, and default value. With a default value (like "CURRENT_TIMESTAMP"), "insert" behavior will not be add.
        - Timestampable are now plugged in "beforeValidationOnCreate" and "beforeValidationOnUpdate"
    - SoftDelete allow default value.
 - Optimizer use now Di for get class. Allow to make better unit test. 

## v1.0.0

### Added
 - \Neutrino\Dotconst 
    - Load PHP Constants from ini files. 
    - May contains the application global, immutable, variables, like 'DB_HOST'.
 - Event "kernel:boot" :
    - Raised on any kernel boot, only one time.
 - Event "kernel:terminate" :
    - Raised on any kernel termination, only one time.
### Change
 - Upgrade Phalcon\Version : 3.0 > 3.1
 - [DELETED] \Neutrino\Dotenv : Replaced by \Neutrino\Dotconst 
   - Performance change.
