#!/bin/bash

mysql --user=root --password=root < /pos/installation/mysql/script/create_server_db.sql

for FN in $(ls /pos/installation/mysql/is4c_log/tables/*.table)
do
  echo "Inserting records from $FN"
  mysql --user=root --password=root < $FN
done

for FN in $(ls /pos/installation/mysql/is4c_log/views/*.viw)
do
  echo "Inserting records from $FN"
  mysql --user=root --password=root < $FN
done

for FN in $(ls /pos/installation/mysql/is4c_op/tables/*.table)
do
  echo "Inserting records from $FN"
  mysql --user=root --password=root < $FN
done

for FN in $(ls /pos/installation/mysql/is4c_op/views/*.viw)
do
  echo "Inserting records from $FN"
  mysql --user=root --password=root < $FN
done

for FN in $(ls /pos/installation/mysql/is4c_op/data/*.insert)
do
  echo "Inserting records from $FN"
  mysql --user=root --password=root < $FN
done

mysql --user=root --password=root < /pos/installation/mysql/script/create_server_acct.sql

# we can do better by just copying the template files
#/pos/installation/ubuntu/php_server.pl
#/pos/installation/ubuntu/apache_server.pl
