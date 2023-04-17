# Using csvkit to format, clean, and fix CSV files
* url: http://datachild.local/programming/format-clean-fix-csv-with-csvkit
* category: programming
* published: 2023-04-17
* tags: python, csv
* description: Formatting CSV, TSV, and other files, converting CSV delimiters, converting CSV quoting symbols, fixing invalid CSV files, working with compressed CSV files

Haven't you seen valid CSV files in a while? Me too.
The [`csvkit`](https://csvkit.readthedocs.io/en/latest/) package is a Python-based set of tools to process CSV files: fix and clean,
convert between different delimiting and quoting, grep, and even query data.
We'll focus on changing the format and fixing CSV files in this tutorial.

But before we start, let's install `csvkit`:

```
pip install csvkit
```
* `pip install` - installs Python package
* `csvkit` - what we want to install

This will install a whole bunch of tools:
```output
csv2ods    csvcut     csvgrep    csvjson    csvpy      csvsql     csvstat    
csvclean   csvformat  csvjoin    csvlook    csvsort    csvstack
```

We'll focus only on a couple of tools from this list.

## Removing CSV header

![Remove CSV header](/articles/remove-csv-header-row.png)

If we want to remove the header row from the CSV file:

```
csvformat -K 1 data.csv > out.csv
```
* `csvformat` - formatting tool for CSV files
* `-K 1` - skips one line (first row) from the CSV file
* `data.csv` - file to remove CSV header from
* `out.csv` - resulting CSV without a header row

We can use `-K 2` to remove the first two lines, or any number to remove the first `N` lines.

### Adding header row to CSV

A quick way to insert a header row is to use the `--no-header-row` option.
This will insert a mock header with columns named "a,b,c...":

```
csvformat --no-header-row data.csv > data-with-header.csv
```
* `--no-header-row` - will add header row

## Removing column(s) from CSV file

![Drop CSV column](/articles/drop-csv-column.png)

To drop a column from the CSV file:

```
csvcut -C 3,4 data.csv > out.csv
```
* `csvcut` - a tool that removes rows/columns from CSV files
* `-C` - remove given columns (by indexes, starting from 1, uppercase "C")
* `3,4` - removes the third and fourth columns for a given file

If we only want specific columns to stay while removing everything else, we can use:

```
csvcut -c 1,2,5 data.csv > out.csv
```
* `-c` - keep specified columns only (lowercase "c")
* `1,2,5` - we'll have first, second, and fifth columns in the resulting file

## Changing CSV delimiter

To convert CSV to a tab-separated (TSV) file:

```
csvformat -D ";" data.csv
```
```output
id;name;price
1;Phone;123
7;
2;TV, Screens;34
3;Boot;5
```
* `csvformat` - a tool to change CSV formatting
* `-D` - specify delimiter instead of a comma
* `;` - delimiter we want to use
* `data.csv` - input CSV file to change

### Changing commas to tabs as CSV delimiter

To convert CSV to a tab-separated (TSV) file:

```
csvformat -T data.csv
```
```output
id	name	price
1	Phone	123
7
2	TV, Screens	34
3	Boot	5
```
* `-T` - here we ask to use tabs as a delimiter for output

## Changing CSV quoting (e.g double quotes to single quotes)

We can use a custom quoting symbol for CSV:
```
csvformat -Q "'" data.csv
```
```output
id,name,price
1,Phone,123
7,
2,***'TV, Screens'***,34
3,Boot,5
```
* `-Q` - set quoting symbol
* `'` - we want to use a single quote

## Cleaning invalid CSV rows

In many cases, we have to deal with broken CSV files.
We can filter out invalid records from CSV files:

```
csvclean data.csv
```
* `csvclean` - filters the given CSV file and creates valid (and file with errors)
* `data.csv` - file to filter invalid records from

This tool will create 2 files:
```
data_err.csv
data_out.csv
```
* `data_out.csv` - this file will contain only valid CSV records
* `data_err.csv` - this file will contain invalid records along with error details

Now we can analyze all errors in the `data_err.csv` file:
```
cat data_err.csv
```
```output
line_number,msg,id,name,price
2,"***Expected 3 columns, found 2 columns***",7,
3,"***Expected 3 columns, found 4 columns***",2,TV, Screens,34
```

## Working with compressed CSV files

All tools from `csvkit` understands gzip compression, so we don't need to decompress:
```
csvformat compressed.csv.gz
```
* `.gz` - we can work with compressed files

## Piping CSV data

We can also pipe CSV data directly to `csvkit` commands, which makes it useful to process CSV output from other programs on the fly:
```
echo '1,2,3,"hi"' | csvformat
```
```output
1,2,3,hi
```
* `echo '1,2,3,"hi"'` - sample command that output some CSV
* `|` - pipe our output to the `csvkit` tool
* `csvformat` - a tool to accept piped CSV data
