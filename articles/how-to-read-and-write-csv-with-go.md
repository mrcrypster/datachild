# Reading CSV, TSV, and invalid CSV files with Golang
* url: http://datachild.net/programming/reading-csv-golang
* category: programming
* published: 2023-04-14
* tags: golang, csv
* description: Reading CSV with Golang line by line or entirely, reading CSV with custom delimiters (including TSV) and escaping rules, and reading broken CSV files.

CSV is still one of the most popular formats to organize table-like data.
Golang is a powerful tool to process CSV given its performance and ease of use.
Let's see how to address the most common cases.

## Reading CSV file

To read CSV files it's recommended to use `encoding/csv` reader component.
We're going to use the following `data.csv` for examples:

```cli
cat data.csv
```
```output
id,name,price
1,Phone,123
2,TV,34
3,Boot,5
```

We might want to read and process CSV files line by line in most cases to handle large files and never run out of memory:

```golang
package main

import (
  "encoding/csv"; "fmt"; "io"; "os"
)

func main() {
  f, _ := os.Open("data.csv")
  r := csv.NewReader(f)

  for {
    row, err := r.Read()

    if err == io.EOF {
      break
    }

    fmt.Println(row)
  }
}
```
```output
[id name price]
[1 Phone 123]
[2 TV 34]
[3 Boot 5]
```

* `encoding/csv` - this is the package that allows us to read CSV files
* `os.Open("data.csv")` - opens data.csv file for reading
* `csv.NewReader(f)` - use the opened file for the CSV reader
* `row, err := r.Read()` - read (next) line from our CSV file
* `if err == io.EOF {` - this will be triggered when we reach the end of file
* `fmt.Println(row)` - prints row array that was read from CSV

If we know we work with small CSV files, we can use `ReadAll()` method to read the entire file:

```golang
package main

import (
  "encoding/csv"; "fmt"; "os"
)

func main() {
  f, _ := os.Open("data.csv")
  r := csv.NewReader(f)

  rows, _ := ***r.ReadAll()***
  fmt.Println(rows)
}
```
```output
[[id name price] [1 Phone 123] [2 TV 34] [3 Boot 5]]
```

* `r.ReadAll()` - will read entire CSV file
* `rows` - will contain array of rows (also arrays)

## Reading TSV files and other custom delimiters

In some cases, CSV files are actually not comma-delimited ("C" comes for comma in "CSV"), but other symbols are used to separate columns. Use `Comma` property to define the delimiter in this case. Let's read [tab separated](https://en.wikipedia.org/wiki/Tab-separated_values) file (tabs are used for columns separation):

```golang
package main

import (
  "encoding/csv"; "fmt"; "io"; "os"
)

func main() {
  f, _ := os.Open("data.tsv")
  r := csv.NewReader(f)
  ***r.Comma = '\\t'***

  for {
    row, err := r.Read()

    if err == io.EOF {
      break
    }

    fmt.Println(row)
  }
}
```

* `r.Comma = '\\t'` &mdash; we can use any (single) symbol here to match delimiter used in file

## Reading CSV with custom quoting symbols

Double quotes should be used to quote values in CSV files, but someone might have decided to use something else when creating CSV you have to deal with.

Unfortunately, `encoding/csv` component [doesn't support custom quotes](https://github.com/golang/go/issues/8458). In such cases, we can use extra tools to reformat before we feed them to our program. Let's take the following single-quoted CSV file as an example:

```cli
cat data-custom.csv
```
```output
id,name,price
1,Phone,123
2,***'TV, Screens'***,34
3,Boot,5
```
We can use python [csvkit](https://csvkit.readthedocs.io/en/latest/tutorial/1_getting_started.html#installing-csvkit) toolset to change quoting:

```cli
csvformat -q "'" data.csv > data-standard.csv
```

* `csvformat` - this tool formats given files based on specified rules
* `-q "'"` - here we state that our file uses single quotes for quoting
* `data-standard.csv` - formatted CSV will be written to this file

This will produce the following file:
```bash
id,name,price
1,Phone,123
2,***"TV, Screens"***,34
3,Boot,5
```

As we can see, now we have double quotes and this file can be used with our Golang program.

## Dealing with broken/invalid CSV files

Broken CSV file is a common case. Let's try to handle the following broken CSV:
```text
id,name,price
1,Phone,123
***7,***
***2,TV, Screens,34***
3,Boot,
```
* `7,` - broken, because it has less than 3 columns
* `2,TV, Screens,34` - broken, because the second column is not escaped but has a comma in it

While processing this file, `encoding/csv` component will throw errors on invalid rows which we catch and process in a way we want:

```golang
package main

import (
  "encoding/csv"; "fmt"; "io"; "os"
)

func main() {
  f, _ := os.Open("data.csv")
  r := csv.NewReader(f)

  for {
    row, err := r.Read()

    if err == io.EOF {
      break
    }

    ***if err != nil {***
      ***fmt.Println(err)***
      ***continue***
    ***}***

    fmt.Println(row)
  }
}
```
```output
[id name price]
[1 Phone 123]
***record on line 3: wrong number of fields***
***record on line 4: wrong number of fields***
[3 Boot 5]
```

* `err != nil` - check if we got an error for the current row
* `fmt.Println(err)` - output error
* `continue` - we do not want to process (or print as in the example) invalid rows, so we skip

Another option is to use `csvclean` tool from [`csvkit`](/programming/format-clean-fix-csv-with-csvkit) toolset to filter invalid rows from the CSV file.
