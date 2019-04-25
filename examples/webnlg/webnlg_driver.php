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


$header = "<?xml version='1.0' standalone='yes'?>";

use nlgen\Generator;

global $argv,$argc;

// usage: webnlg_driver.php memory.json eval.xml delex_dict.json ( write eval to this folder, if defined )

if($argc>1){
    require '../ste/ste.php';
}

// load the memory

$memory_str = file($argv[1]);
$memory = array();

foreach($memory_str as $memory_line){
    $memory[] = json_decode($memory_line, TRUE);
}

//echo "********* MEMORY LOADED\n";

// load the xml
$bench_str = file_get_contents($argv[2]);
$xmlstr = $header.$bench_str;
$benchmark = new SimpleXMLElement($xmlstr);

//echo "********* BENCHMARK LOADED\n";

// load the type data
// (this should be moved to a redis call in a production system)
$types = json_decode(file_get_contents($argv[3]), TRUE);
$types_lookup = array();
foreach($types as $type => $entries){
    foreach($entries as $entry){
        $types_lookup[$entry] = $type;
    }
}

//echo "********* TYPE DATA LOADED\n";

$lexfolder = FALSE;
if($argv>4){
    $lexfolder = $argv[4];
    $lexfps = array();
    for($i=0; $i<8; $i++){
        $lexfps[] = fopen("$lexfolder/all-notdelex-reference".strval($i).".lex", "a");
    }
}

// go through the benchmark
foreach($benchmark->entries->entry as $entry){

    //print_r($entry);

    // build the triples in their format
    $input = array();
    $eid = $entry['eid'];
    $tripleset = $entry->modifiedtripleset;
    $triples = array();
    foreach($tripleset->mtriple as $mtriple){
        $triple = explode(' | ', ((string)$mtriple));
        $s=array($triple[0], $triple[0]);
        $p=array($triple[1], $triple[1]);
        $o=array($triple[2], $triple[2]);
        if(array_key_exists($s[0], $types_lookup)){
            $s[1] = implode(' ',
              preg_split('/(\W)/',
                preg_replace('/"/', "'", 
                  preg_replace('/_/', ' ',
                  strtoupper($types_lookup[$s[0]])))));
        }
        $p[1] = implode(' ',
              preg_split('/(\W)/',
                preg_replace('/"/', "'", 
                  preg_replace('/_/', ' ', $p[0]))));
        $o[1] = implode(' ',
              preg_split('/(\W)/',
                preg_replace('/"/', "'", 
                  preg_replace('/_/', ' ',
                  strtoupper($p[0])))));
        $mytriple = array($s, $p, $o);
        for($i=0; $i<3; $i++){
            $mytriple[$i][] = implode(' ',
              preg_split('/(\W)/',
                preg_replace('/"/', "'", 
                  preg_replace('/_/', ' ', $mytriple[$i][0]))));
        }
        $triples[] = $mytriple;
        //print_r($triple);
        //print_r($mytriple);
    }

    $remaining = $triples;
    $loops = 0;
    $generated = "";
    while(count($remaining) > 0){

        // search for relevant sentences from the memory
        $selected = false;
        $matched = 0;
        foreach($memory as $m){
            $triples_found = array();
            //TODO: use context, too
            foreach($m['triples'] as $triple){
                foreach($remaining as $source){
                    $all_good = TRUE;
                    for($i=0; $i<3; $i++){
                        if(!($triple[$i] == $source[$i][1] ||
                        $triple[$i] == $source[$i][2])){
                            $all_good = FALSE;
                        }
                    }
                    if($all_good){
                        $triples_found[] = array($triple, $source);
                        break;
                    }
                }
                if(count($triples_found) > 0 &&
                  (!$selected || count($matched) < count($triples_found))){
                    $selected = $m;
                    $matched = $triples_found;
                }
            }
        }

        if(!$selected){
            // it's the end of the world as we knew it
            foreach($remaining as $triple){
                $generated .= $triple[0][2] . " " . $triple[1][2] . " " . $triple[2][2] . " ";
            }
            $remaining = array();
        }else{
            // plug-in the data and re-generate
            $sent = $gen->generate($selected['tree']);
            foreach($triples as $triple){
                $sent = str_replace($triple[0][1], $triple[0][2], $sent);
                $sent = str_replace($triple[2][1], $triple[2][2], $sent);
            }
            $generated .= $sent . " ";
            $new_remaining = array();
            foreach($remaining as $triple){
                $was_consumed = FALSE;
                foreach($matched as $consumed){
                    if($triple == $consumed[1]){
                        $was_consumed = TRUE;
                        break;
                    }
                }
                if(!$was_consumed){
                    $new_remaining[] = $triple;
                }
            }
            if(count($remaining) == count($new_remaining)){
                echo "ENTRY:\n";
                print_r($entry);
                echo "REMAINING:\n";
                print_r($remaining);
                echo "MATCHED:\n";
                print_r($matched);
                die("Shouldn't happend");
            }
            $remaining = $new_remaining;
        }
    }

    // print output on their format
    echo trim(strtolower(implode(' ', preg_split('/(\W)/', $generated))))."\n";

    if($lexfolder){
        $lex = array();
        foreach($entry->lex as $l){
            $lex[] = strtolower(implode(' ', preg_split('/(\W)/', (string)$l)))."\n";
        }
        for($i=0; $i<8; $i++){
            if($i>=count($lex)){
                fprintf($lexfps[$i], "\n");
            }else{
                fprintf($lexfps[$i], $lex[$i]);
            }
        }
    }
}
if($lexfolder){
    for($i=0; $i<8; $i++){
        fclose($lexfps[$i]);
    }
}
    
