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

namespace NLGen\Grammars\Availability;

use NLGen\Generator;

class AvailabilityGrammar extends Generator {

    // coarseness 
    public const SUCCINCT = 0;
    public const BASE     = 1;
    public const SPECIFIC = 2;
    public const EXACT    = 3;

    public const COARSENESS = [ "succint", "base", "specific", "exact" ];

    // generate availability, busyList is an array of triples of int (dow=day-of-the-week),
    // start-time (pair int for hour, minute) and end-time (pair of ints),
    // ranges is an array from dow to tuple start-time, end-time (earliest start and end of
    // meetings for that day),
    // coarseness is one of the above constant.
    public function generateAvailability($busyList, $ranges, $coarseness=self::BASE, $context) {
        return $this->generate([ 'busyList' => $busyList, 'ranges' => $ranges, 'coarseness' => $coarseness ], $context);
    }

    function top($data) {
        $busyList = $data['busyList'];
        $ranges = $data['ranges'];
        $coarseness = $data['coarseness'];

        $this->context['coarseness'] = $coarseness;
        
        $table = $this->buildTable($busyList, $ranges);
        //print_r($table);
        $text = "";
        $sem = [];
        while($table) {
            $same_days_free     = $this->analyzeDays($table, $coarseness, true);
            $same_days_busy     = $this->analyzeDays($table, $coarseness, false);
            $same_segments_free = $this->analyzeSegments($table, $coarseness, true);
            $same_segments_busy = $this->analyzeSegments($table, $coarseness, false);

            // find message with larger number of minutes
            $max_mins = -1;
            $focused = null;
            // prefer segments and free lists
            foreach([ $same_segments_free, $same_segments_busy, $same_days_free, $same_days_busy ] as $messages){
                foreach($messages as $m) {
                    $mins = $m->minutes();
                    if($mins > $max_mins) {
                        $max_mins = $mins;
                        $focused = $m;
                    }
                }
            }

            if($text) {
                $text .= " ";
            }
            $start = strlen($text);
            $thisText = $this->focusedMessage($focused);
            echo "$thisText\n";
            $text .= $thisText;
            $sem = $focused->semantics();
            $end = strlen($text);
            $sem['offsetStart'] = $start;
            $sem['offsetEnd'] = $end;

            $table = $this->removeFocused($focused, $table); 
        }
        return [ 'text' => $text, 'sem' => $sem ];
    }

    //////////// grammar

    protected function focusedMessage($focused) {
        $text = "";
        if($focused->fullRange) {
            // day-centered
            $text="for days: " . $this->dows($focused->dows).";";
            foreach($focused->blocks as $block) {
                $text.= " " . $this->block($block);
            }
        }
        return $text;
    }

    protected function block($block) {
        $text = $this->timeRange($block->startTime, $block->endTime)." ".$this->lex->string_for_id("is");
        if($block->purity > 0.95){
            // perfect
        }elseif($block->purity > 0.75){
            $text .= " ".$this->lex->string_for_id("mostly");
        }else{
            $text .= " ".$this->lex->string_for_id("somewhat");
        }
        $text .= " ".$this->lex->string_for_id($block->isFree?"free":"busy");
        $text .= " ".$this->lex->string_for_id($block->isFree?"rest_busy":"rest_free");
        return [ 'text'=>$text, 'sem' => $block->semantics() ];
    }
    
    protected function dows($dows){
        if(count($dows) >= 5) {
            return $this->lex->string_for_id("all_week");
        }
        $elems=[];
        foreach($dows as $dow) {
            $elems[] = $this->lex->string_for_id("dow".$dow);
        }
        if(count($dows) == 1){
            return $elems[0];
        }
        $elems[count($elems)-1] = $this->lex->string_for_id("and"). " " .$elems[count($elems)-1];
        if(count($dows) == 2){
            return implode(" ", $elems);
        }else{
            return implode(", ", $elems); // oxford comma
        }
    }

    protected function timeRange($start, $end) {
        if($start[0] <= 9 && $end[0] <= 13 && $end[0] >= 12) {
            return $this->lex->string_for_id("morning");
        }
        if($start[0] >= 12 && $start[0] <= 13 && $end[0] >= 17) {
            return $this->lex->string_for_id("afternoon");
        }
        if($this->context['coarseness'] < 2) {
            if($start[0] > 9 && $end[0] < 12) {
                return $this->lex->string_for_id("mid_morning");
            }
            if($start[0] > 13 && $end[0] < 16) {
                return $this->lex->string_for_id("mid_afternoon");
            }
            if($end[0] < 10) {
                return $this->lex->string_for_id("early_morning");
            }
            if($start[0] > 15) {
                return $this->lex->string_for_id("late_afternoon");
            }
            // fall through
        }
        $text = $this->lex->string_for_id("from")." ".$this->hour($start);
        $text .= " ".$this->lex->string_for_id("to")." ".$this->hour($end);
        return [ 'text' => $text, 'sem' => [ $start, $end ] ];
    }

    protected function hour($hour) {
        $text = "";
        if($this->context['coarseness'] < 3) {
            if($hour[1] <= 5) {
                // exact
            }elseif($hour[1] > 5 && $hour[1] < 25) {
                $text .= $this->lex->string_for_id("around")." ";
            }elseif($hour[1] >= 25 && $hour[1] <= 35) {
                $text .= $this->lex->string_for_id("half past")." ";
            }else{
                $text .= $this->lex->string_for_id("late")." ";
            }
            $text .= strval($hour[0]);
        }else{
            $text .= sprintf("%d:%02d", $hour[0], $hour[1]);
        }
        if($hour[0] < 12){
            $text .= " AM";
        }else{
            $text .= " PM";
        }
        return [ 'text' => $text, 'sem' => $hour ];
    }

    function buildTable($busyList, $ranges) {
        $table = [];
        foreach($ranges as $dow => $startEnd) {
            $table[$dow] = [ new MeetingBlockMessage($startEnd[0], $startEnd[1], [$dow], true, 1.0, true) ];
        }
        foreach($busyList as $busy) {
            [$dow, $start, $end] = $busy;
            // find replacement index
            $found = -1;
            $foundEntry = null;
            $offset = 0;
            foreach($table[$dow] as $entry) {
                if($entry->includes($start, $end)) {
                    $found = $offset;
                    $foundEntry = $entry;
                    break;
                }
                $offset++;
            }
            if($found < 0) {
                // bug
                print_r($table);
                print_r($busy);
                die("not found!");
            }
            $splitted = $foundEntry->split($start, $end, false);
            /*echo "For " . $foundEntry . " split at ".sprintf("%d:%02d", $start[0], $start[1])."-".sprintf("%d:%02d", $end[0], $end[1])." got: ";
            foreach($splitted as $b){
                echo $b." ";
            }
            echo"\n";*/
            array_splice($table[$dow], $found, 1, $splitted);
        }
        //echo "Built:\n" . $this->tableToString($table)."\n";
        return $table;
    }

    //////////// analysis functions

    function analyzeDays($table, $coarseness, $isFree) {
        $distilled=[];
        foreach($table as $dow=>$entries) {
            $distilled[$dow] = $this->distillDay($dow, $entries, $coarseness, $isFree);
        }
        $result=[];
        while($distilled){
            $groupFocused = array_pop($distilled);
            $cluster = [ $groupFocused ];
            
            foreach($distilled as $focused){
                [ $compatible, $newGroupFocused ] = $this->compatible($groupFocused, $focused, $cluster, $coarseness);
                if($compatible) {
                    $groupFocused = $newGroupFocused;
                    $cluster[] = $focused;
                }
            }
            foreach($cluster as $focused){
                unset($distilled[$focused->dows[0]]);
            }
            $result[] = $groupFocused;
        }
        return $result;
    }

    function analyzeSegments($table, $coarseness, $isFree) {
        //TODO
        return [];
    }

    function distillDay(int $dow, array $entries, int $coarseness, bool $isFree) : FocusedSegmentMessage {
        $startTime = $entries[0]->startTime;
        $endTime = $entries[count($entries)-1]->endTime;
        $blocks=[];
        $previous = null;
        $gap = 0;
        foreach($entries as $block) {
            if($block->isFree != $isFree) {
                if($previous) {
                    // check if the gap can be absorbed due to coarseness
                    $gap += $block->minutes();
                    $absorbed = false;
                    switch ($coarseness) {
                    case self::EXACT: $absorbed = $gap <= 5; break;
                    case self::SPECIFIC: $absorbed = $gap <= 15; break;
                    case self::BASE: $absorbed = $gap <= 30; break;
                    case self::SUCCINCT: $absorbed = $gap <= 60; break;
                    }
                    if(! $absorbed) {
                        $blocks[] = $previous;
                        $previous = null;
                        $gap = 0;
                    }
                }
            }else{
                if($previous){
                    $covered0 = $previous->minutes() * $previous->purity;
                    $covered1 = $block->minutes() * $block->purity;
                    $new = new MeetingBlockMessage($previous->startTime, $block->endTime, [ $dow ], $isFree, 0,
                                                   $previous->startTime == $startTime && $block->endTime== $endTime);
                    $covered = $new->minutes();
                    $new->purity = $covered / ($covered0 + $covered1);
                    $previous = $new;
                    $gap = 0;
                }else{
                    $previous = $block;
                    $gap = 0;
                }
            }
        }
        if($previous){
            $blocks[] = $previous;
        }
        return new FocusedSegmentMessage($startTime, $endTime, [ $dow ], true, $blocks);
    }

    function distillSegment(array $startTime, array $endTime, array $entries, int $coarseness, bool $isFree) : FocusedSegmentMessage {
        //TODO
    }

    function compatible(FocusedSegmentMessage $current, FocusedSegmentMessage $other, array $cluster, int $coarseness) : array {
        // check the new member is close enough to all elements in the cluster
        foreach($cluster as $focused){
            if($this->incompatible($other->blocks, $focused->blocks, $coarseness)) {
                return [ false, null ];
            }
        }
        // build new message
        $newBlocks = [];
        $allOtherBlocksAsSet = $this->blocksToSet($other->blocks);
        
        foreach($current->blocks as $block) {
            // adjust purity based on $other blocks
            $thisBlockAsSet =  $this->blocksToSet([ $block ]);
            $intersection = $this->intersection($thisBlockAsSet, $allOtherBlocksAsSet);
            $size = count($thisBlockAsSet);
            $purity = $size > 0 ? $intersection * 1.0 / $size : 1.0;
            $newPurity = min($purity, $block->purity);
            $newBlocks[] = new MeetingBlockMessage($block->startTime, $block->endTime, array_merge($block->dows, [ $other->dows[0] ]),
                                                 $block->isFree, $newPurity, $block->fullRange);
        }        
        return [ true, new FocusedSegmentMessage($current->startTime, $current->endTime, array_merge($current->dows, $other->dows),
                                                 $current->fullRange, $newBlocks) ];
    }

    function incompatible(array $blocks1, array $blocks2, int $coarseness) : bool {
        $bl1s = $this->blocksToSet($blocks1);
        $bl2s = $this->blocksToSet($blocks2);

        $intersect = $this->intersection($bl1s, $bl2s);
        $union = count($bl1s) + count($bl2s) - $intersect;
        $jaccard = $union == 0 ? 1.0 : $intersect * 1.0 / $union;

        switch ($coarseness) {
        case self::EXACT: return $jaccard < 1.0; break;
        case self::SPECIFIC: return $jaccard < 0.98; break;
        case self::BASE: return $jaccard < 0.9; break;
        case self::SUCCINCT: return $jaccard < 0.6; break;
        }
        return true;
    }

    function blocksToSet(array $blocks) : array {
        $result = [];
        foreach($blocks as $block) {
            $time = $block->startTime[0];
            $time_m = $block->startTime[1];
            while($time != $block->endTime[0] && $time_m >= $block->endTime[1]){
                $next = $time;
                $next_m = $time_m + 5;
                if($next_m >= 60) {
                    $next += 1;
                    $next_m = 0;
                }
                $result[$time.":".$time_m."-".$next.":".$next_m."=".($block->isFree?"f":"b")] = 1;

                $time = $next;
                $time_m = $next_m;
            }
        }
        return $result;
    }

    function intersection(array $a, array $b) : int {
        $result = 0;
        foreach($a as $t => $x) {
            if(isset($a[$t])) {
                $result++;
            }
        }
        return $result;
    }

    function removeFocused(FocusedSegmentMessage $focused, array $table) : array {
        $block = new MeetingBlockMessage($focused->startTime, $focused->endTime, $focused->dows, false, 1.0, $focused->fullRange);

        //echo "Removing: " . strval($block)."\n";
        //echo "Before:\n".$this->tableToString($table);
        
        foreach($focused->dows as $dow) {
            $current = $table[$dow];
            $new = [];
            foreach($current as $entry) {
                if($block->includesOther($entry)) {
                    // consumed
                }elseif($entry->endTime[0] < $block->startTime[0] ||
                        ($entry->endTime[0] == $block->startTime[0] &&
                         $entry->endTime[1] < $block->startTime[1])){
                    // before
                    $new[] = $entry;
                }elseif($entry->startTime[0] < $block->startTime[0] ||
                        ($entry->startTime[0] == $block->startTime[0] &&
                         $entry->startTime[1] < $block->startTime[1])) {
                    // starts before
                    $new[] = new MeetingBlockMessage($entry->startTime, $other->startTime, $entry->dows, $entry->isFree, $entry->purity, false);
                    if ($entry->endTime[0] < $block->endTime[0] ||
                        ($entry->endTime[0] == $block->endTime[0] &&
                         $entry->endTime[1] <= $block->endTime[1])){
                        // ends during
                    }else{
                        // includes
                        $new[] = new MeetingBlockMessage($other->endTime, $entry->endTime, $entry->dows, $entry->isFree, $entry->purity, false);
                    }
                }elseif($entry->startTime[0] < $block->endTime[0] ||
                        ($entry->startTime[0] == $block->endTime[0] &&
                         $entry->startTime[1] < $block->endTime[1])) {
                    // starts during
                    $new[] = new MeetingBlockMessage($block->endTime, $entry->endTime, $entry->dows, $entry->isFree, $entry->purity, false);
                }else{
                    // it is after
                    $new[] = $entry;
                }
            }
            if($new) {
                $table[$dow] = $new;
            }else{
                unset($table[$dow]);
            }
        }
        //echo "\n\nAfter:\n".$this->tableToString($table)."\n";
        return $table;
    }

    function tableToString(array $table) : string {
        $result = "";
        foreach($table as $dow => $entries) {
            if($result) {
                $result .= "\n";
            }
            $es = [];
            foreach($entries as $entry){
                $es[] = strval($entry);
            }
            $result.="$dow: ".implode("; ", $es);
        }
        return $result;
    }
}
