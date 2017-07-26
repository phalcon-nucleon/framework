<?php
/**
 * Laravel 5.4 Fluent Class
 *
 * @see https://github.com/illuminate/support/blob/401bb82931e22bb8e8de727f3bde9cff7d186821/Fluent.php
 */

namespace Neutrino\Support\Fluent;


interface Fluentable extends \ArrayAccess, \JsonSerializable
{
    /**
     * Create a new fluent container instance.
     *
     * @param  array|object $attributes
     */
    public function __construct($attributes);

    /**
     * Get an attribute from the container.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes();

    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray();

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize();

    /**
     * Convert the Fluent instance to JSON.
     *
     * @param  int $options
     *
     * @return string
     */
    public function toJson($options = 0);

    /**
     * Determine if the given offset exists.
     *
     * @param  string $offset
     *
     * @return bool
     */
    public function offsetExists($offset);

    /**
     * Get the value for a given offset.
     *
     * @param  string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset);

    /**
     * Set the value at the given offset.
     *
     * @param  string $offset
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($offset, $value);

    /**
     * Unset the value at the given offset.
     *
     * @param  string $offset
     *
     * @return void
     */
    public function offsetUnset($offset);

    /**
     * Handle dynamic calls to the container to set attributes.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return $this
     */
    public function __call($method, $parameters);

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key);

    /**
     * Dynamically set the value of an attribute.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function __set($key, $value);

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function __isset($key);

    /**
     * Dynamically unset an attribute.
     *
     * @param  string $key
     *
     * @return void
     */
    public function __unset($key);
}