<?php
	require_once($_SERVER["DOCUMENT_ROOT"].'/src/htmlparts.php');

	$html='<!DOCTYPE HTML>
<html>
	<head>';
	
	$html.=head();
	
	$html.='
		<title>IS4C - Maintenance &amp; Reporting</title>
	</head>
	<body>';
	
	$html.=body();

	if ($handle = opendir('./src/images/')) {
		$images = array();

		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..')
				$images[] = $file;
		}
		closedir();

		error_log("images: " . var_export($images, true));

		$welcomeimage = $images[rand(0, count($images)-1)];;
	}  else {
		$welcomeimage = 'wideshot.jpg';
	}
	
	$html.=' <div id="page_panel"> ';

	$html .= '<img alt="welcome image" src="./src/images/'. $welcomeimage . '" width="600" /> ';




	$html .='</div>';
	
	$html.=foot();
	
	$html.='
	</body>
</html>';
	
	print_r($html);
?>
