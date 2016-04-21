<?php

// depending on your institution, you will probably have to 
// modify this to work with your set / sets of barcodes
// we want to check imported barcodes in order to prevent 
// SQL injections  
function valid_barcode($string_barcode) {
	
	//our barcodes are constructed 'r' + number
	// check accordingly
		
	//get the first, character part of the barcode
	$barcode_letter = substr($string_barcode,0,1);
	//get the second, number part of the barcode
	$barcode_number = substr($string_barcode,1);
	
	if ( 
		( $barcode_letter === "R" or $barcode_letter === "r" )
		AND 
		( is_numeric($barcode_number) )
	) {
		return true;
	}
	else {
		return false;
	}
}// end function valid_barcode

//read the raw body contents
$str_json = file_get_contents('php://input');

$data = json_decode($str_json);
$data_input_size = count($data);

if ($data !== NULL) {
	
	//start building the query for the list of barcodes to record numbers
	$query = "
	SELECT

	'i' || i.record_num || 'a' AS item_record_num,
	-- r.item_num,
	i.record_num as item_num,
	'b' || b.record_num || 'a' as bib_record_num,
	b.record_num as bib_num, 
	i.creation_date_gmt as item_creation_date,
	i.deletion_date_gmt as item_deletion_date,
	b.creation_date_gmt as bib_creation_date,
	b.deletion_date_gmt as bib_deletion_date

	FROM

	sierra_view.phrase_entry			AS e

	LEFT OUTER JOIN sierra_view.record_metadata	AS i
	ON
	  e.record_id = i.id
	  
	LEFT OUTER JOIN sierra_view.bib_record_item_record_link	AS l
	ON
	  i.id = l.item_record_id
	  
	-- now get the bib record num
	LEFT OUTER JOIN sierra_view.record_metadata	as b
	ON
	  l.bib_record_id = b.id

	WHERE
	(e.index_tag || e.index_entry) in 

	(
	";
	
	//collect invalid barcodes
	$invalid_barcodes = [];
	
	//we expect just a 1D array back
	foreach ($data as $value) {
		// do our check of the string here
		if( ($value != "") AND ( valid_barcode($value) ) ) {
			$query .= "'b" . strtolower( $value ) . "',\n";
		} // end if
		//else we don't put this barcode in the list and we 
		//collect it in our array of invalid barcodes;
		else {
			array_push($invalid_barcodes, $value);
		}
	} //foreach
	//trim the last , from the string
	$query = rtrim($query, ",\n");
	
	//finish writing our query ...
	$query .= ");";
}
else {
	die();
}

//now that we haven't died, run our constructed query
//reset all variables needed for our connection
$username = null;
$password = null;
$dsn = null;
$connection = null;

require_once('/home/library/includes/sql/sqlinv_group.php');

//make our database connection
try {
	// $connection = new PDO($dsn, $username, $password, array(PDO::ATTR_PERSISTENT => true));
	$connection = new PDO($dsn, $username, $password);
}

catch ( PDOException $e ) {
	$row = null;
	$statement = null;
	$connection = null;

	echo "problem connecting to database...\n";
	error_log("{\"error\" : \"PDO Exception: " . $e->getMessage() . "\"}");
	exit(1);
}

//set output to utf-8
$connection->query('SET NAMES UNICODE');
$statement = $connection->prepare($query);
$statement->execute();
$row = $statement->fetchAll(PDO::FETCH_ASSOC);

//collect additional data for our final JSON object
$encode_array['data_input_size'] = $data_input_size;
$encode_array['data_output_size'] = count($row);
$encode_array['invalid_barcodes'] = $invalid_barcodes;

$encode_array['data'] = $row;

header('Content-Type: application/json');
echo json_encode($encode_array);

?>
