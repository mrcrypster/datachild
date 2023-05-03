# Using Sphinx to add fulltext search to Clickhouse
* url: http://datachild.net/data/fulltext-search-sphinx-clickhouse
* category: data
* published: 2023-05-03
* tags: clickhouse, sphinx
* description: How to configure multiple disks as storages in Clickhouse, and how to use different disks for different tables in Clickhouse.

At the moment of writing (May 2023), Clickhouse doesn't support fulltext search indexes. Although it has [ngram-based and token-based data skipping indexes](https://clickhouse.com/docs/en/engines/table-engines/mergetree-family/mergetree#token-bloom-filter), you might need an external solution for full-featured text search, like [Sphinx](http://sphinxsearch.com/).

## Overview of a solution

So, Sphinx enables us to do fulltext search. It works by searching through previously created indexes based on data from Clickhouse:

![Clickhouse and Sphinx usage design](/articles/fulltext-search-sphinx-clickhouse/clickhouse-sphinx-design.png)

So we feed text data from Clickhouse to Sphinx and it'll index that text data for further (efficient) search.

Typically, when we search Sphinx, it returns IDs of matched documents. We can then resolve thouse IDs to original documents in Clickhouse:

![Clickhouse and Sphinx usage design](/articles/fulltext-search-sphinx-clickhouse/app-sphinx-clickhouse.png)

In order to use Sphinx with Clickhouse, we have to configure an index and run indexing process to build fulltext index. When it's done, we can use Sphinx to search text data from Clickhouse.

## Configuring Sphinx index

Sphinx stores text data in documents (just like table rows), each document should have unique ID, which Sphinx requires to be 64-bit integer. Since Clickhouse doesn't (yet?) support autoincrement fields, we should generate unique ID (which is integer) for each row of our table that we want to index. Clickhouse has [rowNumberInAllBlocks()](https://clickhouse.com/docs/en/sql-reference/functions/other-functions#rownumberinallblocks) function to add a row number to the result of the query:

```
SELECT rowNumberInAllBlocks(), col1, col2 FROM some_table
```
* `rowNumberInAllBlocks()` - will add number of row to the result set,
* `col1, col2` - columns of `some_table` that we want to select.

We won't actually use that ID later, so we only need it to surpass Sphinx requirements.

### UUID to identify Clickhouse table rows

Sphinx will only store text index, not the text itself (unless we ask it to, but we won't to save disk space). That's why we need a way to identify documents in Sphinx and corresponding records in Clickhouse. In other words, we need unique identifier for each row in our Clickhouse table. That's exactly, what standard Sphinx document ID is supposed to be used for, but we can't use it since we don't have a way to generate unique integer value for each table record in Clickhouse.

Luckily, Clickhouse has [UUID type](https://clickhouse.com/docs/en/sql-reference/data-types/uuid) and can generate unique UUIDs using [generateUUIDv4()](https://clickhouse.com/docs/en/sql-reference/functions/uuid-functions#generateuuidv4). We can use this function as default expression for `uuid` column to have Clickhouse automatically generate UUIDs for each row of our table:

```
CREATE TABLE some_table
(
    `col` String,
    ***`uuid` UUID DEFAULT generateUUIDv4()***
)
ENGINE = MergeTree ORDER BY ()
```
* `uuid` - this column will have unique UUID for each record,
* `DEFAULT` - this allows to specify expression to use if the column is ommited during data insert.

Now we can ask Sphinx to save `uuid` column value during indexing and return it when searching.

### Sphinx index configuration

We can use `tsvpipe` as a way to feed text data from Clickhouse to Sphinx. Let's configure our index (usually appended to the `/etc/sphinx/sphinx.conf` file):

```
source txt_src
{
  type = tsvpipe
  tsvpipe_command = clickhouse-client -q "SELECT rowNumberInAllBlocks() + 1, col, uuid FROM some_table FORMAT TSV"
  tsvpipe_field = col
  tsvpipe_attr_string = uuid
}

index txt
{
  source = txt_src
  path = /var/indexes/txt
}
```
* `type = tsvpipe` - source type to read `TSV` data,
* `tsvpipe_command` - result of this command should be `TSV` data to index,
* '+ 1' - we use it to starts document IDs from `1` instead of `0`,
* `tsvpipe_field = col` - index `col` column as text,
* `tsvpipe_attr_string = uuid` - Sphinx will return `uuid` value when searching so we can use it later,
* `index txt` - name of our index is `txt`.


### Building and searching an index

Now we can ask Sphinx to index data:

```
indexer txt --rotate
```
* `indexer` - Sphinx indexer utility,
* `txt` - name of our index (defined previously in config),
* `--rotate` - will ask running Sphinx process to use new index once its ready.

At this point we're able to query Sphinx:

```
mysql -P 9306 -h 127.0.0.1 -e "select uuid from txt where match('test')"
```
```output
+--------------------------------------+
| uuid                                 |
+--------------------------------------+
| 83c05036-0490-4ee4-b7aa-9554deaa564d |
...
| cfb234d9-18da-496f-adc4-354aaf54c747 |
+--------------------------------------+
```
* `mysql` - we use Mysql client since Sphinx understands Mysql protocol,
* `-P 9306` - default Sphinx port for Mysql protocol,
* `elect uuid from txt` - we ask Sphinx to retrieve only `uuid` attribute from `txt` index,
* `match('test')` - search for `test` word in indexed documents.


### Resolving documents

After we got `uuid` values from Sphinx, we can look them up in Clickhouse:

```
SELECT col FROM some_table WHERE uuid IN ('83c05036-0490-4ee4-b7aa-9554deaa564d', ...)
```
```output
┌─col───────────────────────────────────────────────────┐
│ test text 1901-01-01 words  119602 test - test, 22,33 │
...
└───────────────────────────────────────────────────────┘
```
* `uuid IN` - we filter `uuid` column based on values returned from Sphinx.

It's important to mention, that Clickhouse `uuid` lookup performance will dramatically depend on a [table sorting key](https://medium.com/datadenys/improving-clickhouse-query-performance-tuning-key-order-f406db7cfeb9).

## Further reading

- (@Plan: Optimizing Clickhouse fulltext search with Sphinx attributes)
- [Ngram Bloom filter in Clickhouse](https://clickhouse.com/docs/en/engines/table-engines/mergetree-family/mergetree#n-gram-bloom-filter)
- [Token Blook filter in Clickhouse](https://clickhouse.com/docs/en/engines/table-engines/mergetree-family/mergetree#token-bloom-filter)
- [CSV/TSV index source in Sphinx](http://sphinxsearch.com/docs/current/xsvpipe.html)