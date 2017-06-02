<?php

namespace Neutrino\Cli\Output;

use Neutrino\Cli\Question\ChoiceQuestion;
use Neutrino\Cli\Question\ConfirmationQuestion;
use Neutrino\Cli\Question\Question;

final class QuestionHelper
{
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

                $output->warn('You must select of these choices : ' . implode(', ', $question->getChoices()));

                $response = self::doAsk($output, $input, $question);
            }
        } else {
            $response = self::doAsk($output, $input, $question);
        }

        if (empty($response)) {
            return $question->getDefault();
        }

        return $response;
    }

    private static function prompt(Writer $output, Question $question)
    {
        $output->question($question->getQuestion());

        switch (true) {
            case  $question instanceof ChoiceQuestion:
                $output->info('Choice one : ' . implode(', ', $question->getChoices()));
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