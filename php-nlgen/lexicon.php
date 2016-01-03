<?php namespace nlgen;

/*
 * Copyright (c) 2011-2016 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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
      if(Lexicon::has_multiple($value)){
        # copy non-array entries from $frame
        $new_value = array();
        $to_copy = array();
        $to_copy['id'] = $id;
        $has_key = false;
        foreach($value as $v){
          if(is_array($v)){
            $new_value[]=$v;
          }else{
            if($has_key){
              $to_copy[$key]=$v;
              $has_key=false;
            }else{
              $has_key=true;
              $key = $v;
            }
          }
        }
        foreach($new_value as &$nv){
          foreach($to_copy as $key => $v){
            if(!isset($nv[$key])){
              $nv[$key] = $v;
            }
          }
        }
        $value = $new_value;
      }else{
        $value['id'] = $id;
      }
      $this->id_to_entries[$id] = $value;
    }
  }

  # these functions might need to be overriden

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

  # complex query, all the keys in the query have to be defined in the entries.
  # if a value is itself an array, it is understood as an OR of the values
  public function query($query) {
    # compile query
    $compiled_query = array();
    foreach($query as $key => $value) {
      $compiled=array();
      if(is_array($value)){
        foreach($value as $v){
          $compiled[$v] = true;
        }
      }else{
        $compiled[$value] = true;
      }
      $compiled_query[$key] = $compiled;
    }
    # go through the whole lexicon
    $result = array();
    foreach($this->id_to_entries as $id => $allentries) {
      $entries = is_array($allentries) ? $allentries : array($allentries);
      # print "entries:"; print_r($entries);
      foreach($entries as $entry) {
        if(!is_array($entry)){
          continue;
        }
        $good = true;
        foreach($compiled_query as $key => $goodvalues) {
          $set = isset($entry[$key]);
          if((!$set || !isset($goodvalues[$entry[$key]]))&&
          ($set || !isset($goodvalues["undefined"]))){
            # print("failed:\n");   print_r($entry);
            # print("key: ");       print_r($key."\n");
            # print("goodvalues:"); print_r($goodvalues);
            $good=false;
            break;
          }
        }
        if($good){
          $result[] = $entry;
        }
      }
    }
    return $result;
  }

  # the rest doesn't need to be overriden in subclasses

  public function string_for_id($id,$data=array()){
    return $this->string($this->find($id),$data);
  }

  public function string($frame, $data=array()){
    if(Lexicon::is_function($frame)){
      $frame = $this->execute_function($frame, $data);
    }elseif(Lexicon::is_mixed($frame)){
      $frame = $this->resolve_mixed($frame, $data);
    }
    return $frame['string'];
  }

  public function resolve($frame, $data=array()){
    if(Lexicon::is_function($frame)){
      $frame = $this->execute_function($frame, $data);
    }elseif(Lexicon::is_mixed($frame)){
      $frame = $this->resolve_mixed($frame, $data);
    }
    return $frame;
  }


  public static function has_multiple($frame){
    return isset($frame[0]);
  }

  public static function get_random($frame){
    # use likelihoods
    $result = Lexicon::sample($frame);
    return $result;
  }

  public static function is_function($frame){
    return isset($frame['function']);
  }

  public static function is_mixed($frame){
    return isset($frame['mixed']);
  }

  public static function get_mixed($frame){
    return $frame['mixed'];
  }

  public function execute_function($frame, $data){
    $savepoint = $this->generator->savepoint();
    $result_string = $this->generator->gen($frame['function'], $data,"tmp");
    $len = count($this->generator->semantics);
    $result_sem = $this->generator->semantics[$len-1]["tmp"];
    $this->generator->rollback($savepoint);
    $result_sem['string']=$result_string;
    return $result_sem;
  }

  public function resolve_mixed($frame, $data){
    $mixed = Lexicon::get_mixed($frame);

    $result = $frame;
    $string = "";
    unset($result['mixed']);
    $count = 0;
    foreach($mixed as $entry){
      if(is_array($entry)){
        $exec_data = $data;
        if(count($entry)>1){
          # extra parameters
          $exec_data = $data; # clone
          foreach($entry as $key=>$value){
            if($key != 'function' && $key != 'mixed'){
              $exec_data[$key] = $value;
            }
          }
        }
        if(Lexicon::is_function($entry)){
          $gen = $this->execute_function($entry, $exec_data);
        }elseif(Lexicon::is_mixed($entry)){
          $gen = $this->resolve_mixed($entry, $exec_data);
        }else{
          $gen = $entry;
        }
        $string = $string . $gen['string'];
        $result[] = $gen;
      }else{
        $string = $string . strval($entry);
      }
    }
    $result['string'] = $string;
    return $result;
  }

  # take an array with 'likelihood' entries and uniformly sample one
  # will add a likelihood entry of 1.0 for entries that don't have it
  # ignores non-array entries
  public static function sample($frame){
    # print "frame: "; print_r($frame);
    $total = 0;
    foreach($frame as &$entry){
      if(!is_array($entry)){
        continue;
      }
      if(!isset($entry["likelihood"])){
        $entry["likelihood"] = 1.0;
      }
      $total += floatval($entry["likelihood"]);
    }
    # print "total: $total\n";

    $result = NULL;
    $rand = rand(0,1000 * $total);
    # print "rand: $rand\n";
    $accum = 0;
    foreach($frame as &$entry){
      if(!is_array($entry)){
        continue;
      }
      $accum += floatval($entry["likelihood"]) * 1000.0;
      $result = $entry;
      if($accum > $rand){
        break;
      }
    }
    # print "result: "; print_r($result);
    return $result;
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
