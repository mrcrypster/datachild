# Vector similarity search using Redis Stack
* url: http://datachild.net/data/vector-similarity-search-using-redis-stack
* category: data
* published: 2024-06-08
* tags: vector search, redis, knn, hnsw, rag
* description: Using Redis Stack to store vectors and do vector similarity search, for KNN and other ML tasks.

Finding similar (or nearest) vectors is a popular task in ML workloads.
One of the popular applications currently is querying LLMs enriched by a local knowledge base using the RAG approach.
[Redis](https://redis.io/) is a popular and effective solution to work with different types of data, including vectors.


## Installing Redis Stack

While standard Redis installation doesn't support vectors and requires installing extensions, [Redis Stack](https://redis.io/about/about-stack/) is an advanced pack that comes with vector support.
To install Redis Stack visit [installation instructions](https://redis.io/docs/latest/operate/oss_and_stack/install/install-stack/linux/) or in the case of Ubuntu run the following:

```bash
curl -fsSL https://packages.redis.io/gpg | sudo gpg --dearmor -o /usr/share/keyrings/redis-archive-keyring.gpg
sudo chmod 644 /usr/share/keyrings/redis-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/redis-archive-keyring.gpg] https://packages.redis.io/deb $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/redis.list
sudo apt-get update
sudo apt-get install redis-stack-server
```
```output
... (will install Redis Stack)
```
* `https://packages.redis.io/deb` - redis official debian/ubuntu source
* `install redis-stack-server` - installs Redis Stack server 


## Storing vectors

We have multiple ways of storing vectors in Redis.
First - is by using [Redis hashes](https://redis.io/docs/latest/develop/data-types/hashes/).
In this case, we can pass vector as some specific key of hash together with other keys:

```python
import numpy as np
from redis import Redis
r = Redis(host="localhost", port=6379)
r.hset('doc_1', mapping={
  'title': 'Some doc',
  'vector': np.random.rand(8).astype(np.float32).tobytes()
})
print(np.frombuffer(r.hget('doc_1', 'vector'), dtype=np.float32))
```
```output
[0.4681091  0.5844017  0.21458998 0.15927918 0.29837957 0.13542081
 0.9707261  0.6643426 ]
```
* `import numpy` - we'll use Numpy to operate with vectors
* `r = Redis` - connect to Redis (Stack) server
* `r.hset` - set hash value (key/value pairs)
* `mapping=` - allows setting multiple keys values at once
* `np.random.rand(8)` - generate random vector of 8 dimensions
* `tobytes()` - saving vectors in Redis requires converting vectors to bytes

Another way of storing vectors is to use [JSON documents](https://redis.io/docs/latest/integrate/redisvl/user-guide/json-v-hashes/) in a similar manner.


## Creating vector index

Since vector similarity search requires a lot of computation work (calculating the distance between the query vector and all vectors in or db), Redis provides vector indexes to make things fast.
To create a vector index, we should specify vector dimensions (since we can only search for similar vectors of the same dimension) and distance metric (L2 or Cosine based on your case):

```python
from redis import Redis
from redis.commands.search.field import VectorField
from redis.commands.search.indexDefinition import IndexDefinition, IndexType

r = Redis(host="localhost", port=6379)

schema = (
    VectorField("vector",
      "HNSW", {
        "TYPE": "FLOAT32",
        "DIM": 8,
        "DISTANCE_METRIC": "L2",
      }
    ),
)

r.ft('my_index').create_index(
  fields=schema,
  definition=IndexDefinition(prefix=['obj:'], index_type=IndexType.HASH)
)

print('index created')

```
```output
Index created
```
* `VectorField("vector"` - should contain the name of our hash field with vector,
* `HNSW` - the type of vector index, [HNSW](https://www.pinecone.io/learn/series/faiss/hnsw/) is one of the ways to optimize search across large amounts of vectors,
* `FLOAT32` - the type of vector coordinates data,
* `"DIM": 8` - number of dimensions of vectors,
* `DISTANCE_METRIC` - we've chosen `L2` ([Euclidean distance](https://en.wikipedia.org/wiki/Euclidean_distance)),
* `my_index` - the name of our index,
* `obj:` - prefix of hash keys to index (basically, the first part of the hash key which is the same for all our objects with vectors).

We can make sure the index was created successfully with the following command:

```bash
redis-cli FT.INFO my_index
```
```output
 1) index_name
 2) my_index
 3) index_options
 4) (empty array)
 5) index_definition
 ...
```

Now we can populate and check our vectors search.

## Searching for similar vectors

To search for the nearest (similar) vectors, we should build a search query with the `KNN` expression:

```python
from redis import Redis
from redis.commands.search.query import Query

r = Redis(host="localhost", port=6379)

query = (
  Query("*=>[KNN 5 @vector $vec as dist]")
  .sort_by("dist")
  .return_fields("id", "dist")
  .dialect(2)
)


query_params = { "vec": np.random.rand(8).astype(np.float32).tobytes() }
found = r.ft('my_index').search(query, query_params).docs
print(found)
```
```output
[Document {'id': 'obj:80079', 'payload': None, 'dist': '0.0622428953648'}, Document {'id': 'obj:29389', 'payload': None, 'dist': '0.0633160546422'}, Document {'id': 'obj:78626', 'payload': None, 'dist': '0.078750140965'}, Document {'id': 'obj:69396', 'payload': None, 'dist': '0.0949668586254'}, Document {'id': 'obj:37440', 'payload': None, 'dist': '0.1012461707'}]
```
* `KNN 5 @vector` - will search for 5 nearest neighbors to the `vector` field of the hashes
* `$vec` - name of the query vector parameter,
* `sort_by("dist")` - sort results by distance,
* `return_fields` - list of fields to return with each item in results,
* `dialect(2)` - setting 2nd dialect is required to do vector search,
* `np.random.rand(8)` - generate random 8-dimensional vector to find similar vectors to,
* `my_index` - the name of the index to search in (created earlier).


## Related reading
* [Efficient vector similarity search with Annoy library based on ANN](/data/efficient-vector-search-with-annoy-library-based-on-ann)