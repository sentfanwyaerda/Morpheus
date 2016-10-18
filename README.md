Morpheus
========

*Webapplication Request Handler and Page Generator*

"Morpheus (shaper [of dreams]) has the ability to take any ~~human~~ form and appear in dreams." ([Wikipedia](http://en.wikipedia.org/wiki/Morpheus_%28mythology%29)). In the same manner **Morpheus** transforms the requests made to your webapplication into an instruction-set and follows-through until the desired page is ready to be delivered. Enabling to *create dreams for kings*.

```php
$morph_file = Morpheus::request( /*dream*/ $URI );
$result = Morpheus::generate($morph_file, TRUE);
/*on TRUE the $morph_file will be forced to generate a current result */
/*on default and FALSE will do an*/ $result = Morpheus::lookup($morph_file);
(bool) Morpheus::cache($morph_file, $result);
```

> More documentation will be available in [the manual](/manual/morpheus)
