<?php

include('../init.php');
require('../classes/database.class.php');

$db = new database($database_name, $database_password, $database_login, $database_adress);

$query ='CREATE TABLE `checklist` (
  `entita` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL)
   ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';
$db->getInsertQuery($query);
$query ='INSERT INTO `checklist` (`entita`) VALUES ("Abies alba Mill")';
$db->getInsertQuery($query);
$query ='INSERT INTO `checklist` (`entita`) VALUES ("Achillea millefolium L.")';
$db->getInsertQuery($query);



$query ='CREATE TABLE `sinonimi` (
  `entita` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sinonimo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL)
   ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';
$db->getInsertQuery($query);
$query = 'INSERT INTO `sinonimi` (`entita`,`sinonimo`) VALUES ("Abies alba Mill","Abies alba Mill. subsp. apennina Brullo, Scelsi & Spamp.")';
$db->getInsertQuery($query);
$query = 'INSERT INTO `sinonimi`(`entita`,`sinonimo`) VALUES ("Achillea millefolium L.","Achillea aspleniifolia auct.")';
$db->getInsertQuery($query);
$query = 'INSERT INTO `sinonimi`(`entita`,`sinonimo`) VALUES ("Achillea millefolium L. subsp. sudetica (Opiz) Oborny","Achillea millefolium L. subsp. alpestris (Wimm. & Grab.) Gremli")';
$db->getInsertQuery($query);
$query = 'INSERT INTO `sinonimi`(`entita`,`sinonimo`) VALUES ("Achillea millefolium L. subsp. sudetica (Opiz) Oborny","Achillea sudetica Opiz")';
$db->getInsertQuery($query);


$query = 'SELECT * FROM `sinonimi`';
$result = $db->getMultipleSelectQuery($query);
