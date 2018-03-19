<?php

namespace Neutrino\HttpClient;

/**
 * Phalcon\Http\Client\Header
 *
 * @package Phalcon\Http\Client
 */
class Header implements \Countable
{
    /** @var string|null */
    public $version;

    /** @var int|null */
    public $code;

    /** @var string|null */
    public $status;

    /** @var array */
    private $headers = [];

    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function set($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if ($this->has($name)) {
            return $this->headers[$name];
        }

        return $default;
    }

    /**
     * Determine if a header exists with a specific key.
     *
     * @param string $name
     *
     * @return boolean
     */
    public function has($name)
    {
        return isset($this->headers[$name]) || array_key_exists($name, $this->headers);
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function remove($name)
    {
        if ($this->has($name)) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    /**
     * Set multiple headers.
     *
     * <code>
     * $headers = [
     *     'X-Foo' => 'bar',
     *     'Content-Type' => 'application/json',
     * ];
     *
     * $curl->addMultiple($headers);
     * </code>
     *
     * @param array $fields
     * @param bool $merge
     *
     * @return $this
     */
    public function setHeaders(array $fields, $merge = false)
    {
        if ($merge) {
            $this->headers = array_merge($this->headers, $fields);
        } else {
            $this->headers = $fields;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Return the header builded for the HTTP Request
     *
     * @return array
     */
    public function build()
    {
        $headers = [];

        foreach ($this->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        return $headers;
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->headers);
    }

    /**
     * @param string $raw
     */
    public function parse($raw)
    {
        if(is_string($raw)){
            $raw = array_filter(explode("\r\n", $raw));
        }

        foreach ($raw as $header) {
            if (preg_match('%^HTTP/(\d(?:\.\d)?)\s+(\d{3})\s?+(.+)?$%i', $header, $status)) {
                $this->version = $status[1];
                $this->code = intval($status[2]);
                $this->status = isset($status[3]) ? $status[3] : '';
            } else {
                $field = explode(':', $header, 2);

                $this->set(trim($field[0]), isset($field[1]) ? trim($field[1]) : null);
            }
        }
    }
}
