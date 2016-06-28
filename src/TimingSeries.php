<?php

namespace Rokde\LaravelQueryAnalyzer;

use Illuminate\Support\Collection;

/**
 * Class TimingSeries
 *
 * @package Rokde\LaravelQueryAnalyzer
 */
class TimingSeries extends Collection
{
    /**
     * TimingSeries constructor.
     * @param array $timings
     */
    public function __construct(array $timings)
    {
        natsort($timings);

        parent::__construct($timings);
    }

    /**
     * returns the median value of all timings
     *
     * @return float
     */
    public function median()
    {
        $count = $this->count();
        if ($count <= 0) {
            return 0.0;
        }

        $middleIndex = intval(floor($count / 2));
        $median = $this->items[$middleIndex];

        if ($count % 2 === 0) {
            $median = ($median + $this->items[$middleIndex - 1]) / 2;
        }

        return $median;
    }

    /**
     * calculates the mean / average value
     *
     * @return float
     */
    public function mean()
    {
        return $this->avg();
    }

    public function avg($key = null)
    {
        return round(parent::avg($key), 2);
    }

    /**
     * returns the mode value of the series
     * most-seen value
     *
     * @return float
     */
    public function mode()
    {
        $factor = 1000;

        $total = $this->min();

        $timings = $this->items;
        foreach ($timings as $i => $t)
        {
            $timings[$i] = intval($t * $factor);
        }

        $v = array_count_values($timings);
        arsort($v);
        foreach ($v as $k => $v) {
            $total = $k;
            break;
        }
        return round($total / $factor, 2);
    }
}