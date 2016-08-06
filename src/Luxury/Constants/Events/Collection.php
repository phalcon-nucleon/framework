<?php

namespace Luxury\Constants\Events;

/**
 * Class Collection
 *
 * @package Luxury\Constants\Events
 *
 * Contains a list of events related to the area 'collection'
 */
final class Collection
{
    const BEFORE_VALIDATION           = 'collection:beforeValidation';
    const BEFORE_VALIDATION_ON_CREATE = 'collection:beforeValidationOnCreate';
    const BEFORE_VALIDATION_ON_UPDATE = 'collection:beforeValidationOnUpdate';
    const VALIDATION                  = 'collection:validation';
    const ON_VALIDATION_FAILS         = 'collection:onValidationFails';
    const AFTER_VALIDATION_ON_CREATE  = 'collection:afterValidationOnCreate';
    const AFTER_VALIDATION_ON_UPDATE  = 'collection:afterValidationOnUpdate';
    const AFTER_VALIDATION            = 'collection:afterValidation';
    const BEFORE_SAVE                 = 'collection:beforeSave';
    const BEFORE_UPDATE               = 'collection:beforeUpdate';
    const BEFORE_CREATE               = 'collection:beforeCreate';
    const AFTER_UPDATE                = 'collection:afterUpdate';
    const AFTER_CREATE                = 'collection:afterCreate';
    const AFTER_SAVE                  = 'collection:afterSave';
    const NOT_SAVE                    = 'collection:notSave';
    const NOT_DELETED                 = 'collection:notDeleted';
    const NOT_SAVED                   = 'collection:notSaved';
}
