<?php

namespace Rokde\LaravelQueryAnalyzer\Console\Commands;

use Illuminate\Console\Command;
use Rokde\LaravelQueryAnalyzer\QueryRepository;

/**
 * Class AnalyzeClearCommand
 *
 * @package Rokde\LaravelQueryAnalyzer\Console\Commands
 */
class AnalyzeClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears internal cache.';

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
        $this->repository->clear();
    }
}