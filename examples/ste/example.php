<?php

require __DIR__ . '/vendor/autoload.php';

use NLGen\Grammars\SimpleTechnicalEnglish;

global $argv,$argc;


$examples = array(
<<<HERE
dep(oil, put)
root(ROOT, oil)
case(surface, on)
det(surface, the)
amod(surface, machined)
nmod(oil, surface)
HERE
,
<<<HERE
root(ROOT, increase)
det(temperature, the)
dobj(increase, temperature)
mark(decrease, to)
acl(temperature, decrease)
det(time, the)
compound(time, cure)
dobj(decrease, time)
HERE
,
<<<HERE
root(ROOT, measure)
det(time, the)
nsubj(absorb, time)
dep(absorb, necessary)
case(gel, for)
det(gel, the)
compound(gel, silica)
nmod(necessary, gel)
mark(absorb, to)
xcomp(measure, absorb)
det(moisture, the)
dobj(absorb, moisture)
HERE
,
<<<HERE
root(ROOT, clean-1)
nmod:poss(skin, your)
dep(clean-1, skin)
case(quantity, with)
det(quantity, a)
amod(quantity, large)
nmod(skin, quantity)
case(water, of)
amod(water, clean-2)
nmod(quantity, water)
HERE
,
<<<HERE
det(shock, the)
nsubj(mount, shock)
root(ROOT, mount)
ccomp(mount, absorbs)
det(vibration, the)
dobj(absorbs, vibration)
HERE
,
<<<HERE
root(ROOT, obey)
det(instruction-PLURAL, the)
compound(instruction-PLURAL, safety)
dobj(obey, instruction-PLURAL)
HERE
,
<<<HERE
root(ROOT, keep)
det(part, the)
amod(part, primary)
dobj(keep, part)
case(assembly, of)
det(assembly, the)
nmod(part, assembly)
HERE
,
<<<HERE
root(ROOT-0, install-1)
det(spacer-3, the-2)
dobj(install-1, spacer-3)
case(washer-PLURAL-7, between-4)
det(washer-PLURAL-7, the-5)
nummod(washer-PLURAL-7, two-6)
nmod(spacer-3, washer-PLURAL-7)
HERE
,
<<<HERE
det(fume-PLURAL, the)
nsubj(dangerous, fume-PLURAL)
case(material, from)
det(material, this)
nmod(fume-PLURAL, material)
cop(dangerous, are)
root(ROOT, dangerous)
case(skin, to)
det(skin, the)
nmod(dangerous, skin)
HERE
,
<<<HERE
det(stay, the)
compound(stay, side)
nsubj(holds, stay)
root(ROOT, holds)
det(leg, the)
amod(leg, main)
compound(leg, gear)
dobj(holds, leg)
HERE
,
<<<HERE
root(ROOT, Obey)
det(instructions, the)
compound(instructions, safety)
dobj(Obey, instructions)
advmod(turn-7, when-5)
nsubj(turn-7, you-6)
advcl(Obey-1, turn-7)
det(valves-9, the-8)
dobj(turn-7, valves-9)
HERE
);

function parse_deps($deps) {
    $result=array();
    $lines = explode("\n", $deps);
    foreach($lines as $line){
        $parts=preg_split("/(\(|,|\))\s*/", $line);
        if(count($parts) > 1){
            $result[] = array($parts[0], $parts[1], $parts[2]);
        }
    }
    return $result;
}


$gen = SimpleTechnicalEnglish::NewSealed();

if($argc>1 && $argv[1] != "test") {
    // load file
    $lines = file($argv[1]);
    $deps = array();
    $current = "";
    foreach($lines as $line) {
        if($line == "\n"){
            $deps[] = $current;
            $current = "";
        }else{
            $current .= $line;
        }
    }
    foreach($deps as $task){
        print ucfirst($gen->generate(parse_deps($task))).".\n";
    }
}else{
    foreach($examples as $example){
        print ucfirst($gen->generate(parse_deps($example))).".\n";
    }
}
//print_r($gen->semantics());


