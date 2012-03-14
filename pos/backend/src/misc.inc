<?php

function export_csv($filename, $data) {
	$fp = fopen('php://temp', 'r+');

	if (!$fp) {
		echo "Error opening temp file pointer.";
		exit;
	}

	foreach ($data as $datline) {
		if (!fputcsv($fp, $datline)) {
			echo "Error writing csv line.";
			exit;
		}
	}

	// reset file pointer, pull out the string
	rewind($fp);

	$csv = "";
	while (($line = fgets($fp)) != false) {
		$csv .= $line;
	}

	header('Content-type: text/csv');
	header('Content-disposition: attachment;filename='.$filename);
	echo $csv;

	exit;
}


?>
