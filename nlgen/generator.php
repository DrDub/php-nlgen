<?php namespace nlgen;

/*
 * Copyright (c) 2011-15 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

  // method interception for a more streamlined framework.
  // the use of 'eval' is reserved at construction time and doesn't involve any user-provided data,
  // a better solution will involve the use of the intercept extension.
  // NB: the current code might not play well with optional arguments and default values.
  public static function NewSealed($onto='', $lexicon='',$debug=false,$silent=false){
    $reflection = new \ReflectionClass(get_called_class());
    $top_reflection = new \ReflectionClass(get_class());
    $BASE = $reflection->getName();
    $target_class_name = $BASE . "Sealed";

    $code_to_eval = "class $target_class_name extends $BASE {\n";

    $methods = $top_reflection->getMethods();
    
    $known = array();
    foreach ($methods as $key => $method) {
      $known[$method->getName()] = 1;
    }
    $sealed = 0;
    $methods = $reflection->getMethods();
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
      // if the last parameter name is 'sem', it is kept as the semantics for $this->gen
      $method_params = $method->getParameters();
      $num_method_params = count($method_params);
      $has_sem = FALSE;
      if($num_method_params>0 && $method_params[$num_method_params-1]->getName() == 'sem'){
        $has_sem = TRUE;
      }
      $code_to_eval .= "  function $name($params){\n    ";
      $code_to_eval .= "    if(isset(\$this->context['debug'])) {\n      error_log(print_r(func_get_args(),true));\n    }\n";
      $code_to_eval .= '    return $this->gen("' . $renamed . '", func_get_args()';
      if($has_sem){
        $code_to_eval .= ',$p' . strval($num_method_params-1);
      }
      $code_to_eval .= ");\n  }\n\n";
    }
    $code_to_eval .= "  protected function is_sealed() { return TRUE; }\n}\n";
    if(! $sealed && ! $silent){
      error_log("No methods intercepted, only non-public methods that do not start with '_' are intercepted.");
    }
    if($debug){
      error_log("SEALED!\n");
      error_log($code_to_eval);
    }
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
    if(isset($this->context['debug'])) {
      error_log("Calling $func, semantics at start:\n");
      error_log(print_r($this->semantics,true));
    }

    // multilingual
    if(isset($this->context['lang'])){
      $func_ml = $func . "_" . $this->context['lang'];
      if(method_exists($this,$func_ml)) {
        $func = $func_ml;
      }
    }

    // prepare the stack
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
      error_log("Calling $func, semantic at end:\n");
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
    // are done with pointers.

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

  // to be overriden in sealed classes
  protected function is_sealed() {
    return FALSE;
  }

  // entry level for user grammar productions
  abstract protected function top($data);
}