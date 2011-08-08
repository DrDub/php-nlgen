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
require '../../php-nlgen/generator.php';

class TarotGenerator extends Generator {
  # data are the 10 cards of the spread, in order
  function top($data){
    # do some analysis and set-up tone in $this->context

    $overall =
    0.5 * ($this->omen($data[0]) + $this->omen($data[1]) * 0.75) +
    ($this->omen($data[3])<0?1.0:-0.25) + 1.3*$this->omen($data[5]) +
    1.3 * $this->omen($data[2]) + $this->omen($data[4]) +
    0.5 * $this->omen($data[6]) + $this->omen($data[7]) +
    $this->omen($data[8]) +
    5 * $this->omen($data[9]);

    $this->context['overall_score'] = $overall;
    $very = abs($overall) > 5 ? 1 : 0;

    $overall_str = $overall == 0? "neutral" :
    (($very?"very_":"") .
    $overall >= 0? "positive" : "negative");
    $this->context['overall'] = $overall_str;

    return array('text' =>
    $this->gen("opening", $overall_str) . "\n" .
    $this->gen("issue", array($data[0], $data[1])) . "\n" .
    $this->gen("time", array($data[3], $data[5])) . "\n" .
    $this->gen("consciousness", array($data[2], $data[4])) . "\n".
    $this->gen("perception", array($data[6], $data[7])) . "\n".
    $this->gen("hope_fears", array($data[8])) . "\n".
    $this->gen("outcome", array($data[9])) . "\n",
    'sem'=>array('type'=>'full'));
  }

  function opening($overall){
    return array('text' =>
    $this->lex->string_for_id("opening_" . $overall),
    'sem'=>array('type'=>'opening', 
    'overall'=>$overall));
  }

  function issue($data){
    # need to do the generation first, so as to reason about the generated text
    $this_sem=&$this->semantics[count($this->semantics)-1];
    $card0 = $this->gen("card", $data[0], 'card0');
    $card1 = $this->gen("card", $data[1], 'card1');
    $card0_issue = $this->gen("issue_card", $data[0],'card0_issue');
    $card1_issue = $this->gen("issue_card", $data[1],'card1_issue');
    $card0_issue_sem = $this_sem['card0_issue'];
    $card1_issue_sem = $this_sem['card1_issue'];
    return array('text' =>
    "Currently, you got the " . $card0 . " and the " . $card1 . ". " .
    "The " . $card0 . " implies " . $card0_issue . 
    " This " . $this->gen("nexus", $data) . " the " . $card1 . " which " . 
    ($card0_issue_sem['omen'] == $card1_issue_sem['omen']? "also ":"") .
    "implies " . $card1_issue . " ",
    'sem' => array('type' => 'issue'));
  }

  function issue_card($card){
    $omen = "omen_".$this->onto->find_by_path(array($card,'omen'));
    $omen_str = $this->lex->string_for_id($omen);
    if($this->lex->has("issue_".$card)){
      $issue = "custom";
      $issue_str = " " . $this->lex->string_for_id("issue_".$card) . ".";
    }else{
      # general from suit
      $suit = $this->onto->find_by_path(array($card,'suit'));
      if($suit){
        $issue = "suit";
        if(isset($this->context['issue_suit']) && $this->context['issue_suit'] == $suit){
          $issue_str=" Same as the other card, this is also a ". $suit . "."; # do not repeat
        }else{
          $issue_str = " " . ucfirst($this->gen("describe_suit", $suit)) . ".";
          $this->context['issue_suit'] = $suit;
        }
      }else{
        $issue = "unknown";
        $issue_str = "";
      }
    }
    return array('text' => $omen_str . "." . $issue_str,
    	'sem'=>array('omen'=>$omen, 'issue'=>$issue));
  }

  function nexus($data){
    if($this->onto->find_by_path(array($data[0],"opposes",$data[1]))){
      return array('text'=>"strongly opposes", 'sem'=>array('type'=>'connective', 'kind'=>'contrast'));
    }elseif ($this->onto->find_by_path(array($data[0],"reinforces",$data[1]))){
      return array('text'=>"strongly reinforces", 'sem'=>array('type'=>'connective', 'kind'=>'list'));
    }else{
      $omen0 = $this->onto->find_by_path(array($data[0],"omen"));
      $omen1 = $this->onto->find_by_path(array($data[1],"omen"));
      if($omen0 == "neutral" || $omen1 == "neutral") {
        return array('text'=>"follows", 'sem'=>array('type'=>'connective', 'kind'=>'joint'));;
      }elseif ($omen0 == $omen1) {
        return array('text'=>"reinforces", 'sem'=>array('type'=>'connective', 'kind'=>'list'));
      }else{
        return array('text'=>"opposes", 'sem'=>array('type'=>'connective', 'kind'=>'list'));
      }
    }
  }

  function time($data){
    return
    "While the " .
    $this->gen("card", $data[0],'card0') . " is leaving, it " .
    $this->gen("nexus", $data) . " the " .
    $this->gen("card", $data[1],'card1') . " that is arriving.";
  }

  function consciousness($data){
    return
    "Deep under, you feel like the " .
    $this->gen("card", $data[0], 'card0') . ". It " .
    $this->gen("nexus", $data) . " the " .
    $this->gen("card", $data[1], 'card1') .
    " that is what you feel consciously.";
  }

  function perception($data){
    return
    "You see yourself " .
    $this->gen("card", $data[0],'card0') . ". Then it " .
    $this->gen("nexus", $data) . " " .
    $this->gen("card", $data[1], 'card1') .
    " that is how the others see you.";
  }

  function hope_fears($data){
    return
    "For your hope and fears, we see the " .
    $this->gen("card", $data[0]) . ".";
  }

  function outcome($data){
    return
    "Finally, the outcome is the " .
    $this->gen("card", $data[0]) . ".";
  }

  function card($card){
    if($this->lex->has($card)){
      return $this->lex->string_for_id($card);
    }
    # build card from ontology
    $onto_frame=$this->onto->find($card);
    if(isset($onto_frame['name'])){
      return $onto_frame['name'];
    }else{
      return $this->gen("card_number",$onto_frame['number'])." of ".$onto_frame['suit'];
    }
  }

  function card_number($num){
    $card_num = "card_number_".$num;
    if($this->lex->has($card_num)){
      return $this->lex->string_for_id($card_num);
    }
    return $this->lex->number_to_string($num);
  }

  function describe_suit($suit){
    $suit_descr="suit_description_".$suit;
    if($this->lex->has($suit_descr)){
      return "the ".$suit." are " .$this->lex->string_for_id($suit_descr);
    }
    return "the ".$suit. " are ".$suit;
  }

  function omen($card){
    return omen2num($this->onto->find_by_path(array($card,'omen')));
  }

}


function omen2num($omen){
  if($omen == "good"){
    return 1;
  }elseif($omen =="bad"){
    return -1;
  }
  return 0;
}