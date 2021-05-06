<?php


class Filter
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


    /**
     *
     * Results with a part of the name (like species) too different from the input one (like species) are discarded
     * rules described in the taxamatch paper are followed
     *
     * @param $str string a part of the name species/infraspecies1
     * @param $str_ph string it's phonetic variant
     * @param $array_names array the names from the database
     * @param $column string the column to filter (species/infraspecies1)
     * @param $column_ph string the column with the phonetic variant
     * @param $query_type string always keep 'fuzzy match' (was used for a discarded feature)
     * @param $allow_phonetic_match string choose to allow phonetic match
     * @return array
     */
    public function preFilter($str, $str_ph, $array_names, $column, $column_ph, $query_type, $allow_phonetic_match)
    {
        $str = strtoupper($str);

        $str_len = strlen($str);
        $length_diff = 4;

        $max_length_diff = $str_len + $length_diff;
        $min_length_diff = $str_len - $length_diff;

        $start1 = substr($str,0,1);
        $this->debug($start1);
        $start2 = substr($str,0,2);
        $this->debug($start2);
        $start3 = substr($str,0,3);
        $this->debug($start3);
        $end1 = substr($str,-1);
        $this->debug($end1);
        $end3 = substr($str,-3);
        $this->debug($end3);





        $sp_match = array();


        foreach ($array_names as $name)
        {
            if (strtoupper($name[$column]) == $str) {
                array_push($sp_match, $name);
            }
            elseif ($name[$column_ph] == $str_ph AND $allow_phonetic_match == True){
                array_push($sp_match, $name);
            }
            elseif ($query_type == 'fuzzy_match')
            {
                if ($min_length_diff <= strlen($name[$column]) AND strlen($name[$column]) <= $max_length_diff)
                {
                    if ((min($str_len, strlen($name[$column])) < 5 AND ($this->startsWith(strtoupper($name[$column]),$start1) OR $this->endsWith(strtoupper($name[$column]),$end1))) OR
                        (min($str_len, strlen($name[$column])) == 5 AND ($this->startsWith(strtoupper($name[$column]),$start2) OR $this->endsWith(strtoupper($name[$column]),$end3))) OR
                        (min($str_len, strlen($name[$column])) > 5 AND ($this->startsWith(strtoupper($name[$column]),$start3) OR $this->endsWith(strtoupper($name[$column]),$end3))))
                    {
                        array_push($sp_match, $name);
                    }
                }
            }

        }
        return $sp_match;
    }


    /**
     *
     * Results (that alredy passed prefilter) with a part of the name (like species) too different from the input one (like species) are discarded
     * rules described in the taxamatch paper are followed
     *
     * @param $str string a part of the name species/infraspecies1
     * @param $array_names array the names from the database
     * @param $column string the column to filter (species/infraspecies1)
     * @param $distance_column string the column with the
     * @param $pre_distance_column string the column with the distance of the previous part of the name(e.g. for $str = species -> genus)
     * @param $allow_phonetic_match string choose to allow phonetic match
     * @return array array with only the most similar names
     */
    public function postFilter($str, $array_names, $column, $distance_column, $pre_distance_column, $allow_phonetic_match)
    {
        $str = strtoupper($str);
        $start1 = substr($str, 0, 1);
        $start3 = substr($str, 0, 3);

        $match = array();


        foreach ($array_names as $name) {

            if ($name[$distance_column] == 0) {
                array_push($match, $name);
            } elseif ($name[$distance_column] == 'P') {
                array_push($match, $name);
            } elseif ($name[$pre_distance_column] == 'P' or ($name[$distance_column] + $name[$pre_distance_column]) <= 4) {
                $halflength_name = intval(strlen($name[$column]) / 2);

                if ($name[$distance_column] <= $halflength_name) {
                    if ($name[$distance_column] < 2 or
                        (($name[$distance_column] = 2 or $name[$distance_column] = 3) and $this->startsWith(strtoupper($name[$column]), $start1)) or
                        ($name[$distance_column] > 3 and $this->startsWith(strtoupper($name[$column]), $start3) and $column != 'genus')) {
                        array_push($match, $name);
                    }

                }
            }
        }
        return $match;
    }
}