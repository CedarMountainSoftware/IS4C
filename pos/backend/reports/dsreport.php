<?php
/*******************************************************************************

    Copyright 2011 Missoula Food Co-op, Missoula, Montana.

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require_once('../src/formlib.inc');
require_once('../src/htmlparts.php');
require_once('../src/misc.inc');

require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");

$page_title = "Reporting";
$header = "DoubleSnap Report";
// include ('../src/header.html');

$html='<!DOCTYPE HTML>
<html>
<head>';

$html .= '<script type="text/javascript" src="../lib/jquery.js"></script>';
$html .= '<script type="text/javascript" src="../lib/jquery-ui.js"></script>';
$html .= <<<SCRIPT
<script language="Javascript">
$(document).ready(function() {
	$('#startdate').datepicker({dateFormat: 'yy-mm-dd'});
	$('#enddate').datepicker({dateFormat: 'yy-mm-dd'});
});

</script>

<link type="text/css" rel="stylesheet" href="../lib/jquery-ui.css" />

SCRIPT;
	
$html.=head();
	
// $html .= '<script src="../src/CalendarControl.js" language="javascript"></script>';

$html.='
	<title>IS4C - DoubleSnap Report</title>
</head>
<body>';
	
$html.=body();


if (isset($_POST['submit'])) {
	// do report
	// show how many of each product sold within given dates
	// dlog.upc
	$startdate = $_REQUEST['startdate'];
	$enddate = $_REQUEST['enddate'];
	$csvexport = $_REQUEST['csvexport'] ? 1 : 0;

	$csvdata = array();

	$link = mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
	if (!$link) {
		echo "couldn't connect to is4c_log.";
		exit;
	}
	$success = mysql_select_db('is4c_log', $link);
	if (!$success) {
		echo "Couldn't select log db: " . mysql_error();
		exit;
	}

/*
	$link = mysql_connect("localhost", "backend", "ils4cbackend");
	if (!$link) {
		echo "couldn't connect to is4c_op.";
		exit;
	}
	$success = mysql_select_db('is4c_op', $link);

	if (!$success) {
		echo "Couldn't select op db: " . mysql_error();
		exit;
	} */

	$ourwhere = "";
//	$ourwhere .= " trans_status <> 'D' AND trans_status <> 'X' ";
	$ourwhere .= " trans_type = 'T' AND trans_subtype = 'DS' ";

	if ($startdate && $enddate) {
		$ourwhere .= " AND `datetime` >= '" . mysql_real_escape_string($startdate) . " 00:00:00' AND `datetime` <= '" . mysql_real_escape_string($enddate) . " 23:59:59'";
	}
	$query = "SELECT di.datetime AS tdate, card_no AS member_no, memb.FirstName AS fname, memb.LastName AS lname, di.description AS description, di.total AS total FROM dtransactions di LEFT JOIN is4c_op.members memb ON di.card_no = memb.CardNo WHERE " . $ourwhere . " ORDER BY `datetime` ASC";

	error_log("dsreport.php running query: " . $query);
	$res = mysql_query($query, $link);
	if (!$res) {
		echo "error: " . mysql_error() . "<br />\n";
	}

	$html .= "<h2>DoubleSnap Report</h2>";

	if ($startdate && $enddate) {
		$html .= "<h3>$startdate - $enddate</h3>";
	} else {
		$html .= "<h3>All Records</h3>";
	}

	$firstvendor = true;
	$firstcat = true;
	$dept_total = 0;	
	$html .= '<table border="1">';
	$html .= '<tr><th align="left">Date</th><th align="left">Description</th><th align="left">Member #</th><th align="left">Name</th><th align="left">DS Tender</th></tr>';
	while ($row = mysql_fetch_assoc($res)) {
		error_log("got row: " . var_export($row, true));

		$datetime = $row['tdate'];
		$desc = $row['description'];
		$member_no = $row['member_no'];
		$fname = $row['fname'];
		$lname = $row['lname'];
		$tender = number_format(-1 * $row['total'], 2);

		$name = $fname . ' ' . $lname;


		if (!$csvexport)
			$html .= tablerow($datetime, $desc, $member_no, $name, $tender);
		else
			$csvdata[] = array($datetime, $desc, $member_no, $name, $tender);
	}


	if ($csvexport) {
		$filename = "doublesnap_report_" . $startdate . '_to_' . $enddate . ".csv";
		export_csv($filename, $csvdata);
		exit;
	}

	$html .= "</table>";

} else {
	$html .= startform();
	$html .= "<table>";
	$html .= tablerow("Start Date :", textbox("startdate", "", "14", "20", array("onclick" => "showCalendarControl(this)")). " (YYYY-MM-DD)");
	$html .= tablerow("End Date :", textbox("enddate", "", "14", "20", array("onclick" => "showCalendarControl(this)")). " (YYYY-MM-DD)");
	$html .= tablerow("<label for=\"csvexport\">CSV Export</label>: " , checkbox("csvexport", 0));
	$html .= hiddeninput("submit", "1");
	$html .= "</table>";
	$html .= '<input type="submit" value="Run Report" />';
	$html .= endform();

}

$html.=foot();
	
$html.='
	</body>
</html>';


echo $html;

?>
