<?php

namespace Neutrino\Cli\Output;

use Neutrino\Cli\Question\ChoiceQuestion;
use Neutrino\Cli\Question\ConfirmationQuestion;
use Neutrino\Cli\Question\Question;

/**
 * Class QuestionHelper
 *
 * @package Neutrino\Cli\Output
 */
final class QuestionHelper
{
    /**
     * Do the ask of an question
     *
     * @param \Neutrino\Cli\Output\Writer     $output
     * @param resource                        $input
     * @param \Neutrino\Cli\Question\Question $question
     *
     * @return null|string
     */
    public static function ask(Writer $output, $input, Question $question)
    {
        if (!isset($input)) {
            $input = STDIN;
        }

        $output->line('');

        $response = self::prompt($output, $input, $question);

        if (is_null($response) || $response === '') {
            return $question->getDefault();
        }

        return $response;
    }

    private static function outputQuestion(Writer $output, Question $question)
    {
        $questionStr = Decorate::info($question->getQuestion());

        if ($question instanceof ConfirmationQuestion) {
            $questionStr .= Decorate::info(' (yes, no)');
            $questionStr .= ' [' . Decorate::notice($question->getDefault() ? 'yes' : 'no') . ']';
        } elseif (!is_null($default = $question->getDefault())) {
            $questionStr .= ' [' . Decorate::notice($default) . ']';
        }

        $output->line(' ' . $questionStr . ':');
    }

    private static function prompt(Writer $output, $input, Question $question)
    {
        $response = null;

        switch (true) {
            case $question instanceof ConfirmationQuestion:
                $response = self::promptConfirmationQuestion($output, $input, $question);
                break;
            case $question instanceof ChoiceQuestion:
                $response = self::promptChoiceQuestion($output, $input, $question);
                break;
            case $question instanceof Question:
            default:
                $response = self::promptQuestion($output, $input, $question);
                break;
        }

        return $response;
    }

    private static function doAsk(Writer $output, $input, Question $question)
    {
        $output->write(' > ', false);

        $response = fgets($input, 4096);
        if (false === $response) {
            throw new \RuntimeException('Aborted');
        }
        $response = trim($response);

        $output->line($response);
        $output->line('');

        $response = $question->normalize($response);

        return $response;
    }

    private static function promptQuestion(Writer $output, $input, Question $question)
    {
        self::outputQuestion($output, $question);

        $response = self::doAsk($output, $input, $question);

        if (is_null($response) || $response === '') {
            $response = $question->getDefault();
        }

        return $response;
    }

    private static function promptConfirmationQuestion(Writer $output, $input, ConfirmationQuestion $question)
    {
        self::outputQuestion($output, $question);

        $response = self::doAsk($output, $input, $question);

        if (is_null($response) || $response === '') {
            $response = $question->getDefault();
        }

        return $response;
    }

    private static function promptChoiceQuestion(Writer $output, $input, ChoiceQuestion $question)
    {
        $maxAttemps = $question->getMaxAttempts();
        $attemps = 0;
        $response = null;

        while (is_null($maxAttemps) || ($attemps++ < $maxAttemps)) {
            self::outputQuestion($output, $question);

            foreach ($question->getChoices() as $key => $choice) {
                $output->line('  [' . Decorate::notice($key) . '] ' . $choice);
            }

            $response = self::doAsk($output, $input, $question);

            $choices = $question->getChoices();

            if (in_array($response, $choices)) {
                return $response;
            }
            if (isset($choices[$response])) {
                return $choices[$response];
            }

            if ((is_null($maxAttemps) || $attemps === $maxAttemps) && (is_null($response) || $response === '')) {
                break;
            }

            (new Block($output, 'error'))->draw(['[ERROR] value "' . $response . '" is invalid']);
        }

        if (is_null($response) || $response === '') {
            $response = $question->getDefault();
        }

        return $response;
    }
}
