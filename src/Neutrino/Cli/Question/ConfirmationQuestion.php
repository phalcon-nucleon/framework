<?php

namespace Neutrino\Cli\Question;

/**
 * Class ConfirmationQuestion
 *
 * @package Neutrino\Cli\Question
 */
class ConfirmationQuestion extends Question
{
    protected $answerRegex;

    /**
     * ConfirmationQuestion constructor.
     *
     * @param string $question
     * @param bool   $default
     * @param string $trueAnswerRegex
     */
    public function __construct($question, $default = true, $trueAnswerRegex = '/^(?:y|o)/i')
    {
        parent::__construct($question, (bool)$default);

        $this->answerRegex = $trueAnswerRegex;
    }

    /**
     * @param $response
     *
     * @return bool|null|string
     */
    public function normalize($response)
    {
        if (is_bool($response)) {
            return $response;
        }

        $response = parent::normalize($response);

        if ($response === null || $response === '') {
            return $this->default;
        }

        return (bool)preg_match($this->answerRegex, $response);
    }
}