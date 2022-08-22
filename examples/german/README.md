# Botman NLGen Example

A small example for the S&S Media article.

To run the example:

composer install

php -S 127.0.0.1:8080

open http://127.0.0.1:8080 on your browser.

Note this example uses NLGen from packagist, unlike the other examples.


Run php run_all.php to obtain all the possible strings that can be generated (see generated.txt).


Example dialog:

- Botman: Sag ,,hallo''
- User: hallo

- Botman: Hallo. Bitten Sie mich, Ihnen einen Pick-up zu verkaufen.
- User: Verkaufe mir einen Pick-up.

- Botman: Sie wollen kaufen, finanzieren oder leasen?
- User: kaufen

- Botman: Es ist standardmäßig mit einem 4x2-Antrieb ausgestattet. Möchten Sie einen 4x4-Antrieb?
- User: nein

- Botman: Es wird standardmäßig mit einer langen Ladefläche (6'4") geliefert. Möchten Sie Ladefläche eine kurze (5'7")?
- User: ja

- Botman: Möchten Sie eine Viererkabine oder Mannschaftskabine? Der Standard ist die Viererkabine.
- User: Viererkabine

- Botman: Sie möchten einen Pick-up mit kurze Ladefläche kaufen.
