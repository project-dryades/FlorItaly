<?php
include('init.php');
require('classes/database.class.php');
require('classes/name_match_class/nmNameMatch.class.php');
require('classes/match_setup.class.php');
require('classes/match_templates.class.php');
require('arrays/match_testi.class.php');
require('functions/funzioni.php');

session_start();


$db = new database($database_name, $database_password, $database_login, $database_adress);
$namematch = new nameMatching2();
$match_testo = new MatchTesti();
$match_text = new NmTemplates($match_testo);
$match_setup = new MatchSetup($db);
$name_match = new NameMatching2();
if (isset($_REQUEST['procedure']))
{
    if ($_REQUEST['procedure'] == 'instantmatch')
    {
        if (isset($_POST['names']))
        {

            $names_from_post2 = getRealInput('POST');


            array_pop($names_from_post2);

            $array_to_update = $_SESSION['array_nomi'];

            $array_final_update = $match_setup->updateArrayFinal($names_from_post2, $array_to_update);

            $_SESSION['array_nomi'] = $array_final_update;
            //print_r($_SESSION['array_nomi']);

            $con = $match_text->getNameListFinal($array_final_update);
            $con .= $match_text->getDownloadButton2();
            echo $con;


        } elseif (isset($_POST['search'])) {
            //print_r($_POST);
            $inserted_names = preg_split("/\n/", $_POST['sp_name']);
            //hai voluto mettere l'a capo, ora rimuovi gli spazi vuoti (ore buttate per questo problema)
            $inserted_names = array_map('trim', $inserted_names);
            //necessario per rimuovere spazi bianchi
            $inserted_names = array_filter($inserted_names);

            // normalize and parse inserted names
            //echo print_r($inserted_names);
            if (count($inserted_names) == 0) {
                header('location: index.php?procedure=instantmatch');
            }

            $original_names = $inserted_names; // da tenere x il nome originale


            // applico namematch
            $final_result_match = array();
            // lista di valori da passare
            $value_to_pass = $match_setup->getArgumentsNameMatch($_POST);
            //print_r($value_to_pass);

            foreach ($inserted_names as $key => $value) {
                //$rimuovi =$name_match->searchMatch($value,$db);
                //importantissimo
                $result_match = $name_match->nameMatch($value, $db, $value_to_pass['score'], $value_to_pass['n_result'], $value_to_pass['phonetic'], $value_to_pass['id_subsp'], $value_to_pass['id_var'], $value_to_pass['id_form'], $value_to_pass['id_cv'], $value_to_pass['insensitive']);
                $final_result_match[] = ['input_name' => $original_names[$key], 'match' => $result_match];
            }

            //echo print_r($final_result_match);

            $names_count = $match_setup->countNamesGeneral($final_result_match);
            $con = $match_text->getCounter($names_count);
            $con .= $match_text->getNameListFinal($final_result_match);
            $con .= $match_text->getDownloadButton2();
            $_SESSION['array_nomi'] = $final_result_match;
            echo $con;


        }
        else {
            if (isset($_SESSION['array_nomi'])){
                unset($_SESSION['array_nomi']);
            }
            $con = '<p>'.$match_testo->intro['35'].'<p>';
            $con .= $match_text->getNameSearch();
            $content =$match_testo->intro['3']. $con;
            echo $content;
        }
    }
    elseif ($_REQUEST['procedure'] == 'download2') {
        array_to_csv_download2($_SESSION['array_nomi']);
    }

}
else {
    $content = $match_testo->intro['2']. $match_testo->intro['1'];
    echo $content;

}