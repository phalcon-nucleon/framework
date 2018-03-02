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
     * Execute la commande :
     *    sass resources/assets/scss/app.scss public/css/app.css
     *
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
        $this->output->write('Compiling sass... ', false);

        try {
            (new SassCompiler())->compile([
              'sass_file' => 'resources/assets/scss/app.scss',
              'output_file' => 'public/css/app.css',
              'cmd_options' => array_filter([
                ($style = $this->getSassStyle()) ? "--style=$style" : '',
                ($sourcemap = $this->getOption('sourcemap')) ? '--sourcemap="' . $sourcemap . '"' : '',
              ])
            ]);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->info('Success');
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

        return null;
    }
}
