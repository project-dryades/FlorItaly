<?php


class Parse
{

    // IMPORTANTISSIMO al momento sono tutti case sensitive gli in_array DA MODIFICARE


    private $genus = array ('gen.', 'gen', 'genus',);
    private $species = array ('sp.', 'sp', 'spp.', 'spp', 'spec.', 'spec', 'specie', 'species','sect.', 'sect');
    private $subsp = array('subsp.', 'ssp.' , 'nothosubsp.', 'nothosubsp', 'notosubsp.',);
    private $var = array('var.', 'var', 'variety','v', 'v.' );
    private $form = array ('f.', 'f', 'form', 'form.','fo', 'fo.');

    private function debug($mixed,$active=False)
    {
        if ($active)
        {
            echo '<p>';
            print_r($mixed);
            echo '<p>';
        }
    }


    // QUESTE 3 FUNZIONI SONO TUTTUNO

    // parser valido solo per genere e specie

    public function binomialParser($str, $key, $other)
    {

        // devo valutare il caso in cui $str è vuota o $result[$other] diventi vuoto
        $result = array();
        if ($str == '')
        {
            $result[$key] = '';
            $result[$other] = '';
            return $result;
        }

        $str_exploded = explode(' ', trim($str));
        $result[$key] = $str_exploded[0];
        unset($str_exploded[0]);

        if (array_key_exists(1, $str_exploded) == False)
        {
            $result[$other] = '';
            return $result;

        }
        $result[$other] = implode(' ', $str_exploded);
        return $result;
    }


   
   /**
    * restituisce 0 se genere o specie non soddisfano i requisiti richiesti, altrimenti 1:
    * 1: sono più corti di 2 caratteri
    * 2: un prefisso che sta ad indicare genere o specie
    * @param array $key
    * @param string $key - ammessi solo 'genus' o 'species'
    */
    
    private function binomialPostParserCheck($array, $key)
    {


        // necessario nel caso di stringhe vuote
        if ((($key == 'genus') OR ($key == 'species')) AND ($array[$key] == ''))
        {
            return True;
        }
        elseif (($key == 'genus') AND (strlen($array[$key]) <= 2 OR in_array($array[$key], $this->genus)))
        {
            return False;
        }
        elseif (($key == 'species') AND (strlen($array[$key]) <= 2 OR in_array($array[$key],$this->species))) 
        {
            return False;
        }
        elseif (($key != 'genus') AND $key != 'species')
        {
            //echo 'CRITERI NON RISPETTATI';
            return False;
        }
        else 
        {
            return True;
        }
    }


    /**
    * continua a fare il parse finchè non trova un genere/specie che soddisfi le richeste
    * di binomialPostParserCheck
    * @param string $str - il nome originale
    * @param string $key - ammessi solo 'genus' o 'species'
    * @param string $other - il nome della $key di ciò che non è genere o specie
    * @return array - un array con il genere/specie separato dal resto
    */

    public function binomialParserFinal($str, $key, $other)
    {
        
        $array = $this->binomialParser($str, $key, $other);
        $check = $this->binomialPostParserCheck($array, $key);
        
        while ($check == False ) 
        {
            $array = $this->binomialParser($array[$other], $key, $other);
            $check = $this->binomialPostParserCheck($array, $key);
        }
        
        return $array;
    }

    // $rank è il nome del rango
    // non modificare, serve per il parsing della checklist
    public function infraParser($str, $other, $infrarank1_exists = False)
    {
        $result = array();
        if ($str == '')
        {
            return $result;
        }

        // iniziamo prima vedo se è presente un infraspecie

        $str_exploded = explode(' ', trim($str));
        
        $rank ='';
        if ($rank == False)
        {
            foreach ($this->subsp as $ssp)
            {
                $rank_position = array_search($ssp, $str_exploded);
                if ($rank_position !== False AND array_key_exists($rank_position+1, $str_exploded)
                    AND preg_match('~^\p{Lu}~u', $str_exploded[$rank_position+1]) == False)
                {
                    $rank = 'subsp';
                    break;
                }

            }
        }
        if ($rank == False)
        {
            foreach ($this->var as $variety)
            {
                $rank_position = array_search($variety, $str_exploded);
                if ($rank_position !== False AND array_key_exists($rank_position+1, $str_exploded)
                    AND preg_match('~^\p{Lu}~u', $str_exploded[$rank_position+1]) == False)
                {
                    $rank = 'var'; 
                    break;
                }
            } 
        }
        if ($rank == False)
        {
            foreach ($this->form as $forma)
            {
                $rank_position = array_search($forma, $str_exploded);
                if ($rank_position !== False AND array_key_exists($rank_position+1, $str_exploded)
                    AND preg_match('~^\p{Lu}~u', $str_exploded[$rank_position+1]) == False
                    AND $str_exploded[$rank_position+1] != 'nom.'
                    AND $str_exploded[$rank_position+1] != 'ex'
                    AND $str_exploded[$rank_position+1] != 'isonym'
                    AND $str_exploded[$rank_position+1] != '&')
                {
                    $rank = 'form'; 
                    break;
                }
            } 
        }

        // se non è presente la stringa inserità è l'autore della specie
        if ($rank == False)
        {
            $sp_auth = implode(' ', $str_exploded);
            if ($infrarank1_exists)
            {
                $result['infrasp1_auth'] = $sp_auth;
            }
            else 
            {
                $result['species_auth'] = $sp_auth;
            }
            
            return $result;
        }
        else 
        {
            $sp_auth = implode(' ', array_slice($str_exploded,0,$rank_position));
            $infra = $str_exploded[$rank_position+1];
            $count = count($str_exploded);
            $rest = implode(' ', array_slice($str_exploded,$rank_position+2,$count));
            if ($infrarank1_exists)
            {
                $result['infrasp2_rank'] = $rank;
                $result['infrasp1_auth'] = $sp_auth;
                $result['infrasp2'] = $infra;
                $result['infrasp2_auth'] = $rest;

            }
            else
            {
            $result['infrasp1_rank'] = $rank;
            $result['species_auth'] = $sp_auth;
            $result['infrasp1'] = $infra;
            $result[$other] = $rest;
            }


            return $result;

        }

    
    }




    // These 3 functions are used for the genus/species parsing

    /**
     * continua a fare il parse finchè non trova un genere/specie che soddisfi le richeste
     * di binomialPostParserCheck
     * @param string $str - il nome originale
     * @param string $key - ammessi solo 'genus' o 'species'
     * @param string $other - il nome della $key di ciò che non è genere o specie
     * @return array - un array con il genere/specie separato dal resto
     */
    public function binomialParserFinalUlt($str, $key, $other)
    {

        $array = $this->binomialParserUlt($str, $key, $other);
        $check = $this->binomialPostParserCheckUlt($array, $key);

        while ($check == False )
        {
            $array = $this->binomialParserUlt($array[$other], $key, $other);
            $check = $this->binomialPostParserCheckUlt($array, $key);
        }

        return $array;
    }

    /**
     * parser for genus and species, which of the 2 is specified by $key
     * if the input string is empty, the genus/species returned will be an empty string
     * the first word of the input string is separated and considered as genus/species
     * NOTE: input parameters are the same of binomialParserFinalUlt
     *
     */

    private function binomialParserUlt($str, $key, $other)
    {

        // devo valutare il caso in cui $str è vuota o $result[$other] diventi vuoto
        $result = array();
        if ($str == '')
        {
            $result[$key] = '';
            $result[$other] = '';
            return $result;
        }

        $str_exploded = explode(' ', trim($str));

        // test for sect
        //if ($key = 'species' and
            if (
            ($str_exploded[0] == 'sect' or $str_exploded[0] == 'sect.') and
            isset($str_exploded[1])){
                // devo metterlo in lower sennò non passa il filtro
            $result[$key] = strtolower($str_exploded[1]);
            unset($str_exploded[0]);
            unset($str_exploded[1]);
                if (array_key_exists(2, $str_exploded) == False)
                {
                    $result[$other] = '';
                    return $result;

                }
        }
        else {
            $result[$key] = $str_exploded[0];
            unset($str_exploded[0]);
            if (array_key_exists(1, $str_exploded) == False)
            {
                $result[$other] = '';
                return $result;

            }
        }



        $result[$other] = implode(' ', $str_exploded);
        return $result;
    }



    /**
     * returns 0 if the string can't be a genus/species, otherwise it returns 1
     * a string can't be a genus/species if:
     * - the string is shorter than 3 characters
     * - the string ends with a dot
     * - the string is flagged as species but the first letter is uppercase
     * @param array $array the parsed string
     * @param string $key only'genus' o 'species'
     * @param bool returns True if the string doesn't follow these parameters, otherwise it returns False
     */

    private function binomialPostParserCheckUlt($array, $key)
    {
        $str = $array[$key];


        // necessario nel caso di stringhe vuote
        if ((($key == 'genus') OR ($key == 'species')) AND ($str == ''))
        {
            return True;
        }

        elseif ($key == 'genus' AND
            (strlen($str) <= 2 OR
            in_array($str, $this->genus) OR
            (strpos($str, '.') !== False)))
        {
            return False;
        }
        elseif ($key == 'species' AND
            (strlen($str) <= 2 OR
            in_array($str,$this->species) OR
            (strpos($str, '.') !== False) OR
            (preg_match('~^\p{Lu}~u', $str) AND strtoupper($str) != $str)))
        {
            return False;
        }
        elseif (($key != 'genus') AND $key != 'species')
        {
           // echo 'CRITERI NON RISPETTATI';
            return False;
        }
        else
        {
            return True;
        }
    }





   // &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
   // RULES

    private function startsWith( $haystack, $needle ) {
        $length = strlen( $needle );
        return substr( $haystack, 0, $length ) === $needle;
    }

    private function endsWith( $haystack, $needle ) {
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }

   //



    /**
     *
     * In an a exploded string, check if the word after the inserted word ends with a dot
     *
     * @param $nextposition string
     * @return bool True if word after the inserted word ends with a dot
     *
     */
    private function dotAfter($nextposition)
    {

        if ($this->endsWith($nextposition ,'.')) {
            $this->debug('true');
            return True;
        }
        else {
            $this->debug('false');
            return False;
        }
    }

    /**
     *
     * In an a exploded string, check if the word after the inserted word starts with a capital letter
     *
     * @param $nextposition string
     * @return bool True if word after the inserted word starts with a capital letter
     *
     */
    private function upperAfter($nextposition)
    {

        if (preg_match('#^\p{Lu}#u', $nextposition)) {
            $this->debug('true');
            return True;
        }
        else {
            $this->debug('false');
            return False;
        }
    }

    /**
     *
     * In an a exploded string, check if the word after the inserted word is shorter than 3 char
     *
     * @param $nextposition string
     * @return bool True if word after the inserted word is shorter than 3 char
     *
     */
    private function shortAfter($nextposition)
    {

        if (strlen($nextposition) < 3) {
            $this->debug('true');
            return True;
        }
        else {
            $this->debug('false');
            return False;
        }
    }


    /**
     *
     * parse a string without genus and species
     *
     * description of the function:
     *
     * at the beginning of the function infraspecific rank indicators accepted are displayed (can be modified)
     *
     * addictional infraspecific rank indicators are added if specified by the user ($subsp_mark, ...)
     *
     * if the input string is empty every value of the array will be empty
     *
     * if the input string has the word 'not' or 'non' a key ('non') is created (this is used in order to avoid errors in author match
     * e.g. non L. matched with L.
     *
     * for identify infraspecific ephithets the following rules mus be followed:
     * - the word before it (infraspecific rank indicator) must be present in one of the arrays at the beginning of the function ($subsp,...)
     * - the infraspecific rank indicator must not be a critical word
     *   or, if it is a critical word, the following word must:
     *      - not be in uppercase
     *      - not be shorter than 3 letters
     *      - not end with a dot
     *      - not be 'nom.', 'ex', 'isonym', '&' (only for f. as form)
     *
     * the returned array will have this structure:
     *
     * if $infrarank1_exists: 'infrasp2_rank','infrasp2_real_rank','infrasp1_auth','infrasp2','infrasp2_auth'
     * otherwise:'infrasp1_rank','infrasp1_real_rank','species_auth','infrasp1',$other
     *
     * @param $str string
     * @param $other string
     * @param array $crit_chars
     * @param false $infrarank1_exists
     * @param false $subsp_mark
     * @param false $var_mark
     * @param false $form_mark
     * @param false $cv_mark
     * @return array the parsed string
     */
    public function infraParserUlt($str, $other,$crit_chars=array(), $infrarank1_exists = False,
                                   $subsp_mark=False,$var_mark=False,$form_mark=False,$cv_mark=False)
    {
        // words that can find infraspecific epithets
        $subsp = array('subsp.', 'subsp', 'ssp', 'ssp.', 'nothosubsp.', 'nothosubsp', 'notosubsp.','subps.');
        $var = array('var.', 'var', 'variety', 'v', 'v.', 'subvar.', 'subvar');
        $form = array('form', 'form.', 'fo', 'fo.', 'f.', 'f');
        $cv = array('cultivar', 'cultivar.', 'cv', 'cv.');
        if ($subsp_mark) {
            array_push($subsp, $subsp_mark);
        }
        if ($var_mark) {
            array_push($var, $var_mark);
        }
        if ($form_mark) {
            array_push($form, $form_mark);
        }
        if ($cv_mark) {
            array_push($cv, $cv_mark);
        }


        // the resulting array
        $result = array();


        if ($str == '') {
            $result = $this->addMissingKeys($result,$other,$infrarank1_exists);
            return $result;
        }

        // str converted to array of words

        $str_exploded = explode(' ', trim($str));

        // check if 'non' is in the string
        $non = array_search(' non ', array_map('strtolower', $str_exploded));
        $not = array_search(' not ', array_map('strtolower', $str_exploded));
        if ($non == True) {
            $result['non'] = 'non';
        }
        elseif ($not == True) {
            $result['non'] = 'non';
        }


        $this->debug($str_exploded);

        // search for a subsp. identifier
        $rank = '';
        $rank_real_word = '';

        if ($rank == False) {
            foreach ($str_exploded as $key => $word) {
                $word_id_rank = in_array($word, $subsp);
                //it must follow these contition in order to be a subsp rank:
                //it always had to be in the $subsp array and must fulfill one of these conditions:
                //1: the infrasp. rank is not in the critical words
                //2  the infrasp. rank is not in the critical words
                if ($word_id_rank == True and ((in_array($word, $crit_chars) == False) or
                        ((in_array($word, $crit_chars) == True) and
                            !(array_key_exists($key + 1, $str_exploded) == False or
                                $this->dotAfter($str_exploded[$key + 1]) == True or
                                $this->upperAfter($str_exploded[$key + 1]) == True or
                                $this->shortAfter($str_exploded[$key + 1]) == True)))) {
                    $rank = 'subsp';
                    $rank_position = $key;
                    $rank_real_word = $word;
                    break;
                }
            }
        }

        // for variety the same as subspecies
        if ($rank == False) {
            foreach ($str_exploded as $key => $word) {
                $word_id_rank = in_array($word, $var);
                //it must follow these contition in order to be a subsp rank:
                //it always had to be in the $subsp array and must fulfill one of these conditions:
                //1: the infrasp. rank is not in the critical words
                //2  the infrasp. rank is not in the critical words
                if ($word_id_rank == True and ((in_array($word, $crit_chars) == False) or
                        ((in_array($word, $crit_chars) == True) and
                            !(array_key_exists($key + 1, $str_exploded) == False or
                                $this->dotAfter($str_exploded[$key + 1]) == True or
                                $this->upperAfter($str_exploded[$key + 1]) == True or
                                $this->shortAfter($str_exploded[$key + 1]) == True)))) {
                    $rank = 'var';
                    $rank_position = $key;
                    $rank_real_word = $word;
                    break;
                }
            }
        }

        // for form there are extra points to consider
        if ($rank == False) {
            foreach ($str_exploded as $key => $word) {
                $word_id_rank = in_array($word, $form);
                //it must follow these contition in order to be a subsp rank:
                //it always had to be in the $subsp array and must fulfill one of these conditions:
                //1: the infrasp. rank is not in the critical words
                //2  the infrasp. rank is not in the critical words
                if ($word_id_rank == True and ((in_array($word, $crit_chars) == False) or
                        ((in_array($word, $crit_chars) == True) and
                            !(array_key_exists($key + 1, $str_exploded) == False or
                                $this->dotAfter($str_exploded[$key + 1]) == True or
                                $this->upperAfter($str_exploded[$key + 1]) == True or
                                $this->shortAfter($str_exploded[$key + 1]) == True)
                            and $str_exploded[$key + 1] != 'nom.'
                            and $str_exploded[$key + 1] != 'ex'
                            and $str_exploded[$key + 1] != 'isonym'
                            and $str_exploded[$key + 1] != '&'))) {
                    $rank = 'form';
                    $rank_position = $key;
                    $rank_real_word = $word;
                    break;
                }
            }
        }

        // for the cultivar

        if ($rank == False) {
            foreach ($str_exploded as $key => $word) {
                $word_id_rank = in_array($word, $cv);
                //it must follow these contition in order to be a subsp rank:
                //it always had to be in the $subsp array and must fulfill one of these conditions:
                //1: the infrasp. rank is not in the critical words
                //2  the infrasp. rank is not in the critical words
                if ($word_id_rank == True and ((in_array($word, $crit_chars) == False) or
                        ((in_array($word, $crit_chars) == True) and
                            !(array_key_exists($key + 1, $str_exploded) == False or
                                $this->dotAfter($str_exploded[$key + 1]) == True or
                                $this->upperAfter($str_exploded[$key + 1]) == True or
                                $this->shortAfter($str_exploded[$key + 1]) == True)))) {
                    $rank = 'cv';
                    $rank_position = $key;
                    $rank_real_word = $word;
                    break;
                }
            }
        }
        // se non è presente la stringa inserità è l'autore della specie
        if ($rank == False) {
            $sp_auth = implode(' ', $str_exploded);
            if ($infrarank1_exists) {
                $result['infrasp1_auth'] = $sp_auth;
            } else {
                $result['species_auth'] = $sp_auth;
            }

            $result = $this->addMissingKeys($result,$other,$infrarank1_exists);
            return $result;
        } else {

            $sp_auth = implode(' ', array_slice($str_exploded, 0, $rank_position));
            // NOTE: necessary for infrasp rank with nothing after
            if (isset($str_exploded[$rank_position + 1])) {
                $infra = $str_exploded[$rank_position + 1];
            }
            else{
                $infra = '';
            }
            $count = count($str_exploded);
            $rest = implode(' ', array_slice($str_exploded, $rank_position + 2, $count));
            if ($infrarank1_exists) {
                $result['infrasp2_rank'] = $rank;
                $result['infrasp2_real_rank'] = $rank_real_word;

                $result['infrasp1_auth'] = $sp_auth;
                $result['infrasp2'] = $infra;
                $result['infrasp2_auth'] = $rest;

            } else {
                $result['infrasp1_rank'] = $rank;
                $result['infrasp1_real_rank'] = $rank_real_word;
                $result['species_auth'] = $sp_auth;
                $result['infrasp1'] = $infra;
                $result[$other] = $rest;
            }

            $result = $this->addMissingKeys($result,$other,$infrarank1_exists);
            return $result;

        }
    }


        /**
         * add the missing keys (with empty values) in order to fully return the array with the structure described in infraParserUlt
         * the keys will be different for infrasp1 or infrasp2
         * @param $result array array to modify
         * @param $other string 'other' key name
         * @param $infrarank1_exists bool
         * @return array array with all the required keys
         */
        private function addMissingKeys($result,$other,$infrarank1_exists)
        {
                if ($infrarank1_exists)
                {
                    if (!isset($result['infrasp2_rank'])) {
                        $result['infrasp2_rank'] = '';
                    }
                    if (!isset($result['infrasp2_real_rank'])) {
                        $result['infrasp2_real_rank'] = '';
                    }
                    if (!isset($result['infrasp1_auth'])) {
                        $result['infrasp1_auth'] = '';
                    }
                    if (!isset($result['infrasp2'])) {
                        $result['infrasp2'] = '';
                    }
                    if (!isset($result['infrasp2_auth'])) {
                        $result['infrasp2_auth'] = '';
                    }

                }
                else
                {
                    if (!isset($result['infrasp1_rank'])) {
                        $result['infrasp1_rank'] = '';
                    }
                    if (!isset($result['infrasp1_real_rank'])) {
                        $result['infrasp1_real_rank'] = '';
                    }
                    if (!isset($result['species_auth'])) {
                        $result['species_auth'] = '';
                    }
                    if (!isset($result['infrasp1'])) {
                        $result['infrasp1'] = '';
                    }
                    if (!isset($result[$other])) {
                        $result[$other] = '';
                    }
                }
                return $result;

        }

        public function nonIsPresent($parsed_name){

            $author = $parsed_name['species_auth'].' '.$parsed_name['infrasp1_auth'].' '.$parsed_name['infrasp2_auth'];
            $parsed_author = explode(' ', $author);
            if (in_array('non', $parsed_author) or in_array('not', $parsed_author)){
                return true;
            }
            else{
                return false;
            }
        }




}