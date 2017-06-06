<?php
/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2016 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  |          Nikita Vershinin <endeveit@gmail.com>                         |
  +------------------------------------------------------------------------+
*/
namespace Neutrino\Error;

use Neutrino\Support\Arr;

/**
 * Class Error
 *
 * @package Phalcon\Error
 *
 * @property-read int        type
 * @property-read int        code
 * @property-read string     typeStr
 * @property-read string     message
 * @property-read string     file
 * @property-read string     line
 * @property-read \Exception exception
 * @property-read bool       isException
 * @property-read bool       isError
 */
class Error implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $attributes;

    /**
     * Class constructor sets the attributes.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            'type'        => -1,
            'code'        => 0,
            'message'     => 'No error message',
            'file'        => '',
            'line'        => '',
            'exception'   => null,
            'isException' => false,
            'isError'     => false,
        ];

        $options = array_merge($defaults, $options);

        foreach ($options as $option => $value) {
            $this->attributes[$option] = $value;
        }

        $this->attributes['typeStr'] = Handler::getErrorType($this->attributes['type']);
    }

    /**
     * @param \Exception|\Error|\Throwable $e
     *
     * @return \Neutrino\Error\Error
     */
    public static function fromException($e)
    {
        return new static([
            'type'        => -1,
            'code'        => $e->getCode(),
            'message'     => $e->getMessage(),
            'file'        => $e->getFile(),
            'line'        => $e->getLine(),
            'isException' => true,
            'exception'   => $e,
        ]);
    }

    public static function fromError($errno, $errstr, $errfile, $errline)
    {
        return new static([
            'type'    => $errno,
            'code'    => $errno,
            'message' => $errstr,
            'file'    => $errfile,
            'line'    => $errline,
            'isError' => true,
        ]);
    }

    public function getErrorType()
    {
        switch ($this->type) {
            case -1:
                return 'Uncaught exception';
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
        }

        return (string)$this->type;
    }

    public function isFateful()
    {
        $type = $this->type;

        return $type == -1 ||
            $type == E_ERROR ||
            $type == E_PARSE ||
            $type == E_CORE_ERROR ||
            $type == E_COMPILE_ERROR ||
            $type == E_USER_ERROR ||
            $type == E_RECOVERABLE_ERROR;
    }

    /**
     * Magic method to retrieve the attributes.
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Whether a offset exists
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return Arr::has($this->attributes, $offset);
    }

    /**
     * Offset to retrieve
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return Arr::get($this->attributes, $offset);
    }

    /**
     * Offset to set
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Offset to unset
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        if (isset($this->attributes[$offset])) {
            unset($this->attributes[$offset]);
        }
    }
}
