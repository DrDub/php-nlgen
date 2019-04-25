<?php

/*
 * Copyright (c) 2011-2019 Pablo Ariel Duboue <pablo.duboue@gmail.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining 
 * a copy of this software and associated documentation files (the "Software"), 
 * to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the 
 * Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included 
 * in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS 
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
 * DEALINGS IN THE SOFTWARE.
 * 
 */

require 'budget_commentary_generator.php';

$ontology = file_get_contents("ontology.json");
$lexicon_en = file_get_contents("lexicon_en.json");
$lexicon_fr = file_get_contents("lexicon_fr.json");

$gen = new BudgetCommentaryGenerator($ontology, 
  array('en' => $lexicon_en, 'fr' => $lexicon_fr));

$json_text = file_get_contents("php://stdin");
$data = json_decode($json_text,TRUE);

print_r($data);
$copy = $data;

print $gen->generate($data, array('lang'=>'fr'))."\n\n";
print $gen->generate($copy, array('lang'=>'en'))."\n\n";
