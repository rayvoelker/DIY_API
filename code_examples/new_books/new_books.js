var api_base_url = 'http://library2.udayton.edu/api/newbooks.php?',
	json_data,
	lc = {"_comment":"This is a draft of Library of Congress Classifications represented in JSON format.","classifications":[{"letter":"A","title":"GENERAL WORKS"},{"letter":"B","title":"PHILOSOPHY. PSYCHOLOGY. RELIGION"},{"letter":"C","title":"AUXILIARY SCIENCES OF HISTORY"},{"letter":"D","title":"WORLD HISTORY AND HISTORY OF EUROPE, ASIA, AFRICA, AUSTRALIA, NEW ZEALAND, ETC."},{"letter":"E","title":"HISTORY OF THE AMERICAS"},{"letter":"F","title":"HISTORY OF THE AMERICAS"},{"letter":"G","title":"GEOGRAPHY. ANTHROPOLOGY. RECREATION"},{"letter":"H","title":"SOCIAL SCIENCES"},{"letter":"J","title":"POLITICAL SCIENCE"},{"letter":"K","title":"LAW"},{"letter":"L","title":"EDUCATION"},{"letter":"M","title":"MUSIC AND BOOKS ON MUSIC"},{"letter":"N","title":"FINE ARTS"},{"letter":"P","title":"LANGUAGE AND LITERATURE"},{"letter":"Q","title":"SCIENCE"},{"letter":"S","title":"AGRICULTURE"},{"letter":"T","title":"TECHNOLOGY"},{"letter":"U","title":"MILITARY SCIENCE"},{"letter":"V","title":"NAVAL SCIENCE"},{"letter":"Z","title":"BIBLIOGRAPHY. LIBRARY SCIENCE. INFORMATION RESOURCES (GENERAL)"}]};
		
function buildSelect() {
	// create the select options based upon our lc classifications
	select = document.getElementById('select')
	
	for ( var i in lc.classifications ){
		var option = document.createElement('option');
		option.value = lc.classifications[i].letter;
		option.innerHTML = lc.classifications[i].letter + ' : ' + lc.classifications[i].title;
		select.appendChild(option);
	}

	var option = document.createElement('option');
	option.value = 'null';
	option.innerHTML = 'NON-CLASSIFIED';
	select.appendChild(option);

	select.addEventListener("change", function() {
		//console.log('changed! ' + this.value);
		//remove all the values from the output area
		do_switch(this);
	});	
} //end function buildSelect

function blankOutput() {
	var output = document.getElementById('output');
	//remove all items from the output div
	while (output.firstChild) {
		output.removeChild(output.firstChild);
	}
	output.innerHTML = 'Loading ...';
	
} //end function blankOutput

//function
function do_switch(element){
	blankOutput();
	switch(element.value) {
		case 'ALL' :
			var body = document.getElementsByTagName('body')[0];
			var script = document.createElement('script');
			script.setAttribute('src',api_base_url + 
				'&callback=newbooks'
			);
			body.appendChild(script);
			console.log('ALL');
			break;
		default:
			var body = document.getElementsByTagName('body')[0];
			//get the index of the letter (this isn't really used now, but maybe if we build it different, this will be useful)
			var index = lc.classifications.map(function(d) { 
				return d['letter']; 
			}).indexOf(element.value);
			
			var script = document.createElement('script');
			script.setAttribute('src',api_base_url + 
				'&callback=newbooks' + 
				'&call_number_prefix=' + element.value
			);
			body.appendChild(script);
			break;
	} // end switch
} //end function do_switch

//our callback function will take the data sent back from our API and do something with it.
function newbooks(data) {
	var output = document.getElementById('output'),
		extra_info = document.getElementById('extra_info');
	json_data = data;
	/*
	if(json_data === undefined) {
		//set the json data so that it's accessible outside of this function 
		json_data = data;
	}
	*/
	
	blankOutput();
	//debugger;
	//if we have no results from the query, display a message and return
	if(data[0].num_results == 0) {
		output.innerHTML = 'No Results';
		extra_info.innerHTML = ' (' + data[0].num_results + ' new books) ';
		return(0);
	}
	else {
		output.innerHTML = '';
	}
	
	if(data && output){
		//we got data, so remove the loading text
		extra_info.innerHTML = ' (' + data[0].num_results + ' new books) ';
		
		var dateBegin = new Date(json_data[0].dateBegin);
		var dateEnd = new Date(json_data[0].dateEnd);
		
		extra_info.innerHTML += (dateBegin.getMonth() + 1) + '/' + dateBegin.getDate() + '/' + dateBegin.getFullYear() + ' to '
			+ (dateEnd.getMonth() + 1) + '/' + dateEnd.getDate() + '/' + dateEnd.getFullYear();
				
		for (var i=0;i<data.length;i++) {
			var node = document.createElement('div');
			
			// output call number if there is a call number in the data
			node.innerHTML += (data[i].call_number_norm !== null ? (data[i].call_number_norm + '<br />\n') : '');
			
			// output author if there is an author in the data
			node.innerHTML += ( (data[i].best_author !== null && data[i].best_author != '' ) ? (data[i].best_author  + '<br />\n') : '');
			
			// output title and the link to the catalog record if there are title, and record_num in the data
			node.innerHTML += ( (data[i].best_title !== null && data[i].record_num !== null) ? ('<a target="_blank" href="http://flyers.udayton.edu/record=b' 
				+ data[i].record_num + '">'
				+ data[i].best_title 
				+ '</a><br />\n') : '');
			
			// output publish year if there is a publish year in the data
			node.innerHTML += (data[i].publish_year ? (data[i].publish_year + '<br />\n') : '');
			
			node.innerHTML += '\n<br />\n<hr>\n</br />'
			
			//append the node to the output div
			output.appendChild(node);
		}//end for
	}//end if
			
} //end function newbooks