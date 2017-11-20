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

        if ($question instanceof ChoiceQuestion) {
            $choices = $question->getChoices();

            $response = $choice = self::doAsk($output, $input, $question);

            $attempts = 0;
            while (!in_array($response, $choices)) {
                $attempts++;
                if ($question->getMaxAttempts() <= $attempts) {
                    $response = null;
                    break;
                }

                $output->warn('You must select one of these choices : ' . implode(', ', $question->getChoices()));

                $response = self::doAsk($output, $input, $question);
            }
        } else {
            $response = self::doAsk($output, $input, $question);
        }

        if (is_null($response) || $response === '') {
            return $question->getDefault();
        }

        return $response;
    }

    private static function prompt(Writer $output, Question $question)
    {
        $output->question($question->getQuestion());

        switch (true) {
            case  $question instanceof ChoiceQuestion:
                $output->info(
                    '[' . implode(', ', $question->getChoices()) . ']'
                    . (empty($question->getDefault()) ? '' : ' (' . $question->getDefault() . ')')
                );
                break;
            case $question instanceof ConfirmationQuestion:
                $output->info('[yes, no] (' . ($question->getDefault() ? 'y' : 'n') . ')');
                break;
            case $question instanceof Question:
            default:
        }
    }

    private static function doAsk(Writer $output, $input, Question $question)
    {
        self::prompt($output, $question);

        return $question->normalize(fgets($input));
    }
}
