<?php

declare(strict_types=1);

namespace Neutrino\Error\Writer;

use Neutrino\Cli\Output\Block;
use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Phalcon\Di\Di;
use Phalcon\Logger\Enum;

/**
 * Writes the errors to the console output (`cli.output` service), in a block colored by severity.
 */
final class Cli implements Writable
{
    public function handle(Error $error): void
    {
        $di = Di::getDefault();
        $output = $di !== null && $di->has(Services\Cli::OUTPUT) ? $di->getShared(Services\Cli::OUTPUT) : null;

        if (!$output instanceof Writer) {
            echo Helper::format($error), "\n";

            return;
        }

        $output->line('');

        (new Block($output, self::style($error), ['padding' => 4]))->draw(explode("\n", Helper::format($error)));
    }

    /**
     * @return 'error'|'warn'|'notice'|'info'
     */
    private static function style(Error $error): string
    {
        return match ($error->logLvl) {
            Enum::WARNING => 'warn',
            Enum::NOTICE  => 'notice',
            Enum::INFO, Enum::DEBUG => 'info',
            default       => 'error',
        };
    }
}
