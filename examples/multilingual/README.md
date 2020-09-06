# Multilingual NLG in NLGen

Multilingual extensions are showcased in the `multilingual/` folder.  It
is a budget modifications commentary generator to go with an existing
Web site (http://budgetplateau.com).  This example was done
independently from that site and it is not affiliated with them in any
way.  The original site allows people to experiment with budget
changes.  The example code produces fictional potential reactions to
the budget changes, both in English and French.  The example code
shows how to deal with multiple lexicons and how functions can be
reused across languages or split for specific ones, as necessary.

To run it:

```bash
composer install
cat sample.json | php budget_commentary.php 
```

