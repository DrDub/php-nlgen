# WebNLG Challenge Driver

A not particularly very succesfull attempt at the WebNLG challenge.

The memory.jsons file was obtained by parsing the provided data and it is thus distributed under the same license:  CC Attribution-Noncommercial-Share Alike 4.0 International.

To execute the driver you will also need the delex_dict.json from their baseline system.

`php webnlg_driver.php memory.jsons benchmark.xml delex_dict.json`

will show the verbalizations to stdout

`php webnlg_driver.php memory.jsons benchmark.xml delex_dict.json folder/to/store/reference`

will show the verbalizations to stdout and write reference files in the same format needed by the BLEU evaluation scripts.

To generate a full evaluation:

rm /path/to/reference/*.lex
rm /path/to/reference/relexicalised_predictions.txt
for xml in /path/to/dev/data/*/*.xml; do php webnlg_driver.php memory.jsons benchmark.xml delex_dict.json /path/to/references >> /path/to/reference/relexicalised_predictions.txt; done



