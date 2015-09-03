var json_data;
//our callback function will take the data sent back from our API and do something with it.
function studyrooms(data) {
	json_data = data;
	//find the output table
	var table = document.getElementById('output_table').getElementsByTagName('tbody')[0];
	if(data && table){
		//remove all the child elements, if there are any.
		while (table.firstChild) {
			table.removeChild(table.firstChild);
		}
		
		for (var i=0;i<data.length;i++) {
			var row = table.insertRow(),
			cell0 = row.insertCell(0),
			cell1 = row.insertCell(1),
			cell2 = row.insertCell(2),
			cell0_text = document.createTextNode( data[i].best_title ),
			cell1_text = document.createTextNode( data[i].available ? 'YES' : 'NO' );
			cell2_text = document.createTextNode( data[i].due_gmt ? data[i].due_gmt : '-' );

			cell0.appendChild(cell0_text);
			cell1.appendChild(cell1_text);
			cell2.appendChild(cell2_text);
			
			if(!data[i].available) {
				cell0.style['background-color'] = 'silver';
				cell1.style['background-color'] = 'silver';
				cell2.style['background-color'] = 'silver';
			}
		}//end for
		
		var load_time = document.getElementById('load_time');
		load_time.innerHTML = ' [ page loaded at ' +
			Date(data[0].created * 1000).toString() +
			' ]';
		
	}//end if
} //end function