<?php

namespace Neutrino\Constants\Events;

/**
 * Class Db
 *
 * Contains a list of events related to the area 'db'
 *
 *  @package Neutrino\Constants\Events
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
