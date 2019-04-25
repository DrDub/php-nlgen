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

use nlgen\Generator;
require '../../nlgen/generator.php';

class Predicate {
  var $predicate;
  var $args;
  function __construct($pred, $args=array()) {
    $this->predicate = $pred;
    $this->args = $args;
  }
  function __toString() {
    return $this->predicate . '(' . join(",", $this->args) . ')';
  }
}

class BudgetCommentaryGenerator extends Generator {

  # data are different dimensions changed plus new values
  function top($data){
    # apply the rules and obtain predicates
    $preds = $this->apply_rules($data);

    # sort by predicate
    $preds = $this->sort_predicates($preds);


    # aggregate, remove duplicates, subsumed
    $preds = $this->sentence_planning($preds);

    #print_r($preds);

    # verbalize
    $result = "";
    foreach($preds as $key => $pred) {
      $result = $result . ucfirst(trim($this->gen($pred->predicate, $pred->args))) . ". ";
    }

    return $result;
  }

  function apply_rules($data){
    $result = array();

    include 'rules.php';

    return $result;
  }

  function sort_predicates($preds){
    $pred_array = array();
    $args_array = array();
    foreach ($preds as $pred) {
      $pred_array[] = $pred->predicate;
      $args_array[] = join(",", $pred->args);
    }
    array_multisort($pred_array, $args_array, $preds);
    return $preds;
  }

  function sentence_planning($preds){
    $new_preds = array();

    # remove subsumed
    $prev = null;
    foreach ($preds as $pred) {
      if(!is_null($prev)){
        if($this->subsumed($pred,$prev)){
          $new_preds[count($new_preds) - 1] = $pred;
          $prev = $pred;
        }else if($this->subsumed($prev,$pred)){
          # ignored
        }else{
          $new_preds[] = $pred;
          $prev = $pred;
        }
      }else{
        $new_preds[] = $pred;
        $prev = $pred;
      }
    }

    # aggregate
    $preds = $new_preds;
    $new_preds = array();
    $prev = null;
    foreach ($preds as $pred) {
      if(!is_null($prev)){
        if($pred->predicate == $prev->predicate && $pred->predicate == 'on_strike'){
          # other predicates TODO
          if(is_array($prev->args[0])){
            $arr = $prev->args[0];
            $arr[] = $pred->args[0];
            $prev = new Predicate($prev->predicate, array($arr));
          }else{
            $prev = new Predicate($prev->predicate,
            array(array($prev->args[0], $pred->args[0])));
          }
          $new_preds[count($new_preds) - 1] = $prev;
        }else{
          $new_preds[] = $pred;
          $prev = $pred;
        }
      }else{
        $new_preds[] = $pred;
        $prev = $pred;
      }
    }

    return $new_preds;
  }

  function subsumed($gen,$spec){
    if($gen->predicate != $spec->predicate){
      return false;
    }
    if($gen->predicate != 'benchmarked') {
      return false;
    }
    if($gen->args[0] != $spec->args[0]) {
      return false;
    }
    if($gen->args[1] != $spec->args[1]) {
      return false;
    }
    $frame = $this->onto->find($gen->args[2]);
    while(isset($frame['includes'])){
      if($frame['includes'] == $spec->args[2]){
        return true;
      }
      $frame=$this->onto->find($frame['includes']);
    }
    return false;
  }

  function on_strike($data){
    $actor = $data[0];
    $actor_str = $this->gen("np", array('head'=>$actor),'actor');
    $sem = $this->current_semantics();

    return $actor_str . ' ' .
    $this->gen('on_strike_vp', array('subject' => $sem['actor']));
  }

  function benchmarked_en($data){
    $position = $data[0]; $metric = $data[1]; $region = $data[2];
    return $this->gen('metric',$metric) . " will be the " .
    $this->lex->string_for_id($position) . " within the " .
    $this->lex->string_for_id($region);
  }

  function benchmarked_fr($data){
    $position = $data[0];
    $metric_str = $this->gen('metric',$data[1], 'metric'); $region = $data[2];
    $frame = $this->lex->find('will_be');
    $sem = $this->current_semantics();
    return $metric_str . ' ' . $frame[$sem['metric']['num']] . " les plus " .
    $this->lex->string_for_id($position) . " dans la " .
    $this->lex->string_for_id($region);
  }

  function pct_change_en($data){
    $metric = $data[0]; $delta = $data[1];
    $str = $this->gen('metric',$metric) . " will ";
    if($delta > 0) {
      $str = $str . "increase by " . $delta . " percent";
    }else{
      $str = $str . "decrease by " . abs($delta) . " percent";
    }
    return $str;
  }

  function pct_change_fr($data){
    $metric_str = $this->gen('metric', $data[0], 'metric'); $delta = $data[1];
    if($delta > 0) {
      $frame = $this->lex->find('will_increase');
    } else {
      $frame = $this->lex->find('will_decrease');
      $delta = abs($delta);
    }
    $sem = $this->current_semantics();
    return $metric_str . ' ' . $frame[$sem['metric']['num']] . ' de ' . $delta . ' pour cent';
  }

  function np($data){
    $head = $data['head'];
    #print_r($head);
    if(gettype($head) == "object"){
      $str = $this->gen($head->predicate,$head->args,'subpred');
      $sem = $this->current_semantics();
      return array('text'=>$str, 'sem'=>$sem['subpred']);
    }else if(gettype($head) == "array"){
      $gen = 'fem';
      $str = array();
      for($i=0;$i<count($head); $i++) {
        $subnp = 'subnp'.strval($i);
        $str[] = $this->gen('np', array('head'=>$head[$i]), $subnp);
        $sem = $this->current_semantics();
        if($sem[$subnp]['gen'] != 'fem'){
          $gen = 'masc';
        }
      }
      return array('text'=>join(", ", array_slice($str, 0, count($str)-1)) . ' ' .
      $this->lex->string_for_id('conjunction') . ' ' . $str[count($str)-1],
          'sem' => array('gen'=>$gen, 'num'=>'pl'));
    }else if($this->lex->has($head)){
      return array('text'=>$this->lex->string($head,$data), 'sem'=>$data);
    }else{
      return array('text'=>$head, 'sem'=>array('gen'=>'masc', 'num'=>'sing'));
    }
  }

  function on_strike_vp($data){
    return $this->lex->string_for_id('on_strike');
  }

  function employee_en($place){
    $employees = $this->lex->string_for_id('employees');
    $place_frame = $this->lex->find($place[0]);
    $place = $this->lex->string($place_frame);
    if(isset($place_frame['is_short'])){
      return array('text'=> 'the ' . $place . ' ' . $employees,
      'sem'=>array('gen'=>'masc', 'num'=>'pl'));
    }else{
      return array('text'=> 'the ' . $employees . ' at the ' . $place,
      'sem'=>array('gen'=>'masc', 'num'=>'pl'));
    }
  }

  function metric_en($metric){
    return "the " . $this->lex->string_for_id($metric);
  }

  function metric_fr($metric){
    $frame = $this->lex->find($metric);
    return array('text'=>$this->lex->string($frame),'sem'=>array('num'=>$frame['num']));
  }

  function employee_fr($place){
    $place = $this->lex->string_for_id($place[0]);
    return array('text'=>'les ' . $this->lex->string_for_id('employees'). ' de ' . $place,
      'sem'=>array('gen'=>'masc', 'num'=>'pl'));
  }
}
