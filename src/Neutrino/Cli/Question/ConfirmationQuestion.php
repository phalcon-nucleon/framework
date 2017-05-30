<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 30/05/2017
 * Time: 14:44
 */

namespace Neutrino\Cli\Question;


class ConfirmationQuestion extends Question
{
    protected $answerRegex;

    public function __construct($question, $default = true, $answerRegex = '/^(?:y|o)/i')
    {
        parent::__construct($question, (bool)$default);

        $this->answerRegex = $answerRegex;
    }

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