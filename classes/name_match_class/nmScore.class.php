<?php
include_once __DIR__ . '/nmDamerauLevenshteinMod.class.php';


class Score
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

    public function onlyGenusScore($array,$column,$distance_column)
    {
        $result_array=array();
        $i=0;
        foreach ($array as $name) {
            //array_push($result_array, $name[$column], $name[$distance_column]);
            $result_array[$i]=array($column => $name[$column],$distance_column => $name[$distance_column]);
            $i++;
        }
        $this->debug($result_array);
        $result_array=array_unique($result_array, SORT_REGULAR);
        $this->debug($result_array);

        foreach ($result_array as &$name)
        {
            $score = $this->levToScore($name,$column,$distance_column);
            unset($name[$distance_column]);
            $name['auth_score'] = 0;
            $name['name_score'] = $score;
            $name['score'] = $score * 0.9;
            $name['original_name'] = $name[$column];
            unset($name[$column]);
        }

        // order $result_array
        array_multisort(array_column($result_array, 'score'), SORT_DESC, $result_array);

        $this->debug($result_array);
        return($result_array);


    }

    /**
     *
     * return a similarity score for authors' names
     * first the parsed name authors and the database names authors are merged (sp_auth + infrasp1_auth,...)
     * then the result is calculated using 1 - lev/longest name
     *
     * @param $array array
     * @param $parsed_name array
     * @param $column_sp_auth string
     * @param $column_in1_auth string
     * @param $column_in2_auth string
     * @return mixed the array with the author similarity score
     */
    public function getAuthorScore($array,$parsed_name,$column_sp_auth, $column_in1_auth,$column_in2_auth)
    {
        $match = new DamerauLevenshteinMod();

        foreach ($array as &$name) {





            $comp_name1 = strtolower(trim($name[$column_sp_auth].' '.$name[$column_in1_auth].' '.$name[$column_in2_auth]));
            $comp_name2 = strtolower(trim($parsed_name[$column_sp_auth].' '.$parsed_name[$column_in1_auth].' '.$parsed_name[$column_in2_auth]));

            //print_r($comp_name1);
            //print_r($comp_name2);
            $score5 = $match->ngram($comp_name1, $comp_name2);
            $this->debug($score5);
            //print_r('<br>'.$score5);
            //$result = intval((1 - $score5 / strlen(max($comp_name1,$comp_name2))) *100);
            $result = intval($score5*100);
            $this->debug($result);
            if ($result < 0) {
                $name['auth_score'] = 0;
            }
            else {
                $name['auth_score'] = $result;
            }
            }
        $this->debug($array);
        return $array;
    }


    /**
     *
     * chenge the ED_distance to a score that ranges from 1 to 100
     *
     * @param $array
     * @param $genus_column
     * @param $species_column
     * @param $infrasp1_column
     * @param $infrasp2_column
     * @return mixed
     */
    public function getScore($array,$genus_column,
                             $species_column,
                             $infrasp1_column,
                             $infrasp2_column)

    {
        $distance_genus_column = 'ED_'.$genus_column;
        $distance_species_column = 'ED_'.$species_column;
        $distance_infrasp1_column = 'ED_'.$infrasp1_column;
        $distance_infrasp2_column = 'ED_'.$infrasp2_column;

        $genus_score = $genus_column.'_score';
        $species_score = $species_column.'_score';
        $infrasp1_score = $infrasp1_column.'_score';
        $infrasp2_score = $infrasp2_column.'_score';



        foreach ($array as &$name)
        {
            if(array_key_exists($distance_genus_column,$name) and $name[$distance_genus_column] !== '') {
                $name[$genus_score] = $this->levToScore($name, $genus_column, $distance_genus_column);

            }

            if(array_key_exists($distance_species_column,$name) and $name[$distance_species_column] !== '') {
                $name[$species_score] = $this->levToScore($name, $species_column, $distance_species_column);
            }

            if(array_key_exists($distance_infrasp1_column,$name) and $name[$distance_infrasp1_column] !== '')  {
                $name[$infrasp1_score] = $this->levToScore($name, $infrasp1_column, $distance_infrasp1_column);
            }

            if(array_key_exists($distance_infrasp2_column,$name) and $name[$distance_infrasp2_column] !== '')  {
                $name[$infrasp2_score] = $this->levToScore($name, $infrasp2_column, $distance_infrasp2_column);
            }
        }
        return $array;
    }

    private function levToScore($name,$column,$distance_column)
    {
        if ($name[$distance_column] === 0) {
            $score = 100;
        }
        elseif ($name[$distance_column] == 'P'){
            $score = 96;
        }
        else{

            // nel momento in cui non ho subspecie, la divisione per zero causava errore
            if (strlen($name[$column]) === 0)
            {
                $pre_score = 0;
            }
            else
            {
            $pre_score = $name[$distance_column]/strlen($name[$column]);
            }
            $this->debug($pre_score);

            $score = intval((1 - $pre_score) * 100);

        }
        return $score;
    }

    /**
     * assign a mean score for the name
     * 1: first step mean score
     * first infrasp. score is decreased to 70% of the original one if the rank is different between the input name and the database name
     * so both the input name and the database name must have a rank (!= '') and it must be different in order to have the score decreased
     *
     *
     * in the mean are considered only the epithets present in both the input name and in the database name
     * eg.
     * - input name: achillea millefolium
     * - database name achillea millefolium (100 score)
     * - database name achillea millefolium subsp. millefolium (100 score)
     *
     * the same system is applied even in the opposite condition:
     * - input name: achillea millefolium subsp. millefolium
     * - database name achillea millefolium (100 score)
     * - database name achillea millefolium subsp. millefolium (100 score)
     *
     * if the number of the epithet is not the same, the score will be later decreased using decreaseScore function
     *
     * 2: second step decrease score
     * $to_decrease is set to true if the number of the epithet is not the same (between input name and database name)
     * if the number is not the same but infrasp == species or infrasp2 == infrasp the score is decreased less
     *
     *
     * @param $array
     * @param $parsed_name
     * @param $infrasp1_rank
     * @param $infrasp2_rank
     * @return array the input array, in the array will be added a key (name_score) with the mean score
     */
    public function modifyScore($array, $parsed_name,$infrasp1_rank, $infrasp2_rank, $genus, $species, $infrasp1, $infrasp2)
    {
        foreach ($array as &$name)
        {
            $final_score = 0;
            $counter = 0;
            $this->debug($name);


            // decrease sore if ifrasp. rank is different

            if ($name[$infrasp1_rank] !== '' and $parsed_name[$infrasp1_rank] !== '' and $parsed_name[$infrasp1_rank] != $name[$infrasp1_rank] ){
                $name['infrasp1_score'] = $name['infrasp1_score'] * 0.7;
            }
            if ($name[$infrasp2_rank] !== '' and $parsed_name[$infrasp2_rank] !== '' and $parsed_name[$infrasp2_rank] != $name[$infrasp2_rank] ){
                $name['infrasp2_score'] = $name['infrasp2_score'] * 0.7;
            }

            // mean of scores
            if ($name[$infrasp2] != '' and $parsed_name[$infrasp2] != ''){
                $final_score = $final_score + $name['infrasp2_score'];
                $counter++;

            }
            if ($name[$infrasp1] != '' and $parsed_name[$infrasp1] != ''){
                $final_score = $final_score + $name['infrasp1_score'];
                $counter++;

            }
            if ($name[$species] != '' and $parsed_name[$species] != ''){
                $final_score = $final_score + $name['species_score'];
                $counter++;

            }
            if ($name[$genus] != '' and $parsed_name[$genus] != ''){
                $final_score = $final_score + $name['genus_score'];
                $counter++;

            }

            $name['name_score'] = intval($final_score/$counter);

            // part 2: decrease score
            $name['name_score_old'] = $name['name_score'];
            $to_decrease = false;
            $to_ultra_decrease = false;
            $decrease_score = 0.9;
            $ultra_decrease_score = 0.7;


            // decreasing score
            // for infrasp2
            if ($name[$infrasp2] == '' and $parsed_name[$infrasp2] != ''){
                if($parsed_name[$infrasp2] == $parsed_name[$infrasp1]){
                    $to_decrease = true;
                }
                else{
                    $to_ultra_decrease = true;
                }
            }
            if ($name[$infrasp2] != '' and $parsed_name[$infrasp2] == ''){
                if($name[$infrasp2] == $name[$infrasp1]){
                    $to_decrease = true;
                }
                else{
                    $to_ultra_decrease = true;
                }
            }

            // for infrasp1
            if ($name[$infrasp1] == '' and $parsed_name[$infrasp1] != ''){
                if($parsed_name[$infrasp1] == $parsed_name[$species]){
                    $to_decrease = true;
                }
                else{
                    $to_ultra_decrease = true;
                }
            }
            if ($name[$infrasp1] != '' and $parsed_name[$infrasp1] == ''){
                if($name[$infrasp1] == $name[$species]){
                    $to_decrease = true;
                }
                else{
                    $to_ultra_decrease = true;
                }
            }

            // decreasing score
            if ($to_decrease){
                $name['name_score'] = intval($name['name_score'] * $decrease_score);
                $name['risk_match'] = true;
            }
            if ($to_ultra_decrease){
                $name['name_score'] = intval($name['name_score'] * $ultra_decrease_score);
            }


        }
        return $array;
    }


    /**
     *
     * Combines name score (weight 0.9) with author score (weight 0.1)
     *
     * @param $array
     * @param $score_threshold
     * @return array
     */
    public function getCombinedScore($array, $score_threshold)
    {
        $result = array();
        foreach ($array as $name)
        {
            $name['score'] = intval($name['auth_score'] * 0.1 + $name['name_score'] * 0.9);
            //echo $name['score'].' < '.$score_threshold.'<br>';
            if ($name['score'] >= $score_threshold){

                array_push($result,$name);
            }
        }
        return $result;
    }

}