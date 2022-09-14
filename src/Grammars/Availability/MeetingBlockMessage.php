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

require_once __DIR__ . "/util.php";

class MeetingBlockMessage {

    public array $startTime; // array int (hour), int (minute)
    public array $endTime;
    public array $dows; // array int (day-of-the-week)
    public bool $isFree; // true if this blocks means free time
    public float $purity; // percentage of the block that is completely free or not free (depending on $isFree)
    public bool $fullRange; // do the start and end cover the full range of the day?

    public function __construct(array $startTime, array $endTime, array $dows, bool $isFree, float $purity, bool $fullRange) {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->dows = $dows;
        $this->isFree = $isFree;
        $this->purity = $purity;
        $this->fullRange = $fullRange;
    }

    public function includesOther(MeetingBlockMessage $other) : bool {
        return ($this->startTime[0] < $other->startTime[0] || (
            $this->startTime[0] == $other->startTime[0] &&
            $this->startTime[1] <= $other->startTime[1])) &&
            ($this->endTime[0] > $other->endTime[0] ||
             ($this->endTime[0] == $other->endTime[0] &&
              $this->endTime[1] >= $other->endTime[1]));
    }

    public function includes(array $start, array $end) : bool {
        return ($this->startTime[0] < $start[0] || (
            $this->startTime[0] == $start[0] &&
            $this->startTime[1] <= $start[1])) &&
            ($this->endTime[0] > $end[0] ||
             ($this->endTime[0] == $end[0] &&
              $this->endTime[1] >= $end[1]));
    }

    public function splitOther(MeetingBlockMessage $other) : array {
        $result = [];

        if($this->startTime == $other->startTime) {
            if($this->endTime == $other->endTime) {
                return $other;
            }
        }else{
            $result[] = new MeetingBlockMessage($this->startTime, $other->startTime, $this->dows, $this->isFree, $this->purity, false);
        }
        $result[] = $other;
        if($this->endTime != $other->endTime) {
            $result[] = new MeetingBlockMessage($other->endTime, $this->endTime, $this->dows, $this->isFree, $this->purity, false);
        }

        return $result;
    }
    
    public function split(array $start, array $end, bool $isFree) : array {
        return $this->splitOther(new MeetingBlockMessage($start, $end, $this->dows, $isFree, $this->purity, false));
    }

    public function semantics(): array {
        return [ 'startTime' => $this->startTime,
                 'endTime' => $this->endTime,
                 'dows' => $this->dows,
                 'isFree' => $this->isFree,
                 'purity' => $this->purity,
                 'fullRange' => $this->fullRange
        ];
    }

    public function minutes() : int {
        return minDiff($this->startTime, $this->endTime) * count($this->dows);
    }

    public function __toString() : string {
        $dowS =[];
        foreach($this->dows as $dow){
            $dowS[] = strval($dow);
        }
        
        return ($this->isFree?"FREE([":"BUSY([").implode(",",$dowS)."]=".sprintf("%d:%02d",$this->startTime[0],$this->startTime[1])."-".
                                               sprintf("%d:%02d", $this->endTime[0],$this->endTime[1])." ".sprintf("%3d",$this->purity*100).
                                               ($this->fullRange?" fullrange":"").")";
    }
}
