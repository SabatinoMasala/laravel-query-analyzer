# Query Analyzer for Laravel

[![Latest Stable Version](https://poser.pugx.org/rokde/laravel-query-analyzer/v/stable.svg)](https://packagist.org/packages/rokde/laravel-query-analyzer) [![Latest Unstable Version](https://poser.pugx.org/rokde/laravel-query-analyzer/v/unstable.svg)](https://packagist.org/packages/rokde/laravel-query-analyzer) [![License](https://poser.pugx.org/rokde/laravel-query-analyzer/license.svg)](https://packagist.org/packages/rokde/laravel-query-analyzer) [![Total Downloads](https://poser.pugx.org/rokde/laravel-query-analyzer/downloads.svg)](https://packagist.org/packages/rokde/laravel-query-analyzer)

## Quickstart

```
composer require rokde/laravel-query-analyzer
```

Add to `providers` in `config/app.php`:

```
Rokde\LaravelQueryAnalyzer\LaravelQueryAnalyzerProvider::class,
```

## Installation

Add to your composer.json following lines

	"require": {
		"rokde/laravel-query-analyzer": "~0.0"
	}

Add `Rokde\LaravelQueryAnalyzer\LaravelQueryAnalyzerProvider::class,` to `providers` in `config/app.php`.

Run `php artisan vendor:publish --provider="Rokde\LaravelQueryAnalyzer\LaravelQueryAnalyzerProvider"`

## Configuration

### `enabled`

Is the analyzing enabled or not.

## Usage

You have a console command to get all queries listed.

### List all queries

	$> php artisan analyze:queries

Lists all queries. You have `--limit` and `--offset` as options for paginate through all the queries.

Example output:

	01 select * from `users` where `users`.`id` = ? limit 1
    02 select * from `profiles` where `profiles`.`user_id` = ? and `profiles`.`user_id` is not null


### List details of one query

	$> php artisan analyze:queries 1

Lists details for query number 1.

Example output:

	select * from `users` where `users`.`id` = ? limit 1
    +-------+---------+---------+------+------+
    | count | fastest | slowest | avg  | mode |
    +-------+---------+---------+------+------+
    | 14    | 0.4     | 0.73    | 0.53 | 0.58 |
    +-------+---------+---------+------+------+
	
    +----------+---------+---------+------+------+
    | bindings | fastest | slowest | avg  | mode |
    +----------+---------+---------+------+------+
    | [1]      | 0.4     | 0.73    | 0.53 | 0.58 |
    | [2]      | 0.41    | 0.74    | 0.54 | 0.59 |
    +----------+---------+---------+------+------+

You can see the summary of all queries combined and afterwards the bindings-dependent timings. Maybe some bindings
 cause an extra long run.

### Clear the data

	$> php artisan analyze:clear

Clears the whole data for analysis.
