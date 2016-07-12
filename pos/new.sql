ALTER TABLE opdata.products  ADD COLUMN `doublesnap` TINYINT(1) NULL DEFAULT 0 AFTER `alcohol`;

insert into tenders values(12,'DS','Double Snap',null,null,0,20,0);
insert into opdata.tenders values(12,'DS','Double Snap',null,null,0,20,0);

/*
ALTER TABLE translog.localtemptrans  ADD COLUMN `ebt` VARCHAR(32) NULL DEFAULT '' AFTER `foodstamp`;
ALTER TABLE translog.localtrans  ADD COLUMN `ebt` VARCHAR(32) NULL DEFAULT '' AFTER `foodstamp`;
ALTER TABLE is4c_log.dtransactions ADD COLUMN `ebt` VARCHAR(32) NULL DEFAULT '' AFTER `foodstamp`;

alter table translog.localtemptrans drop column ebt;
alter table translog.localtrans drop column ebt;
alter table is4c_log.dtransactions drop column ebt;
*/
