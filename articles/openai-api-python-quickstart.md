# Quick start OpenAI API example using Python
* url: http://datachild.net/data/openai-api-python-quickstart
* category: data
* published: 2023-05-09
* tags: python, openai
* description: How to start using OpenAI API with Python. Simple example of Python script that generates data based on OpenAI language model.

OpenAI has a simple and friendly API, let's take a quick look how to start using it with Python.

## Installing libs

The only thing we need to install is [openai](https://github.com/openai/openai-python) module:

```
pip install openai
```
* `pip install` - installs Python package,
* `openai` - module to work with OpenAI.


## Getting API key
The second thing we need to do is to get API key for our app from [this page](https://platform.openai.com/account/api-keys):

![Creating key for OpenAI API](/articles/openai-api-python-quickstart/openai-api-key.png)

## Testing API

Now we can use API to get answers from OpenAI using prompts. The only thing we need to define is the [model](https://platform.openai.com/docs/models) we're going to use. We've picked `text-davinci-003` for our example:

```
import openai

def ask(prompt):
  openai.api_key = '<YOUR-API_KEY>'
  return openai.Completion.create(
    model="text-davinci-003",
    prompt=prompt,
  )['choices'][0]['text'].strip()

print(ask('pi number value, 25 decimals'))
```
```output
3.141592653589793238462643383279
```
* `import openai` - load module to work with OpenAI API,
* `def ask(prompt)` - we create custom function to ask questions and return answers from AI,
* `openai.api_key` - set our API key to be able to send API requests,
* `openai.Completion.create` - create completion object to get answers for our prompts,
* `model=` - choose relevant model from [available ones](https://platform.openai.com/docs/models/gpt-3-5),
* `prompt=` - our prompt (we use function argument here),
* `['choices'][0]['text']` - API response will go here,
* `.strip()` - remove all whitespaces before and after anwer we got.

This will be it. It's an extremely simple example, but that what makes OpenAI API so cool - is't easy. Now let's take a look on a more advanced example we can use in practice.

## Practical example - generating test data

One of powerful OpenAI features is that we can ask to use certain format for output. For example, we can ask API to generate various user data and format it in suitable way (e.g., CSV), so our script can understand it.

Let's create a function to generate given number of user-related data records. We'll add the following code to our previous example:
```
from io import StringIO
import csv

def gen_test(n = 5, fields = ['phone', 'first name', 'last name', 'address', 'email']):
  prompt = 'generate a CSV list of ' + str(n) + ' random records including: ' + ', '.join(fields);

  csv_data = ask(prompt)

  f = StringIO(csv_data)
  reader = csv.reader(f, delimiter=',')

  return [row for row in reader]

print(gen_test())
```
```output
[['613-456-2264', 'John', 'Smith', '12 Main Street, Ottawa, ON', ' johnsm@example.com'], ['416-213-7648', 'Bob', 'Taylor', '321 Anywhere Street, Toronto, ON', ' btayl@example.com'], ['905-522-9637', 'Jane', 'Davis', '14 Maple Street, Hamilton, ON', ' jdavis@example.com'], ['250-546-6315', 'Rick', 'Jones', '5 Central Avenue, Vancouver, BC', ' rjones@example.com'], ['514-867-0912', 'Sarah', 'Millar', '7 West Street, Montreal, QC', ' smillar@example.com']]
```
* `import csv` - package to work with CSV,
* `gen_test(` - name of the function that returns a list of generated records,
* `n =` - number of records to generate,
* `fields =` - list of fields to generate for each record,
* `prompt =` - prepare a prompt for the API,
* `csv.reader` - parse `csv_data` (returned text from API) as CSV,
* `row for row in reader` - creates of list of parsed records.

Now we can try generating data with custom arguments:

```
print(gen_test(10, ['email', 'username', 'password']))
```
```output
[['email', 'username', 'password'], ['sales@hotmail.com', 'salesperson', 'xhA6sdk4'], ['media@gmail.com', 'mediaperson', '$q3YUjXa'], ['outreach@outlook.com', 'outreach', 'CgE5i#Nj'], ['trade@yahoo.com', 'trader', 'X5nd*bz0'], ['accounting@gmail.com', 'accountant', 'wF0$Dol7'], ['hr@outlook.com', 'hrmanager', 'gF2yD3*h'], ['marketing@hotmail.com', 'marketer', 'K5hM1#r9'], ['consulting@yahoo.com', 'consultant', 'd!$92Hp1'], ['businessdev@gmail.com', 'bdmanager', '2t#Gopn0'], ['engineering@outlook.com', 'engineer', 'TfT%V7au']]
```

Cool thing here is we can put any imaginable fields as we work with a very smart AI:

```
print(gen_test(3, ['First Name', 'Birth Year', 'Credit Card Number', 'Favorite Movie']))
```
```output
[['Katie', '1986', '4916287068583236', 'Up'], ['Matthew', '1988', '4485709366735621', 'Toy Story'], ['Mia', '1976', '6011938424765310', 'The Godfather']]
```

## Further reading
* [OpenAI Python module repository](https://github.com/openai/openai-python)
* [List of OpenAI models that can be used in API](https://platform.openai.com/docs/models)
* (@Plan: Formatting unstructured data using OpenAI API)
* (@Plan: Searching text documents using vector database and OpenAI API)