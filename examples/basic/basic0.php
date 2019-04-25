<?php

/*
 * Copyright (c) 2011-19 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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
require '../../nlgen/generator.php';

// execute as php basic0.php 0 0 0 0

class BasicGenerator extends Generator {

  var $agents = array('Juan','Pedro','The helpdesk operator');
  var $events = array('started','is','finished');
  var $actions = array('working on','coding','doing QA on');
  var $themes = array('Component ABC','Item 25','the delivery subsystem');

  protected function top($data){
    return
      $this->person($data[0]). " " .
      $this->action($data[1], $data[2]). " " .
      $this->item($data[3]);
  }

  protected function person($agt){
    return $this->agents[$agt];
  }
  
  protected function action($evt, $act){
    return $this->events[$evt]." ".$this->actions[$act];
  }
  
  protected function item($thm){
    return $this->themes[$thm];
  }
}

global $argv,$argc;

$gen = BasicGenerator::NewSealed();

print $gen->generate(array_splice($argv,1) /*,array("debug"=>1)*/)."\n";

print_r($gen->semantics());


