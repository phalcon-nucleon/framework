<?php

namespace Neutrino\Foundation\Cli\Tasks;

use Neutrino\Assets\SassCompiler;
use Neutrino\Cli\Task;

/**
 * Class AssetsTask
 *
 * Neutrino\Foundation\Cli\Tasks
 */
class AssetsSassTask extends Task
{
    /**
     * Compilation des assets sass.
     * Run command :
     *    sass {src} {dest} [--style={outputStyle}] [--sourcemap={type}]
     *
     * @option --src={path}          : Define the src file. If not specified, we compile all files in config
     * @option --dest={path}         : Define the dest file. If not specified, we compile all files in config
     * @option --sourcemap={type}    : Define sourcemap type
     * @option --style={outputStyle} : Define output style
     * @option --compress            : alias of --style=compressed
     * @option --nested              : alias of --style=nested
     * @option --compact             : alias of --style=compact
     * @option --expanded            : alias of --style=expanded
     *
     * @throws \Exception
     */
    public function mainAction()
    {
        if($this->hasOption('src') && $this->hasOption('dest')){
            $src = $this->getOption('src');
            $dest = $this->getOption('dest');

            $this->compile($src, $dest);
            return;
        }

        if($this->hasOption('src')){
            $this->block(['You pass {src} option, without {dest} option.'], 'error');
            return;
        }

        if($this->hasOption('dest')){
            $this->block(['You pass {dest} option, without {src} option.'], 'error');
            return;
        }

        $files = $this->config->assets->sass->files;

        foreach ($files as $src => $dest) {
            $this->compile($src, $dest);
        }
    }

    private function compile($src, $dest)
    {
        $this->notice('Compiling : ');
        $this->notice("\tsrc  : $src");
        $this->notice("\tdest : $dest");

        try {
            (new SassCompiler())->compile([
                'sass_file'   => $src,
                'output_file' => $dest,
                'cmd_options' => array_filter([
                    ($style = $this->getSassStyle()) ? "--style=$style" : '',
                    ($sourcemap = $this->getSourcemap()) ? '--sourcemap="' . $sourcemap . '"' : '',
                ])
            ]);
        } catch (\Exception $e) {
            $this->block([$e->getMessage()], 'error');
            return;
        }

        $this->info('Success !');
    }

    private function getSassStyle()
    {
        if ($this->hasOption('compress')) {
            return 'compressed';
        }
        if ($this->hasOption('nested')) {
            return 'nested';
        }
        if ($this->hasOption('compact')) {
            return 'compact';
        }
        if ($this->hasOption('expanded')) {
            return 'expanded';
        }

        switch ($compress = $this->getOption('output')) {
            case 'compressed':
            case 'nested':
            case 'compact':
            case 'expanded':
            return $compress;
        }

        return isset($this->config->assets->sass->options->style)
            ? $this->config->assets->sass->options->style
            : null;
    }

    private function getSourcemap()
    {
        return $this->hasOption('sourcemap')
            ? $this->getOption('sourcemap')
            : isset($this->config->assets->sass->options->sourcemap)
                ? $this->config->assets->sass->options->sourcemap
                : null;
    }
}
