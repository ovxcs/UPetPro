-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema stom2
-- -----------------------------------------------------
DROP SCHEMA IF EXISTS `stom2` ;

-- -----------------------------------------------------
-- Schema stom2
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `stom2` DEFAULT CHARACTER SET utf8 ;
USE `stom2` ;

-- -----------------------------------------------------
-- Table `stom2`.`contacts`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `stom2`.`contacts` ;

CREATE TABLE IF NOT EXISTS `stom2`.`contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `flags` INT UNSIGNED ZEROFILL NULL,
  `val` VARCHAR(255) NOT NULL,
  `ord` SMALLINT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  UNIQUE INDEX `val_UNIQUE` (`val` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 100;


-- -----------------------------------------------------
-- Table `stom2`.`accounts`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `stom2`.`accounts` ;

CREATE TABLE IF NOT EXISTS `stom2`.`accounts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cid` INT UNSIGNED NOT NULL,
  `cid2` INT UNSIGNED NULL,
  `fid` INT NULL,
  `tid` SMALLINT NULL,
  `flags` BIGINT NULL,
  `pwh` CHAR(64) NULL,
  `uname` CHAR(20) NULL,
  `check` CHAR(64) NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) INVISIBLE,
  UNIQUE INDEX `fidx` (`fid` ASC, `tid` ASC) INVISIBLE,
  UNIQUE INDEX `uname_UNIQUE` (`uname` ASC) VISIBLE,
  UNIQUE INDEX `cid_UNIQUE` (`cid` ASC) VISIBLE,
  UNIQUE INDEX `cid2_UNIQUE` (`cid2` ASC) VISIBLE,
  INDEX `fk_account_contact_idx` (`cid` ASC, `cid2` ASC) VISIBLE,
  CONSTRAINT `fk_account_contact`
    FOREIGN KEY (`cid` , `cid2`)
    REFERENCES `stom2`.`contacts` (`id` , `id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 100;


-- -----------------------------------------------------
-- Table `stom2`.`personal`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `stom2`.`personal` ;

CREATE TABLE IF NOT EXISTS `stom2`.`personal` (
  `id` INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phoneId` INT UNSIGNED ZEROFILL NULL,
  `emailId` INT UNSIGNED ZEROFILL NULL,
  `fullname` VARCHAR(50) NULL,
  `contacts` VARCHAR(100) NULL,
  `aid` INT UNSIGNED NULL,
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `phone_UNIQUE` (`phoneId` ASC) VISIBLE,
  UNIQUE INDEX `email_UNIQUE` (`emailId` ASC) VISIBLE,
  INDEX `fk_pers_contacts_idx` (`phoneId` ASC, `emailId` ASC) VISIBLE,
  UNIQUE INDEX `aid_UNIQUE` (`aid` ASC) VISIBLE,
  CONSTRAINT `fk_pers_contacts`
    FOREIGN KEY (`phoneId` , `emailId`)
    REFERENCES `stom2`.`contacts` (`id` , `id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_pers_accounts`
    FOREIGN KEY (`aid`)
    REFERENCES `stom2`.`accounts` (`cid`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 100;


-- -----------------------------------------------------
-- Table `stom2`.`clients`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `stom2`.`clients` ;

CREATE TABLE IF NOT EXISTS `stom2`.`clients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fullname` VARCHAR(100) NOT NULL,
  `phoneId` INT UNSIGNED NULL,
  `emailId` INT UNSIGNED NULL,
  `address` VARCHAR(45) NULL,
  `birthd` DATE NULL,
  `md_id` INT UNSIGNED NULL,
  `aid` INT UNSIGNED NULL,
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  INDEX `md_id_idx` (`md_id` ASC) VISIBLE,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `fullname_UNIQUE` (`fullname` ASC) VISIBLE,
  INDEX `fk_clients_phone_idx` (`phoneId` ASC, `emailId` ASC) VISIBLE,
  INDEX `fk_clients_acc_idx` (`aid` ASC) VISIBLE,
  CONSTRAINT `fk_clients_md_id`
    FOREIGN KEY (`md_id`)
    REFERENCES `stom2`.`personal` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_clients_contacts`
    FOREIGN KEY (`phoneId` , `emailId`)
    REFERENCES `stom2`.`contacts` (`id` , `id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_clients_acc`
    FOREIGN KEY (`aid`)
    REFERENCES `stom2`.`accounts` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 100;


-- -----------------------------------------------------
-- Table `stom2`.`visits`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `stom2`.`visits` ;

CREATE TABLE IF NOT EXISTS `stom2`.`visits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `md_id` INT UNSIGNED NOT NULL,
  `cl_id` INT UNSIGNED NOT NULL,
  `sched_ts` BIGINT NULL COMMENT 'Date and Time',
  `start_ts` BIGINT NULL,
  `minutes` SMALLINT NULL,
  `kind` SMALLINT NULL,
  `status` SMALLINT NULL,
  `obs` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  INDEX `cl_id_idx` (`cl_id` ASC) INVISIBLE,
  INDEX `md_id_idx` (`md_id` ASC) VISIBLE,
  CONSTRAINT `fk_visits_cl_id`
    FOREIGN KEY (`cl_id`)
    REFERENCES `stom2`.`clients` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_visits_md_id`
    FOREIGN KEY (`md_id`)
    REFERENCES `stom2`.`personal` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 100;


-- -----------------------------------------------------
-- Table `stom2`.`radiographs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `stom2`.`radiographs` ;

CREATE TABLE IF NOT EXISTS `stom2`.`radiographs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cl_id` INT UNSIGNED NOT NULL,
  `ts` BIGINT NULL,
  `path` VARCHAR(255) NULL,
  `info` VARCHAR(255) NULL,
  `md_id` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  INDEX `cl_id_idx` (`cl_id` ASC) VISIBLE,
  INDEX `fk_radoi_md_id_idx` (`md_id` ASC) VISIBLE,
  CONSTRAINT `fk_radio_cl_id`
    FOREIGN KEY (`cl_id`)
    REFERENCES `stom2`.`clients` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_radio_md_id`
    FOREIGN KEY (`md_id`)
    REFERENCES `stom2`.`personal` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 100;


-- -----------------------------------------------------
-- Table `stom2`.`stock`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `stom2`.`stock` ;

CREATE TABLE IF NOT EXISTS `stom2`.`stock` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL,
  `code` VARCHAR(45) NULL,
  `um` CHAR(8) NULL,
  `qty` DECIMAL NULL,
  `adm_um` CHAR(8) NULL,
  `adm_qty` DECIMAL NULL,
  `in_ts` BIGINT NULL,
  `exp_ts` BIGINT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 100;


-- -----------------------------------------------------
-- Table `stom2`.`personal_stock`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `stom2`.`personal_stock` ;

CREATE TABLE IF NOT EXISTS `stom2`.`personal_stock` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `st_id` INT UNSIGNED NOT NULL,
  `md_id` INT(8) UNSIGNED NOT NULL,
  `alloc_ts` BIGINT UNSIGNED NOT NULL,
  `qty` INT NULL,
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  INDEX `st_id_idx` (`st_id` ASC) VISIBLE,
  INDEX `md_id_idx` (`md_id` ASC) VISIBLE,
  PRIMARY KEY (`st_id`, `md_id`, `alloc_ts`),
  CONSTRAINT `fk_pers_stk_st_id`
    FOREIGN KEY (`st_id`)
    REFERENCES `stom2`.`stock` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_pers_stk_md_id`
    FOREIGN KEY (`md_id`)
    REFERENCES `stom2`.`personal` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 100;


-- -----------------------------------------------------
-- Table `stom2`.`sessions`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `stom2`.`sessions` ;

CREATE TABLE IF NOT EXISTS `stom2`.`sessions` (
  `mwt` CHAR(64) NOT NULL,
  `aid` INT UNSIGNED NULL,
  `cts` BIGINT UNSIGNED ZEROFILL NOT NULL,
  `lats` BIGINT UNSIGNED ZEROFILL NOT NULL,
  `flags` INT ZEROFILL NULL,
  `address` VARCHAR(45) NULL,
  PRIMARY KEY (`mwt`),
  UNIQUE INDEX `mwt_UNIQUE` (`mwt` ASC) VISIBLE,
  INDEX `fk_ses_acc_idx` (`aid` ASC) VISIBLE,
  CONSTRAINT `fk_ses_acc`
    FOREIGN KEY (`aid`)
    REFERENCES `stom2`.`accounts` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

USE `stom2` ;

-- -----------------------------------------------------
-- Placeholder table for view `stom2`.`agenda`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stom2`.`agenda` (`id` INT, `sched_ts` INT, `start_ts` INT, `kind` INT, `status` INT, `md_id` INT, `cl_id` INT, `fullname` INT, `val` INT, `flags` INT);

-- -----------------------------------------------------
-- View `stom2`.`agenda`
-- -----------------------------------------------------

USE `stom2`;

DELIMITER $$

USE `stom2`$$
DROP TRIGGER IF EXISTS `stom2`.`clients_BEFORE_INSERT_contacts` $$
USE `stom2`$$
CREATE DEFINER = CURRENT_USER TRIGGER `stom2`.`clients_BEFORE_INSERT_contacts` BEFORE INSERT ON `clients` FOR EACH ROW
BEGIN
	IF (NEW.phone IS NULL AND NEW.email IS NULL) THEN
		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT = '\'phone\' and \'email\' cannot both be null';
	END IF;
END$$


USE `stom2`$$
DROP TRIGGER IF EXISTS `stom2`.`clients_BEFORE_UPDATE_contacts` $$
USE `stom2`$$
CREATE DEFINER = CURRENT_USER TRIGGER `stom2`.`clients_BEFORE_UPDATE_contacts` BEFORE UPDATE ON `clients` FOR EACH ROW
BEGIN
	IF (NEW.phone IS NULL AND NEW.email IS NULL) THEN
		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT = '\'phone\' and \'email\' cannot both be null';
	END IF;
END$$


DELIMITER ;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

-- -----------------------------------------------------
-- Data for table `stom2`.`contacts`
-- -----------------------------------------------------
START TRANSACTION;
USE `stom2`;
INSERT INTO `stom2`.`contacts` (`id`, `flags`, `val`, `ord`) VALUES (47, 2, '08779954777', NULL);

COMMIT;


-- -----------------------------------------------------
-- Data for table `stom2`.`accounts`
-- -----------------------------------------------------
START TRANSACTION;
USE `stom2`;
INSERT INTO `stom2`.`accounts` (`id`, `cid`, `cid2`, `fid`, `tid`, `flags`, `pwh`, `uname`, `check`) VALUES (34, 47, 47, 87, 1, NULL, '0eb4d1b34c22c2ccf79548d13745d59c767111bdb7915c70a541aa0da143c25b', NULL, NULL);

COMMIT;


-- -----------------------------------------------------
-- Data for table `stom2`.`personal`
-- -----------------------------------------------------
START TRANSACTION;
USE `stom2`;
INSERT INTO `stom2`.`personal` (`id`, `phoneId`, `emailId`, `fullname`, `contacts`, `aid`) VALUES (87, NULL, NULL, 'Donald,Duck', NULL, NULL);

COMMIT;

