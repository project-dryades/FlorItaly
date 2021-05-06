<?php
// FUNZIONE UTILISSIMA PER GET E POST
// Function to fix up PHP's messing up input containing dots, etc.
// `$source` can be either 'POST' or 'GET'
function getRealInput($source) {
    $pairs = explode("&", $source == 'POST' ? file_get_contents("php://input") : $_SERVER['QUERY_STRING']);
    $vars = array();
    foreach ($pairs as $pair) {
        $nv = explode("=", $pair);
        $name = urldecode($nv[0]);
        $value = urldecode($nv[1]);
        $vars[$name] = $value;
    }
    return $vars;
}

function array_to_csv_download2($array, $filename = "export.csv", $delimiter=";") {
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'";');
    // open the "output" stream
    // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
    $f = fopen('php://output', 'w');
    fputcsv($f, array('Nomi inseriti / Input names', 'Nomi accettati / Accepted names', 'Sinonimo di / Synonym of', 'Punteggio (%) / Score (%)'), $delimiter);
    foreach ($array as $smaller_array) {


        if (count($smaller_array['match']) == 1)
        {
            if (array_key_exists('synonym_of', $smaller_array['match'][0]) AND $smaller_array['match'][0]['synonym_of'] != '')
            {
                fputcsv($f, array($smaller_array['input_name'], $smaller_array['match'][0]['synonym_of'], $smaller_array['match'][0]['original_name'], $smaller_array['match'][0]['score']),$delimiter);
            }
            else 
            {
                fputcsv($f, array($smaller_array['input_name'], $smaller_array['match'][0]['original_name'],'', $smaller_array['match'][0]['score']), $delimiter);
            }
            
        }

        else
        {
            fputcsv($f, array($smaller_array['input_name']), $delimiter); 
        }
        
    }
}

