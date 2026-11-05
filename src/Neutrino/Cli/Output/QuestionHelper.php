<?php

declare(strict_types=1);

namespace Neutrino\Cli\Output;

use Neutrino\Cli\Question\ChoiceQuestion;
use Neutrino\Cli\Question\ConfirmationQuestion;
use Neutrino\Cli\Question\Question;
use RuntimeException;

/**
 * Asks questions on the console.
 */
final class QuestionHelper
{
    /**
     * @param resource|null $input Stream the answer is read from (STDIN by default)
     */
    public static function ask(Writer $output, mixed $input, Question $question): mixed
    {
        $input ??= STDIN;

        $output->line('');

        $response = match (true) {
            $question instanceof ChoiceQuestion => self::promptChoiceQuestion($output, $input, $question),
            default                             => self::promptQuestion($output, $input, $question),
        };

        return $response === null || $response === '' ? $question->getDefault() : $response;
    }

    private static function outputQuestion(Writer $output, Question $question): void
    {
        $questionStr = Decorate::info($question->getQuestion());

        if ($question instanceof ConfirmationQuestion) {
            $questionStr .= Decorate::info(' (yes, no)');
            $questionStr .= ' [' . Decorate::notice($question->getDefault() ? 'yes' : 'no') . ']';
        } elseif (($default = $question->getDefault()) !== null) {
            $questionStr .= ' [' . Decorate::notice(is_scalar($default) ? (string) $default : get_debug_type($default)) . ']';
        }

        $output->line(' ' . $questionStr . ':');
    }

    /**
     * @param resource $input
     */
    private static function doAsk(Writer $output, mixed $input, Question $question): mixed
    {
        $output->write(' > ', false);

        $response = fgets($input, 4096);
        if ($response === false) {
            throw new RuntimeException('Aborted');
        }
        $response = trim($response);

        $output->line($response);
        $output->line('');

        return $question->normalize($response);
    }

    /**
     * @param resource $input
     */
    private static function promptQuestion(Writer $output, mixed $input, Question $question): mixed
    {
        self::outputQuestion($output, $question);

        return self::doAsk($output, $input, $question);
    }

    /**
     * @param resource $input
     */
    private static function promptChoiceQuestion(Writer $output, mixed $input, ChoiceQuestion $question): mixed
    {
        $maxAttempts = $question->getMaxAttempts();
        $attempts = 0;
        $response = null;

        while ($maxAttempts === null || $attempts++ < $maxAttempts) {
            self::outputQuestion($output, $question);

            $choices = $question->getChoices();
            foreach ($choices as $key => $choice) {
                $output->line('  [' . Decorate::notice((string) $key) . '] ' . $choice);
            }

            $response = self::doAsk($output, $input, $question);

            if (in_array($response, $choices, true)) {
                return $response;
            }
            if ((is_string($response) || is_int($response)) && isset($choices[$response])) {
                return $choices[$response];
            }

            if (($maxAttempts === null || $attempts === $maxAttempts) && ($response === null || $response === '')) {
                break;
            }

            (new Block($output, 'error'))->draw(['[ERROR] value "' . (is_scalar($response) ? (string) $response : '') . '" is invalid']);
        }

        return $response;
    }
}
