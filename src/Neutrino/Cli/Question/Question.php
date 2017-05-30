<?php

namespace Neutrino\Cli\Question;

class Question
{
    /** @var string */
    protected $question;

    /** @var null|string */
    protected $default;

    /**
     * Question constructor.
     *
     * @param string      $question
     * @param null|string $default
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
     * @return null|string
     */
    public function getDefault()
    {
        return $this->default;
    }

    public function normalize($response)
    {
        return trim($response);
    }
}