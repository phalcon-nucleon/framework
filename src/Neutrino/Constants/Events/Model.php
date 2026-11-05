<?php

declare(strict_types=1);

namespace Neutrino\Constants\Events;

/**
 * Class Model
 *
 * Contains a list of events related to the area 'model'
 *
 *  @package Neutrino\Constants\Events
 */
final class Model
{
    public const string NOT_DELETED                 = 'model:notDeleted';
    public const string ON_VALIDATION_FAILS         = 'model:onValidationFails';
    public const string BEFORE_VALIDATION           = 'model:beforeValidation';
    public const string BEFORE_VALIDATION_ON_CREATE = 'model:beforeValidationOnCreate';
    public const string BEFORE_VALIDATION_ON_UPDATE = 'model:beforeValidationOnUpdate';
    public const string AFTER_VALIDATION_ON_CREATE  = 'model:afterValidationOnCreate';
    public const string AFTER_VALIDATION_ON_UPDATE  = 'model:afterValidationOnUpdate';
    public const string AFTER_VALIDATION            = 'model:afterValidation';
    public const string BEFORE_SAVE                 = 'model:beforeSave';
    public const string BEFORE_UPDATE               = 'model:beforeUpdate';
    public const string BEFORE_CREATE               = 'model:beforeCreate';
    public const string AFTER_UPDATE                = 'model:afterUpdate';
    public const string AFTER_CREATE                = 'model:afterCreate';
    public const string AFTER_SAVE                  = 'model:afterSave';
    public const string BEFORE_DELETE               = 'model:beforeDelete';
    public const string AFTER_DELETE                = 'model:afterDelete';
    public const string PREPARE_SAVE                = 'model:prepareSave';
    public const string VALIDATION                  = 'model:validation';
}
