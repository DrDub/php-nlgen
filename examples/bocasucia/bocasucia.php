<?php

/*
 * Copyright (c) 2011 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

require 'bocasucia_generator.php';

$ontology = file_get_contents("ontology.json");
$lexicon = file_get_contents("lexicon.json");

$gen = new BocaSuciaGenerator($ontology, $lexicon);

# inputs

# possible constraints

# length
#   ultra-short  (a short insult)
#   short        (one long or a few short)
#   default      (the full markov model)

# taboo-unlock
#   yes / true / 1  (implies gay, racist and sexual violent insults)
#   0 (default)

# level
#   polite
#   normal (default)
#   nasty  (includes normal)

# speaker, target
#   sm (default, single male)
#   sf (single female)
#   pm (plural male)
#   pf (plural female)

# seed
#   a number to seed the random generator, default is to use a random one, but fixed for the class itself.

# other constraints are insult types, in all caps and a probability associated with them
# they will override the defaults. For full access, the taboo unlock has to be fired.

global $argv,$argc;

$constraints = array();
for ($i = 1; $i < $argc; $i+=2) {
  $key = $argv[$i];
  if(substr($key,0,1) == "-"){
    $key=substr($key,1);
  }
  $constraints[$key] = $argv[$i+1];
}

if(!isset($constraints['seed'])){
  $constraints['seed'] = rand();
}

print $constraints['seed']." ".$gen->generate($constraints)."\n";

if(isset($constraints['show_sem'])){
  print_r($gen->semantics());
}
if(isset($constraints['show_gloss'])){
  show_gloss($gen->semantics(),"");
}

function show_gloss($array,$indent){
  if(isset($array['text'])){
    if(strlen($array['text'])>0){
      print $indent.$array['text']."\n";
    }
  }
  if(isset($array['gloss'])){
    print $indent."  Gloss: ".$array['gloss']."\n";
  }
  foreach($array as $value){
    if(is_array($value)){
      show_gloss($value, "$indent    ");
    }
  }
}