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
     * @param null|string $default
     * @param null|int    $maxAttempts
     */
    public function __construct($question, array $choices, $default = null, $maxAttempts = null)
    {
        parent::__construct($question, $default);

        $this
            ->setChoices($choices)
            ->setMaxAttempts($maxAttempts);
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

    /**
     * @param array $choices
     *
     * @return $this
     */
    public function setChoices($choices)
    {
        $this->choices = $choices;

        return $this;
    }

    /**
     * @param int $maxAttempts
     *
     * @return $this
     */
    public function setMaxAttempts($maxAttempts)
    {
        if (null !== $maxAttempts && $maxAttempts < 1) {
            throw new \InvalidArgumentException('Maximum number of attempts must be a positive value.');
        }

        $this->maxAttempts = $maxAttempts;

        return $this;
    }
}