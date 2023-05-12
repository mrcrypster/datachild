# How to use Regex to feed text data to ClickHouse
* url: http://datachild.net/data/clickhouse-feed-regex-data
* category: data
* published: 2023-05-12
* tags: clickhouse
* description: Using regex input format can help in loading unformatted or broken text data into Clickhouse. Using Regexp format for that with a practical example.

Text data formats like CSV or JSON are cool. But in practice, text data files can be poorly formatted, broken, or just strangely structured (or as people say, unstructured) pieces of text.

ClickHouse supports ingesting data with [Regexp data format](https://clickhouse.com/docs/en/interfaces/formats#data-format-regexp), which allows specifying regular expression with capture groups. Then ClickHouse will map captured matches to the columns of the target table by index (first capture goes to the first column and so on):

![ClickHouse maps regex captures to target columns](/articles/clickhouse-feed-regex-data/text-regex-clickhouse.png)

Suppose we need to ingest data to the ClickHouse table from the following `hits.txt` file:
```
Name: John - Views: 12
Name: News - Views: 4325 - Latest: 2022-12-12
Name: Modern Sports - Views: 5436 - Latest: 2023-01-01
```

In order to use the `Regexp` format we have to specify the `format_regexp` option with the regular expression itself:

```
clickhouse-client -q "INSERT INTO hits SETTINGS format_regexp = 'Name: (.+?) - Views: ([^ ]+).*' FORMAT Regexp" < hits.txt
```
* `SETTINGS` - this allows configuring additional parameters for the current query,
* `format_regexp` - specify the regular expression to apply to lines of text file,
* `(.+?)` - first capture group (will go to the first column of the `hits` table),
* `([^ ]+)` - second capture group (will go to the second column of the `hits` table),
* `FORMAT Regexp` - let ClickHouse know we want it to use the `Regexp` format,
* `< hits.txt` - pipe `hits.txt` file to ClickHouse client.

ClickHouse will apply the given regular expression to each line in the source text file. Now let's see how our target table was populated:

```
select * from hits format PrettySpace
```
```output
 name            views

 John                1 
 News                4 
 Modern Sports       5 
```

**Note**, that given regular **must match an entire line** from a file, even if you plan to capture only a part of it. Also, as of version `23.1.2.9` ClickHouse won't allow specifying table columns in the insert statement (`INSERT INTO table(col1, col2, ...)`), so the number of capture groups should be the same as the entire number of columns in the target table.

## Skipping unmatched lines

By default, ClickHouse will break on lines that can't be matched with a given regex:

```
Code: 117. DB::Exception: Line "broken line" doesn't match the regexp.: (at row 3)
: While executing ParallelParsingBlockInputFormat: data for INSERT was parsed from stdin: (in query: INSERT INTO hits SETTINGS format_regexp = 'Name: (.+?) - Views: (.+?).*' FORMAT Regexp). (INCORRECT_DATA)
```
* `doesn't match the regexp` - ClickHouse breaks on lines it can't match.

This will be the case if our file has the following lines in it:
```
Name: John - Views: 12
***broken line***
Name: Modern Sports - Views: 5436 - Latest: 2023-01-01
```

We can use the `format_regexp_skip_unmatched` settings option to ask ClickHouse to skip unmatched lines instead of throwing an exception:

```
clickhouse-client --progress -q "INSERT INTO hits SETTINGS format_regexp = 'Name: (.+?) - Views: (.+?).*', format_regexp_skip_unmatched = 1 FORMAT Regexp" < hits.txt
```
* `format_regexp_skip_unmatched = 1` - unmatched lines will be silently skipped during processing.