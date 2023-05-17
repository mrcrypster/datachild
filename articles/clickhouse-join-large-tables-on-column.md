# How to merge large tables in ClickHouse using join
* url: http://datachild.net/data/clickhouse-join-large-tables-on-column
* category: data
* published: 2023-05-17
* tags: clickhouse
* description: How to merge multiple large tables into single table based on a given column. Solution to MEMORY_LIMIT_EXCEEDED problem when joining large tables.

One case that needs attention in ClickHouse is when we need to merge data from different tables horizontally using join on a certain key column. Suppose, we have two tables - `events` and `errors`. Both of them have `event_id` key and we would like to join them to the resulting table with all columns from both source tables:

![Merging 2 tables in ClickHouse](/articles/clickhouse-join-large-tables-on-column/merge-tables.png)

## OvercommitTracker problem

Wait, why can' we just join those with a query?

```
SELECT count(*)
FROM events e JOIN errors r
ON (r.event_id = e.event_id)
WHERE label = 'payment' and error = 'out-of-money'
```

Well, yes, but joining large tables can lead to consuming lot of memory, and the following error might occur:

```
Code: 241. DB::Exception: Received from localhost:9000. DB::Exception: Memory limit (total) exceeded: would use 8.39 GiB (attempt to allocate chunk of 2147614720 bytes), maximum: 6.99 GiB. OvercommitTracker decision: Query was selected to stop by OvercommitTracker.: While executing JoiningTransform. (MEMORY_LIMIT_EXCEEDED)
(query: ...)
```

This happens, because ClickHouse has [`OvercommitTracker`](https://clickhouse.com/docs/en/operations/settings/memory-overcommit) that decides to stop further execution of the query that's trying to use more memory than we have (that can configured).

## Joining large tables by parts

Since we usually deal with large tables in ClickHouse, we might meet that error once in a while.

Practical approach to this problem, is to do several smaller joins instead of the single one:
1. Define a total range of key column (the one we join on, `event_id` in our case).
2. Split that range to multiple parts, so we can join separate parts instead of entire tables. The number of parts should be picked so that there's enough RAM to do join for each individual part.
3. Iterate through all parts to build resulting table.

![Merging large tables by parts in ClickHouse](/articles/clickhouse-join-large-tables-on-column/merge-by-parts.png)

Example PHP code to join our tables by parts:

```
$table_left = 'events';
$table_right = 'errors';
$bulk = 1000000;

$min_max = clickhousy::row('SELECT min(id) min, max(id) max FROM ' . $table_left);
$offset = $min_max['min'];
$max = $min_max['max'];

do {
  $limit = $offset + $bulk;
  $sql =  "INSERT INTO data " .
          "SELECT event_id, date, label, error FROM ". 
          "(SELECT * FROM {$table_left} WHERE event_id >= {$offset} and event_id < {$limit}) as t1 " .
          "LEFT JOIN (SELECT * FROM {$table_right} WHERE event_id >= {$offset} and event_id < {$limit}) as t2 " .
          "ON (t1.event_id = t2.event_id)";
  echo "{$offset}\n";
  passthru('clickhouse-client --progress -q ' . escapeshellarg($sql));
  $offset = $limit;
} while ( $offset < $max );
```
* `$table_left` - source table,
* `$table_right` - table to join,
* `$bulk` - step in `event_id` value increase over iterations (1 million in our case),
* `clickhousy::row` - gets single record from ClickHouse [clickhousy lib](https://github.com/mrcrypster/clickhousy),
* `INSERT INTO data` - we insert data into resulting `data` table,
* `event_id >= {$offset} and event_id < {$limit}` - limit selected range from both tables using `event_id` column,
* `ON (t1.event_id = t2.event_id)` - join filtered parts instead of entire source tables,
* `$offset < $max` - iterate while we meet max `event_id`.

## Further reading
* [JOIN in ClickHouse](https://clickhouse.com/docs/en/sql-reference/statements/select/join)
* [Query execution resources limits in ClickHouse](https://clickhouse.com/docs/en/operations/settings/query-complexity)