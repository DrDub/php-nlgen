<?php

require __DIR__ . '/vendor/autoload.php';

$ontology   = file_get_contents("ontology.json");
$lexicon_en = file_get_contents("lexicon_en.json");
$lexicon_fr = file_get_contents("lexicon_fr.json");

$gen = new BudgetCommentaryGenerator($ontology, 
  array('en' => $lexicon_en, 'fr' => $lexicon_fr));

$json_text = file_get_contents("php://input");
$data = json_decode($json_text,TRUE);

$copy = $data;

header('Content-type: text/plain; charset=UTF-8');
$lang = 'en';
if($_GET['lang'] == 'fr'){
  $lang = 'fr';
}

echo $gen->generate($data, array('lang'=>$lang))."\n";
