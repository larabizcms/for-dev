<?php
/**
 * LARABIZ CMS - Full SPA Laravel CMS
 *
 * @package    larabizcms/larabiz
 * @author     The Anh Dang
 * @link       https://larabiz.com
 */

namespace LarabizCMS\ForDev\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateViewPathCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'larabiz:generate-view-path';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate view path';

    public function handle(): void
    {
        $views = $this->laravel['view']->getFinder()->getHints();
        $basePath = base_path();
        $namespaces = [];

        foreach ($views as $namespace => $paths) {
            $namespaces[] = [
                'namespace' => $namespace,
                'path' => str_replace("{$basePath}/", '', realpath($paths[0])),
            ];
        }

        $config = [];
        if (File::exists(base_path('ide-blade.json'))) {
            $config = json_decode(File::get(base_path('ide-blade.json')), true, 512, JSON_THROW_ON_ERROR);
        }

        $config['namespaces'] = $namespaces;
        File::put(
            base_path('ide-blade.json'),
            json_encode($config, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
