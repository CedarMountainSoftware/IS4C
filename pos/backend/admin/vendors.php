<?php
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/formlib.inc');
	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");

	function db_quote($str) {
		return "'" . mysql_real_escape_string($str) . "'";
	}

	$html='<!DOCTYPE HTML>
<html>
	<head>';
	
	$html.=head();
	
	$html.='
		<title>IS4C - Vendors</title>
	</head>
	<body>';
	
	$html.=body();

	$action = $_REQUEST['action'];

	$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
	mysql_select_db('is4c_op',$link);


	$html.='<div id="page_panel">';
	$html .= startform();

	if ($_REQUEST['submit']) {
		$vendor_name = $_REQUEST['vendor_name'];

		if ($action == "add") {
			$query = "INSERT INTO vendors (vendor_name) VALUES (".db_quote($vendor_name) .")";
		} else if ($action == "edit") {
			$vendor_id = $_REQUEST['vendor_id'];
			$query = "UPDATE vendors SET vendor_name = " . db_quote($vendor_name) . " WHERE vendor_id = " . $vendor_id;
		}

		$res = mysql_query($query, $link);

		header("Location: vendors.php");
		exit;

	} else {

		switch ($action) {

			case "delete":
				$vendor_id = $_REQUEST['vendor_id'];
				$query = "DELETE FROM vendors WHERE vendor_id = " . $vendor_id;
				$res = mysql_query($query, $link);

				header("Location: vendors.php");
				exit;

				break;

			case "edit":
				$vendor_id = $_REQUEST['vendor_id'];
				$query = "SELECT vendor_id, vendor_name FROM vendors WHERE vendor_id = '" . mysql_real_escape_string($vendor_id) . "'";
				$res = mysql_query($query, $link);
				if ($row = mysql_fetch_assoc($res)) {
					$vendor_id = $row['vendor_id'];
					$vendor_name = $row['vendor_name'];
				}

			case "add":
				if ($vendor_id) {
					$html .= hiddeninput("vendor_id", $vendor_id);
				}
				$html .= hiddeninput("action", $action);
				$html .= hiddeninput("submit", 1);
				$html .= "<table cellspacing=\"5\">";
				$html .= tablerow("Vendor Name", textbox("vendor_name", $vendor_name, 20, 255));
				$html .= "</table>";
				$html .= submitbox("Save", "Save");
				break;



			case "list":
			default:
				$html .= "<table cellspacing=\"5\">";
				$html .= tableheaderrow("Vendor Name");
				$query = "SELECT vendor_id, vendor_name FROM vendors ORDER BY vendor_name ASC";
				$res = mysql_query($query, $link);
				while ($row = mysql_fetch_assoc($res)) {
					$html .= tablerow(
						'<a href="vendors.php?action=edit&vendor_id='.$row['vendor_id'].'">' . $row['vendor_name'] . '</a>',
						'<a href="vendors.php?action=delete&vendor_id='.$row['vendor_id'].'">' . "Delete" . '</a>'
						);
				}
				$html .= "</table>";

				$html .= '<a href="vendors.php?action=add">Add New Vendor</a>';
				break;
		}
	}
	
	$html .= endform();
	$html .= '</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';

	print_r($html);


?>
