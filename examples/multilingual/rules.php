<?php
    if(  $data["culture_feries"] > 2 && $data["culture_dimanche"] == 52  ){
      $result[] = new Predicate("pct_change", array("reading", "10"));
    }
    if(  $data["culture_feries"] + $data["culture_dimanche"] < 30  ){
      $result[] = new Predicate("pct_change", array("reading", "-10"));
    }
    if(  $data["culture_spectacles"] < 50  ){
      $result[] = new Predicate("on_strike", array(new Predicate("employee", array("maison_culture"))));
    }
    if(  $data["culture_spectacles"] < 100  ){
      $result[] = new Predicate("benchmarked", array("bottom", "culture", "city"));
    }
    if(  $data["culture_spectacles"] > 140  ){
      $result[] = new Predicate("benchmarked", array("top", "culture", "city"));
    }
    if(  $data["culture_spectacles"] > 160  ){
      $result[] = new Predicate("benchmarked", array("top", "culture", "province"));
    }
    if(  $data["baignade_interieures"] < -500  ){
      $result[] = new Predicate("on_strike", array(new Predicate("employee", array("heated_pool"))));
    }
    if(  $data["baignade_interieures"] > 500  ){
      $result[] = new Predicate("benchmarked", array("top", "sport", "city"));
    }
    if(  $data["deneigement_chargements"] == 5  ){
      $result[] = new Predicate("pct_change", array("car_accident", "5"));
    }
    if(  $data["deneigement_chargements"] == 7  ){
      $result[] = new Predicate("benchmarked", array("top", "snow_removal", "province"));
    }
    if(  $data["deneigement_chargements"] == 5 && $data["deneigement_findesemaine"] < 3  ){
      $result[] = new Predicate("pct_change", array("car_accident", "10"));
    }
    if(  $data["routier_nidsdepoule"] > 10  ){
      $result[] = new Predicate("pct_change", array("car_accident", "-5"));
    }
    if(  $data["routier_nidsdepoule"] > 20  ){
      $result[] = new Predicate("benchmarked", array("top", "street_care", "city"));
    }
    if(  $data["routier_nidsdepoule"] > 25  ){
      $result[] = new Predicate("benchmarked", array("top", "street_care", "province"));
    }
