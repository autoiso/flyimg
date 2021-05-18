-- noinspection SqlNoDataSourceInspectionForFile
create user fi_dev with CREATEDB ;
alter user fi_dev with encrypted password 'fi_dev';
CREATE DATABASE fi_dev OWNER fi_dev;