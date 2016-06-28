<?php

namespace Rokde\LaravelQueryAnalyzer;

use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;

/**
 * Class QueryRepository
 *
 * @package Rokde\LaravelQueryAnalyzer
 */
class QueryRepository
{
    const INDEX_KEY = 'queries.index';
    const CACHE_KEYS_KEY = 'queries.keys';
    const TIMINGS_KEY = 'queries.timings.index';
    const BINDINGS_KEY = 'queries.bindings.timings';

    /**
     * cache repository
     *
     * @var Repository
     */
    private $cache;

    /**
     * index of all queries stored
     *
     * @var array|string[]
     */
    private $index = [];

    /**
     * QueryRepository constructor.
     * @param Repository $cache
     */
    public function __construct(Repository $cache)
    {
        $this->cache = $cache;

        $this->index = $this->setupIndex();
    }

    /**
     * store a query for analysis
     *
     * @param string $sql
     * @param array $bindings
     * @param float $time
     */
    public function store(string $sql, array $bindings, float $time)
    {
        $this->storeQueryToIndex($sql);

        $this->storeQueryTime($sql, $time);

        $this->storeQueryBindingsTime($sql, $bindings, $time);
    }

    /**
     * returns all known queries
     *
     * @return Collection
     */
    public function queries()
    {
        return collect($this->index);
    }

    /**
     * returns only queries with a specified start string
     *
     * @param string $startString
     * @return Collection
     */
    public function only($startString)
    {
        return $this->queries()->filter(function ($entry) use ($startString) {
            return starts_with(strtolower($entry), strtolower($startString));
        });
    }

    /**
     * returns all timings for query
     *
     * @param string $query
     * @return TimingSeries
     */
    public function timings($query)
    {
        $cacheKey = $this->makeCacheKey($query, self::TIMINGS_KEY);

        return new TimingSeries($this->cache->get($cacheKey, []));
    }

    /**
     * returns all bindings for query with binding timing series
     *
     * @param string $query
     * @return Collection of array['bindings' => array(), 'timings' => TimingSeries]
     */
    public function bindings($query)
    {
        $cacheKey = $this->makeCacheKey($query, self::BINDINGS_KEY);

        $result = $this->cache->get($cacheKey, []);
        $bindings = collect();
        foreach ($result as $binding => $values) {
            $bindings->push(['bindings' => $values['bindings'], 'timings' => new TimingSeries($values['timings'])]);
        }

        return $bindings;
    }

    /**
     * clear all caches
     */
    public function clear()
    {
        $this->cache->forget(self::INDEX_KEY);
        foreach ($this->cache->get(self::CACHE_KEYS_KEY) as $key) {
            $this->cache->forget($key);
        }
        $this->cache->forget(self::CACHE_KEYS_KEY);
    }

    /**
     * setting up an index and retrieve contents
     *
     * @return array|string[]
     */
    private function setupIndex()
    {
        return $this->cache->get(self::INDEX_KEY, []);
    }

    /**
     * adds a query to index
     *
     * @param string $sql
     */
    private function storeQueryToIndex($sql)
    {
        if (!in_array($sql, $this->index)) {
            $this->index[] = $sql;

            $this->cache->forever(self::INDEX_KEY, $this->index);
        }
    }

    /**
     * stores time for query
     *
     * @param string $sql
     * @param float $time
     */
    private function storeQueryTime($sql, $time)
    {
        $cacheKey = $this->makeCacheKey($sql, self::TIMINGS_KEY);

        $timings = $this->cache->get($cacheKey, []);
        $timings[] = $time;

        $this->cache->forever($cacheKey, $timings);
    }

    /**
     * stores time for query with their bindings
     *
     * @param $sql
     * @param $bindings
     * @param $time
     */
    private function storeQueryBindingsTime($sql, $bindings, $time)
    {
        $cacheKey = $this->makeCacheKey($sql, self::BINDINGS_KEY);
        $bindingsKey = json_encode($bindings);

        $entry = $this->cache->get($cacheKey, []);

        if (!array_key_exists($bindingsKey, $entry)) {
            $entry[$bindingsKey] = [
                'bindings' => $bindings,
                'timings' => []
            ];
        }

        $entry[$bindingsKey]['timings'][] = $time;

        $this->cache->forever($cacheKey, $entry);
    }

    /**
     * make a cache key
     *
     * @param string $key
     * @param string $prefix
     * @return string
     */
    private function makeCacheKey($key, $prefix = '')
    {
        $key = md5($prefix . $key);

        $keys = $this->cache->get(self::CACHE_KEYS_KEY, []);
        $keys[$key] = $key;
        $this->cache->forever(self::CACHE_KEYS_KEY, $keys);

        return $key;
    }
}