<?php

namespace Rokde\LaravelQueryAnalyzer;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;
use Rokde\LaravelQueryAnalyzer\Console\Commands\AnalyzeClearCommand;
use Rokde\LaravelQueryAnalyzer\Console\Commands\AnalyzeQueriesCommand;

class LaravelQueryAnalyzerProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     * @param Dispatcher $events
     * @param Repository $cache
     */
    public function boot(Dispatcher $events, Repository $cache)
    {
        $this->publishes([
            __DIR__ . '/../config/query-analyzer.php' => config_path('query-analyzer.php'),
        ], 'config');

        $this->setupListener($events, $cache);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/query-analyzer.php', 'query-analyzer'
        );

        $this->registerConsoleCommands();
    }

    /**
     * setting up listener
     *
     * @param Dispatcher $events
     * @param Repository $cache
     */
    private function setupListener(Dispatcher $events, Repository $cache)
    {
        $enabled = config('query-analyzer.enabled', false);

        if (!$enabled) {
            return;
        }

        $queryRepository = new QueryRepository($cache);

        $events->listen(QueryExecuted::class, function (QueryExecuted $queryExecuted) use ($queryRepository) {
            $sql = $queryExecuted->sql;
            $bindings = $queryExecuted->bindings;
            $time = $queryExecuted->time;

            try {
                $queryRepository->store($sql, $bindings, $time);
            } catch (\Exception $e) {
                //  be quiet on error
            }
        });
    }

    /**
     * registers console commands
     */
    private function registerConsoleCommands()
    {
        $enabled = config('query-analyzer.enabled', false);

        if (!$enabled) {
            return;
        }

        $this->commands([AnalyzeQueriesCommand::class, AnalyzeClearCommand::class]);
    }
}
