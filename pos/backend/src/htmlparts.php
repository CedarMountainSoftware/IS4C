<?php
	function head() {
		return '
		<link href="/src/screen.css" media="screen" rel="stylesheet" type="text/css"/>';
	}
	
	function body() {
		return '
		<div id="page_top"><a class="a_unstyled" href="/">IS4C Maintenance &amp; Reporting</a></div>
		<div id="page_nav">
			<ul>
				<li><a href="/item">Item Maintenance</a></li>
<!--				<li><a href="/batch">Sale Batches</a></li> -->
				<li><a href="/admin/shelftags.php">Shelf Tags</a></li>
				<li><a href="/reports/">Reports</a></li>
<!--				<li>Dayend Balancing</li> -->
				<li><a href="/sync">Synchronization</a></li>
				<li><a href="/admin/members.php">Members</a></li>
				<li><a href="/admin/vendors.php">Vendors</a></li>
			</ul>
			<br /><br /><br /><br />
			<a href="http://localhost/">localhost</a>
			</div>

			';
		
	}
	
	function foot() {
		return '
		<div id="page_foot">
			<p class="p_status">'.$_SERVER['REMOTE_ADDR'].'</p>
		</div>';
	}
?>
