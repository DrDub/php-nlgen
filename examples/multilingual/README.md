# Multilingual NLG in NLGen

This demo showcases NLGen multilingual extensions.  It is a budget
modifications commentary generator that originally went with the now
defunct [http://budgetplateau.com](http://web.archive.org/web/20111129102920/http://budgetplateau.com/) website (see
a [post-mortem](https://opennorth.ca/2012/11/citizen-budget-results-from-plateau-mont-royal/)).
This example was done independently from that site and it is not
affiliated with them in any way.  The original site allowed people to
experiment with budget changes.  The example code produces fictional
potential reactions to the budget changes, both in English and French.
The example code shows how to deal with multiple lexicons and how
functions can be reused across languages or split for specific ones,
as necessary.

To run it:

```bash
composer install
cat sample.json | php budget_commentary.php 
```

