# Morpheus-tag

## Variables

```
@name(value) @other(value) @namespace:variable(value)
```

The syntax of these tags are based upon (how variables are tagged inline within) `TaskPaper`.

Within the scope of an HTML document, you could place your tags within ```<morpheus>@name(value)</morpheus>``` to get them processed with injection.

## Injection

```
<morpheus />
```

- ```ref=""``` sets the pointer of where the morpheus-tag should be applied. The *#id* will search for the ```<div id="id"></div>```. Without reference it will be applied on the spot (the position within the HTML document).

- ```content-type=""``` could be a specific content-type or the shorts *HTML*, *XML*, *JSON*, *MD* (or *markdown*) and *TEXT*. It is the type of the content of ```<morpheus>``` and ```</morpheus>```

- ```action=""``` could be *add* (*add-prefix*, *add-postfix*) or *replace*.

- ```template=""``` 

### Example:

To load `/profile.md`, process the *JSON* of the content against the template, and *replace* the `#profile` with the result:

```
<morpheus ref="profile" content-type="JSON" action="replace" template="/profile.md">
	{"name":"Santa Clauss","age":246}
</morpheus>
```