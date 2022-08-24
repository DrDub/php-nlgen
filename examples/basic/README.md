# Basic examples for PHP-NLGen

Try running them with an extra argument for debug output or with no
arguments to see the generated code with the function intercept.

## Setup

```bash
$ composer install
```

These examples use locally installed package. If you edit the
`composer.json` and remove the `repository` entry, you can use the
nlgen package as available on packagist.org.


## BasicGenerator1

No lexicon, four semantic inputs. 

```bash
$ php BasicGenerator1.php 0 0 0 0

Juan started working on Component ABC
Array
(
    [top] => Array
        (
            [person] => Array
                (
                    [text] => Juan
                )

            [action] => Array
                (
                    [text] => started working on
                )

            [item] => Array
                (
                    [text] => Component ABC
                )

            [text] => Juan started working on Component ABC
        )

)
```

## BasicGenerator2

Uses a lexicon. The lexical entry for "code" has two variants that are
randomly sampled.

```bash
php BasicGenerator1.php juan ongoing code itm_25
Juan Perez is doing programming on Item 25
Array
(
    [top] => Array
        (
            [person] => Array
                (
                    [text] => Juan Perez
                )

            [action] => Array
                (
                    [text] => is doing programming on
                )

            [item] => Array
                (
                    [text] => Item 25
                )

            [text] => Juan Perez is doing programming on Item 25
        )

)
```

## BasicGenerator3

Now with a small ontology to decide whether to use an article (THE
helpdesk operator) vs. proper nouns that require no article. In the
previous example, the common nouns were already present with articles
in the lexicon, which diminishes its utility.

```bash
$ php BasicGenerator3.php  helpdesk finish qa sub_delivery
The helpdesk operator finished doing QA on the delivery subsystem
Array
(
    [top] => Array
        (
            [person] => Array
                (
                    [text] => the helpdesk operator
                )

            [action] => Array
                (
                    [text] => finished doing QA on
                )

            [item] => Array
                (
                    [text] => the delivery subsystem
                )

            [text] => The helpdesk operator finished doing QA on the delivery subsystem
        )

)
```
