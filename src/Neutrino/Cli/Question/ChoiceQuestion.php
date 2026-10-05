<?php

declare(strict_types=1);

namespace Neutrino\Cli\Question;

use InvalidArgumentException;

/**
 * Question with a list of choices, answered by value or by key.
 */
class ChoiceQuestion extends Question
{
    /** @var array<int|string, string> */
    private array $choices = [];

    private ?int $maxAttempts = null;

    /**
     * @param array<int|string, string> $choices
     * @param int|null                  $maxAttempts `null`: until a valid answer
     */
    public function __construct(string $question, array $choices, mixed $default = null, ?int $maxAttempts = null)
    {
        parent::__construct($question, $default);

        $this->setChoices($choices)->setMaxAttempts($maxAttempts);
    }

    /**
     * @return array<int|string, string>
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    public function getMaxAttempts(): ?int
    {
        return $this->maxAttempts;
    }

    /**
     * @param array<int|string, string> $choices
     */
    public function setChoices(array $choices): static
    {
        $this->choices = $choices;

        return $this;
    }

    public function setMaxAttempts(?int $maxAttempts): static
    {
        if ($maxAttempts !== null && $maxAttempts < 1) {
            throw new InvalidArgumentException('Maximum number of attempts must be a positive value.');
        }

        $this->maxAttempts = $maxAttempts;

        return $this;
    }
}
