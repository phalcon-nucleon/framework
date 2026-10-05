<?php

declare(strict_types=1);

namespace Neutrino\Cli;

/**
 * Implemented by a provider of the CLI kernel to declare console commands.
 *
 * The router provider reads it on the kernel's providers, without instantiating them.
 */
interface ProvidesTasks
{
    /**
     * Commands, by pattern: `['make:migration {name}' => MakerTask::class, 'cache:clear' => [CacheTask::class, 'clear']]`.
     *
     * @return array<string, class-string<Task>|array{0: class-string<Task>, 1?: string}>
     */
    public static function tasks(): array;
}
