<?php

/*
// 
// Ray Voelker
// University of Dayton Libraries
// 300 College Park Dayton, OH 45419-1360
// rvoelker1@udayton.edu
// ray.voelker@gmail.com
//  If you have any questions or comments 
//  about this script, page or feature, please
//  feel free to contact me.
//
*/

//replace all the non alphanumeric characters from the callback function name
if(isset( $_GET['callback'] )) {
	$callback = preg_replace("/[^a-zA-Z0-9\s]/", "", $_GET['callback']);
}
else {
	$callback = null;
}

if(isset( $_GET['call_number_prefix'] )) {
	//call number prefix can be at most 3 characters, and must only consist of letters
	// ... unless we are requesting something else from the prefix ... like the null values for example
	switch($_GET['call_number_prefix']) {
		case 'null' :
			$call_number_prefix = null;
			$call_number_prefix_sql = 'AND x.call_number_prefix is null';
			break;
		case 'ALL' :
		case 'all' :
		case 'All' :
			break;
		default :
			$call_number_prefix = substr( 
				preg_replace("/[^a-zA-Z\s]/", "", $_GET['call_number_prefix']), 
				0,3
			);
			break;
	} //end switch
	
	
}
else{
	$call_number_prefix = null;
	$call_number_prefix_sql = null;
}
	

//get the dates needed for the query
$date_object = new DateTime();
$date_now = $date_object->format('Y-m-d');
$date_object->sub(new DateInterval('P30D')); //substract 30 day
$date_past = $date_object->format('Y-m-d');
$time_start = microtime(true);

/*
include file (newbooks.php) supplies the following arguments as the 
example below illustrates :
	$username = "username";
	$password = "password";

	$dsn = "pgsql:"
		. "host=sierra-db.school.edu;"
		. "dbname=iii;"
		. "port=1032;"
		. "sslmode=require;"
		. "charset=utf8;"
*/

//reset all variables needed for our connection
$username = null;
$password = null;
$dsn = null;
$connection = null;

require_once($_SERVER['DOCUMENT_ROOT'] . '/../includes/newbooks.php');

try {
	$connection = new PDO($dsn, $username, $password);
}

catch ( PDOException $e ) {
	$connection = null;
	echo "problem connecting to database...\n";
	error_log('PDO Exception: '.$e->getMessage());
	$connection = null;
	exit(1);
}

//set output to utf-8
$connection->query('SET NAMES UNICODE');

/* 
-- The select statement:
-- UPPER( COALESCE (i.call_number_norm, x.call_number_prefix) ) as call_number_norm
-- Will return what seems to be the first letter of the call number from the bib record.
--   If there is no call number, then at least display the call_number_prefix, in place of 
--   call_number_norm. Having no call number seems to be the case mostly for electronic
--   resources that we probably still want to display.
-- Also note, that in least our institutions case, sudoc numbers seem to be indexed 
--   into the call_number_norm field, so we sort on call_number_prefix FIRST
*/
$sql  = '
SELECT
b.record_id,
b.cataloging_date_gmt::DATE AS cataloging_date_gmt,
p.best_author,
p.best_title,
p.publish_year,
r.record_num,
UPPER( x.call_number_prefix ) as call_number_prefix,
UPPER( COALESCE (i.call_number_norm, x.call_number_prefix) ) as call_number_norm

FROM
sierra_view.bib_record					AS b
JOIN
sierra_view.record_metadata				AS r
  ON r.id = b.record_id
LEFT OUTER JOIN
sierra_view.bib_record_call_number_prefix	AS x
  ON x.bib_record_id = b.record_id
LEFT OUTER JOIN
sierra_view.bib_record_property			AS p
  ON p.bib_record_id = b.record_id
LEFT OUTER JOIN
sierra_view.bib_record_item_record_link	AS l	
  ON l.bib_record_id = b.record_id
LEFT OUTER JOIN
sierra_view.item_record_property		AS i
  ON l.item_record_id = i.item_record_id

WHERE       
b.cataloging_date_gmt >= date(\'' .  $date_past . '\') 
AND b.cataloging_date_gmt <  date(\'' . $date_now . '\') 
AND b.is_suppressed is FALSE
-- if we want to do any call number limiting ... we can use this part of the where clause
';

if($call_number_prefix) {
	$sql .= 'AND x.call_number_prefix LIKE LOWER(\'' . $call_number_prefix . '\')';
}
else if( $call_number_prefix_sql ) {
	$sql .= $call_number_prefix_sql;	
}

$sql .= '
GROUP BY
b.record_id,
cataloging_date_gmt,
p.best_author,
p.best_title,
p.publish_year,
r.record_num,
call_number_prefix,
call_number_norm

ORDER BY
call_number_prefix,
call_number_norm
';

$statement = $connection->prepare($sql);
$statement->execute();
$row = $statement->fetchAll(PDO::FETCH_ASSOC);
$num_results = count($row);

//set some extra info into the first result... 
//  be careful, as this actaully creates a single result, even though there may be 
//  no actual results from the query
$row[0]['num_results'] = $num_results;
$row[0]['queryTime'] = ( microtime(true) - $time_start );
$row[0]['created'] = time();
$row[0]['dateBegin'] = $date_past;
$row[0]['dateEnd'] = $date_now;
$row[0]['call_number_prefix'] = $call_number_prefix;
$row[0]['query'] = $sql;

$statement = null;
$connection = null;

//wrap the data in the callback function if requested
if ( $callback ) {
	header('Content-Type: application/javascript');
	echo $callback . '(' . json_encode($row) . ')';
}
else {
	/* this will allow us to use Cross-Origin Resource Sharing (CORS) 
	and the JavaScript XMLHttpRequest object */
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');
	echo json_encode($row);
}

$row = null;
?>