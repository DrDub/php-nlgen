# NLGen: Example Generators

These examples use the library through composer. To use them, do:

```bash
cd basic # or bocasucia, etc
composer install
php BasicGenerator1.php 0 0 0 0 # or bocasucia.php or ...
```

If you edit the `composer.json` and remove the `repository` entry, you
can use the nlgen package as available on packagist.org.

The examples here are intended to show a continuum of increasing complexity.

The first example (`basic/BasicGenerator1.php`) is a simple event
reporting text nugget, to fit into a larger Web interface.  It is
intended to verbalize quadruplets of the form `($agent, $event,
$action, $theme)`, meaning the agent $agent has performed an `$event`
(started, finished, make progress) with respect to the action `$action`
on the topic of `$theme`.

For example:

  Juan started working on Item 25.

This is a really, really simple example and you might be hard pressed
to justify using a NLG framework for it. Still, readability of the
source code might justify it, particularly as you try to add more
things to it.

The next example (`basic/BasicGenerator2.php`) is same as
`BasicGenerator1`, but this time with a lexicon for providing texts
for `$agent` and `$theme`. In a production system you'll expect this
information to be fetched from a DB and a `Lexicon` subclass that can
be tied to a DB is a planned feature (see ROADMAP).

The last basic example (`basic/BasicGenerator3.php`) uses an ontology
to distinguish between actors that are people and are referred by name
(e.g., _Juan_) versus events referring to automatic tools (e.g.,
_"nightly build"_).  This information is used to add an article to
form a noun phrase (e.g., _"Juan"_ vs. _"The nightly build"_).  While
this might be the simplest possible use of ontological information for
NLG, it might just as well be too simple.


A whole section of more complex examples using semantic annotations is
planned to go into a folder medium/ . There we want examples that
should how semantic annotations can simplify dealing with number
agreement and pronominalization. The planned example is a SMS-powered
package-delivering notification system that should generate:

* _Your two packages were delivered yesterday. The recipient signed
  them in._

* _Your package was delivered two days ago. The recipient signed it in._

Again, simple example, but here you can profit from semantic
annotations to the point of simplifying the code and make it much more
reusable.


The next example is a Tarot spread interpreter in the `tarot/`
folder. This is as complex as it can get and currently it can only
produce an opening statement, referring to the overall "goodness" of
the spread and a then generates a statement about the current
situation (cards 1 and 2).

The ontology only has (vague) information about 11 cards and still can
produce this type of texts:

_"Ouch. Currently, you got the empress and the lovers.  The empress
implies a little bit of a puzzle.  This follows the lovers which
implies good things to come.  The lovers refers to relationships,
sexuality but also personal beliefs and values."_

_"And what is this supposed to mean? Currently, you got the five of
pentacles and the fool.  The five of pentacles implies a little bit of
a puzzle. The pentacles are nurturing, concrete.  This follows the
fool which implies good things to come."_

_"Things seem to be doing well, I would say. Currently, you got the
lovers and the tower.  The lovers implies good things to come.  The
lovers refers to relationships, sexuality but also personal beliefs
and values.  This strongly opposes the tower which implies something
bad."_

_"Good, good. Currently, you got the five of pentacles and the ace of
pentacles.  The five of pentacles implies a little bit of a puzzle.
The pentacles are nurturing, concrete.  This follows the ace of
pentacles which implies good things to come.  Same as the other card,
this is also a pentacles."_


An insult generator example is in the `bocasucia/` folder.  As this
generator is heavily lexicon-driven, most of the structure lies in the
lexicon rules.  It is an example of the 'function' and 'mixed' lexical
capabilities in lexicon.php.  The subject matter (insults in river
plate Spanish, with English glosses) might bother certain people.  If
you feel this might be your case, please refrain from looking into
this example.  This example is also under construction, but much more
functional than the tarot reader (for all the functionality rendered
by a program that just generates insults, that is).


Multilingual extensions are showcased in the `multilingual/` folder.
It is a budget modifications commentary generator to go along a 
now defunct Web site ([http://budgetplateau.com](http://web.archive.org/web/20111129102920/http://budgetplateau.com/), unrelated to the author).

The `ste` folder contains Simple Technical English Linearizer built
duing the first NLG hackathon at INLG'16.

The `webnlg` contains an attempt at the WebNLG 2017 challenge
(unsubmitted).

The `chatbot` folder contains a small chatbot integration example
presented at the Vancouver PHP Meetup in 2019.

The `german` folder contains an expansion of the chatbot to the German
language. It handles case and gender agreements (including weak and
strong endings for adjectives).

The `availability` folder contains example codes (including an
[Elm](https://elm-lang.org/) front-end) for the `AvailibityGenerator`
shipped with NLGen.
