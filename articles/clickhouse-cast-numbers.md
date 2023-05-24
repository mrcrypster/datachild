# Converting strings to numbers in ClickHouse
* url: http://datachild.net/data/clickhouse-cast-numbers
* category: data
* published: 2023-05-24
* tags: clickhouse
* description: How to convert strings to integers and floats in ClickHouse. Controlling invalid values behavior on conversion.

We have to deal with strings a lot since a lot of source data is stored in text formats. Can we just create tables with all `String` columns and never mind all the conversion stuff? Yes, but we'll experience poor query performance since conversion will still happen, but at a query time. Not saying, that strings take more disk space than special types, like numbers or dates. Thankfully we have a set of handy functions in ClickHouse to easily convert from strings to target types.

## Converting to integers

To convert strings (or other appropriate types) to integers we can use the `toInt32()` function:

```
SELECT toInt32('1231231')
```
```output
┌─toInt32('1231231')─┐
│            1231231 │
└────────────────────┘
```
* `toInt32` - converts given value to a 32-bit integer,
* `'1231231'` - string value we want to convert to integer.

Since we convert to an `Int32` type, negative numbers will also be treated the right way:

```
SELECT toInt32('-18')
```
```output
┌─toInt32('-18')─┐
│            -18 │
└────────────────┘
```

Note, that we can use `8`, `16`, `32`, `64`, `128`, and `256` bit conversions depending on the expected size of resulting value:
```
SELECT toInt8('4')
```
```output
┌─toInt8('4')─┐
│           4 │
└─────────────┘
```
* `toInt8` - will convert to an 8-bit sized integer.


## Unsigned integers

If we expect the converted value to be an unsigned integer, we can use the unsigned version of the conversion function:

```
SELECT toUInt32('4234')
```
```output
┌─toUInt32('4234')─┐
│             4234 │
└──────────────────┘
```
* `toUInt32` - will convert the given string to an unsigned integer (32-bit in this case).

Unsigned functions also have the same `8`...`256` bit versions.


## Converting to floats

Similarly, we can use `toFloat32` or `toFloat64` to convert given values to floating point values (either 32-bit or 64-bit):

```
SELECT toFloat32('1.123'), toFloat32('-1.7')
```
```output
┌─toFloat32('1.123')─┬─toFloat32('-1.7')─┐
│              1.123 │              -1.7 │
└────────────────────┴───────────────────┘
```
* `toFloat32` - converts given value to 32-bit float.

In case we want to convert to decimals, there's `toDecimal32` function (with `64`, `128`, and `256` versions):
```
SELECT toDecimal32('97689.43243', 2)
```
```output
┌─toDecimal32('97689.43243', 2)─┐
│                      97689.43 │
└───────────────────────────────┘
```
* `toDecimal32` - converts given value (string `97689.43243` in our case) to a 32-bit decimal,
* `, 2)` - the second argument sets the number of decimal places for the converted value.

## Handling invalid values

If we try to convert from strings with invalid numbers, ClickHouse will react with an exception:

```
SELECT toInt32('3e2')
```
```output
***DB::Exception***: Cannot parse string '3e2' as Int32: syntax error at position 1 (parsed just '3').
```

For these cases ClickHouse arms us with special versions of converting functions. If we want ClickHouse to return null on errors, we simply add the `OrNull` suffix to the function name. Similarly, ClickHouse returns `0` if we add `OrZero` to the function name:

```
SELECT toInt32OrNull('3e2'), toInt32OrZero('a'), toFloat32OrZero('---');
```
```output
┌─toInt32OrNull('3e2')─┬─toInt32OrZero('a')─┬─toFloat32OrZero('---')─┐
│                 ᴺᵁᴸᴸ │                  0 │                      0 │
└──────────────────────┴────────────────────┴────────────────────────┘
```

### Using custom values on errors

We can also ask ClickHouse to return a specific default value instead of just `null` or `0`. In that case, we add the `OrDefault` suffix to conversion function names:

```
SELECT toFloat32OrDefault('s3', toFloat32(2.0)), toInt32OrDefault('3e2', toInt32(5));
```
```output
┌─toFloat32OrDefault('s3', toFloat32(2.))─┬─toInt32OrDefault('3e2', toInt32(5))─┐
│                                       2 │                                   5 │
└─────────────────────────────────────────┴─────────────────────────────────────┘
```
* `OrDefault` - asks ClickHouse to return a custom-defined value if it fails to convert,
* `toFloat32(` - we use conversion here to make sure ClickHouse uses the same type for default value as we want for  converted value,
* `2.0` - default value we want to see upon failed conversions.


## Further reading
* [ClickHouse type conversion functions](https://clickhouse.com/docs/en/sql-reference/functions/type-conversion-functions)