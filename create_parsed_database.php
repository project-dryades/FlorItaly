<?php

/**
 * script da eseguire quando la tabella checklist o la tabella sinonimi vengono aggiornate
 * va ad aggiornare la tabella con il parsing dei nomi
 * importante per il matching dei nomi scientifici quando non c'è corrispondenza esatta
*/

include('../init.php');
require('../classes/database.class.php');
require('../classes/name_match_class/nmNameMatch.class.php');

$namematch = new nameMatching2();
$db = new database($database_name, $database_password, $database_login, $database_adress);

// Variables to define
$table_accepted_names = '';
$column_accepted_names = '';
$table_synonyms = '';
$column_synonyms = '';
$column_accepted_names_for_synonyms = '';


// 0: elimino il contenuto della tabella esistente
$query_create_parsed = '
CREATE TABLE `parsed_checklist` (
  `id` int(11) NOT NULL,
  `original_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `original_name_norm` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `synonym_of` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `genus` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `genus_ph` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `species` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `species_ph` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `species_auth` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `infrasp1_rank` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `infrasp1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `infrasp1_ph` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `infrasp1_auth` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `infrasp2_rank` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `infrasp2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `infrasp2_ph` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `infrasp2_auth` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_synonym_table` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci';

//$truncate = "TRUNCATE `flore_checklist_2018`.`parsed_checklist`";
$db->getInsertQuery($query_create_parsed);


// 1: seleziono tutti i nomi (non sinonimi) presenti

$query_nomi_accettati = 'SELECT '.$column_accepted_names.' FROM '.$table_accepted_names;
$result_nomi_accettati = $db->getMultipleSelectQuery($query_nomi_accettati);

// DEVO CONSIDERARE ANCHE QUELLI PRESENTI NELLA TABELLA SINONIMI QUINDI:

$query_nomi_accettati2 = 'SELECT '.$column_accepted_names.' FROM '.$table_synonyms;
$result_nomi_accettati2 = $db->getMultipleSelectQuery($query_nomi_accettati2);

// gli unisco rimuovendo i duplicati

$result_nomi_accettati_merge = array_unique(array_merge($result_nomi_accettati, $result_nomi_accettati2), SORT_REGULAR);
//echo count($result_nomi_accettati).'<br>'. count($result_nomi_accettati2).'<br>'. count($result_nomi_accettati_merge);

// aggiungo synonym_table
foreach ($result_nomi_accettati_merge as &$result_nome_accettato_merge){
    $result_nome_accettato_merge['id_synonym_table'] = 0;
}


// 2: seleziono tutti i sinonimi (tenendo anche il nome accettato)

$query_nomi_sinonimi = 'SELECT '.$column_accepted_names.', '.$column_synonyms.' FROM '.$table_synonyms;
$result_nomi_sinonimi = $db->getMultipleSelectQuery($query_nomi_sinonimi);

foreach ($result_nomi_sinonimi as &$result_nome_sinonimo){
    $result_nome_sinonimo['id_synonym_table'] = 1;
}

// 3: unisco nomi accettati e sinonimi in un unico array

$accepted_names = array_merge($result_nomi_accettati_merge, $result_nomi_sinonimi);
/*
foreach ($accepted_names as $show){
    echo print_r($show).'<br>';
}
*/

// 4: effettuo il parsing dei nomi e gli inserisco nel database
$table_parsed = 'parsed_checklist';
$i = 0;

//$accepted = $namematch->parseChecklistName($accepted_names[0], 'entita', 'sinonimo');

//use for test:
//$accepted_names = array($accepted_names[15962]);

// 5: parsing and insert into database

foreach ($accepted_names as $accepted_name) {


    $accepted = $namematch->parseChecklistName($accepted_name, $column_accepted_names, $column_synonyms, $accepted_name['id_synonym_table']);
    /*test
    if ($i == 15962){
        print_r($accepted);
        print_r($accepted_names[$i]);
        break;
    }
    */

    $query = "INSERT INTO ".$table_parsed." (id) VALUES (".$i.")";
    //echo $query;
    $db->getInsertQuery($query);

    $query2 = "UPDATE ".$table_parsed." SET ";
    foreach ($accepted as $key => $value) {

        $query2 .= "`".$key."` = \"".$value."\", ";


    }
    $query2 = substr($query2,0,-2);
    $query2 .=" WHERE id = ".$i;
    echo $query2.'<br>';
    $db->getInsertQuery($query2);

    $i++;
}

$query_primary_key = "ALTER TABLE `parsed_checklist` ADD PRIMARY KEY(`id`)";
//echo $query_primary_key;
//$db->getSingleQuery($query_primary_key);
