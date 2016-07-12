<?php
require_once("prehkeys.php");
require_once("maindisplay.php");
	if(isset($_POST['ebt']) && strlen($_POST['ebt'])>0){
		$_SESSION['EBT'] = $_POST['ebt'];
		ttl();
		gohome();
	}else{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title></title>
    </head>
    <body onLoad='document.searchform.ebt.focus();'>
        <?php
            if (!function_exists("printheaderb")) include("drawscreen.php");

            $_SESSION["away"] = 1;
            printheaderb();
            enterebtbox("enter non-member's EBT number");
            $_SESSION["scan"] = "noScan";
            printfooter();
        ?>
    </body>
</html>

<?php } ?>
