<?php
namespace NLGen\Grammars\Availability;
class AvailabilityGenerator extends AvailabilityGrammar {
  public function __construct() {
    parent::__construct(null,<<<'EOD_LEX'
{
    "all_week":"all week"
    ,"all_day":"all day"
    ,"dow0":"Monday"
    ,"dow1":"Tuesday"
    ,"dow2":"Wednesday"
    ,"dow3":"Thursday"
    ,"dow4":"Friday"
    ,"dow5":"Saturday"
    ,"dow6":"Sunday"
    ,"and":"and"
    ,"morning":"in the morning"
    ,"afternoon":"in the afternoon"
    ,"mid_morning":"in the mid-morning"
    ,"mid_afternoon":"in the mid-afternoon"
    ,"early_morning":"in the early morning"
    ,"late_afternoon":"in the late afternoon"
    ,"from":"from"
    ,"to":"to"
    ,"around":"around"
    ,"late":"late"
    ,"half_past":"half past"
    ,"be":[  { "string":"is", "number":"sg" }
            ,{"string":"are","number":"pl" } ]
    ,"mostly":[ { "string": "mostly", "likelihood": 2.0}, {"string":"quite"}]
    ,"somewhat":"somewhat"
    ,"also":"also"
    ,"free_choice":[{ "string": "free"} , {"string":"available"}]
    ,"busy_choice":[{"string":"busy"},{"string":"unavailable"},{"string":"taken"},{"string":"committed"}]
    ,"free":{ "string": "free"}
    ,"busy":{"string":"busy"}
    ,"rest_free":"the rest is free"
    ,"rest_busy":"the rest is busy"
}


EOD_LEX);
  }
  function focusedMessage_orig($params){
    return AvailabilityGrammar::focusedMessage($params[0]);
  }

  function focusedMessage($p0){
    if(isset($this->context['debug'])) {
      error_log(print_r(func_get_args(),true));
    }
    return $this->gen("focusedMessage_orig", func_get_args(), "focusedMessage");
  }

  function purity_orig($params){
    return AvailabilityGrammar::purity($params[0]);
  }

  function purity($p0){
    if(isset($this->context['debug'])) {
      error_log(print_r(func_get_args(),true));
    }
    return $this->gen("purity_orig", func_get_args(), "purity");
  }

  function block_orig($params){
    return AvailabilityGrammar::block($params[0],$params[1],$params[2]);
  }

  function block($p0,$p1,$p2){
    if(isset($this->context['debug'])) {
      error_log(print_r(func_get_args(),true));
    }
    return $this->gen("block_orig", func_get_args(), "block");
  }

  function dows_orig($params){
    return AvailabilityGrammar::dows($params[0]);
  }

  function dows($p0){
    if(isset($this->context['debug'])) {
      error_log(print_r(func_get_args(),true));
    }
    return $this->gen("dows_orig", func_get_args(), "dows");
  }

  function timeRange_orig($params){
    return AvailabilityGrammar::timeRange($params[0],$params[1]);
  }

  function timeRange($p0,$p1){
    if(isset($this->context['debug'])) {
      error_log(print_r(func_get_args(),true));
    }
    return $this->gen("timeRange_orig", func_get_args(), "timeRange");
  }

  function hour_orig($params){
    return AvailabilityGrammar::hour($params[0]);
  }

  function hour($p0){
    if(isset($this->context['debug'])) {
      error_log(print_r(func_get_args(),true));
    }
    return $this->gen("hour_orig", func_get_args(), "hour");
  }

  protected function is_sealed() { return TRUE; }
}
