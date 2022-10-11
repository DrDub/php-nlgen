# AvailabilityGenerator

This generation grammar constructs text descriptions for weekly
schedules.  The grammar is ready to use and accessible by importing
`\NLGen\Grammars\Availability\AvailabilityGenerator`.

The method `generateAvailability` receives a list of "busy times" in
the form of

`[ day-of-week, [ start hour, start minute ], [ end hour, end minute ] ]`

a list of ranges indicating when the scheduled day starts and ends (in
the form of `[ day-of-week => [ start hour, start minute ], [ end
hour, end minute ] ]`) and a constant indicating how "coarse" should
be the text (one liner summarizing or very detailed).

See also  `tests/Availability/AvailabilityTest`.

## Examples in this folder

The examples here include:

* `random.php`, produces a random schedule and shows the generated
  text at the four levels of coarseness. Useful for testing but it
  doesn't show a lot of the symmetry exploited by the analysis in the
  code.
  
* `regular.php` a specific schedule with two similar days.

* `ical.php`, receives an ical file such as the ones that can be
  exported from Google Calendar and the monday of the week you want to
  generate. Shows the output over the four coarseness levels.
  
* `api.php`, this end-point is intended to be used with `Main.elm` to
  produce a dynamic visualization of the generator (because [PHP loves Elm](http://wiki.duboue.net/PHP_Elm)). 
  The provided [Elm](https://elm-lang.org/) front-end allows you to
  select busy times and see the output at a selected level of
  coarseness. It compiles to `index.html` with the provided `Makefile`. 
  See the live demo at http://textualization.com/availability/.

## Known issues

The grammar lacks **opportunistic aggregation**. As such, it might
decide that two blocks are not equal enough to aggregate but then
verbalize them with the same words. That produces redundant text such
as "Tuesday afternoon is mostly free. Wednesday afternoon is mostly
free."

