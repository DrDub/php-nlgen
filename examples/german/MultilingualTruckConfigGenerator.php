<?php

/*
 * Copyright (c) 2022 Pablo Ariel Duboue <pablo.duboue@gmail.com>
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

require __DIR__ . '/vendor/autoload.php';

use NLGen\Generator;

class MultilingualTruckConfigGenerator extends Generator {
    
    # data includes 'type' (question or configuration)
    function top($data){
        # check for templates
        if(! is_array($data)) {
            return $this->lex->string_for_id($data);
        }      
        if($data['type'] == 'question') {
            return $this->question($data);
        }
        # check and remove defaults
        # TODO replace this code with an ontology lookup
        $info = [ 'vehicle' => $data['vehicle'] ];
        $box        = $data['box'] ?? "";
        $cabin      = $data['cabin'] ?? "";
        $drivetrain = $data['drivetrain'] ?? ""; 
        $engine     = $data['engine'] ?? ""; 
        $color      = $data['color'] ?? ""; 
        if($box == $this->lex->string_for_id('short') || $box == 'short'){
            $info['box'] = 'short';
        }
        if($cabin == $this->lex->string_for_id('crew') || $cabin == 'crew'){
            $info['cabin'] = 'crew';
        }
        if($color == $this->lex->string_for_id('red') || $color == 'red'){
            $info['color'] = 'red';
        }
        if($engine == $this->lex->string_for_id('inboard') || $engine == 'inboard'){
            $info['engine'] = 'inboard';
        }
        if($drivetrain == '4x4'){
            $info['drivetrain'] = '4x4';
        }
        //error_log(print_r($info, TRUE));
        
        return $this->sentence($data['action'], $info);
    }

    function get_options($question) {
        $all_opts = $this->onto->find_all_by_class('option');
        //print_r($all_opts);
        
        $opts = [];        
        $has_default=false;
        $default = NULL;
        $other = NULL;
        foreach($all_opts as $id) {
            $frame = $this->onto->find($id);
            if($frame['question'] == $question){
                //print_r($frame);
                $entry = NULL;
                if($this->lex->has("opt_".$frame['id'])){
                    $entry = $this->lex->find("opt_".$frame['id']);
                }else{
                    $entry = $this->lex->find($frame['id']);
                }
                $entry['onto'] = $frame['id'];
                $entry['default'] = isset($frame['default']);
                if($entry['default']){
                    $has_default=true;
                    $default = $entry;
                }else{
                    $other = $entry;
                }
                $opts[] = $entry;
            }
        }
        //print_r($opts);
        return [ $opts, $has_default, $default, $other ];
    }

    protected function question($data) {
        $question = $data['need'];
        $object   = $data['target'] ?? NULL;

        [ $opts, $has_default, $default, $other ] = $this->get_options($question);

        $key = "q_" . $question;
        $text = "";
        $sem = [];
        if($has_default) {
            $text = $this->standard($default, $question, $object) . " " . $this->alternative($other, $question, $object);
            $sem  = [ 'default' => $default, 'other' => $other ];
        }else{
            $text = $this->lex->string_for_id($key, [ "options" => $this->list($opts, 'or') ]);
        }
        $sem['opts'] = $opts;
        
        if($question == "vehicle") {
            $text = $this->lex->string_for_id("greet") . " " . $text;
        }
        return [ 'text' => $text, 'sem' => $sem ];
    }

    protected function standard($entry, $type, $object) {
        if($type == "color")      return $this->standard_color($entry, $object);
        if($type == "drivetrain") return $this->standard_drivetrain($entry, $object);
        return $this->standard_base($entry, $object);
    }
    protected function standard_base($entry, $object) {
        $option = $this->option($entry, 'dat');
        return $this->lex->string_for_id("standard", [ "option" => $option ]);
    }

    protected function alternative($entry, $type, $object) {
        if($type == "color")      return $this->alternative_color($entry, $object);
        if($type == "drivetrain") return $this->alternative_drivetrain($entry, $object);
        return $this->alternative_base($entry, $object);
    }
    
    protected function alternative_base($entry, $object) {
        $option = $this->option($entry, 'acc');
        return $this->lex->string_for_id("altern", [ "option" => $option ]);
    }

    protected function standard_color($entry, $object) {
        $query = [ 'id' => $entry['onto'] ];
        if($this->context['lang'] == 'de') {
            $query['root'] = 'y';
        }
        $color = $this->lex->query($query)[0]['string'];
        return $this->lex->string_for_id("standardc", [ "color" => $color ]);
    }

    protected function standard_drivetrain_en($entry, $object) {
        $option = $this->option($entry, 'acc');
        return $this->lex->string_for_id("standardd", [ "option" => $option ]);
    }
    
    protected function standard_drivetrain_de($entry, $object) {
        return $this->standard_base($entry, $object);
    }

    protected function alternative_color($entry, $object) {
        $object = $this->lex->find($object);
        $colorobj = $this->colorobj($entry, $object);
        return $this->lex->string_for_id("alternc", [ "colorobj" => $colorobj ]);
    }

    protected function colorobj_en($entry, $object) {
        return $this->add_article($this->lex->string_for_id($entry['onto']). " " . $object['string'], false);
    }
    
    protected function colorobj_de($entry, $object) {
        return $this->determiner([ 'def' => 'n',  'gender' => $object['gender'], 'case' => 'acc' ]) . " " .
           $this->lex->query([ 'id' => $entry['onto'], 'gender' => $object['gender'], 'case' => 'acc' ])[0]['string'] . " " .
           $object['string'];
    }
    
    protected function alternative_drivetrain_de($entry, $object) {
        return $this->alternative_base($entry, $object);
    }
    
    protected function alternative_drivetrain_en($entry, $object) {
        $option = $this->add_article($this->option($entry, 'acc'), false);
        return $this->lex->string_for_id("altern", [ "option" => $option ]);
    }
    
    protected function option_en($entry, $case) {
        $question = $this->onto->find($entry['onto'])['question'];
        if($question == 'drivetrain') {
            return $this->lex->string_for_id($entry['id']);
        }
        $opt = $entry['string'];
        if($question == 'box') {
            $opt .= " bed";
        }
        if($question == 'cabin') {
            $opt .= "-cabin";
        }
        return "with " . $this->add_article($opt, false);
    }
    
    protected function option_de($entry, $case) {
        return $this->determiner([ 'def' => 'n', 'gender' => $entry['gender'], 'case' => $case ]). " " . $entry['string'];
    }

    protected function add_article($string, $def) {
        if($def){
            return "the $string";
        }
        if(! $string) {
            return "";
        }
        $art = "a";
        $f = $string[0]; # TODO use pronountiation here
        if($f == "o" || $f == "i" || $f == "a" || $f == "e" || $f == "u"){
            $art .= "n";
        }
        return "$art $string";
    }

    protected function determiner($data) {
        $query = $data;
        $query['class'] = 'det';
        $frame = $this->lex->query($query)[0];
        return [ 'text' => $frame['string'], 'sem' => $frame ];
    }

    protected function list($opts, $conj) {
        $len = count($opts);
        if($len < 1) return "";
        $copy = $opts;
        $last = array_pop($copy);
        $list = [];
        foreach($copy as $entry){
            $list[] = $entry['string'];
        }
        if($len < 2) {
            return $last['string'];
        }
        return implode(", ", $list)." ".$this->lex->string_for_id($conj)." ". $last['string'];
    }

  protected function sentence($action, $info) {
      $action = $this->lex->string_for_id($action);
      $configured = $this->vehicle($info);
      $result = $this->lex->string_for_id('config', ['configured' => $configured, 'action' => $action ]);
      $sem = $this->current_semantics()['vehicle'];
      if(isset($sem['leftover'])){
          $result = $result . " " . $this->vehicle_s($sem['leftover']);
      }
      return $result;
  }

  protected function vehicle_en($data) {
      $result = "a ";
      $vehicle = $this->lex->string_for_id($data['vehicle']);
      
      if(isset($data['color'])){
          $result .= $this->lex->string_for_id($data['color']). " ";
          unset($data['color']);
      }
      if(count($data) == 4){
          $leftover = $data['vehicle'] == 'truck' ? [ 'drivetrain' => $data['drivetrain'] ] :
                    [ 'engine' => $data['engine'] ];
          return [ 'text' => $result . $this->box_ap($data['box'],'','') . " $vehicle " . $this->cabin_pp($data['cabin']),
                   'sem' => [ 'leftover' => $leftover ]];
      }
      if(isset($data['box'])){
          $result .=  $this->box_ap($data['box'], '', '') . " $vehicle";
          if(isset($data['cabin'])) {
              $result = $result . " " . $this->cabin_pp($data['cabin']);
          }elseif(isset($data['drivetrain'])) {
              $result = $result . " " . $this->drivetrain_pp($data['drivetrain']);
          }elseif(isset($data['engine'])) {
              $result = $result . " " . $this->engine_pp($data['engine']);
          }
          return $result;
      }
      if(isset($data['cabin'])){
          $result .= $this->cabin_ap($data['cabin']) . " " . $vehicle;
          if(isset($data['drivetrain'])) {
              return [ 'text' => $result,
                       'sem'  => [ 'leftover' => ['drivetrain' => $data['drivetrain'] ]]];
          }elseif(isset($data['engine'])) {
              return $result . " " . $this->engine_pp($data['engine']);
          }else{
              return $result;
          }
      }
      if(isset($data['drivetrain'])){
          return $result . $this->drivetrain_ap($data['drivetrain']) . " " . $vehicle;
      }
      if(isset($data['engine'])){
          return $result . $vehicle . " " . $this->engine_pp($data['engine']);
      }
      return $result. $vehicle;
  }

  protected function vehicle_de($data) {
      $object = $this->lex->find($data['vehicle']);
      unset($data['vehicle']);
      
      $result = $this->determiner([ 'def' => 'n',  'gender' => $object['gender'], 'case' => 'acc' ]) . " ";
      if(isset($data['color'])){
          $result .= $this->lex->query([ 'id' => $data['color'], 'gender' => $object['gender'], 'case' => 'acc' ])[0]['string']. " ";
          unset($data['color']);
      }
      if (isset($data['drivetrain'])) {
          $result .= $this->drivetrain_ap($data['drivetrain']);
          unset($data['drivetrain']);
      }
      $result .= $object['string'];

      $opts = count($data);
      if($opts) {
          $result .= " " . $this->lex->string_for_id('with') . " ";

          $opts=[];
          foreach($data as $key => $value) {
              switch ($key) {
              case 'box':        $opts[] = [ 'string' => $this->box_pp($value, '', 'dat') ]; break;
              case 'cabin':      $opts[] = [ 'string' => ucfirst($this->lex->string_for_id($value)) ]; break;
              case 'engine':     $opts[] = [ 'string' =>
                                             $this->determiner(['def' => 'n', 'gender'=>$this->lex->find("opt_$value")['gender'],
                                                                'case' => 'dat' ]) . " ".
                                             ucfirst($this->lex->string_for_id($value)) ]; break;
              }
          }
          $result .= $this->list($opts, 'and');
      }
      return $result;
  }

  protected function cabin_ap($cabin) {
      return $this->lex->string_for_id($cabin) . "-cabin";
  }
  protected function cabin_pp_en($cabin) {
      return "with a " . $this->lex->string_for_id($cabin) . " cabin";
  }

  protected function cabin_pp_de($cabin) {
      return "mit " . ucfirst($this->lex->string_for_id($cabin));
  }

  protected function drivetrain_ap_en($drivetrain) {
      if($drivetrain == "4x4"){
          return "four wheel-drive";
      }
      return "standard traction";          
  }

  protected function drivetrain_ap_de($drivetrain) {
      if($drivetrain == "4x4"){
          return "Allrad-";
      }
      return "Standardtraktion "; 
  }

  protected function drivetrain_pp($drivetrain) {
      return "with " . $this->drivetrain_ap($drivetrain);
  }
  
  protected function engine_pp($engine) {
      return "with " . $this->lex->string_for_id($engine);
  }
  
  protected function box_ap_de($box, $article, $case) {
      return $this->noun_phrase("box", $box, $article, $case);
  }
  protected function box_ap_en($box, $article, $case) {
      return $this->lex->string_for_id($box) . "-box";
  }
  protected function box_pp_en($box, $prep, $case) {
      return "having a " . $this->lex->string_for_id($box) . " box";
  }

  protected function box_pp_de($box, $prep, $case) {
      $result = "";
      if($prep) {
          $frame = $this->lex->find($prep);
          $case = $case ?? $frame['case'];
          $result = $frame['string'] . " ";
      }
      
      return $result . $this->noun_phrase("box", $box, "", $case);
  }

  protected function noun_phrase_en($head, $modifier, $article, $case) {
      $article_text =  "";
      if($article) {
          if($article == "indef"){
              $article_text = "a "; # TODO 'an'
          }else{
              $article_text = "the ";
          }
      }
      return $article_text. ($modifier?$this->lex->string_for_id($modifier) . " " : "") . $this->lex->string_for_id($head);
  }

  protected function noun_phrase_de($head, $modifier, $article, $case) {
      $article_text = "";
      $head_sem = [];
      $head_text = "";
      if(is_array($head)) {
          $head_text = $this->multihead_noun($head);
          $head_sem = $this->current_semantics()['multihead_noun'];
      }else{
          $head_sem = $this->lex->find($head);
          $head_text = $head_sem['string'];
      }
      $det_text = "";
      $det_strong = false;
      if($article){
          if($article == "indef") {
              $det_text = $this->determiner([ 'def' => 'n',  'gender' => $head_sem['gender'], 'case' => $case ]) . " ";
              $det_strong = $this->current_semantics()['determiner']['ending'] == 'strong';
          }
      }
      $modif_text = "";
      if($modifier) { # only single adjectives at the moment
          $ending = $det_strong ? ['weak', 'mixed' ] : [ 'strong', 'mixed' ];
          if(is_array($modifier)){
              $modifier = $modifier['onto'];
          }
          $modif_text = $this->lex->query([ 'id' => $modifier, 'gender' => $head_sem['gender'], 'case' => $case, 'ending' => $ending ])[0]['string'].' ';
      }
      return $article_text.$det_text.$modif_text.$head_text;
  }

  protected function vehicle_s($data) {
      if(isset($data['cabin'])){
          return "It comes " . $this->cabin_pp($data['cabin']) . ".";
      }
      if(isset($data['drivetrain'])) {
          return "It is a " . $this->drivetrain_ap($data['drivetrain']) . ".";
      }
      if(isset($data['box'])) {
          return "It has a " . $data['box'] . " box.";
      }
      if(isset($data['engine'])) {
          return "It has an " . $data['engine'] . " engine.";
      }
      return "";
  }
}

/*
$ontology   = file_get_contents("ontology.json");
$lexicon_en = file_get_contents("lexicon_en.json");
$lexicon_de = file_get_contents("lexicon_de.json");
$gen = MultilingualTruckConfigGenerator::NewSealed($ontology,
                                                  [ 'en' => $lexicon_en, 'de' => $lexicon_de ] );
echo $gen->generate([ 'type' => 'question', 'need' => 'drivetrain', 'target' => 'pickup' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'question', 'need' => 'action', 'target' => 'pickup' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'configuration', 'action' => 'lease',
                      'vehicle' => 'boat', 'box' => 'short', 'color' => 'red' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'configuration', 'action' => 'buy',
                      'vehicle' => 'boat', 'box' => 'short', 'engine' => 'inboard', 'color' => 'white' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'configuration', 'action' => 'buy',
                      'vehicle' => 'truck', 'box' => 'long', 'drivetrain' => '4x4', 'color' => 'red',
                      'cabin' => 'crew' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'question', 'need' => 'color', 'target' => 'truck' ], ['lang'=>'de']) . "\n\n";
echo $gen->generate([ 'type' => 'question', 'need' => 'color', 'target' => 'boat' ], ['lang'=>'de']) . "\n\n";
*/
