<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler\Extensions;

use Neutrino\Constants\Services;
use Neutrino\View\Engines\Volt\Compiler\ExtensionExtend;
use Phalcon\Config\Config;

/**
 * Makes the PHP functions callable in the templates: `{{ ucfirst(name) }}`.
 *
 * The functions of {@see PhpFunctionExtension::DENY} are refused (commands, files, configuration, callbacks
 * that could call any of them): a compromised or generated template cannot use them. `view.php_functions.deny`
 * replaces this list, `view.php_functions.allow` allows some of its functions again.
 */
class PhpFunctionExtension extends ExtensionExtend
{
    /**
     * @var list<string>
     */
    public const array DENY = [
        // commands
        'exec', 'system', 'passthru', 'shell_exec', 'proc_open', 'popen', 'pcntl_exec', 'mail', 'dl',
        // code evaluation and callbacks
        'assert', 'call_user_func', 'call_user_func_array', 'forward_static_call', 'forward_static_call_array',
        'array_map', 'array_filter', 'array_walk', 'array_walk_recursive', 'array_reduce', 'usort', 'uasort',
        'uksort', 'preg_replace_callback', 'preg_replace_callback_array', 'iterator_apply', 'register_shutdown_function',
        'register_tick_function', 'set_error_handler', 'set_exception_handler', 'spl_autoload_register', 'ob_start',
        'create_function', 'extract', 'parse_str', 'unserialize', 'array_udiff', 'array_udiff_assoc',
        'array_udiff_uassoc', 'array_diff_uassoc', 'array_diff_ukey', 'array_uintersect', 'array_uintersect_assoc',
        'array_uintersect_uassoc', 'array_intersect_uassoc', 'array_intersect_ukey', 'mb_ereg_replace_callback',
        'header_register_callback', 'session_set_save_handler', 'stream_wrapper_register', 'stream_filter_register',
        // files
        'unlink', 'rmdir', 'mkdir', 'rename', 'copy', 'touch', 'chmod', 'chown', 'chgrp', 'symlink', 'link',
        'tempnam', 'tmpfile', 'move_uploaded_file', 'file_put_contents', 'fopen', 'fwrite', 'fputs', 'file',
        'file_get_contents', 'readfile', 'fpassthru', 'highlight_file', 'show_source', 'parse_ini_file', 'glob',
        'scandir', 'opendir', 'dir', 'error_log', 'gzopen', 'gzfile', 'readgzfile', 'bzopen',
        // network
        'fsockopen', 'pfsockopen', 'stream_socket_client', 'stream_socket_server', 'curl_init', 'curl_exec',
        'curl_multi_exec',
        // configuration, environment and secrets (the constants of Dotconst hold keys and passwords; Volt compiles
        // its own `constant()`, out of reach of this list)
        'ini_set', 'ini_alter', 'ini_restore', 'ini_get', 'ini_get_all', 'get_cfg_var', 'putenv', 'getenv',
        'set_include_path', 'phpinfo', 'header', 'setcookie', 'session_start', 'session_destroy',
        'get_defined_constants', 'get_defined_vars',
    ];

    /** @var array<string, true>|null */
    private ?array $deny = null;

    /** @var array<string, true> */
    private array $allow = [];

    public function compileFunction(string $name, string $arguments, ?array $funcArguments): ?string
    {
        if (!function_exists($name) || !$this->allowed(strtolower($name))) {
            return null;
        }

        return $name . '(' . $arguments . ')';
    }

    private function allowed(string $function): bool
    {
        if ($this->deny === null) {
            $this->loadConfig();
        }

        return isset($this->allow[$function]) || !isset($this->deny[$function]);
    }

    private function loadConfig(): void
    {
        $di = $this->compiler->getDI();
        $config = $di->has(Services::CONFIG) ? $di->getShared(Services::CONFIG) : null;
        $deny = $config instanceof Config ? $config->path('view.php_functions.deny') : null;
        $allow = $config instanceof Config ? $config->path('view.php_functions.allow') : null;

        $this->deny = self::set($deny instanceof Config ? $deny->toArray() : self::DENY);
        $this->allow = self::set($allow instanceof Config ? $allow->toArray() : []);
    }

    /**
     * @param array<mixed> $functions
     *
     * @return array<string, true>
     */
    private static function set(array $functions): array
    {
        $set = [];
        foreach ($functions as $function) {
            if (is_string($function)) {
                $set[strtolower($function)] = true;
            }
        }

        return $set;
    }
}
