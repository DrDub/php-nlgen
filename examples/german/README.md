# German NLGen Example

A small example for the S&S Media PHP Magazin article [Textgenerierungsgrammatik mit NLGen](https://entwickler.de/machine-learning/natural-language-generation-php).

To run the example:

composer install

php -S 127.0.0.1:8080

open http://127.0.0.1:8080 on your browser. (You can also say 'hello' to run the dialog in English.)

Note this example uses NLGen from packagist, unlike the other examples.


Run php run_all.php to obtain all the possible strings that can be generated (see generated.txt).


## About the code

This is one of the most complex examples of NLGen to date, as it
handles case and gender agreements (including weak and strong endings
for adjectives).

The code was written following German grammatic information from
various online sources but the author doesn't speak any German. If you
can speak German and see errors, please file a bug report.


## Example dialog

* Botman: Sag ,,hallo''
* User: hallo

* Botman: Hallo. Was willst du? (Boot oder Pickup)
* User: boot

* Botman: Sie wollen kaufen, finanzieren oder leasen?
* User: kaufen

* Botman: Es ist standardmäßig mit einem Außenbordmotor ausgestattet. Möchten Sie einen Innenbordmotor?
* User: ja

* Botman: Es ist standardmäßig mit einer langen Ladefläche ausgestattet. Möchten Sie eine kurze Ladefläche?
* User: ja

* Botman: Es ist standardmäßig mit einer Viererkabine ausgestattet. Möchten Sie eine Mannschaftskabine?
* User: ja

* Botman: Die Standardfarbe ist weiß. Bevorzugen Sie ein rotes Boot?
* User: ja

* Botman: Sie möchten ein rotes Boot mit kurzer Ladefläche, Mannschaftskabine und einem Innenbordmotor kaufen.
