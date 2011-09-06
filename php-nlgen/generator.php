<?php namespace nlgen;

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

require 'ontology.php';
require 'lexicon.php';

abstract class Generator {

  var $context = array();
  
  # the semantics array is used as a stack.
  # the currently generated frame is the last item in the stack.
  # as text gets generated, their semantic annotations go into the stack, which gets unravelled after each function call.
  # the old frame gets tossed out and its semantics go into the caller frame, inside an entry with its name.
  # the stack can be preserved with a call to savepoint and rollback. 
  var $semantics = array();

  var $onto;
  var $lex;


  function __construct($onto='', $lexicon='') {
    $this->onto = is_object($onto) ? $onto : new Ontology($onto);
    $this->lex = is_object($lexicon) ? $lexicon : new Lexicon($this,$lexicon);
  }

  public function generate($data) {
    $this->context = array();
    $this->semantics = array();
    array_push($this->semantics, array());

    $this->context['initial_data'] = $data;

    return $this->gen("top", $data);
  }

  function gen($func, $data, $name=NULL) {
    //print "Calling $func, semantics at start:\n";
    //print_r($this->semantics);
    
    # prepare the stack
    if($name == NULL){
      $name = $func;
    }
    $current_sem = &$this->semantics[count($this->semantics)-1];
    $rec_sem = array();
    $i = "";
    while(isset($current_sem[$name.$i])){
      ++$i;
    }
    $name = $name.$i;
    $current_sem[$name] = &$rec_sem;
    array_push($this->semantics,&$rec_sem);

    # call the function
    $text_and_maybe_sem = $this->$func($data);

    # process output and unraven stack
    if(is_array($text_and_maybe_sem)){
      if(isset($text_and_maybe_sem['sem'])){
        $sem = $text_and_maybe_sem['sem'];
        $this->apply_semantics(&$rec_sem, $sem);
      }
      $text=$text_and_maybe_sem['text'];
    }else{
      $text=$text_and_maybe_sem;
    }
    $rec_sem['text'] = $text;

    array_pop($this->semantics);

    //print "Calling $func, semantic at end:\n";
    //print_r($this->semantics);
    
    return 	$text;
  }

  function apply_semantics($basic, $addition){
    foreach ($addition as $key => $value) {
      if(is_array($value)){
        # recurse
        if(isset($basic[$key])){
          $this->apply_semantics(&$basic[$key], $value);
        }else{
          $basic[$key]=$value;
        }
      }else{
        $basic[$key]=$value;
      }
    }
  }
  
  public function semantics() {
    return $this->semantics[0];
  }
  
  public function savepoint() {
    $len = count($this->semantics);
    $savepoint = array();
    $savepoint["depth"] = $len;
    # shallow clone, we will know the state of the keys at that time
    $top = $this->semantics[$len-1]; # clone
    $savepoint["last"] = $top;
    #NOTE: this savepoint technique doesn't deal with complex semantic manipulations done
    #  directly on $this->semantics.
    # The easy option of deep cloning semantics won't work because the linking between frames
    # are done with pointers.
    
    #print "semantics at save point: "; print_r($this->semantics);
    #print "savepoint: "; print_r($savepoint);
    return $savepoint;
  }
  
  public function rollback($savepoint) {
    #print "semantics at rollback: "; print_r($this->semantics);
    #print "savepoint: "; print_r($savepoint);
    # revert the length of the semantics stack
    $len=$savepoint["depth"];
    array_splice($this->semantics, $len);
    # remove new keys
    $old = $savepoint["last"];
    $top = &$this->semantics[$len-1];
    $to_remove=array();
    foreach($top as $key=>$value){
      if(!isset($old[$key])){
        $to_remove[] = $key;
      }
    }
    foreach($to_remove as $key){
      unset($top[$key]);
    }
    # print "semantics after rollback: "; print_r($this->semantics);
  }
  
  abstract function top($data);
}