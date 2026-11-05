<?php

declare(strict_types=1);

namespace Neutrino\Cli\Question;

/**
 * Yes / no question: the answer is a boolean.
 */
class ConfirmationQuestion extends Question
{
    public function __construct(string $question, bool $default = true, protected string $answerRegex = '/^(?:y|o)/i')
    {
        parent::__construct($question, $default);
    }

    public function normalize(string $response): bool
    {
        if ($response === '') {
            return (bool) $this->default;
        }

        return preg_match($this->answerRegex, $response) === 1;
    }
}
