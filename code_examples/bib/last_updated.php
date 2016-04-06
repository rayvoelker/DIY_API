<?php

//we don't need to do any input sanitization, since there are no inputs

/*
include file (sqlinv_group.php) supplies the following
arguments as the example below illustrates what the contents of the file looks like :
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

require_once($_SERVER['DOCUMENT_ROOT'] . '/../includes/sql/sqlinv_group.php');

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
	error_log('PDO Exception: '.$e->getMessage());
	exit(1);
}

//set output to utf-8
$connection->query('SET NAMES UNICODE');

// defines the number of records we want returned from the SQL
$records_returned = 20;

$sql = '
-- last ' . $records_returned . ' number of bib records updated in the system
SELECT

-- concatinates all the fields that go into creating the record number 
b.record_type_code || b.record_num || \'a\' AS bib_number,

-- throw in just the record number if that may be more useful, 
-- and a link to the bib in the webpac just to be fancy
b.record_num,
b.record_last_updated_gmt, b.previous_last_updated_gmt,
\'https://flyers.udayton.edu/record=b\' || b.record_num AS webpac_link,

-- these are the creation date of the record itself, and then the date the record was "cataloged"
b.creation_date_gmt, br.cataloging_date_gmt, 
b.num_revisions,
p.best_title, p.best_author

FROM 
sierra_view.record_metadata	AS b

JOIN
sierra_view.bib_record 		AS br
ON
  b.id = br.record_id

JOIN
sierra_view.bib_record_property	AS p
ON
  b.id = p.bib_record_id

WHERE
b.record_type_code = \'b\' -- limit to bib records
and b.record_last_updated_gmt is not null -- must have been updated

-- records should not be deleted
and b.deletion_date_gmt is null 

-- ensure that the record belongs to us
and b.campus_code = \'\' 


-- we can now order the results by when the records were last updated
ORDER BY
b.record_last_updated_gmt desc,
p.best_title_norm

-- adjust this limit for how many records you would like back
LIMIT 
' . $records_returned . '
'
;

$statement = $connection->prepare($sql);
$statement->execute();
$return_array = $statement->fetchAll(PDO::FETCH_ASSOC);

$encode_array = [];
$encode_array['description'] = 'fetches info about the last ' 
	. $records_returned . 
	' bib records last updated in the system';
$encode_array['data'] = $return_array;
$encode_array['query'] = $sql;

//$row['query'] = $sql;

header('Content-Type: application/json');
echo json_encode($encode_array, JSON_PRETTY_PRINT);

$row = null;
$statement = null;
$connection = null;

?>
