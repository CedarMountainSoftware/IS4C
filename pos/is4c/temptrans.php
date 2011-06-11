<?php
/* moved from end.php so these functions could be called from other places. */
            function cleartemptrans() {
                $db = tDataConnect();

                if($_SESSION["msg"] == 2) {
                    $_SESSION["msg"] = 99;
                    sql_query("update localtemptrans set trans_status = 'X'", $db);
                }

                if ($_SESSION["DBMS"] == "mssql") {
                    sql_query("exec clearTempTables", $db);
                }
                else {
                    moveTempData();
                    truncateTempTables();
                }

                sql_close($db);

                testremote();

                loadglobalvalues();    
                $_SESSION["transno"] = $_SESSION["transno"] + 1;
                setglobalvalue("TransNo", $_SESSION["transno"]);

                if ($_SESSION["TaxExempt"] != 0) {
                    $_SESSION["TaxExempt"] = 0;
                    setglobalvalue("TaxExempt", 0);
                }

                memberReset();
                transReset();
                printReset();

                getsubtotals();

                delete_file(remote_oux());
                delete_file(local_inx());

                return 1;
            }


            function truncateTempTables() {
                $connection = tDataConnect();
                $query1 = "truncate table localtemptrans";
                $query2 = "truncate table activitytemplog";

                sql_query($query1, $connection);
                sql_query($query2, $connection);

                sql_close($connection);
            }

            function moveTempData() {
                $connection = tDataConnect();

                sql_query("update localtemptrans set trans_type = 'T' where trans_subtype = 'CP'", $connection);
                sql_query("update localtemptrans set upc = 'DISCOUNT', description = upc, department = 0 where trans_status = 'S'", $connection);

                sql_query("insert into localtrans select * from localtemptrans", $connection);
                sql_query("insert into dtransactions select * from localtemptrans", $connection);
                sql_query("insert into activitylog select * from activitytemplog", $connection);
                sql_query("insert into activitylog select * from activitytemplog", $connection);

                sql_close($connection);
            }
?>
