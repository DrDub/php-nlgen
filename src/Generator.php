<?php

/*
 * Copyright (c) 2011-2022 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

namespace NLGen;

use NLGen\Ontology;
use NLGen\Lexicon;

abstract class Generator {

  var $context = array();

  // the semantics array is used as a stack.
  // the currently generated frame is the last item in the stack.
  // as text gets generated, their semantic annotations go into the stack, which gets unravelled after each function call.
  // the old frame gets tossed out and its semantics go into the caller frame, inside an entry with its name.
  // the stack can be preserved with a call to savepoint and rollback.
  var $semantics = array();

  var $onto;
  var $lex;
  var $mlex; // for multilingual lexicons

  var $last_sem;

  function __construct($onto='', $lexicon='') {
    $this->onto = is_object($onto) ? $onto : new Ontology($onto);
    if(is_object($lexicon)){
      $this->lex = $lexicon;
    }elseif(is_array($lexicon)){
      // multilingual
      $this->mlex = array();
      foreach ($lexicon as $lang => $llexicon) {
        $this->mlex[$lang] = is_object($llexicon) ? $llexicon : new Lexicon($this,$llexicon);
      }
    }else{
      $this->lex = new Lexicon($this,$lexicon);
    }
  }

  public static function Compile($langs=NULL,$debug=false,$silent=false){
    $langs = $langs ?? [];
    $multilingual = $langs;
    
    $reflection = new \ReflectionClass(get_called_class());
    $top_reflection = new \ReflectionClass(get_class());
    $BASE = $reflection->getName();
    $base_path = explode('\\', $BASE);
    $target_class_name = array_pop($base_path) . "Sealed";

    $code_to_eval = "class $target_class_name extends $BASE {\n";

    $methods = $top_reflection->getMethods();
    
    $known = array();
    foreach ($methods as $key => $method) {
      $known[$method->getName()] = 1;
    }
    $sealed = 0;
    $methods = $reflection->getMethods();
    $generated_methods = [];
    $multilingual_generic_methods = [];
    foreach ($methods as $key => $method) {
      $name=$method->getName();
      if(isset($known[$name]) || substr($name,0,1) == "_" || $method->isStatic() || $method->isPublic()){
        continue;
      }

      $sealed += 1;
      
      if($debug){
        error_log("Sealing ".$name ."\n");
      }
      
      // rename method
      $renamed = $name . "_orig";
      $generic = '';
      if($multilingual) {
        $found_lang = '';
        foreach($langs as $lang) {
          if(substr($name, -3) == "_" . $lang) {
              $generic = substr($name, 0, strlen($name) - 3);
            $found_lang = $lang;
          }
        }
        if($generic){
          $renamed = $generic . "_orig_" . $found_lang;
        }
      }
      $params="";
      $params_calling = "";
      for($i=0; $i<$method->getNumberOfParameters(); $i++){
        if($i>0){
	      $params .= ",";
	      $params_calling .= ",";
	    }
        $params .= '$p' . $i;
        $params_calling .= '$params[' . $i . ']';
      }
      $code_to_eval .= "  function $renamed(". '$params' ."){\n    return $BASE::$name($params_calling);\n  }\n\n";

      // regular method calls $this->gen with renamed and array of parameters
      $generated_methods[] = $name;
      $code_to_eval .= "  function $name($params){\n";
      $code_to_eval .= "    if(isset(\$this->context['debug'])) {\n      error_log(print_r(func_get_args(),true));\n    }\n";
      $code_to_eval .= '    return $this->gen("' . $renamed . '", func_get_args(), "'.$name.'");'."\n  }\n\n";
      if($multilingual && $generic) {
        $multilingual_generic_methods[$generic] = [ 'params' => $params ];
      }
    }
    foreach($multilingual_generic_methods as $name => $m) {
      if(!isset($generated_methods[$name])) {
        if($debug){
          error_log("Adding ".$name ."\n");
        }
        $code_to_eval .= "  function $name(" . $m['params'] . "){\n";
        $code_to_eval .= "    if(isset(\$this->context['debug'])) {\n      error_log(print_r(func_get_args(),true));\n    }\n";
        $code_to_eval .= '    return $this->gen("' . $name . '_orig", func_get_args(), "'.$name.'");'."\n  }\n\n";
      }         
    }
    $code_to_eval .= "  protected function is_sealed() { return TRUE; }\n}\n";
    if(! $sealed && ! $silent){
      error_log("No methods intercepted, only non-public methods that do not start with '_' are intercepted.");
    }
    if($debug){
      error_log("SEALED!\n");
      error_log($code_to_eval);
    }
    return [ $code_to_eval, $target_class_name ];
  }
    
  // method interception for a more streamlined framework.
  // the use of 'eval' is reserved at construction time and doesn't involve any user-provided data,
  // a better solution will involve the use of the intercept extension.
  // NB: the current code might not play well with optional arguments and default values.
  public static function NewSealed($onto='', $lexicon='',$debug=false,$silent=false){
    $langs = NULL;
    if(is_array($lexicon)){
      $langs = array_keys($lexicon);
      if($debug){
        error_log("Multilingual: ".implode(", ", $langs));
      }
    }
    [ $code_to_eval, $target_class_name ] = self::Compile($langs,$debug,$silent);
    eval($code_to_eval);
    return new $target_class_name($onto,$lexicon);
  }

  public function generate($data, $context=array()) {
    if(isset($context['debug']) && !$this->is_sealed()){
       print "Warning, executing a non-sealed class.\n";
    }

    $this->context = $context;
    $this->semantics = array();
    array_push($this->semantics, array());

    $this->context['initial_data'] = $data;

    // multilingual
    if(isset($context['lang'])) {
      $this->lex = $this->mlex[$context['lang']];
    }

    return $this->gen("top", $data);
  }

  function gen($func, $data, $name=NULL) {
    // multilingual
    if(isset($this->context['lang'])){
      $func_ml = $func . "_" . $this->context['lang'];
      if(method_exists($this, $func_ml)) {
        $func = $func_ml;
      }
    }

    // prepare the stack
    if($name == NULL){
      $name = $func;
    }
    if(isset($this->context['debug'])) {
      error_log("Calling $func/$name, semantics at start:\n");
      error_log(print_r($this->semantics,true));
    }

    $current_sem = &$this->semantics[count($this->semantics)-1];
    $rec_sem = array();
    $i = "";
    while(isset($current_sem[$name.$i])){
      ++$i;
    }
    $name = $name.$i;
    $current_sem[$name] = &$rec_sem;
    array_push($this->semantics, 0);
    $this->semantics[count($this->semantics)-1] = &$rec_sem;

    // call the function
    $text_and_maybe_sem = $this->$func($data);

    // process output and unraven stack
    if(is_array($text_and_maybe_sem)){
      if(isset($text_and_maybe_sem['sem'])){
        $sem = $text_and_maybe_sem['sem'];
        $this->apply_semantics($rec_sem, $sem);
      }
      $text=$text_and_maybe_sem['text'];
      $this->last_sem = $sem;
    }else{
      $text=$text_and_maybe_sem;
      $this->last_sem = array();
    }
    $rec_sem['text'] = $text;

    array_pop($this->semantics);

    if(isset($this->context['debug'])) {
      error_log("Calling $func/$name, semantics at end:\n");
      error_log(print_r($this->semantics,true));
    }

    return 	$text;
  }

  function apply_semantics(&$basic, $addition){
    foreach ($addition as $key => $value) {
      if(is_array($value)){
        // recurse
        if(isset($basic[$key])){
          $this->apply_semantics($basic[$key], $value);
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
  
  function current_semantics() {
    return $this->semantics[count($this->semantics)-1];
  }

  public function savepoint() {
    $len = count($this->semantics);
    $savepoint = array();
    $savepoint["depth"] = $len;
    // shallow clone, we will know the state of the keys at that time
    $top = $this->semantics[$len-1]; // clone
    $savepoint["last"] = $top;
    //NOTE: this savepoint technique doesn't deal with complex semantic manipulations done
    // directly on $this->semantics.
    // The easy option of deep cloning semantics won't work because the linking between frames
    // is  done with pointers.

    if(isset($this->context['debug'])) {
      error_log("semantics at save point: "); error_log(print_r($this->semantics,true));
      error_log("savepoint: "); error_log(print_r($savepoint,true));
    }
    return $savepoint;
  }

  public function rollback($savepoint) {
    if(isset($this->context['debug'])) {
      error_log("semantics at rollback: "); error_log(print_r($this->semantics,true));
      error_log("savepoint: "); error_log(print_r($savepoint,true));
    }
    // revert the length of the semantics stack
    $len=$savepoint["depth"];
    array_splice($this->semantics, $len);
    // remove new keys
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
    if(isset($this->context['debug'])) {
      error_log("semantics after rollback: "); error_log(print_r($this->semantics,true));
    }
  }

  // add with space, if any of the strings are non-empty, it concatenates them with a space
  public function addWS(&$accum, string... $str) {
    foreach($str as $s) {
      if($s){
        $accum .= " $s";
      }
    }
    return $accum;
  }

  // to be overriden in sealed classes
  protected function is_sealed() {
    return FALSE;
  }

  // entry level for user grammar productions
  abstract protected function top($data);
}
