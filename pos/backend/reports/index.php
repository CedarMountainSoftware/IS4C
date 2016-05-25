<?php
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');

	$html='<!DOCTYPE HTML>
<html>
	<head>';
	
	$html.=head();
	
	$html.='
		<title>IS4C - Reports</title>
	</head>
	<body>';
	
	$html.=body();
	
	$html.='
		<div id="page_panel">
			<h1>Reports</h1>
			<ul>
				<li><a href="prodsales.php">Product Sales Report</a></li>
				<li><a href="custitems.php">Custom Items Report</a> --- (<a href="../admin/custcategories.php">Edit Custom Categories</a>)</li>

				<li><a href="activeitems.php">Active Items</a></li>

				<li><a href="purchases.php">Member Purchases Report</a></li>
				<li><a href="purchasetotals.php">Member Purchases Totals</a></li>
			</ul>
		</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';
	
	print_r($html);
?>
