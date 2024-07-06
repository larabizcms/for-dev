<?php
/**
 * LARABIZ CMS - Full SPA Laravel CMS
 *
 * @package    larabizcms/larabiz
 * @author     The Anh Dang
 * @link       https://larabiz.com
 */

namespace LarabizCMS\ForDev\Providers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Stringable;

class ForDevServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->commands([
            \LarabizCMS\ForDev\Commands\GenerateViewPathCommand::class,
            \LarabizCMS\ForDev\Commands\GithubReleaseModuleCommand::class,
        ]);

        $this->macroBuilderForDev();
    }

    private function macroBuilderForDev(): void
    {
        $rawBuilderCallback = function ($sql, $binding) {
            switch (1) {
                case is_int($binding):
                case is_bool($binding):
                    $binding = (int) $binding;
                    break;
                case is_string($binding):
                    $binding = "'" . $binding . "'";
                    break;
                case $binding instanceof Stringable:
                    $binding = "'" . $binding->toString() . "'";
                    break;
                case $binding instanceof \Stringable:
                    $binding = "'" . $binding->__toString() . "'";
                    break;
                case $binding instanceof Carbon:
                    $binding = "'". $binding->format('Y-m-d H:i:s') ."'";
                    break;
            }

            return preg_replace(
                '/\?/',
                $binding,
                $sql,
                1
            );
        };

        Builder::macro(
            'toRawSql',
            function () use ($rawBuilderCallback) {
                /** @var Builder $this */
                return array_reduce(
                    $this->getBindings(),
                    $rawBuilderCallback,
                    $this->toSql()
                );
            }
        );

        EloquentBuilder::macro(
            'toRawSql',
            function () use ($rawBuilderCallback) {
                /** @var EloquentBuilder $this */
                return array_reduce(
                    $this->getBindings(),
                    $rawBuilderCallback,
                    $this->toSql()
                );
            }
        );

        Builder::macro(
            'ddRawSql',
            function () {
                /** @var Builder $this */
                dd($this->toRawSql());
            }
        );

        EloquentBuilder::macro(
            'ddRawSql',
            function () {
                /** @var EloquentBuilder $this */
                dd($this->toRawSql());
            }
        );
    }
}
