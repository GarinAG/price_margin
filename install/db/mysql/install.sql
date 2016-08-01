CREATE TABLE IF NOT EXISTS `b_interprice_margin` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `NAME` VARCHAR(255) NOT NULL,
  `SITE_ID` VARCHAR(2) NOT NULL,
  `ACTIVE` VARCHAR(1) NOT NULL DEFAULT 'Y',
  `ITEM` INT(18) null,
  `SECTION` INT(18) null,
  `PRICE` decimal(18,2) null,
  `MARGIN` decimal(3,2) null,
  `USER_ID` INT(18) null,
  `GROUP` INT(18) null,
  `USER_MODIFIER` INT(18) null,
   PRIMARY KEY (ID)
);
