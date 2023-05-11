# Formatting unstructured data using OpenAI API and Python
* url: http://datachild.net/machinelearning/how-to-format-data-using-openai-api
* category: machinelearning
* published: 2023-05-11
* tags: python, openai
* description: How to use OpenAI to format unstructured text data, e.g. CSV. Setting additional formatting requirements to format specific values in the resulting CSV.

OpenAI provides a [simple API](http://datachild.net/machinelearning/openai-api-python-quickstart) for its awesome products, including ChatGPT. One of the cases OpenAI can be helpful with is formatting data that is either poorly formatted or has a format that can't be easily parsed.

Let's suppose we have the following piece of text data:
```
John Doe's phone is: +123435667656, he's a CEO of the company.
His vice president is Samantha Doe (sam@company.com, +323438562342).
Seniour engineer is Daniel Brown (dan@company.com).
Chief of Marketing is Mira Clasko, can be contacted by m@company.com.
```

We can ask OpenAI to create CSV from this data with certain columns.
We'll use the simple prompt so AI knows how to structure the data:
```
import openai

def as_csv(data, columns):
  openai.api_key = '<YOUR-API_KEY>'
  prompt = 'Format given text in CSV with header and following columns: ' + ', '.join(columns) + '. Return only CSV.' + "\\n\\n"

  return openai.Completion.create(
    model="text-davinci-003",
    prompt=prompt + data,
    max_tokens=3900,
    temperature=0
  )['choices'][0]['text'].strip()

data = open('unformatted.txt').read()
csv = as_csv(data, ['person name', 'position', 'phone', 'email'])

print(csv)
```
```output
Name, Phone, Position, Email
John Doe, +123435667656, CEO, 
Samantha Doe, +323438562342, Vice President, sam@company.com
Daniel Brown, , Seniour Engineer, dan@company.com
Mira Clasko, , Chief of Marketing, m@company.com
```
* `import openai` - load module to work with OpenAI API,
* `as_csv(data, columns)` - this function will return CSV with a given list of `columns` based on text `data`,
* `Format given text in CSV` - we let AI know we want it to give us CSV,
* `', '.join(columns)` - this part of the prompt lists columns we want to see in the final CSV,
* `text-davinci-003` - AI model we want to use (most powerful one),
* `prompt=prompt + data` - send task for AI together with data,
* `open('unformatted.txt').read()` - reads text data from text file.

Awesome, we now have CSV we can work with instead of unstructured text. But let's do some improvements to make sure we get what we expect.



## CSV quoting, delimiters, and empty values rules

By tweaking our prompt, we can ensure the output CSV is well formatted and follows our requirements:

```
...
  prompt = """Format given text in CSV 
  (doublequoted, delimited by comma, use "N/A" for empty values)
  with with header and following columns: """ + ','.join(columns) + '. Return only CSV.' + "\\n\\n"
...
```
```output
"Name","Position","Phone","Email"
"John Doe","CEO","+123435667656","N/A"
"Samantha Doe","Vice President","+323438562342","sam@company.com"
"Daniel Brown","Seniour Engineer","N/A","dan@company.com"
"Mira Clasko","Chief of Marketing","N/A","m@company.com"
```
* `doublequoted` - we want double quotes to be used for CSV,
* `use "N/A" for empty values` - replace empty values with `N/A`.



## Formatting specific values

We can also add instructions to convert specific columns to a certain standard:

```
...
  prompt = """Format given text in CSV
  (doublequoted, delimited by comma, use "N/A" for empty values, prettify phone numbers with parenthesis, use last name then first name for person name column)
  with with header and following columns:""" + ','.join(columns) + '. Return only CSV.' + "\\n\\n"
...
```
```output
"Name","Position","Phone","Email"
"Doe, John","CEO","(+123) 435-667-656","N/A"
"Doe, Samantha","Vice President","(+323) 438-562-342","sam@company.com"
"Brown, Daniel","Seniour Engineer","N/A","dan@company.com"
"Clasko, Mira","Chief of Marketing","N/A","m@company.com"
```
* `prettify phone numbers` - we can ask AI to format certain values in a "pretty" way,
* `use last name then first name` - we can also instruct AI to keep certain order for names.

Cute! Keep in mind that designing prompts is very important when working with AI. Be as strict and detailed as possible about what kind of formatting you expect to get.



## Further reading

* [Quick start OpenAI API example using Python](http://datachild.net/machinelearning/openai-api-python-quickstart)