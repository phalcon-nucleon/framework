<?php

declare(strict_types=1);

namespace Neutrino\Cli;

use Neutrino\Cli\Output\Block;
use Neutrino\Cli\Output\QuestionHelper;
use Neutrino\Cli\Output\Table;
use Neutrino\Cli\Output\Writer;
use Neutrino\Cli\Question\ChoiceQuestion;
use Neutrino\Cli\Question\ConfirmationQuestion;
use Neutrino\Cli\Question\Question;
use Neutrino\Constants\Services;
use Phalcon\Cli\Dispatcher;
use Phalcon\Cli\Task as PhalconTask;

/**
 * Base class of the console tasks: arguments, options, output and questions.
 *
 * @property-read \Neutrino\Foundation\Cli\Kernel $application
 * @property-read \Phalcon\Config\Config          $config
 * @property-read \Neutrino\Cli\Router            $router
 * @property-read \Phalcon\Cli\Dispatcher         $dispatcher
 * @property-read \Neutrino\Cli\Output\Writer     $output
 */
abstract class Task extends PhalconTask
{
    /** @var array<int|string, mixed> */
    protected array $options = [];

    /** @var array<int|string, mixed> */
    protected array $arguments = [];

    /**
     * Stream the answers to the questions are read from (STDIN by default).
     *
     * @var resource|null
     */
    protected mixed $input = null;

    /**
     * No return type, so that tasks can override it as before.
     *
     * @return void
     */
    protected function onConstruct()
    {
        $this->applyArguments();
        $this->applyOptions();
    }

    /**
     * Called by the dispatcher before each action: the task instance is shared, so a task run twice in the same
     * process (callTask(), tests) reads the arguments and options of the current run.
     */
    public function beforeExecuteRoute(): void
    {
        $this->applyArguments();
        $this->applyOptions();
    }

    protected function applyOptions(): void
    {
        $this->options = $this->cliDispatcher()->getOptions();
    }

    protected function applyArguments(): void
    {
        $this->arguments = $this->cliDispatcher()->getParams();
    }

    /**
     * Runs another task.
     *
     * @param list<string>          $args
     * @param array<string, mixed> $opts `true` for a flag (`-name`), a value otherwise (`--name=value`)
     */
    public function callTask(string $task, string $action, array $args = [], array $opts = []): mixed
    {
        $options = [];
        foreach ($opts as $name => $opt) {
            $options[] = $opt === true ? "-$name" : "--$name=" . (is_scalar($opt) ? (string) $opt : '');
        }

        /** @var \Neutrino\Foundation\Cli\Kernel $application */
        $application = $this->getDI()->getShared(Services::APP);

        return $application->handle(array_merge(['task' => $task, 'action' => $action], $args, $options));
    }

    public function line(string $str): void
    {
        $this->writer()->write($str, true);
    }

    public function info(string $str): void
    {
        $this->writer()->info($str);
    }

    public function notice(string $str): void
    {
        $this->writer()->notice($str);
    }

    public function warn(string $str): void
    {
        $this->writer()->warn($str);
    }

    public function error(string $str): void
    {
        $this->writer()->error($str);
    }

    public function question(string $str): void
    {
        $this->writer()->question($str);
    }

    public function prompt(string $str, ?string $default = null): mixed
    {
        return $this->ask(new Question($str, $default));
    }

    public function confirm(string $str, bool $default = false): bool
    {
        return (bool) $this->ask(new ConfirmationQuestion($str, $default));
    }

    /**
     * @param array<int|string, string> $choices
     */
    public function choices(string $str, array $choices, mixed $default = null, ?int $maxAttempts = null): mixed
    {
        return $this->ask(new ChoiceQuestion($str, $choices, $default, $maxAttempts));
    }

    public function ask(Question $question): mixed
    {
        return QuestionHelper::ask($this->writer(), $this->input, $question);
    }

    /**
     * @param array<array<int|string, scalar|null>> $datas
     * @param list<string>                          $headers
     * @param int                                   $style   Table::STYLE_DEFAULT | Table::NO_STYLE | Table::NO_HEADER
     */
    public function table(array $datas, array $headers = [], int $style = Table::STYLE_DEFAULT): void
    {
        (new Table($this->writer(), $datas, $headers, $style))->display();
    }

    /**
     * @param list<string>                                     $lines
     * @param 'line'|'info'|'notice'|'warn'|'error'|'question' $style Writer method used to draw the block
     */
    public function block(array $lines, string $style, int $padding = 4): void
    {
        (new Block($this->writer(), $style, ['padding' => $padding]))->draw($lines);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function getArgs(): array
    {
        return $this->arguments;
    }

    protected function getArg(int|string $name, mixed $default = null): mixed
    {
        return $this->arguments[$name] ?? $default;
    }

    protected function hasArg(int|string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function getOptions(): array
    {
        return $this->options;
    }

    protected function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * Whether one of the options was given (`hasOption('f', 'force')`).
     */
    protected function hasOption(string ...$options): bool
    {
        foreach ($options as $option) {
            if (array_key_exists($option, $this->options)) {
                return true;
            }
        }

        return false;
    }

    protected function writer(): Writer
    {
        /** @var Writer */
        return $this->getDI()->getShared(Services\Cli::OUTPUT);
    }

    private function cliDispatcher(): Dispatcher
    {
        /** @var Dispatcher */
        return $this->getDI()->getShared(Services::DISPATCHER);
    }
}
