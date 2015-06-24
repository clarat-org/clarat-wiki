#!/bin/bash

export CURRENT_TIME=`date +"%Y-%m-%d-%H-%M-%S"`
mysqldump --single-transaction -u $DB_USER -p$DB_PWD -h $DB_HOST $DB_TABLE > /tmp/wiki-$CURRENT_TIME.sql

sftp -o "IdentityFile=/app/.ssh/id_rsa" backups@$SFTP_IP:backups <<COMMAND
  put /tmp/wiki-$CURRENT_TIME.sql
  quit
COMMAND
