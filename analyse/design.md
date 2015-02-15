### Morpheus::Analyse()

* select [ %{template}.archive ]
* index *.html
* diff *.html > (obj) #generated-rules
* apply manual rules (%{template}.morph) #fix
* output as archive
