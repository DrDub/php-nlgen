<?php

require __DIR__ . '/vendor/autoload.php';

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;

$ontology   = file_get_contents("ontology.json");
$lexicon_en = file_get_contents("lexicon_en.json");
$lexicon_de = file_get_contents("lexicon_de.json");

$gen = MultilingualTruckConfigGenerator::NewSealed($ontology,
                                                   [ 'en' => $lexicon_en, 'de' => $lexicon_de ] );
$GLOBALS['gen'] = $gen;

class ConfigurationConversation extends Conversation {

    protected $gen;
    protected $lang;
    protected $config;

    public function __construct($lang) {
        $this->gen = $GLOBALS['gen'];
        $this->lang = $lang;
        $this->config = [];
    }

    public function run() {
        $this->askNext("vehicle");
    }
    
    public function askNext($type) {
        $question = $this->generateQuestion($type);
        //error_log($question);
        $sem = $this->gen->semantics()['top']['question'];
        $this->ask($question, function(Answer $answer) use ($sem, $type) {
            $answer_text = $answer->getText();
            //error_log("Received: " . $answer_text);
            $correct = false;
            if(isset($sem['default'])) {
                # yes / no
                if(strtolower($answer_text) == $this->gen->lex->string_for_id('yes')) {
                    $this->config[$type] = $sem['other']['onto'];
                }else{
                    $this->config[$type] = $sem['default']['onto'];
                }
                $correct = true;
            }else{
                # find valid answers and match it
                foreach($sem['opts'] as $opt){
                    //error_log("  Sem: " . $opt['string']);
                    if(strtolower($answer_text) == strtolower($opt['string'])) {
                        $this->config[$type] = $opt['onto'];
                        $correct = true;
                    }
                }
            }
            if($correct){
                $type = $this->nextQuestion($type);
                if($type) {
                    $this->askNext($type);
                }else{
                    $this->say($this->gen->generate($this->config, [ 'lang' => $this->lang ]));
                }
            }else{
                $this->askNext($type);
            }
        });
    }
    
    protected function generateQuestion($question) {
        $data = [ 'type' => 'question', 'need' => $question];
        if(isset($this->config['vehicle'])) {
            $data['target'] = $this->config['vehicle'];
        }
        return $this->gen->generate($data, [ 'lang' => $this->lang ]);
    }

    protected function nextQuestion($question) {
        //error_log("In next question $question: " . print_r($this->config, TRUE));
        $current = $this->gen->onto->find($question);
        foreach($this->gen->onto->find_all_by_class('question') as $id) {
            $frame = $this->gen->onto->find($id);
            if($frame['order'] == $current['order'] + 1){
                $applies = true;
                if(isset($frame['applies'])){
                    foreach($frame['applies'] as $key => $value) {
                        if(! isset($this->config[$key]) || $this->config[$key] != $value){
                            $applies = false;
                        }
                    }
                }
                if($applies){
                    return $id;
                }
            }
        }
        return NULL;
    }
}

