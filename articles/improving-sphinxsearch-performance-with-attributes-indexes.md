# Improving Sphinxsearch performance with attributes indexes
* url: http://datachild.net/data/improving-sphinxsearch-performance-with-attributes-indexes
* category: data
* published: 2023-06-23
* tags: sphinx
* description: Sphinxsearch is a popular full-text database and provides filtering based on attributes. Filtering queries can run with or without full-text search and might demonstrate poor performance on big document sets. Sphinx introduces attribute indexes to improve filtering queries performance, let's see how this works.

[Sphinxsearch](https://sphinxsearch.com/) is a simple but still powerful full-text search database. It supports not only text column indexing but attribute filtering as well (e.g. when you want to search product in a specific category only).

Though the main use case when using Sphinx is to search text, you might need to search based on attribute values only from time to time. Let's say you have indexed blog posts with Sphinx with the following RT index:
```
DESCRIBE posts
```
```output
+-----------+--------+------------+------+
| Field     | Type   | Properties | Key  |
+-----------+--------+------------+------+
| id        | bigint |            |      |
| subject   | field  | indexed    |      |
| post      | field  | indexed    |      |
| cat_id    | uint   |            |      |
| author_id | uint   |            |      |
| params    | json   |            |      |
+-----------+--------+------------+------+
```
* `DESCRIBE posts` - we're using [SphinxQL](http://sphinxsearch.com/docs/current/sphinxql-reference.html) and [Mysql protocol](http://sphinxsearch.com/docs/current/sphinxql.html) to manage Sphinxsearch.

So we have `subject` and `post` fields which are full-text indexed. We also have `cat_id` and `author_id` attributes which allow us to further filter search results. The `params` attribute is of a JSON type to store unstructured data.


## Filtering on attributes without full-text search

What if we want to select posts for specific categories or authors without searching any specific text (so no full-text search is done)? Sphinx allows doing that:

```
SELECT * FROM posts WHERE cat_id = 10 ORDER BY id DESC LIMIT 10
```
```output
+--------+--------+-----------+------------------------------------------------------------------+
| id     | cat_id | author_id | params                                                           |
+--------+--------+-----------+------------------------------------------------------------------+
| 999947 |     10 |       233 | {"entity":"da8215f2be2ef80a0b02","ts":4805248}                   |
...
| 999724 |     10 |        92 | {"entity":"5376eacebd742d70ca2f82","ts":4483027}                 |
+--------+--------+-----------+------------------------------------------------------------------+
```

In this case, Sphinx will have to scan an entire attribute index to fetch the necessary data. Why? Because Sphinx is a full-text index in the first place. Meaning, in simple words, it stores words in a sorted way and then assigns document ids to each word. When searching for text, Sphinxsearch fetches a (small) subset of relevant document IDs. Then it's pretty easy to additionally filter this subset based on attribute values without any additional indexes. But if we skip text search and just filter on attributes, Sphinx has to scan all of its documents. Let's see how this query is executed:

```
EXPLAIN SELECT * FROM posts WHERE cat_id = 10 ORDER BY id DESC LIMIT 10
```
```output
+-------+-----------+---------------------------+
| Index | AttrIndex | Analysis                  |
+-------+-----------+---------------------------+
| posts |           | ***Not using attribute index*** |
+-------+-----------+---------------------------+
```
* `EXPLAIN` - as in Mysql, this gives us query execution analysis.

As we can see, Sphinx is unable to use any indexes for this query. This query takes `0.16` seconds on a 5-million docs index:

```
SELECT * FROM posts WHERE cat_id = 10 ORDER BY id DESC LIMIT 10;
...
10 rows in set (***0.16 sec***)
```
* `0.16 sec` - can this kind of performance be improved?


## Attribute indexes

Modern versions of Sphinxsearch introduce [attribute indexes](http://sphinxsearch.com/docs/sphinx3.html#using-attribute-indexes). Those are similar to Mysql indexes and, once created, can be used to improve query performance.

To create an attribute index we do the following:
```
CREATE INDEX cat ON posts(cat_id)
```
```output
Query OK, 0 rows affected (1.32 sec)
```
* `CREATE INDEX` - creates attribute index,
* `cat` - index name,
* `posts` - full-text (RT) index name to create attribute index for,
* `cat_id` - the name of the attribute to create an index on.

Now let's check how our query performs:
```
SELECT * FROM posts WHERE cat_id = 10 ORDER BY id DESC LIMIT 10;
...
10 rows in set (***0.00 sec***)
```
* `0.00 sec` - as we can see, the speed of the query increased by at least 10 times.

We can also make sure that Sphinx is using an attribute index for this query:
```
EXPLAIN SELECT * FROM posts WHERE cat_id = 10 ORDER BY id DESC LIMIT 10;
```
```output
+-------+-----------+----------------------------------------------------------------------------------------------------------------+
| Index | AttrIndex | Analysis                                                                                                       |
+-------+-----------+----------------------------------------------------------------------------------------------------------------+
| posts |           | Using attribute indexes on 100.00% of total data (using on 100.00% of ram data, using on 100.00% of disk data) |
| posts | cat       | Using on 100.00% of ram data, 100.00% of disk data                                                             |
+-------+-----------+----------------------------------------------------------------------------------------------------------------+
```

**Note,** that as of version `3.5.1`, Sphinx only supports integer and float types (including multi-value attributes) for attribute indexes.

## Indexing JSON attribute keys

Indexes can also be created on JSON attribute keys. Let's take a look at this query:
```
SELECT * FROM posts WHERE params.ts = 7868928;
```
```output
+---------+--------+-----------+--------------------------------------------------------------------+
| id      | cat_id | author_id | params                                                             |
+---------+--------+-----------+--------------------------------------------------------------------+
|  760483 |     30 |       145 | {"entity":"15176c3856e0868fd23b7f2ef35bafc311","ts":7868928}       |
| 2316721 |     13 |        14 | {"entity":"72220affe550382ac9dd6a","ts":7868928}                   |
| 4999737 |     12 |       262 | {"entity":"742aa2cc33a03c78c3d0530514ca7e602214bb5a","ts":7868928} |
+---------+--------+-----------+--------------------------------------------------------------------+
3 rows in set (***0.28 sec***)
```

This query can be improved with a JSON attribute index, that's created for a specific key in a JSON attribute. In this case, we have to implicitly specify the key type since JSON is not strictly typed:

```
CREATE INDEX ts ON posts(UINT(params.ts))
```
```output
Query OK, 0 rows affected (3.56 sec)
```
* `UINT(` - we cast given JSON key to unsigned integer,
* `params` - the name of the JSON attribute,
* `.ts` - the name of the JSON key to create index on.

Now let's see how our query performs:

```
SELECT * FROM posts WHERE uint(params.ts) = 7868928
```
```output
...
3 rows in set (***0.01 sec***)
```

As a result, our query now performs 10x faster. **Note,** that we had to cast to `uint` in the query itself so Sphinx can use the relevant attribute index:

```
EXPLAIN SELECT * FROM posts WHERE uint(params.ts) = 7868928;
```
```output
+-------+-----------+----------------------------------------------------------------------------------------------------------------+
| Index | AttrIndex | Analysis                                                                                                       |
+-------+-----------+----------------------------------------------------------------------------------------------------------------+
| posts |           | Using attribute indexes on 100.00% of total data (using on 100.00% of ram data, using on 100.00% of disk data) |
| posts | ts        | Using on 100.00% of ram data, 100.00% of disk data                                                             |
+-------+-----------+----------------------------------------------------------------------------------------------------------------+
```

## Managing attribute indexes

To find what attribute indexes are created for a specific full-text index, we can use the following:
```
SHOW INDEX FROM posts
```
```output
+------+-----------+----------+------+-----------+
| Seq  | IndexName | AttrName | Type | Expr      |
+------+-----------+----------+------+-----------+
| 1    | cat       | cat_id   | uint | cat_id    |
| 2    | ts        | params   | uint | params.ts |
+------+-----------+----------+------+-----------+
```

Dropping indexes is done using:
```
DROP INDEX ts ON posts
```
* `ts` - the name of the attribute index to remove,
* `posts` - the name of the full-text index to remove specified attribute index from.


## Optimizing indexes

Sphinx indexes performance might degrade over time since a lot of fragmenting happens. That's why running an optimization routine periodically should be done:
```
OPTIMIZE INDEX posts
```
* `OPTIMIZE INDEX` - this will launch the index optimization process in the background.



## Further reading
* [Using Sphinx with Clickhouse](https://datachild.net/data/fulltext-search-sphinx-clickhouse)
* [CREATE INDEX statement in Sphinx](http://sphinxsearch.com/docs/sphinx3.html#create-index-syntax)
* [OPTIMIZE INDEX statement in Sphinx](http://sphinxsearch.com/docs/current/sphinxql-optimize-index.html)