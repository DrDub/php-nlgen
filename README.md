[![Latest Stable Version](http://poser.pugx.org/nlgen/nlgen/v)](https://packagist.org/packages/nlgen/nlgen) [![Total Downloads](http://poser.pugx.org/nlgen/nlgen/downloads)](https://packagist.org/packages/nlgen/nlgen) [![Latest Unstable Version](http://poser.pugx.org/nlgen/nlgen/v/unstable)](https://packagist.org/packages/nlgen/nlgen) [![License](http://poser.pugx.org/nlgen/nlgen/license)](https://packagist.org/packages/nlgen/nlgen)

# NLGen: a library for creating recursive-descent natural language generators

These are pure PHP helper classes to implement recursive-descent
natural language generators [1].  The classes provided are an abstract
generator, an ontology container and a lexicon container.

These classes should help build simple to mid-level generators,
speaking about their complexity.  Emphasis has been made in keeping
more advanced features out of the way for simpler cases (i.e., if
there is no need to use the ontology or the lexicon, they can be
skipped).

The generator keeps track of semantic annotations on the generated
text, so as to enable further generation functions to reason about the
text.  A global context blackboard is also available.

For details on the multilingual example see the Make Web Not War talk. [2]

This is work in progress, see the ROADMAP for some insights in future
development.

* [1] http://duboue.net/blog5.html
* [2] http://duboue.net/papers/makewebnotwar20111128.html


## Available Generation Grammars

NLGen ships with a generation grammar ready to use, that constructs
text descriptions for weekly schedules. The grammar is accessible by
importing `\NLGen\Grammars\Availability\AvailabilityGenerator`.

The method `generateAvailability` receives a list of "busy times" in
the form of

`[ day-of-week, [ start hour, start minute ], [ end hour, end minute ] ]`

a list of ranges indicating when the scheduled day starts and ends (in
the form of `[ day-of-week => [ start hour, start minute ], [ end
hour, end minute ] ]`) and a constant indicating how "coarse" should
be the text (one liner summarizing or very detailed).

See `examples/availability` and `tests/Availability/AvailabilityTest`.

Example:

```php
use NLGen\Grammars\Availability\AvailabilityGenerator;

$gen = new AvailabilityGenerator();
$busyList = [
  [3, [16, 30], [17, 30] ],
  [6, [ 6, 55], [11, 41] ],
  [6, [14, 32], [22, 05] ]
];
$fullRanges = [];
foreach(range(0, 6) as $dow) {
 $fullRanges[$dow] = [ [6, 0], [24, 0] ];
}
echp $gen->generateAvailability($busyList, $this->fullRanges, AvailabilityGenerator::BASE, null);
```

Produces _All week is mostly free all day. Sunday is busy from late 6 AM to late 11 AM, and from half past 14 PM to 22 PM; the rest is free._


## Using it in your own projects

Look at the `examples/` folder, but in a nutshell, subclass the
`NLGen\Generator` class and implemented a function named `top`. This
function can return either a string or an array with a `text` and
`sem` for semantic annotations on the returned text.

If you want to use other functions to assemble the text use
`$this->gen('name_of_the_function',
$data_array_input_to_the_function)` to call it (instead of
`$this->name_of_the_function($data_array_input_to_the_function)`. Or
you can define your functions as *protected* and use function
interposition, described below. The generator abstract class keeps
track of the semantic annotations for you and other goodies.

If the functions that implement the grammar are *protected*, a dynamic
class can be created with the `NewSealed` class method. This dynamic
class will have function interception so you can call
`$this->name_of_function` as usual but actually `$this->gen` will be
called.

Either way you use it, to call the class, if your instantiated
subclass is `$my_gen` then `$my_gen->generate($input_data_as_an_array)`
will return the generated strings. If you want to access the semantic
annotations, use `$my_gen->semantics()` afterward.

For different use cases, see the `examples/` folder.


## Most basic example

This example is grafted from the `examples/basic` folder. To be
invoked command-line with `php basic.php 0 0 0 0` (it produces _Juan
started working on Component ABC_).

```php
class BasicGenerator extends Generator {

  var $agents = array('Juan','Pedro','The helpdesk operator');
  var $events = array('started','is','finished');
  var $actions = array('working on','coding','doing QA on');
  var $themes = array('Component ABC','Item 25','the delivery subsystem');

  protected function top($data){
    return
      $this->person($data[0]). " " .
      $this->action($data[1], $data[2]). " " .
      $this->item($data[3]);
  }

  protected function person($agt){ return $this->agents[$agt]; }
  protected function action($evt, $act){ return $this->events[$evt]." ".$this->actions[$act]; }
  protected function item($thm) { return $this->themes[$thm];  }
}

global $argv,$argc;
$gen = BasicGenerator::NewSealed();
print $gen->generate(array_splice($argv,1) /*,array("debug"=>1)*/)."\n";
```


## Learning more about NLG

I highly recommend Building Natural Language Generation Systems (2000)
by Reiter and Dale.

The SIGGEN site [2] has plenty of good resources. You might also want
to look at the NLG portal at the Association for Computational
Linguistics wiki [3].

Last but not least, you might be interested in the author's blog [4]
and the class notes of his recent NLG course [5].


* [2] http://www.siggen.org/
* [3] http://aclweb.org/aclwiki/index.php?title=Natural_Language_Generation_Portal
* [4] http://duboue.net/blog.html
* [5] http://wiki.duboue.net/index.php/2011_FaMAF_Intro_to_NLG

## Integrations
* https://doc.tiki.org/Natural-Language-Generation


## Sponsorship

Work on NLGen is sponsored by [Textualization Software Ltd.](http://textualization.com).


## License

This library is licensed under the MIT License - See the [LICENSE](LICENSE) file for details.


