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

class Ontology {

  var $id_to_frames = array();

  public function __construct($json_text) {
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
      $this->id_to_frames[$id] = $value;
    }
  }
  
  # to implement a subclass, override these methods

  public function find($id){
    return $this->has($id)?$this->id_to_frames[$id]:NULL;
  }

  public function has($id){
    return isset($this->id_to_frames[$id]);
  }

  public function find_all_by_class($class){
    $result=array();
    foreach($this->id_to_frames as $id => $frame){
      if($frame['class'] == $class){
        $result[] = $id;
      }
    }
    return $result;
  }
  
  # this method doesn't need overriding (but might profit from optimizations)
  public function find_by_path($array) {
    $current = $this->find($array[0]);
    $path = array_values($array);
    unset($path[0]);
    foreach ($path as $i => $value) {
      if(! is_array($current)) {
        if(! $this->has($current)){
          return NULL;
        }
        $current = $this->find($current);
      }
      if(! isset($current[$value])) {
        return NULL;
      }
      $current = $current[$value];
    }
    return $current;
  }

  }