// ==UserScript==
// @name           BudgetPlateau commentary
// @namespace      ca.duboue
// @description    Budget Commentary Generator
// @include        http://budgetplateau.com/
// ==/UserScript==

function pad_commentary_update(){
    // collect the json file to send

    var elems = [ "culture_feries", "culture_dimanche", "culture_spectacles", "baignade_interieures", 
		  "deneigement_chargements", "deneigement_findesemaine", "routier_nidsdepoule" ];

    var json = "{ ";
    for(var i=0;i<elems.length;i++){
	var elem = document.getElementById(elems[i]);
	json = json + ' "' + elems[i] + '" : "' + elem.value + '"';
	if(i < elems.length - 1){
	    json = json + ", ";
	}
    }
    json = json +" }";

    var lang="en";
    rb = document.getElementsByName("padlang");
    for (i = 0; i <rb.length; i++) {
	if (rb[i].checked) {
	    lang = rb[i].value; 
	}
    }
    
    //fetch the output
    var response = GM_xmlhttpRequest({
	method: "POST",
	url: "http://cheron.ca/pablo/demo/budget/driver.php?lang=" + lang,
	data: json,
	synchronous: "true",
	headers: {
	    "Content-Type": "text/json"
	}
    });
    var div = document.getElementById('padcommentary');
    div.innerHTML = response.responseText.replace(/\./g, '. <br/>');
}

padbox = document.createElement('ul');
padbox.style.position = 'fixed';
padbox.style.top = '10px';
padbox.style.left = '10px';
padbox.style.padding = '20px';
padbox.style.backgroundColor = '#ccc';
button = document.createElement('input');
button.type = 'button';
button.value = 'commentary';
button.addEventListener('click',pad_commentary_update);
padbox.appendChild(button);
padbox.appendChild(document.createElement('br'));
radio0 = document.createElement('input');
radio0.type="radio";
radio0.name="padlang";
radio0.value="en";
radio0.checked=1;
padbox.appendChild(radio0);
padbox.appendChild(document.createTextNode("English"));
radio1 = document.createElement('input');
radio1.type="radio";
radio1.name="padlang";
radio1.value="fr";
padbox.appendChild(radio1);
padbox.appendChild(document.createTextNode("Français"));
div = document.createElement('div');
div.id = "padcommentary";
padbox.appendChild(document.createElement('br'));
padbox.appendChild(div);
body = document.getElementsByTagName('body')[0];
body.appendChild(padbox);
