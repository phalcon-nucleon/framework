#Release Note

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
