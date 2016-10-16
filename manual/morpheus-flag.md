### Morpheus-flag

```
{flag}
```

**with default value**

```
{flag|default}
```


**with conditonal value**

```
{flag?true:false}
```

#### operators
```
{:tag:flag}
{:tag.class:flag}
```

Is an alias for `<tag>{flag}<tag>`, with the option to also set its class.

```
{*flag}
{**flag}
```

Creates an `<div id="flag">{flag}</div>` on the spot. With the second `*` it adds an `<a name="flag"></a>` as an prefix.

```
{%flag}
```

This flag searches exclusifly in the [Heracles](http://www.github.com/sentfanwyaerda/Heracles) domain.

Other operators will be `{.flag} {#flag} {@flag} ...` but are not assigned to any functionality.
