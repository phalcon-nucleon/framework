<?php

namespace Luxury\Constants\Events;

/**
 * Class Db
 *
 * @package Luxury\Constants\Events
 *
 * Contains a list of events related to the area 'db'
 */
final class Db
{
    const BEFORE_QUERY         = 'db:beforeQuery';
    const AFTER_QUERY          = 'db:afterQuery';
    const BEGIN_TRANSACTION    = 'db:beginTransaction';
    const CREATE_SAVEPOINT     = 'db:createSavepoint';
    const ROLLBACK_TRANSACTION = 'db:rollbackTransaction';
    const ROLLBACK_SAVEPOINT   = 'db:rollbackSavepoint';
    const COMMIT_TRANSACTION   = 'db:commitTransaction';
    const RELEASE_SAVEPOINT    = 'db:releaseSavepoint';
}
