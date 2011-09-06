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

use nlgen\Generator;
use nlgen\Lexicon;
require '../../php-nlgen/generator.php';

class BocaSuciaGenerator extends Generator {

  var $default_restrictions;
  var $markov_model;

  function __construct($onto='', $lexicon='') {
    parent::__construct($onto,$lexicon);
    $this->default_restrictions = array("length" => "default",
    "taboo-unlock" => "0",
    "level" => "normal",
    "speaker" => "sm",
    "target" => "sm",
    "seed" => strval(rand()) );

    # table of '^$SL' with history == 2
    # ^ --> start (can't be generated)
    # $ --> end
    # S --> short insult
    # L --> long insult
    # the table is key 2-char string "ab" into an array of "c" => float
    #   indicating P(c|ab)
    $this->markov_model = array();
    $this->markov_model['^^'] = array('$'=>0.0, 'S'=>0.3, 'L'=>0.7);
    $this->markov_model['^S'] = array('$'=>0.0, 'S'=>0.5, 'L'=>0.5);
    $this->markov_model['^L'] = array('$'=>0.1, 'S'=>0.7, 'L'=>0.3);
    $this->markov_model['SS'] = array('$'=>0.2, 'S'=>0.4, 'L'=>0.4);
    $this->markov_model['SL'] = array('$'=>0.6, 'S'=>0.4, 'L'=>0.1);
    $this->markov_model['LS'] = array('$'=>0.3, 'S'=>0.1, 'L'=>0.6);
    $this->markov_model['LL'] = array('$'=>0.8, 'S'=>0.2, 'L'=>0.0);
  }

  function _set_defaults($restrictions) {
    foreach ($this->default_restrictions as $key => $value) {
      if(! isset($restrictions[$key])){
        $restrictions[$key] = $value;
      }
    }
    return $restrictions;
  }

  function _sample_model(){
    $past = "^^";
    $current = "^";
    $result = array();
    $keys = array('$','S','L');
    $l_count = 0;
    $s_count = 0;
    while($current != "$") {
      $past = substr($past, 1);
      array_push($result, $current);
      $past = $past . $current;
      $rand = rand(0,1000);
      $current = 'L';
      $accum = 0;
      $table = $this->markov_model[$past];
      foreach($keys as $key){
        $accum += $table[$key] * 1000;
        if($accum > $rand){
          $current = $key;
          break;
        }
      }
      if($current == "L"){
        ++$l_count;
        if($l_count > 2){
          $current = '$';
        }
      }
      if($current == "S"){
        ++$s_count;
        if($s_count > 3){
          $current = '$';
        }
      }
    }
    return array_slice($result, 1);
  }

  # top level, this is the content planner
  function top($restrictions){
    # set defaults
    $restrictions = $this->_set_defaults($restrictions);
    $this->context['restrictions'] = $restrictions;

    if($restrictions["length"] == "ultra-short"){
      $restrictions["length"] = "short";
      return $this->gen("insult",$restrictions);
    }
    srand(intval($restrictions["seed"]));

    # compute the probabilities based on the Markov model
    $plan = $this->_sample_model();
    if($restrictions["length"] == "short"){
      $end = 0;
      while($end < count($plan) && $plan[$end] == 'S'){
        ++$end;
      }
      if($end == 0){
        $end = 1;
      }
      $plan = array_splice($plan, 0, $end);
    }

    $result = "";
    foreach($plan as $len){
      $restrictions["length"] = $len == "S"?"short":"long";
      $gen = $this->gen("insult",$restrictions);
      if($gen) {
        $result = $result .
        (strlen($result)>0?" ":"") . ($len == "S"?"¡":"") . ucfirst($gen) . ($len == "S"?"!":".");
      }
    }
    return $result;
  }

  function insult($restrictions) {
    # found lexical entries that satisfy the restrictions
    $query = $this->restrictions_to_lexical_query($restrictions);
    $good_entries = $this->lex->query($query);

    # sample them a few times in case of repeat with past discourse
    # this code is similar to Lexicon::sample but split for efficiency
    $done = false;
    $total = 0;
    #print "good_entries: "; print_r($good_entries);
    foreach($good_entries as $key=>$entry){
      $likelihood = 1.0;
      if(! isset($entry["likelihood"])){
        $good_entries[$key]["likelihood"] = 1.0;
      }else{
        $likelihood = floatval($entry["likelihood"]);
      }
      $total += $likelihood;
    }
    if($total == 0){
      return "";
    }
    #print "good_entries: "; print_r($good_entries);
    #print "total: $total\n";

    $result = NULL;
    $repeated_loop_count = 0;
    do {
      $rand = rand(0,1000 * $total);
      # print "rand: $rand\n";
      $accum = 0;
      $chosen = NULL;
      foreach($good_entries as &$entry){
        $chosen = $entry;
        $accum += floatval($entry["likelihood"]) * 1000;
        if($accum > $rand){
          break;
        }
      }
      $savepoint = $this->savepoint();
      $call_data=$restrictions;
      foreach($chosen as $key=>$value){
        $call_data[$key]=$value;
      }
      $result = $this->lex->resolve($chosen,$call_data);

      # determine if text is already in the past discourse history
      $repeated = false;
      #print "str=".$this->semantics[0]['top']['insult']['string']." res=".$result['string']."\n";
      if(isset($this->semantics[0]['top']['insult']['string']) &&
      $this->semantics[0]['top']['insult']['string'] == $result['string']){
        $repeated = true;
      }
      $i = 1;
      while(isset($this->semantics[0]['top']["insult$i"])){
        if(isset($this->semantics[0]['top']["insult$i"]['string']) &&
        $this->semantics[0]['top']["insult$i"]['string'] == $result['string']){
          $repeated = true;
          break;
        }
        ++$i;
      }

      if($repeated){
        $this->rollback($savepoint);
        #print "rollback! " . $result['string'] ."\n";
        $result = NULL;
      }else{
        $done = true;
      }
      ++$repeated_loop_count;
    } while(!$done && $repeated_loop_count < 10);
    if($result == NULL){
      return "";
    }

    return array('text' => $result['string'], 'sem' => $result);
  }

  function verb($data) {
    $entry=$this->lex->find($data['root']);
    $sem = $entry;
    foreach($data as $key => $value){
      $sem[$key] = $value;
    }
    $infinitive = $entry['string'];
    $subject = isset($data['subject'])?$data['subject']:"target";
    $key = $this->get_person_number_key($subject,$sem);
    $mode = isset($data['mode'])?$data['mode']:'indicative';
    $sem['mode'] = $mode;
    if(isset($data['tense'])){
      $tense=$data['tense'];
      $key=$key.'_'.$tense;
      $sem['tense']=$tense;
    }

    if(isset($entry[$mode]) && isset($entry[$mode][$key])){
      return array('text'=>$entry[$mode][$key],'sem'=>$sem);
    }
     
    #TODO all the rest
    if($data["mode"]=="imperative"){
      $word=substr($infinitive,0,strlen($infinitive)-1);
      return array('text'=>$word,'sem'=>$sem);
      # I am not dealing with accents as it'd require a lexical component
      # compare 'andá' vs. 'andate'
      # return substr($word,0,strlen($word)-1).accent(substr($word,strlen($word)-1));
    }else{
      return array('text'=>$infinitive,'sem'=>$sem);
    }
  }

  function noun($data){
    #TODO additional info in $data can be used as a query
    $entry=$this->lex->find($data['root']);
    #TODO this should be in the lexicon, maybe a Spanish lexicon...?
    if(!isset($entry['gender'])){
      $entry['gender'] = "masculine";
    }
    if(!isset($data['number'])){
      $entry['number'] = "singular";
    }
    $pre_decoration = "";
    $post_decoration = "";
    if(isset($data['decorated'])){
      if($this->lex->has($data['root']."_pre_decoration")){
        $pre_decoration = $this->lex->string_for_id("pre_decoration", $data);
        if(strlen($pre_decoration) > 5){
          $pre_decoration = "$pre_decoration ";
        }
      }
      if($this->lex->has($data['root']."_post_decoration")){
        $post_decoration = $this->lex->string_for_id("post_decoration", $data);
        if(strlen($post_decoration) > 0){
          $post_decoration = " $post_decoration";
        }
      }
    }
    # inflect
    $inflected=$entry['string'];
    if(isset($data['agreement'])){
      $ng = number_gender_to_array($this->context['restrictions'][$data['agreement']]);
      if($ng['gender'][0]=='female'){
        $inflected=substr($inflected,0,-1).'a';
        $entry['gender'] = 'female';
      }
      if($ng['number'][0]=='plural'){
        $inflected=$inflected.'s';
        $entry['number'] = 'plural';
      }
    }
    # finite / indefinite / anarticulated
    $article_def = isset($data['article'])? $data['article'] : 'definite';
    $gender_masc = $entry['gender']=="masculine";
    $number_sing = $entry['number']=="singular";
    $article='';
    if($article_def == 'definite'){
      $article = $number_sing?($gender_masc?"el":"la"):($gender_masc?"los":"las");
    }elseif($article_def == 'indefinite'){
      $article = $number_sing?($gender_masc?"un":"una"):($gender_masc?"unos":"unas");
    }
    if(strlen($article)>0){
      $article="$article ";
    }

    return array('text'=>$article.$pre_decoration.$inflected.$post_decoration, 'sem'=>$entry);
  }

  function decorator($data){
    $decoration = $this->lex->string_for_id($data['root'], $data);
    if(strlen($decoration) > 5){
      $decoration = "$decoration ";
    }
    return $decoration;
  }

  function phrase($data){
    # choose a phrase using likelihoods
    $phrases = array();
    foreach($data['phrases'] as $phrase){
      if(is_array($phrase)){
        $phrases[]=$phrase;
      }else{
        $phrases[]=array('string'=>$phrase);
      }
    }
    $chosen = Lexicon::sample($phrases);
    $for_call=$data;
    foreach($chosen as $key => $value){
      $for_call[$key]=$value;
    }
    $resolved = $this->lex->resolve($chosen, $for_call);
    return array('text'=>$resolved['string'],'sem'=>$resolved);
  }

  function full_phrase($data){
    $entry = $this->lex->find($data['phrase']);
    $for_call=$data;
    foreach($entry as $key => $value){
      $for_call[$key]=$value;
    }
    $resolved = $this->lex->resolve($entry, $for_call);
    return array('text'=>$resolved['string'],'sem'=>$resolved);
  }

  function pronoun($data){
    #TODO this should be in the lexicon, maybe a Spanish lexicon...?
    $referent = isset($data['referent'])?$data['referent']:"target";
    $sem = $data;
    $sem['gloss'] = $referent;
    $key = $this->get_person_number_key($referent,$sem);
    $case = isset($data['case'])?$data['case']:'accusative';
    $sem['case']=$case;
    $string = $this->lex->string_for_id('pronoun_'.$key.'_'.$case);
    return array('text'=>$string,'sem'=>$sem);
  }

  # helper functions

  function restrictions_to_lexical_query($restrictions) {
    $query = array();
    # length
    if($restrictions["length"] == "short"){
      $query['length'] = array("LENGTH-SHORT","LENGTH-MEDIUM");
    }else{
      $query['length'] = array("LENGTH-LONG","LENGTH-MEDIUM");
    }
    # taboo-unlock
    if($restrictions['taboo-unlock']){
      #TODO
    }
    # level
    if($restrictions['level'] == "nasty") {
      $query['insult_level'] = array("NORMAL","NASTY");
    }elseif($restrictions['level'] == "normal") {
      $query['insult_level'] = "NORMAL";
    }else{
      $query['insult_level'] = "POLITE";
    }

    # speaker, target
    foreach(array("speaker","target") as $who){
      $ng=number_gender_to_array($restrictions[$who]);
      $query[$who . "_number"] = $ng['number'];
      $query[$who . "_gender"] = $ng['gender'];
    }

    return $query;
  }

  function get_person_number_key($ref,&$sem){
    $person = $ref == 'target' ? 2 : 1;
    $ng = number_gender_to_array($this->context['restrictions'][$ref]);
    $number = $ng["number"][0];
    $sem['person'] = $person;
    $sem['number'] = $number;
    return $person.substr($number,0,1);
  }
}

function number_gender_to_array($ng){
  $number = substr($ng,0,1) == "s" ? "singular" : "plural";
  $gender = substr($ng,1,1) == "m" ? "male" : "female";
  return array("number" => array($number,"undefined"),
    "gender" => array($gender, "undefined"));
}

function accent($char){
  switch($char[0]){
    case 'a': return 'á';
    case 'e': return 'é';
    case 'i': return 'í';
    case 'o': return 'ó';
    case 'u': return 'ú';
    case 'A': return 'Á';
    case 'E': return 'É';
    case 'I': return 'Í';
    case 'O': return 'Ó';
    case 'U': return 'Ú';
  }
  return $char;
}

