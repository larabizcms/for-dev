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
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/for-dev.php', 'for-dev');
    }

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
                    $binding = "'".$binding."'";
                    break;
                case $binding instanceof Stringable:
                    $binding = "'".$binding->toString()."'";
                    break;
                case $binding instanceof \Stringable:
                    $binding = "'".$binding->__toString()."'";
                    break;
                case $binding instanceof Carbon:
                    $binding = "'".$binding->format('Y-m-d H:i:s')."'";
                    break;
            }

            return preg_replace(
                '/\?/',
                $binding,
                $sql,
                1
            );
        };

        /**
         * Adds a macro to the Builder class that returns the raw SQL query with bindings replaced.
         *
         * @return string The raw SQL query with bindings replaced.
         */
        Builder::macro(
            'toRawSql',
            function () use ($rawBuilderCallback) {
                /**
                 * @var Builder $this The Builder instance on which the macro was called.
                 */
                return array_reduce(
                    $this->getBindings(), // Get the bindings for the query.
                    $rawBuilderCallback, // Call the rawBuilderCallback function on each binding.
                    $this->toSql() // Get the raw SQL query with placeholders for bindings.
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
