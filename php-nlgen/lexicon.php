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

class Lexicon {
  var $id_to_entries = array();
  var $generator;

  public function __construct($generator, $json_text) {
    $this->generator = $generator;

    if(! $json_text){
      return;
    }
    $array = json_decode($json_text,TRUE);

    if(! $array){
      print "$json_text";

      // Define the errors.
      $constants = get_defined_constants(true);
      $json_errors = array();
      foreach ($constants["json"] as $name => $value) {
        if (!strncmp($name, "JSON_ERROR_", 11)) {
          $json_errors[$value] = $name;
        }
      }
      die ($json_errors[json_last_error()]);
    }
     
    foreach ($array as $id => $value) {
      $value['id'] = $id;
      $this->id_to_entries[$id] = $value;
    }
  }
  
  # these functions need to be overriden

  # return one, at random
  public function find($id) {
    if(isset($this->id_to_entries[$id])){
      $frame = $this->id_to_entries[$id];
      if(Lexicon::has_multiple($frame)){
        return Lexicon::get_random($frame);
      }else{
        return $frame;
      }
    }
    return NULL;
  }

  public function has($id){
    return isset($this->id_to_entries[$id]);
  }

  # return all
  public function find_all($id) {
    return isset($this->id_to_entries[$id])?$this->id_to_entries[$id]:NULL;
  }
  
  # the rest doesn't need to be overriden in subclasses

  public function string_for_id($id){
    return $this->string($this->find($id));
  }

  public function string($frame){
    return $frame['string'];
  }

  public static function has_multiple($frame){
    return isset($frame[0]);
  }

  public static function get_random($frame){
    return $frame[rand(0, count($frame)-2)];
  }

  # The rest is WIP

  public static function get_POS($frame){
    return $frame['POS'];
  }

  public static function pluralize($frame){
    if(isset($frame['plural'])){
      return $frame['plural'];
    }
    #TODO add more rules
    return $frame['string']."s";
  }

  public static function past($frame, $person){
    if($person){
      if(isset($frame['past'.$person])) {
        return $frame['past'.$person];
      }
    }
    if(isset($frame['past'])){
      return $frame['past'];
    }
    #TODO add more rules
    return $frame['string']."ed";
  }

  public static function present($frame, $person){
    if($person){
      if(isset($frame['present'.$person])) {
        return $frame['present'.$person];
      }
    }
    if(isset($frame['present'])){
      $present = $frame['present'];
    }else{
      $present = $frame['string'];
    }
    if($person == "3s"){
      #TODO add more rules
      $present = $present . "s";
    }
    return $present;
  }

  public static function continuous($frame){
    if(isset($frame['continuous'])){
      return $frame['continuous'];
    }
    return $frame['string']."ing";
  }

  var $_basic_numbers = array("zero","one","two","three","four","five",
  "six","seven","eight","ten","twelve","thirteen","fourteen","fifeteen",
  "sixteen","eighteen","nineteen");

  public function number_to_string($num){
    if(isset($this->_basic_numbers[$num])){
      return $this->_basic_numbers[$num];
    }
    # TODO recurse
    return $num;
  }

  #TODO pronouns
  #TODO complex forms of the verb ('would have been', etc)
  #TODO lexical entries that involve calling the generator
}
