<?php
/*******************************************************************************

    Copyright 2011 Missoula Food Co-op, Missoula, Montana.

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

$page_title = "Reporting";
$header = "Member Purchases Report";

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
	
$html.='
	<title>IS4C - Purchases Report</title>
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

	$link = mysql_connect("localhost", "backend", "is4cbackend");
	if (!$link) {
		echo "couldn't connect to is4c_log.";
		exit;
	}
	$success = mysql_select_db('is4c_log', $link);
	if (!$success) {
		echo "Couldn't select log db: " . mysql_error();
		exit;
	}

	$ourwhere = "";
	$ourwhere .= " trans_type = 'T' ";
	if ($startdate && $enddate) {
		$ourwhere .= " AND ( `datetime` >= '" . mysql_real_escape_string($startdate) . " 00:00:00' AND `datetime` <= '" . mysql_real_escape_string($enddate) . " 23:59:59' )";
	}
	$query = "select datetime, description, trans_subtype, total, card_no, memb.FirstName AS first, memb.LastName AS last, memb.addshopper, memb.sponsorCardNo, spons.FirstName as sponsfirst, spons.LastName as sponslast from dtransactions LEFT JOIN is4c_op.members memb ON dtransactions.card_no = memb.CardNo LEFT JOIN is4c_op.members spons ON memb.sponsorCardNo = spons.CardNo WHERE " . $ourwhere . " order by datetime ASC";

	error_log("purchases.php running query: " . $query);
	$res = mysql_query($query, $link);
	if (!$res) {
		echo "error: " . mysql_error() . "<br />\n";
	}


	// first put all the transactions into an array so we can process change
	$transactions = array();
	$transidx = 0;

	while ($row = mysql_fetch_assoc($res)) {

		if ($row['total'] == 0)
			continue;

		// if it's change, deduct from previous (verify member no matches)
		if ($row['description'] == "Change" && $transactions[$transidx - 1]['cardno'] == $row['card_no']) {
			$transactions[$transidx - 1]['amount'] -= $row['total'];
		} else {
			$transactions[$transidx] = array(
				'datetime' => $row['datetime'],
				'paytype' => $row['description'],
				'tendertype' => $row['trans_subtype'],
				'amount' => $row['total'] * -1,
				'cardno' => $row['card_no'],
				'firstname' => $row['first'],
				'lastname' => $row['last'],
				'addshopper' => $row['addshopper']
			);

			if ($row['addshopper']) {
				$transactions[$transidx]['sponsorcard'] = $row['sponsorCardNo'];
				$transactions[$transidx]['sponsfirst'] = $row['sponsfirst'];
				$transactions[$transidx]['sponslast'] = $row['sponslast'];
			}
		}

		$transidx++;
	}

	$cc_total = $ca_total = $ck_total = $fs_total = 0;

	// generate a few stats
	foreach ($transactions as $trans) {
		switch ($trans['tendertype']) {
			case 'CC':
				$cc_total += $trans['amount'];
				break;

			case 'CA':
				$ca_total += $trans['amount'];
				break;

			case 'FS':
				$fs_total += $trans['amount'];
				break;

			case 'CK':
				$ck_total += $trans['amount'];
				break;
		}

		if ($trans['addshopper']) {
			$as_total += $trans['amount'];
		} else {
			$fm_total += $trans['amount'];
		}

	}

	$html .= "<h2>Member Purchases Report</h2>";

	if ($startdate && $enddate) {
		$html .= "<h3>$startdate - $enddate</h3>";
	} else {
		$html .= "<h3>All Records</h3>";
	}

	$html .= "<h3>Totals:</h3>";

	$html .= "Cash: " . '$'.cashformat($ca_total);
	$html .= "&nbsp;&nbsp;&nbsp;&nbsp;";
	$html .= "Check: " . '$'.cashformat($ck_total);
	$html .= "&nbsp;&nbsp;&nbsp;&nbsp;";
	$html .= "Credit: " . '$'.cashformat($cc_total);
	$html .= "&nbsp;&nbsp;&nbsp;&nbsp;";
	$html .= "EBT: " . '$'.cashformat($fs_total);
	$html .= "<br />";



	$html .= "Full Members: ". '$'.cashformat($fm_total);
	$html .= "&nbsp;&nbsp;&nbsp;&nbsp;";
	$html .= "Sponsored Shoppers: ". '$'.cashformat($as_total);
	$html .= "<br />";

	$html .= "<h3>Transactions:</h3>";

	$html .= "<table >";
	$html .= '<tr><th align="left">Date Time</th><th align="left">Payment</th><th align="left">Amount</th><th align="left">Card Num</th> <th align="left">First</th> <th align="left">Last</th> <th align="left">Member Type</th> </tr>';
	

	foreach ($transactions as $trans) {

		if (!$csvexport)
			$html .= "<tr>" .
			"<td>" . $trans['datetime'] . "</td>" .
			"<td>" . $trans['paytype'] . "</td>" .
			"<td align=\"right\">" . cashformat($trans['amount']) . "</td>" .
			"<td>" . $trans['cardno'] . "</td>" .
			"<td>" . $trans['firstname'] . "</td>" .
			"<td>" . $trans['lastname'] . "</td>" .
			"<td>" . ( $trans['addshopper'] ? ("Sponsored (" . $trans['sponsorcard'] . ':' . $trans['sponsfirst'] . ' ' . $trans['sponslast'] . ")") : ("Full") ) . "</td>" .
			"</tr>\n";
		else
			$csvdata[] = $trans;
	}

	if ($csvexport) {
		$filename = "member_purchases_report_" . $startdate . '_to_' . $enddate . ".csv";
		export_csv($filename, $csvdata);
		exit;
	}

	$html .= "</table>";

} else {
	$html .= startform();
	$html .= "<h2>Member Purchases Report</h2>";

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
