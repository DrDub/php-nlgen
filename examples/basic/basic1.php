<?php

/*
 * Copyright (c) 2011-13 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

use nlgen\Generator;
require '../../php-nlgen/generator.php';

class BasicGenerator extends Generator {

  protected function top($data){
    return
      ucfirst($this->person($data[0]). " " .
	      $this->action($data[1], $data[2]). " " .
	      $this->item($data[3]));
  }

  protected function person($agent){
    return $this->lex->string_for_id($agent);
  }
  
  protected function action($event, $action){
    return $this->lex->string_for_id($event)." ".$this->lex->string_for_id($action);
  }
  
  protected function item($theme){
    return $this->lex->string_for_id($theme);
  }
}

global $argv,$argc;

$lexicon_json = <<<HERE
{
  "juan" :        {"string" : "Juan Perez"},
  "pedro" :       {"string" : "Pedro Gomez"},
  "helpdesk" :    {"string" : "the helpdesk operator"},
  "start" :       {"string" : "started"},
  "ongoing" :     {"string" : "is"},
  "finish":       {"string" : "finished"},
  "general" :     {"string" : "working on"},
  "code" :        [ {"string" : "coding"}, {"string":"doing programming on" } ],
  "qa" :          {"string" : "doing QA on"},
  "comp_abc" :    {"string" : "Component ABC"},
  "itm_25" :      {"string" : "Item 25"},
  "sub_delivery": {"string" : "the delivery subsystem"}
}
HERE
;

$gen = BasicGenerator::NewSealed('',$lexicon_json);

// example inputs juan ongoing code sub_delivery
//                helpdesk finish qa itm_25

print $gen->generate(array_splice($argv,1))."\n";

print_r($gen->semantics());


