# How manage ingesting errors in ClickHouse
* url: http://datachild.net/data/clickhouse-skip-ingest-errors
* category: data
* published: 2023-05-23
* tags: clickhouse
* description: Managing errors when ingesting data into ClickHouse, including text data sources like CSV and TSV.

In many cases, we have to work with broken or invalid data files before ingesting data into ClickHouse. ClickHouse supports a huge amount of [input formats](https://clickhouse.com/docs/en/interfaces/formats) but in case of an invalid format, we get the following error:

```
Code: 117. DB::Exception: Expected end of line
```

We have several strategies here to use (and combine) to achieve desired results.

## Skipping errors

ClickHouse has several settings options to control errors while ingesting data from text formats (e.g., CSV or TSV):

* `input_format_allow_errors_num` - total number of acceptable invalid records during ingest operation (e.g. value of `5` will allow 5 invalid records),
* `input_format_allow_errors_ratio` - acceptable portion of invalid records during ingest operation, specified as a float number from `0` (meaning no errors are allowed) to `1` (meaning 100% of records can be invalid).

Both settings will ask ClickHouse to allow a certain amount of errors, either in absolute value or in percent to total imported records. Invalid records will be skipped while valid records will be imported successfully. ClickHouse throws an exception when it reaches the first limit - either absolute or ratio. If limits are not reached, no error is raised.

It's a good approach to use both settings options at a query time to have better control over queries:

```
cat data.csv | clickhouse-client --progress -q \
"INSERT INTO some_table SETTINGS input_format_allow_errors_num = 5, input_format_allow_errors_ratio = 0.1 FORMAT CSV"
```
* `data.csv` - file to ingest data into ClickHouse from,
* `SETTINGS` - allows setting query options,
* `5` - we limit the total amount of allowed invalid records to 5,
* `0.1` - we limit the percentage of invalid records to 10%.

## Fixing input files

Skipping errors can be a quick fix, but you might want to try fixing source files instead. If dealing with delimited text formats (like CSV), take a look at [fixing data with CSVkit](https://datachild.net/programming/format-clean-fix-csv-with-csvkit).

## Further reading
* [input_format_allow_errors_num settings option in ClickHouse](https://clickhouse.com/docs/en/operations/settings/formats#input_format_allow_errors_num)
* [input_format_allow_errors_ratio settings option in ClickHouse](https://clickhouse.com/docs/en/operations/settings/formats#input_format_allow_errors_ratio)