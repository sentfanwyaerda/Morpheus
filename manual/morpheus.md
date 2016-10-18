# Morpheus

## Syntax

> See also [flag](./morpheus-flag)

The standard prefix `{` and postfix `}` can be replaced by any other, if needed.

```markdown
Hello {planet}
```

```php
$flags = array('planet'=>'World!');
```

* With a default value: `{gender|Neutral}`
* Conditional output: `{gender?Human:Robot}`

which can be combined with:

* Identified `{*name}` gives `<div id="{name}">{name}</div>` with the *id* normalized: strtolower and spaces replaced by underscore, and Anchored `{**profile}` prefixes `<a name="{profile}"></a>`
* Encapsuled within HTML: `{:div.profile:name}` gives `<div class="profile">{name}</div>`
* Variables from the [Heracles](#) scope: `{%name}`
* Variables from the [Hades](#) scope: `{.name}`
* Variables from an yet undefined scope: `{@name}`, `{!name}`, `{~name}`, `{\name}`

> Also supports [mustache](#Mustache)-syntax (see below)

* Mustache uses `{{name}}` (double or tripple mustaches, or `{{&name}}`), and section `{{#group}} ... {{/group}}` or inverted section `{{^group}} ... {{/group}}`, and partials `{{> template}}`

## Basic Parse
* `Morpheus::basic_parse($str, $flags=array());`
* `Morpheus::basic_parse_str($str, $flags=array(), $prefix, $postfix);`
* `Morpheus::_basic_parse_encapsule($trigger, $str, $id=NULL);`
* `Morpheus::basic_parse_template($src, $flags=array(), $prefix, $postfix);`
* `Morpheus::parse_include_str($str, $flags, $prefix, $postfix);`

## Mustache
* `Morpheus::mustache();`

## Template
* `Morpheus::set_template();`
* `Morpheus::get_template();`
* `Morpheus::__toString();`

## Hooks
> more information about [hooks](./morpheus-hook)

## Parsers
* Markdown (GitHub-flavoured)
* qTranslate(d)
* TaskPaper(ed)

## Helpers

* `Morpheus::escape_preg_chars();`
* `Morpheus::htmlspecialchars();` makes `htmlspecialchars()` recursive.

### Morpheus::get_root()

* `Morpheus::get_root($sub=NULL);`

While `Hades::get_root()` accepts several mime-types to find the root of particular sets.

### Hades::notify()

## Morpheus::