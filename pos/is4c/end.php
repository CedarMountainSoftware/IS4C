<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <body bgcolor='#ffffff'>
        <?
			include_once("temptrans.php");
            if (!function_exists("get_config_auto")) {
                include_once("/pos/is4c/lib/conf.php");
                apply_configurations();
            }
            include_once("session.php");
            include_once("printLib.php");
            include_once("printReceipt.php");
            include_once("connect.php");
            include_once("additem.php");
            include_once("ccLib.php");
            include_once("maindisplay.php");



            if ($_SESSION["End"] == 1) {
                addtransDiscount();
                addTax();
            }
            $receiptType = $_SESSION["receiptType"];
            $_SESSION["receiptType"] = "";

            if (strlen($receiptType) > 0) {
                printReceipt($receiptType);

                if ($_SESSION["End"] == 1 || $_SESSION["msg"] == 2) {
                    if ($_SESSION["msg"] == 2) {
                        $returnHome = 1;
                    }
                    else {
                        $returnHome = 0;
                    }

                    $_SESSION["End"] = 0;

                    if (cleartemptrans() == 1) {

                        // force cleartemptrans to finish before returning home.
                        // Because returnHome() depends on javascript which
                        // can be triggered independently of php

                        if ($returnHome == 1) {
                            $returnHome = 0;
                            returnHome();
                        }
                    }
                }
            }

        ?>
    </body></html>
