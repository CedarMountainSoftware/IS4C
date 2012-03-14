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
		<title>IS4C - Members</title>
	</head>
	<body>';
	
	$html.=body();

	$action = $_REQUEST['action'];

	$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
	mysql_select_db('is4c_op',$link);


	$html.='<div id="page_panel">';
	$html .= startform();

	if ($_REQUEST['submit']) {
		$cardno = $_REQUEST['cardno'];
		$firstname = $_REQUEST['firstname'];
		$lastname = $_REQUEST['lastname'];
		$jobtitle = $_REQUEST['jobtitle'];
		$cashierpassword = $_REQUEST['cashierpassword'];
		$active = $_REQUEST['active'] ? 1 : 0;
		$addshopper = $_REQUEST['addshopper'] ? 1 : 0;
		$sponsorcardno = $_REQUEST['sponsorcardno'];


		if ($action == "add") {
			$query = "INSERT INTO members (CardNo, FirstName, LastName, CashierPassword, AdminPassword, JobTitle, Active, addshopper, sponsorCardNo) VALUES (".db_quote($cardno) . "," . db_quote($firstname) . "," . db_quote($lastname) . "," . db_quote($cashierpassword) . "," . db_quote($cashierpassword) . "," . db_quote($jobtitle) . "," . db_quote($active) . "," . db_quote($addshopper) . "," . db_quote($sponsorcardno) .")";
		} else if ($action == "edit") {
			$id = $_REQUEST['id'];
			$query = "UPDATE members SET CardNo = " . db_quote($cardno) . ", FirstName = " . db_quote($firstname) . ", LastName = " . db_quote($lastname) . ", CashierPassword = " . db_quote($cashierpassword) . ", AdminPassword = " . db_quote($adminpassword) . ", JobTitle = " . db_quote($jobtitle) . ", Active = " . db_quote($active) . ", addshopper = " . db_quote($addshopper) . ", sponsorCardNo = " . ($addshopper ? db_quote($sponsorcardno) : "NULL")  . " WHERE id = " . $id;
		}

		$res = mysql_query($query, $link);

		header("Location: members.php");
		exit;

	} else {

		switch ($action) {

			case "delete":
				$id = $_REQUEST['id'];
				$query = "DELETE FROM members WHERE id = " . $id;
				$res = mysql_query($query, $link);

				header("Location: members.php");
				exit;

				break;

			case "edit":
				$id = $_REQUEST['id'];
				$query = "SELECT CardNo, FirstName, LastName, CashierPassword, AdminPassword, JobTitle, Active, addshopper, sponsorCardNo FROM members WHERE id = '" . mysql_real_escape_string($id) . "'";
				$res = mysql_query($query, $link);
				if ($row = mysql_fetch_assoc($res)) {
					$cardno = $row['CardNo'];
					$firstname = $row['FirstName'];
					$lastname = $row['LastName'];
					$cashierpassword = $row['CashierPassword'];
					$jobtitle = $row['JobTitle'];
					$active = $row['Active'] ? 1 : 0;
					$addshopper = $row['addshopper'];
					$sponsorcardno = $row['sponsorCardNo'];
				}

			case "add":
				if ($id) {
					$html .= hiddeninput("id", $id);
				}
				$html .= hiddeninput("action", $action);
				$html .= hiddeninput("submit", 1);
				$html .= "<table cellspacing=\"5\">";
				$html .= tablerow("Card No", textbox("cardno", $cardno, 6, 8));
				$html .= tablerow("First Name", textbox("firstname", $firstname, 20, 30));
				$html .= tablerow("Last Name", textbox("lastname", $lastname, 20, 30));
				$html .= tablerow("Cashier Password", textbox("cashierpassword", $cashierpassword, 5, 10));
				// $html .= tablerow("Admin Password", textbox("adminpassword", $adminpassword, 5, 10));
				$html .= tablerow("Job Title", textbox("jobtitle", $jobtitle, 20, 30));
				if ($action == "add") $active = 1; // default to active for new additions
				$html .= tablerow("Active", checkbox("active", $active));
				$html .= tablerow("Additional Shopper", checkbox("addshopper", $addshopper));
				$html .= tablerow("Sponsor Card No:", textbox("sponsorcardno", $sponsorcardno, 6, 8));
				$html .= "</table>";
				$html .= submitbox("Save", "Save");
				break;




			case "list":
			default:
				$html .= "<table cellspacing=\"5\">";
				$html .= tableheaderrow("CardNo", "First Name", "Last Name", "Job Title", "Active", "Additional Shopper?", "");
				$query = "SELECT members.id, members.CardNo, members.FirstName, members.LastName, members.CashierPassword, members.AdminPassword, members.JobTitle, members.Active, members.addshopper, members.sponsorCardNo, addmems.FirstName as sponsfirst, addmems.LastName as sponslast FROM members LEFT JOIN members addmems ON (addmems.CardNo = members.sponsorCardNo) ORDER BY LastName ASC";
				$res = mysql_query($query, $link);
				while ($row = mysql_fetch_assoc($res)) {
					$html .= tablerow(
						'<a href="members.php?action=edit&id='.$row['id'].'">' . $row['CardNo'] . '</a>',
						$row['FirstName'],
						$row['LastName'],
						$row['JobTitle'],
						$row['Active'] ? "Yes" : "No",
						$row['addshopper'] ? ("Yes (" . $row['sponsorCardNo'] . ": " . $row['sponsfirst'] . ' ' . $row['sponslast'] . ")") : "",
						'<a href="members.php?action=delete&id='.$row['id'].'">' . "Delete" . '</a>'
						);
				}
				$html .= "</table>";

				$html .= '<a href="members.php?action=add">Add New Member</a>';
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
