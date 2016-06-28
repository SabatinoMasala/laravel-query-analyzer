<?php

namespace Rokde\LaravelQueryAnalyzer\Console\Commands;

use Illuminate\Console\Command;
use Rokde\LaravelQueryAnalyzer\QueryRepository;

/**
 * Class AnalyzeQueriesCommand
 *
 * @package Rokde\LaravelQueryAnalyzer\Console\Commands
 */
class AnalyzeQueriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:queries
                            {query? : Display a query}
                            {--filter= : Filter statements which starts with given value} 
                            {--limit= : Limit displaying queries} 
                            {--offset= : Start displaying queries from offset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays all stored queries.';

    /**
     * query repository
     *
     * @var QueryRepository
     */
    private $repository;

    /**
     * QueryAnalyzeCommand constructor.
     * @param QueryRepository $repository
     */
    public function __construct(QueryRepository $repository)
    {
        $this->repository = $repository;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $query = $this->argument('query');
        if ($query !== null) {
            $this->handleQueryCall($query);
            return;
        }

        $limit = intval($this->option('limit'));
        $offset = intval($this->option('offset'));
        $queries = $this->getQueries($offset, $limit);

        if ($queries->isEmpty()) {
            $this->error('No queries found');
            return;
        }

        $index = max(1, 1 + $offset);
        $length = max(strlen($queries->count()) + 1, strlen($index));

        $queries->map(function ($query) use (&$index, $length) {
            $this->line('<info>' . str_pad($index++, $length, 0, STR_PAD_LEFT) . '</info> ' . $query);
        });
    }

    /**
     * handles query call
     *
     * @param int|string $queryId
     */
    private function handleQueryCall($queryId)
    {
        $query = $queryId;
        if (is_numeric($queryId)) {
            $query = $this->getQueries()->splice(--$queryId, 1)->first();
        }

        $this->comment($query);

        $timings = $this->repository->timings($query);

        $this->table(['count', 'fastest', 'slowest', 'avg', 'mode'], [
            [$timings->count(), $timings->min(), $timings->max(), $timings->avg(), $timings->mode()],
        ]);

        $this->line('');

        $bindings = $this->repository->bindings($query);

        $rows = [];
        $bindings->map(function ($entry) use (&$rows) {
            $rows[] = [
                json_encode($entry['bindings']),
                $entry['timings']->min(),
                $entry['timings']->max(),
                $entry['timings']->avg(),
                $entry['timings']->mode(),
            ];
        });

        $this->table(['bindings', 'fastest', 'slowest', 'avg', 'mode'], $rows);
    }

    /**
     * returns queries
     *
     * @param int $offset
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    private function getQueries($offset = 0, $limit = 0)
    {
        $filter = $this->option('filter');
        if ($filter) {
            $queries = $this->repository->only($filter);
        } else {
            $queries = $this->repository->queries();
        }

        if ($offset > 0) {
            $queries = $queries->splice($offset);
        }
        if ($limit > 0) {
            $queries = $queries->take($limit);
        }

        return $queries;
    }
}