<?php

namespace Luxury\Constants\Events;

/**
 * Class Model
 *
 * @package Luxury\Constants\Events
 *
 * Contains a list of events related to the area 'model'
 */
final class Model
{
    const NOT_DELETED                 = 'model:notDeleted';
    const NOT_SAVED                   = 'model:notSaved';
    const ON_VALIDATION_FAILS         = 'model:onValidationFails';
    const BEFORE_VALIDATION           = 'model:beforeValidation';
    const BEFORE_VALIDATION_ON_CREATE = 'model:beforeValidationOnCreate';
    const BEFORE_VALIDATION_ON_UPDATE = 'model:beforeValidationOnUpdate';
    const AFTER_VALIDATION_ON_CREATE  = 'model:afterValidationOnCreate';
    const AFTER_VALIDATION_ON_UPDATE  = 'model:afterValidationOnUpdate';
    const AFTER_VALIDATION            = 'model:afterValidation';
    const BEFORE_SAVE                 = 'model:beforeSave';
    const BEFORE_UPDATE               = 'model:beforeUpdate';
    const BEFORE_CREATE               = 'model:beforeCreate';
    const AFTER_UPDATE                = 'model:afterUpdate';
    const AFTER_CREATE                = 'model:afterCreate';
    const AFTER_SAVE                  = 'model:afterSave';
    const NOT_SAVE                    = 'model:notSave';
    const BEFORE_DELETE               = 'model:beforeDelete';
    const AFTER_DELETE                = 'model:afterDelete';
}
