<?php
// required classes
include_once __DIR__. '/../database.class.php';
require __DIR__. '/nmAdjust.class.php';
require __DIR__. '/nmQueries.class.php';
require __DIR__. '/nmPhoneticMatch.class.php';
include __DIR__. '/nmParser.class.php';
require __DIR__ . '/nmFilter.class.php';
require __DIR__. '/nmDamerauLevenshteinMod.class.php';
require __DIR__. '/genus_prefilter_class.php';
require __DIR__. '/nmPreCheck.class.php';
require __DIR__. '/nmScore.class.php';
require __DIR__. '/nmLog.class.php';



class NameMatching2
{


    private function debug($mixed,$active=False)
    {
        if ($active)
        {
            echo '<p>';
            print_r($mixed);
            echo '<p>';
        }
    }
    //nota: query_type = 'exact_match', 'near_match', 'fuzzy_match', 'fuzzy_match_slow';


    /**
     *
     * parse a plant scientific name in its components (found in the $reference_array)
     * Note: the parameters used are designed to correctly parse the names in Floritaly's checklist,
     * for this reason the function may not work as expected with other names
     *
     * @param array $name the name to parse, it must be normalized
     *
     * @return array the parsed string
     * example:
     *
     */
    public function parseChecklistName($name, $key_for_accepted_names, $key_for_synonyms, $synonym_table)
    {

        $table_columns = array(0 => 'id',
            1 => 'original_name',
            2 => 'original_name_norm',
            3 => 'synonym_of',
            4 => 'genus',
            5 => 'genus_ph',
            6 => 'species',
            7 => 'species_ph',
            8 => 'species_auth',
            9 => 'infrasp1_rank',
            10 => 'infrasp1',
            11 => 'infrasp1_ph',
            12 => 'infrasp1_auth',
            13 => 'infrasp2_rank',
            14 => 'infrasp2',
            15 => 'infrasp2_ph',
            16 => 'infrasp2_auth',
        );

        // set required istances
        $adjust = new Adjust();
        $parser = new Parse();
        $phonetic = new NearMatch();

        // set required variables (don't change)
        $other = 'other';
        $crit_chars = array();
        $subsp_mark = false;
        $var_mark = false;
        $form_mark = false;
        $cv_mark = false;

        // order names and synonyms
        if (array_key_exists($key_for_synonyms, $name))
        {
            $synonym_of = $name[$key_for_accepted_names];
            $accepted_name = $name[$key_for_synonyms];
        }
        else
        {
            $accepted_name = $name[$key_for_accepted_names];
            $synonym_of = '';
        }

        $original_str = $accepted_name;

        // for hybrids
        $str_exploded = explode(' ', strtoupper($original_str));
        if (in_array('X', $str_exploded) OR in_array('×', $str_exploded))
        {
            $final_parsing = array();
            $final_parsing[$table_columns[1]] = $accepted_name;
            $final_parsing[$table_columns[2]] = $accepted_name;
            $final_parsing[$table_columns[3]] = $synonym_of;
            $final_parsing[$table_columns[4]] = '';
            $final_parsing[$table_columns[5]] = '';
            $final_parsing[$table_columns[6]] = '';
            $final_parsing[$table_columns[7]] = '';
            $final_parsing[$table_columns[8]] = '';
            $final_parsing[$table_columns[9]] = '';
            $final_parsing[$table_columns[10]] = '';
            $final_parsing[$table_columns[11]] = '';
            $final_parsing[$table_columns[12]] = '';
            $final_parsing[$table_columns[13]] = '';
            $final_parsing[$table_columns[14]] = '';
            $final_parsing[$table_columns[15]] = '';
            $final_parsing[$table_columns[16]] = '';

            //added for synonym
            $final_parsing['id_synonym_table'] = $synonym_table;

            return $final_parsing;
        }


        // 1: normalization procedure
        $str = $adjust->utf8ToAscii($original_str);
        $str = $adjust->removeSpecialCharacters($str);
        $str = $adjust->removeNumbers($str); //if the names in the database have dates, remove/comment this string
        $str = $adjust->spacePoints($str);
        $str = $adjust->reduceSpaces($str);

        $name_norm = $str;


        // parsing procedure

        //for genus
        $parsed_genus = $parser->binomialParserFinalUlt($str, $table_columns[4], $other);
        $genus = $parsed_genus[$table_columns[4]];

        // for species
        $parsed_species = $parser->binomialParserFinalUlt($parsed_genus[$other], $table_columns[6], $other);
        $species = $parsed_species[$table_columns[6]];

        // for infrasp1
        $parsed_infrasp1 = $parser->infraParserUlt($parsed_species[$other], $other, $crit_chars, False, $subsp_mark, $var_mark, $form_mark, $cv_mark);
        $infrasp1 = $parsed_infrasp1[$table_columns[10]];
        //$log->log(print_r($parsed_infrasp1));

        // for infrasp2
        $parsed_infrasp2 = $parser->infraParserUlt($parsed_infrasp1[$other], $other, $crit_chars, True, $subsp_mark, $var_mark, $form_mark, $cv_mark);
        $infrasp2 = $parsed_infrasp2[$table_columns[14]];

        $final_parsing = array_merge($parsed_genus,$parsed_species,$parsed_infrasp1,$parsed_infrasp2);
        if (array_key_exists($other,$final_parsing)){
            unset($final_parsing[$other]);
        }

        //adjusting (adding and revoving keys)
        unset($final_parsing['infrasp1_real_rank']);
        unset($final_parsing['infrasp2_real_rank']);

        $final_parsing[$table_columns[1]] = $original_str;
        $final_parsing[$table_columns[3]] = $synonym_of;
        $final_parsing[$table_columns[2]] = $name_norm;

        // adding phonetic equivalents
        $final_parsing[$table_columns[5]] = $phonetic->near_match($final_parsing[$table_columns[4]]);
        $final_parsing[$table_columns[7]] = $phonetic->near_match($final_parsing[$table_columns[6]]);
        $final_parsing[$table_columns[11]] = $phonetic->near_match($final_parsing[$table_columns[10]]);
        $final_parsing[$table_columns[15]] = $phonetic->near_match($final_parsing[$table_columns[14]]);

        // adding synonym table
        $final_parsing['id_synonym_table'] = $synonym_table;


        return $final_parsing;



    }





    /**
     * NM_00 General notes
     *
     * This function requires to a database with a checklist of plants
     *
     * Example of resulting array
     * Input name: Aria nivea Host subsp. nivea
     * in this case the result is ambiguous and 2 names are returned
     *
     * Array(
     * [0] => Array (   [id] => 12058
     *                  [original_name] => Aria nivea Host
     *                  [original_name_norm] => Aria nivea Host
     *                  [synonym_of] => Sorbus aria (L.) Crantz
     *                  [genus] => Aria
     *                  [genus_ph] => ARA
     *                  [species] => nivea
     *                  [species_ph] => NIVIA
     *                  [species_auth] => Host
     *                  [infrasp1_rank] =>
     *                  [infrasp1] =>
     *                  [infrasp1_ph] =>
     *                  [infrasp1_auth] =>
     *                  [infrasp2_rank] =>
     *                  [infrasp2] =>
     *                  [infrasp2_ph] =>
     *                  [infrasp2_auth] =>
     *                  [ED_genus] => 0
     *                  [ED_species] => 0
     *                  [ED_infrasp1] => 5
     *                  [ED_infrasp2] => 0
     *                  [genus_score] => 100
     *                  [species_score] => 100
     *                  [infrasp1_score] => 70
     *                  [infrasp2_score] => 100
     *                  [auth_score] => 100
     *                  [name_score] => 90
     *                  [name_score_old] => 100
     *                  [score] => 91
     *              )
     * [1] => Array (   [id] => 1364
     *                  [original_name] => Arabis nova Vill. subsp. nova
     *                  [original_name_norm] => Arabis nova Vill. subsp. nova
     *                  [synonym_of] =>
     *                  [genus] => Arabis
     *                  [genus_ph] => ARABIS
     *                  [species] => nova
     *                  [species_ph] => NAVA
     *                  [species_auth] => Vill.
     *                  [infrasp1_rank] => subsp
     *                  [infrasp1] => nova
     *                  [infrasp1_ph] => NAVA
     *                  [infrasp1_auth] =>
     *                  [infrasp2_rank] =>
     *                  [infrasp2] =>
     *                  [infrasp2_ph] =>
     *                  [infrasp2_auth] =>
     *                  [ED_genus] => 2
     *                  [ED_species] => 2
     *                  [ED_infrasp1] => 2
     *                  [ED_infrasp2] => 0
     *                  [genus_score] => 66
     *                  [species_score] => 50
     *                  [infrasp1_score] => 50
     *                  [infrasp2_score] => 100
     *                  [auth_score] => 0
     *                  [name_score] => 55
     *                  [name_score_old] => 55
     *                  [score] => 49
     *              )
     *      )
     *
     */
    public function nameMatch($str, $db, $score_threshold=0, $result_number=5, $allow_phonetic_match=True, $subsp_mark=False,$var_mark=False,$form_mark=False,$cv_mark=False,$force_case_insensitive=False,$synonym_tables=array())
    {

        /**
         * NM_01 Istances for all the classes used
         */

        $adjust = new Adjust();
        $pre_check = new PreCheck();
        $phonetic = new NearMatch();
        $lev = new DamerauLevenshteinMod();
        $queries = new NmQueries($db, $phonetic, $lev);
        $parser = new Parse();
        $gen_filter = new genusfilter();
        $filter = new Filter();
        $score = new Score();
        $log = new nmLog();
        // try to remove it
        $query_type='fuzzy_match';


        /**
         * NM_02 Things required for the setup process
         * @var string $table_name the name of the table with the parsed names
         * @var array $table_columns the name of every column of the database
         */

        $table_name = 'parsed_checklist';

        $table_columns = array(
            0 => 'id',
            1 => 'original_name',
            2 => 'original_name_norm',
            3 => 'synonym_of',
            4 => 'genus',
            5 => 'genus_ph',
            6 => 'species',
            7 => 'species_ph',
            8 => 'species_auth',
            9 => 'infrasp1_rank',
            10 => 'infrasp1',
            11 => 'infrasp1_ph',
            12 => 'infrasp1_auth',
            13 => 'infrasp2_rank',
            14 => 'infrasp2',
            15 => 'infrasp2_ph',
            16 => 'infrasp2_auth',
        );


        /**
         * NM_03 Base variables
         * NOTE: Do not change
         * @var string $original_str the input string is kept as this
         * @var string  $other the name of the key for the unparsed part of the string
         * @var string $risk_match
        */

        $original_str = $str;
        $other = 'other';
        $risk_match = false;

        $log->log('Input string: '.$original_str.' (file: '.__FILE__.' line: '.__LINE__.')');


        /**
         * NM_04 Normalization
         * All the function used are described in the class nmAdjust.class.php
         * The input string is transformed in the following way:
         * - characters are replaced with their ascii equivalent
         * - special characters are removed
         * - numbers are removed:
         * - double spaces, along with leading and trailing spaces are removed
         */

        $str = $adjust->utf8ToAscii($str);
        $str = $adjust->removeSpecialCharacters($str);
        $str = $adjust->removeNumbers($str); //if the names in the database have dates, remove/comment this string
        $str = $adjust->spacePoints($str);
        $str = $adjust->reduceSpaces($str);

        $log->log('Adjusted string: '.$str.' (file: '.__FILE__.' line: '.__LINE__.')');

        /**
         * NM_05 Pre check
         * All the function used are described in the class nmPreCheck.class.php
         * checks if the name is written in uppercase or in lowercase.
         * returns:
         * - 'upper' if it is all uppercase or if the first 2 letters are uppercase
         * - 'lower' if it is all lowercase
         * - 'mixed' otherwise
         *
         * then checks if there are characters that may cause problems in the parsing
         * NOTE: if the input string is in uppercase or force_case_sensitive is selected, the string in turned into lowercase
         * NOTE: even if it is changed, $uplow_check stays 'true'
         * for this reason $crit_chars are always returned in lowercase
         */

        $uplow_check = $pre_check->uplowCheck($str);
        if ($uplow_check == 'upper' or $force_case_insensitive == True) {
            $str = strtolower($str);
        }

        $crit_chars = $pre_check->warningCheck($str,$uplow_check,$subsp_mark,$cv_mark,$force_case_insensitive);
        $this->debug($crit_chars);

        $log->log('Uppercase or lowercase? '.$uplow_check.' (file: '.__FILE__.' line: '.__LINE__.')<br>'
            .'Critical characters: '.implode(', ',$crit_chars).' (file: '.__FILE__.' line: '.__LINE__.')');


        /*
         * NM_06 Exact match
         * search an exact match (between normalized strings) in the database
         * All the function used are described in the class nmQueries.class.php
         * if a match is found the name is returned with a score of 100
         * if the name is a synonym, even the accepted name is returned
         */

        $exact_match = $queries->selectExactMatch($str,$table_name,$table_columns[2],$table_columns[3],$table_columns[1],true,$synonym_tables);
        $this->debug($exact_match);

        if (is_array($exact_match) and count($exact_match) != 0) {
            foreach ($exact_match as &$exm){

            $exm['score'] = 100;
            $exm['auth_score'] = 100;
            $exm['name_score'] = 100;

            if (isset($exm[$table_columns[3]])) {
                $log->log('exact match: '.$exm[$table_columns[1]].'<br>'
                    .'synonym of : '.$exm[$table_columns[3]].'<br>'
                    .'(file: ' . __FILE__ . ' line: ' . __LINE__ . ')');
            }
            else{
                $log->log('exact match: '.$exm[$table_columns[1]].'<br>'
                    .'(file: ' . __FILE__ . ' line: ' . __LINE__ . ')');
            }
            }

            return $exact_match;
        }

        $this->debug('EXACT MATCH FAILED');
        $log->log('Exact match failed '.'(file: ' . __FILE__ . ' line: ' . __LINE__ . ')');

        /**
         * NM_07 Parsing
         * The name is parsed into its components
         * All the function used are described in the class nmParser.class.php
         *
         * For genus and species:
         * The first two strings in the string will be considered genus and species
         * Except if:
         * - the string is shorter than 3 characters
         * - the string ends with a dot
         * - the string is flagged as species but the first letter is uppercase
         * for every step the input string will be separated in an array with genus/species and other:
         * for example:
         * input string: abies alba muller
         * array with genus parsed: Array ([genus] => abies [other] => alba muller)
         * NOTE: if the input string is as mortin. abies alba muller the result will be the same following the reasons explained above
         */

        // for genus
        $parsed_genus = $parser->binomialParserFinalUlt($str, $table_columns[4], $other);
        $genus = $parsed_genus[$table_columns[4]];

        // for species
        $parsed_species = $parser->binomialParserFinalUlt($parsed_genus[$other], $table_columns[6], $other);
        $species = $parsed_species[$table_columns[6]];


        /**
         * for infrasp 1 and 2 if the result from the parser is an empty array, an array is created with
         * pre rank author (empty)
         * rank epithet (empty)
         * other (empty)
         *
         * for exaple for Abies alba
         * parsed_infrasp1 will be empty but an array is created with
         * * pre rank author (empty)
         * rank epithet (empty)
         * other (empty)
         *
         */

        // for infrasp1
        $parsed_infrasp1 = $parser->infraParserUlt($parsed_species[$other], $other, $crit_chars, False, $subsp_mark, $var_mark, $form_mark, $cv_mark);
        $infrasp1 = $parsed_infrasp1[$table_columns[10]];
        //$log->log(print_r($parsed_infrasp1));

        // for infrasp2
        $parsed_infrasp2 = $parser->infraParserUlt($parsed_infrasp1[$other], $other, $crit_chars, True, $subsp_mark, $var_mark, $form_mark, $cv_mark);
        $infrasp2 = $parsed_infrasp2[$table_columns[14]];
        //$log->log(print_r($parsed_infrasp2));

        $log->log('genus: '.$genus.'<br>'
            .'species: '.$species.'<br>'
            .'infrasp1: '.$infrasp1.'<br>'
            .'infrasp2: '.$infrasp2.'<br>'
            .'(file: ' . __FILE__ . ' line: ' . __LINE__ . ')');

        /**
         * NM_08 Genus match
         *
         * the phonetic genus is calculated (nmPhoneticMatch.class.php)
         *
         * for prematch all the functions are explained in nmQueries.class.php
         * for postmatch all the functions are explained in genus_prefilter_class.php
         * in both the cases, taxamatch rules are followed
         * to better understand the filter, check the paper of taxamatch
         *
         * prematch:
         * only names with a similar genus are selected from the database
         *
         * distance calculation:
         * - ED = 0 for exact matches
         * - ED = P for phonetic matches
         * - ED calculated with Lev for other cases
         *
         * postmatch:
         * only names with a genus name similar to the input one are kept
         *
         * returned results:
         * if the input name has only the genus AN EMPTY ARRAY is returned (can be changed)
         * To see the resulting genus, with their distance, change logAdv($keep_false=True) in nmLog.class.php
         */

        // phonetic match
        $genus_ph = $phonetic->near_match($genus);

        // prematch
        $pre_genus = $queries->selectPreGenusFilter($genus, $genus_ph, $table_name, $table_columns[4], $table_columns[5], $query_type, $allow_phonetic_match, $synonym_tables);
        //$this->debug($pre_genus);

        // distance calculation
        foreach ($pre_genus as &$name) {

            if (strtoupper($genus) == strtoupper($name[$table_columns[4]])) {
                $name['ED_genus'] = 0;
            } elseif ($genus_ph == $name[$table_columns[5]] and $allow_phonetic_match == True) {
                $name['ED_genus'] = 'P';
            } else {
                $name['ED_genus'] = $lev->mdld_php(strtoupper($genus), strtoupper($name[$table_columns[4]]),1,1);
            }

        }

        // postmatch
        $post_genus = $gen_filter->genusPostFilter($genus, $pre_genus);

        // returned results
        if ($species == '') {
            //disabled for now
            //$genus_result = $score->onlyGenusScore($post_genus, $table_columns[4], 'ED_genus');
            //return $genus_result;
            return array();
        }

        // Show genus results (disabled from nmLog)
        if ($log->logAdv())
        {
            $tempgen = array_unique(array_column($post_genus, $table_columns[4]));
            $genresult = array_intersect_key($post_genus, $tempgen);
            $gentab = '<table>';
            $gentab .= '<tr style="border: 1px solid black"><th>input_genus|</th><th>similar_genus|</th><th>distance</th><tr>';
            foreach ($genresult as $genr) {
                $gentab .= '<tr><td>'.$genus.'</td><td>'.$genr[$table_columns[4]].'</td><td>'.$genr['ED_genus'].'</td><tr>';
            }
            $gentab .='</table>';
            $log->log('Similar genus:<br>'.$gentab.'(file: ' . __FILE__ . ' line: ' . __LINE__ . ')');

        }


        /**
         * NM_09 Species match
         *
         * follows the same rules as genus
         * pre e post filter function are described in nmFilter.class.php
         */

        // phonetic match
        $species_ph = $phonetic->near_match($species);

        //prematch
        $pre_species = $filter->preFilter($species, $species_ph, $post_genus, $table_columns[6], $table_columns[7], $query_type, $allow_phonetic_match);
        $this->debug($pre_species);

        // distance calculation
        foreach ($pre_species as &$name) {

            if (strtoupper($species) == strtoupper($name[$table_columns[6]])) {
                $name['ED_species'] = 0;
            } elseif ($species_ph == $name[$table_columns[7]] and $allow_phonetic_match == True) {
                $name['ED_species'] = 'P';
            } else {
                $name['ED_species'] = $lev->mdld_php(strtoupper($species), strtoupper($name[$table_columns[6]]), 1, 4);
            }

        }

        // postmatch
        $post_species = $filter->postFilter($species, $pre_species, $table_columns[6], 'ED_species', 'ED_genus', $allow_phonetic_match);
        $this->debug($post_species);

        // Show species results (disabled from nmLog)
        if ($log->logAdv())
        {
            $tempsp = array_unique(array_column($post_species, $table_columns[6]));
            $spresult = array_intersect_key($post_species, $tempsp);
            $sptab = '<table>';
            $sptab .= '<tr style="border: 1px solid black"><th>input_species|</th><th>similar_species|</th><th>distance</th><tr>';
            foreach ($spresult as $spr) {
                $sptab .= '<tr><td>'.$species.'</td><td>'.$spr[$table_columns[6]].'</td><td>'.$spr['ED_species'].'</td><tr>';
            }
            $sptab .='</table>';
            $log->log('Similar species:<br>'.$sptab.'(file: ' . __FILE__ . ' line: ' . __LINE__ . ')');

        }


        /**
         * NM_10 Infrasp1 match
         *
         * this one is tricky, it is completly rewritten from the original version
         * to reparse the infraspecies (happens if the algorithm is quite sure that the crit_car:
         * is part of the author and not a infrasp1 identifier:
         * - infrasp1_real_rank must be one of the critical characters
         * - infrasp1 must not be empty
         * - species epitheth must be different from isfrasp1 epitheth
         * - infrasp1_real_rank must be present in one of the authors names in DB OR
         * - the infrasp1 of the parsed name is in the database author name
         *
         * e.g. CONSOLIDA REGALIS S.F.GRAY
         * gray will be considered as infrasp1, but then reparsed as author
         *
         * Before reparsing, the crit char in the name is turned into uppercase, in this way it is treated as an Author by the parsing algorithm
         *
         * then lev is calculated for remaining strings,
         * NOTE: if the input name has a infrasp, names in db without infrasp will have avery high ED
         * e.g input: ACHILLEA MILLEFOLIUM V. MILLEFILIUM match Achillea millefolium ED_infrasp1 = 11
         * Names with an ED > 2 will be removed (except if they have an empty infrasp1)
         *
         */

       // start of the tricky part
        if ($parsed_infrasp1['infrasp1_real_rank'] != ''and $parsed_infrasp1['infrasp1'] != '' and in_array($parsed_infrasp1['infrasp1_real_rank'], $crit_chars)){
            if ($parsed_species[$table_columns[6]] != $parsed_infrasp1[$table_columns[10]]){
                $crit_is_author = false;
                //lev for every infrasp1, if distance is less than 2 the parsing is kept
                foreach ($post_species as $name_test) {

                    $exploded_auth = explode(' ',strtolower($name_test[$table_columns[8]]));
                    if (in_array($crit_chars[0], $exploded_auth)){
                        $crit_is_author = true;

                        break;
                    }
                    if ($parsed_infrasp1['infrasp1'] != '' and in_array($parsed_infrasp1['infrasp1'],$exploded_auth)){
                        $crit_is_author = true;

                        break;
                    }
                }
                // if crit car is part of the author name, parsed infrasp1 is reparsed
                if ($crit_is_author)
                {
                    $temp = explode(' ', $parsed_species[$other]);
                    $temp_infrasp1_key = array_search($parsed_infrasp1['infrasp1_real_rank'], $temp);
                    $temp[$temp_infrasp1_key] = strtoupper($temp[$temp_infrasp1_key]);
                    $temp = implode(' ', $temp);

                    $parsed_infrasp1 = $parser->infraParserUlt($temp, $other, $crit_chars, False, $subsp_mark, $var_mark, $form_mark, $cv_mark);
                    unset($crit_chars[0]);

                    $log->log('infraspecific epitheth reparsed:<br>
                                author: '.$parsed_infrasp1['species_auth'].' (file: ' . __FILE__ . ' line: ' . __LINE__ . ')');

                }
                else{
                    $log->log('infraspecific epitheth NOT reparsed<br>
                                 (file: ' . __FILE__ . ' line: ' . __LINE__ . ')');
                }
            }
        }
        // end of the tricky part

        //classic filter part
        //$infrasp1_rank = $parsed_infrasp1[$table_columns[9]];
        $infrasp1 = $parsed_infrasp1[$table_columns[10]];
        $infrasp1_ph = $phonetic->near_match($infrasp1);

        $distance_infrasp1 = $this->distLev($post_species, $infrasp1, $infrasp1_ph, $table_columns[10], $table_columns[11], $allow_phonetic_match);
        $post_infrasp1 = array();

        //infrasp is NOT removed if ED <2 or if the database name has no infrasp1 or if the input name has no infrasp1
        foreach ($distance_infrasp1 as $dif1) {

            if ($dif1['ED_infrasp1'] <= 2 or $dif1['infrasp1'] == '' or $parsed_infrasp1[$table_columns[10]] == '') {
                array_push($post_infrasp1, $dif1);
            }
        }


        if ($log->logAdv())
        {
            if (empty($post_infrasp1)){
                $log->log('No similar subspecies (file: ' . __FILE__ . ' line: ' . __LINE__ . ')');
            }else {

                $tempinfrasp = array_unique(array_column($post_infrasp1, $table_columns[10]));
                $infraspresult = array_intersect_key($post_infrasp1, $tempinfrasp);
                $infrasptab = '<table>';
                $infrasptab .= '<tr style="border: 1px solid black"><th>input_infrasp|</th><th>similar_infrasp|</th><th>distance</th><tr>';
                foreach ($infraspresult as $infr) {
                    $infrasptab .= '<tr><td>' . $infrasp1 . '</td><td>' . $infr[$table_columns[10]] . '</td><td>' . $infr['ED_infrasp1'] . '</td><tr>';
                }
                $infrasptab .= '</table>';
                $log->log('Similar infrasp1:<br>' . $infrasptab . '(file: ' . __FILE__ . ' line: ' . __LINE__ . ')');
            }

        }



        /**
         * NM_11 Infrasp2 match
         *
         * Exact same rules as Infrasp1
         */

        // start of the tricky part
        if ($parsed_infrasp2['infrasp2_real_rank'] != ''and $parsed_infrasp2['infrasp2'] != '' and in_array($parsed_infrasp2['infrasp2_real_rank'], $crit_chars)){
            if ($parsed_infrasp1[$table_columns[10]] != $parsed_infrasp2[$table_columns[14]]){
                $crit_is_author2 = false;
                //lev for every infrasp1, if distance is less than 2 the parsing is kept
                foreach ($post_infrasp1 as $name_test2) {

                    $exploded_auth2 = explode(' ',strtolower($name_test2[$table_columns[12]]));

                    if (in_array($crit_chars[0],$exploded_auth2)){
                        $crit_is_author2 = true;

                        break;
                    }
                    if ($parsed_infrasp2['infrasp2'] != '' and in_array($parsed_infrasp2['infrasp2'],$exploded_auth2)){
                        $crit_is_author2 = true;

                        break;
                    }
                }
                // if crit car is part of the author name, parsed infrasp1 is reparsed
                if ($crit_is_author2)
                {
                    $temp2 = explode(' ', $parsed_infrasp1[$other]);
                    $temp_infrasp2_key = array_search($parsed_infrasp2['infrasp2_real_rank'], $temp2);
                    $temp2[$temp_infrasp2_key] = strtoupper($temp2[$temp_infrasp2_key]);
                    $temp2 = implode(' ', $temp2);

                    $parsed_infrasp2 = $parser->infraParserUlt($temp2, $other, $crit_chars, True, $subsp_mark, $var_mark, $form_mark, $cv_mark);

                    $log->log('infraspecific 2 epitheth reparsed:<br>
                                author: '.$parsed_infrasp2['infrasp1_auth'].' (file: ' . __FILE__ . ' line: ' . __LINE__ . ')');

                }
                else{
                    $log->log('infraspecific 2 epitheth NOT reparsed<br>
                                 (file: ' . __FILE__ . ' line: ' . __LINE__ . ')');
                }
            }
        }
        // end of the tricky part

        //classic filter part
        //$infrasp2_rank = $parsed_infrasp2[$table_columns[13]];
        $infrasp2 = $parsed_infrasp2[$table_columns[14]];
        $infrasp2_ph = $phonetic->near_match($infrasp2);

        $distance_infrasp2 = $this->distLev($post_infrasp1, $infrasp2, $infrasp2_ph, $table_columns[14], $table_columns[15], $allow_phonetic_match);

        //infrasp2 is NOT removed if ED <2 or if the database name has no infrasp2 or if the input name has no infrasp2
        $post_infrasp2 = array();
        foreach ($distance_infrasp2 as $dif2) {

            if ($dif2['ED_infrasp2'] <= 2 or $dif2['infrasp2'] == ''or $parsed_infrasp2[$table_columns[14]] == '') {
                array_push($post_infrasp2, $dif2);
            }
        }


        if ($log->logAdv())
        {
            if (empty($post_infrasp2)){
                $log->log('No similar subspecies 2 (file: ' . __FILE__ . ' line: ' . __LINE__ . ')');
            }else {

                $tempinfrasp2 = array_unique(array_column($post_infrasp2, $table_columns[14]));
                $infrasp2result = array_intersect_key($post_infrasp2, $tempinfrasp2);
                $infrasp2tab = '<table>';
                $infrasp2tab .= '<tr style="border: 1px solid black"><th>input_infrasp2|</th><th>similar_infrasp2|</th><th>distance</th><tr>';
                foreach ($infrasp2result as $infr2) {
                    $infrasp2tab .= '<tr><td>' . $infrasp2 . '</td><td>' . $infr2[$table_columns[14]] . '</td><td>' . $infr2['ED_infrasp2'] . '</td><tr>';
                }
                $infrasp2tab .= '</table>';
                $log->log('Similar infrasp2:<br>' . $infrasp2tab . '(file: ' . __FILE__ . ' line: ' . __LINE__ . ')');
            }

        }

        /**
         * NM_12 Score
         *
         * the final parsed array is build, merging all the parsed arrays created
         * the key 'other', if present is removed because it is no more necessary
         *
         * then a score is given for both the taxon name and the author name
         * All the function used are explained in nmScore.class.php
         */

        $final_parsing = array_merge($parsed_genus,$parsed_species,$parsed_infrasp1,$parsed_infrasp2);
        if (array_key_exists($other,$final_parsing)){
            unset($final_parsing[$other]);
        }

        $final_array = $post_infrasp2;



        // assign a similarity score for every part of the name except authors
        $final_array = $score->getScore($final_array, $table_columns[4],$table_columns[6],$table_columns[10],$table_columns[14]);
        // assign a similarity score for authors
        $final_array = $score->getAuthorScore($final_array, $final_parsing, $table_columns[8],$table_columns[12],$table_columns[16]);
        // mean score
        $final_array = $score->modifyScore($final_array,$final_parsing,$table_columns[9],$table_columns[13],$table_columns[4],$table_columns[6],$table_columns[10],$table_columns[14]);
        // combine score if below threshold it is removed
        $final_array = $score->getCombinedScore($final_array,$score_threshold);
        // if no results are left above the threshold
        if (count($final_array) == 0){
            $final_array = array ();
            return $final_array;
        }



        /**
         * NM_13 Result shaping
         *
         * the array of resulting names (similar to the input one) are ordered
         * from the one with the highest score to the one with the lowest score
         *
         * certain conditions can influence the result returned:
         *
         * 1:
         * if the result has multible names but the first one:
         * - has a score >= 89 and
         * - has a score > than the score of the second name + 11 and
         * - the name score has not been decreased and
         * - the author of the input name has 'non' and the first result too (or neither one have 'non)
         * a single result is returned
         *
         *
         *
         * 2:
         * if the result has a single name but it has a score <= 89 or its score has been decreased or the word 'non'
         * is present only in the input name or in the database name, a second name (empty) will be created
         *
         * NOTE: the functon for the 'non' word check is in nmParser.class.php
         *
         * this because the script to display results, set the result as AMBIGUOUS if more than 1 name is returned
         */


        // order and remove
        array_multisort(array_column($final_array, 'score'), SORT_DESC, $final_array);
        $final_array = array_splice($final_array, 0, $result_number);
        //print_r($final_parsing);
        //print_r($final_array);

        //for log
        if ($log->logAdv()){
            $ftab = '<table>';
            $ftab .= '<tr style="border: 1px solid black"><th>name|</th><th>synonym_of|</th><th>name_score|</th><th>auth_score|</th><th>score|</th><th>synonym_from|</th><tr>';
            foreach ($final_array as $name) {
                $ftab .= '<tr><td>' . $name['original_name'] . '</td><td>' . $name['synonym_of'] . '</td><td>' . $name['name_score'] . '</td><td>' . $name['auth_score'] . '</td><td>' . $name['score'] . '</td><td>' . $name['riferimento'] . '</td><tr>';
            }
            $ftab .= '</table>';
            $log->log('Input string: '.$original_str.'<br>Results:<br>' . $ftab . '(file: ' . __FILE__ . ' line: ' . __LINE__ . ')');
        }


        /**
         * Example of resulting array
         * in this case the result is ambiguous and 2 names are returned
         *
         * Array(
         * [0] => Array ( [id] => 12058 [original_name] => Aria nivea Host [original_name_norm] => Aria nivea Host
         * [synonym_of] => Sorbus aria (L.) Crantz [genus] => Aria [genus_ph] => ARA [species] => nivea
         * [species_ph] => NIVIA [species_auth] => Host [infrasp1_rank] => [infrasp1] => [infrasp1_ph] => [infrasp1_auth] =>
         * [infrasp2_rank] => [infrasp2] => [infrasp2_ph] => [infrasp2_auth] => [ED_genus] => 0 [ED_species] => 0
         * [ED_infrasp1] => 5 [ED_infrasp2] => 0 [genus_score] => 100 [species_score] => 100 [infrasp1_score] => 70
         * [infrasp2_score] => 100 [auth_score] => 100 [name_score] => 90 [name_score_old] => 100 [score] => 91 )
         * [1] => Array ( [id] => 1364 [original_name] => Arabis nova Vill. subsp. nova [original_name_norm] => Arabis nova Vill. subsp. nova
         * [synonym_of] => [genus] => Arabis [genus_ph] => ARABIS [species] => nova [species_ph] => NAVA [species_auth] => Vill.
         * [infrasp1_rank] => subsp [infrasp1] => nova [infrasp1_ph] => NAVA [infrasp1_auth] => [infrasp2_rank] =>
         * [infrasp2] => [infrasp2_ph] => [infrasp2_auth] => [ED_genus] => 2 [ED_species] => 2 [ED_infrasp1] => 2 [ED_infrasp2] => 0
         * [genus_score] => 66 [species_score] => 50 [infrasp1_score] => 50 [infrasp2_score] => 100 [auth_score] => 0
         * [name_score] => 55 [name_score_old] => 55 [score] => 49 )
         */
        // check if author is inserted
        $auth_sum = $final_parsing[$table_columns[8]].$final_parsing[$table_columns[12]].$final_parsing[$table_columns[16]];


        if (isset($final_array[1])) {
            if ($final_array[0]['score'] > 89 and
                // added author check:
                ($final_array[0]['auth_score'] >= 40 or $auth_sum == '') and
                $final_array[0]['score'] - $final_array[1]['score'] > 11 and
                $final_array[0]['name_score'] == $final_array[0]['name_score_old'] and
                    (($parser->nonIsPresent($final_parsing) == false and $parser->nonIsPresent($final_array[0]) == false) or
                     ($parser->nonIsPresent($final_parsing) == true and $parser->nonIsPresent($final_array[0]) == true))) {
                $array_temp = $final_array[0];
                $final_array = array();
                $final_array[0] = $array_temp;
            }
            elseif ($final_array[0]['name_score'] == 100 and $final_array[1]['name_score'] != 100 and
                    // added author check:
                    ($final_array[0]['auth_score'] >= 40 or $auth_sum == '') and
                    (($parser->nonIsPresent($final_parsing) == false and $parser->nonIsPresent($final_array[0]) == false) or
                    ($parser->nonIsPresent($final_parsing) == true and $parser->nonIsPresent($final_array[0]) == true)))
            {
                $array_temp = $final_array[0];
                $final_array = array();
                $final_array[0] = $array_temp;
            }

            // sperimental elsseif, read description below
            elseif (isset($final_array[0]['risk_match']) and $final_array[0]['name_score_old'] == 100 and $risk_match == true and
                    (($parser->nonIsPresent($final_parsing) == false and $parser->nonIsPresent($final_array[0]) == false) or
                    ($parser->nonIsPresent($final_parsing) == true and $parser->nonIsPresent($final_array[0]) == true)))
            {
                $array_temp = $final_array[0];
                $final_array = array();
                $final_array[0] = $array_temp;
            }
        }

        /**
         * sperimental elseif
         * is only one result is present and the name has a different infraspecific level but
         * species == infrasp1 or infrasp1 == infrasp2, the result is considered true
         *
         */

        elseif (!isset($final_array[1]) and isset($final_array[0]['risk_match']) and $final_array[0]['name_score_old'] == 100 and $risk_match == true and
            (($parser->nonIsPresent($final_parsing) == false and $parser->nonIsPresent($final_array[0]) == false) or
                ($parser->nonIsPresent($final_parsing) == true and $parser->nonIsPresent($final_array[0]) == true)))
        {
            return $final_array;
        }

        elseif (!isset($final_array[1]) and
            ($final_array[0]['score'] <= 89 or
             $final_array[0]['name_score'] != $final_array[0]['name_score_old']) or
             ($final_array[0]['auth_score'] < 40 and $auth_sum != '') or
             ($parser->nonIsPresent($final_parsing) == false and $parser->nonIsPresent($final_array[0]) == true) or
             ($parser->nonIsPresent($final_parsing) == true and $parser->nonIsPresent($final_array[0]) == false)) {
            $final_array[1] = array('original_name' =>'', 'synonym_of'=>'', 'score'=>'');
        }




        return $final_array;




    }



    private function distLev($array,$name_epithet,$name_epithet_ph,$name_column,$name_ph_column,$allow_phonetic_match,$rank=False, $rank_column=False)
    {
        $lev = new DamerauLevenshteinMod();
        $name_ED = 'ED_'.$name_column;
        if (count($array) == 0)
        {
            return $array;
        }
        foreach ($array as &$name) {

            // probably needs to be removed
            /*
            if ($infrasp1_rank == $name[$table_columns[9]]) {
                $name['rank_match1'] = 1;
            } else {
                $name['rank_match1'] = 0;
            }
*/

            if (strtolower($name_epithet) == strtolower($name[$name_column])) {
                $name[$name_ED] = 0;

            } elseif ($name_epithet_ph == $name[$name_ph_column] and $allow_phonetic_match == True) {
                $name[$name_ED] = 'P';
            } else {
                $name[$name_ED] = $lev->mdld_php(strtolower($name_epithet), strtolower($name[$name_column]), 1, 1);
            }

        }
        return $array;
    }

    public function searchMatch($str,$db)
    {
        $str = trim($str);
        $table_name = 'parsed_checklist';
        $table_column = 'original_name';
        $queries = new NmQueries($db, '', '');
        $lev = new DamerauLevenshteinMod();

        $most_similar_results = $queries->getSimilarStrings($str,$table_name,$table_column);
        // remove duplicates
        $most_similar_results = array_unique($most_similar_results);
        $dist1 = 999;
        $most_similar1 ='';


        if (!empty($most_similar_results)) {

            foreach ($most_similar_results as $most_similar_result) {
                $end1 = substr($str,-1,1);
                $end2 = substr($most_similar_result,-1,1);

                // if lenght is different and they don't end with the same letter of the input string they are discarded
                // CAUSES PROBLEMS
                //if (strtolower($end1) != strtolower($end2) and strlen($str) != strlen($most_similar_result))
                //{
                //    continue;
                //}

                // if it has a different number of spaces it is discarded
                if (substr_count($most_similar_result, ' ') != substr_count($str, ' ')){
                    continue;
                }
                // if position of space is too different it is discarded
                $pos1 = strpos($str, ' ');
                $pos2 = strpos($most_similar_result, ' ');
                //echo $pos1;
                //echo $pos2;
                if ($pos2 != $pos1 and $pos2 != $pos1 +1 and $pos2 != $pos1 -1 and $pos2 != $pos1 +2 and $pos2 != $pos1 -2){
                    continue;
                }

                $dist_test1 = levenshtein(strtolower($str), $most_similar_result,2,2,2);
                //echo $most_similar_result . ' ' . $dist_test1 . '<br>';
                if ($dist_test1 < $dist1) {
                    $dist1 = $dist_test1;
                    $most_similar1 = $most_similar_result;
                }
            }
            //echo $most_similar1; //UTILE DA VEDERE
            return $most_similar1;
        }

    }

}