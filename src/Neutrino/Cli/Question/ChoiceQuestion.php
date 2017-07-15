<?php

namespace Neutrino\Cli\Question;

/**
 * Class ChoiceQuestion
 *
 * @package Neutrino\Cli\Question
 */
class ChoiceQuestion extends Question
{
    /** @var array */
    private $choices;

    /** @var int */
    private $maxAttempts;

    /**
     * Question constructor.
     *
     * @param string      $question
     * @param array       $choices
     * @param int         $maxAttempts
     * @param null|string $default
     */
    public function __construct($question, array $choices, $maxAttempts, $default = null)
    {
        parent::__construct($question, $default);

        $this->choices     = $choices;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * @return array
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @return int
     */
    public function getMaxAttempts()
    {
        return $this->maxAttempts;
    }
}