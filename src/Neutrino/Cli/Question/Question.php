<?php

declare(strict_types=1);

namespace Neutrino\Cli\Question;

/**
 * Question asked on the console, with its default answer.
 */
class Question
{
    public function __construct(protected string $question, protected mixed $default = null) {}

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Converts the answer typed by the user.
     */
    public function normalize(string $response): mixed
    {
        return $response;
    }
}
