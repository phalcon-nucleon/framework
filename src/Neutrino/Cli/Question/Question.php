<?php

namespace Neutrino\Cli\Question;

/**
 * Class Question
 *
 * @package Neutrino\Cli\Question
 */
class Question
{
    /** @var string */
    protected $question;

    /** @var null|bool|int|string */
    protected $default;

    /**
     * Question constructor.
     *
     * @param string               $question
     * @param null|bool|int|string $default
     */
    public function __construct($question, $default = null)
    {
        $this->question = $question;
        $this->default = $default;
    }

    /**
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @return null|bool|int|string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param $response
     *
     * @return string
     */
    public function normalize($response)
    {
        return trim($response);
    }
}