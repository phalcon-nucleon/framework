<?php

declare(strict_types=1);

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
    public const string BEFORE_QUERY         = 'db:beforeQuery';
    public const string AFTER_QUERY          = 'db:afterQuery';
    public const string BEGIN_TRANSACTION    = 'db:beginTransaction';
    public const string CREATE_SAVEPOINT     = 'db:createSavepoint';
    public const string ROLLBACK_TRANSACTION = 'db:rollbackTransaction';
    public const string ROLLBACK_SAVEPOINT   = 'db:rollbackSavepoint';
    public const string COMMIT_TRANSACTION   = 'db:commitTransaction';
    public const string RELEASE_SAVEPOINT    = 'db:releaseSavepoint';
    public const string CONNECTION_LOST      = 'db:connectionLost';
}
