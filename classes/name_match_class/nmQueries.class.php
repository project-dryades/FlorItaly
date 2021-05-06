<?php
// RICORDA NEWTABLE DDEVE DIVENTARE GLOBALE
// richiede la tabella del database parsed oltre al database 
class NmQueries
{

    private $db;

    public function __construct($db, $phonetic, $lev)
    {
        $this->phonetic = $phonetic;
        $this->lev = $lev;
        $this->db = $db;
    }

    private function debug($mixed,$active=False)
    {
        if ($active)
        {
            echo '<p>';
            print_r($mixed);
            echo '<p>';
        }
    }


    public function selectPreGenusFilter($str,$str_ph, $table, $column, $phonetic_column, $query_type, $allow_phonetic_match)
    {
        // string in uppercase
        $str = strtoupper($str);
        // escape all
        $str = $this->db->escapeString($str);
        $this->debug($str);
        $str_ph = $this->db->escapeString($str_ph);
        $this->debug($str_ph);
        $table = $this->db->escapeString($table);
        $this->debug($table);
        $column = $this->db->escapeString($column);
        $this->debug($column);
        $phonetic_column = $this->db->escapeString($phonetic_column);
        $this->debug($phonetic_column);


        
        // per la query servono

        //la lunghezza della stringa
        $str_len = strlen($str);
        $this->debug('lunghezza stringa: '.$str_len);
        // diversi valori
        //la differenza di lunghezza tollerata
        $length_diff = 2;

        $max_length_diff = $str_len + $length_diff;
        $min_length_diff = $str_len - $length_diff;

        
        //l'inizio e la fine della stringa
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

        // escape new values
        $str_len = $this->db->escapeString($str_len);
        $max_length_diff = $this->db->escapeString($max_length_diff);
        $min_length_diff = $this->db->escapeString($min_length_diff);
        $start1 = $this->db->escapeString($start1);
        $start2 = $this->db->escapeString($start2);
        $start3 = $this->db->escapeString($start3);
        $end1 = $this->db->escapeString($end1);
        $end1 = $this->db->escapeString($end1);

        // creo la query
        //tieni a mente exact match e phonetic match

        // nel caso del genere (1a situazione che incontro) la tabella a cui faccio riferimento
        // vado a creare la tabella $newtable (temporanea)
        // nella quale inserisco i valori che soddisfano il pre_genus_match di taxamatch

            $query = "SELECT DISTINCT * FROM $table 
                    WHERE UPPER($column) = '$str'";

        // Note: the phonetic str and the phonetic column are alredy in uppercase

        if ($allow_phonetic_match)
        {
            $query .= " OR $phonetic_column = '$str_ph'";
        }

        


        // seconda condizione di taxaMatch
        if ($query_type == 'fuzzy_match')
        {
        
        $query .= " OR ((LENGTH($column) between $min_length_diff AND $max_length_diff) AND (
           
                 (LEAST($str_len, LENGTH($column)) < 5 and (UPPER($column) LIKE '$start1%' OR UPPER($column) LIKE '%$end1'))
           
                 OR (LEAST($str_len, LENGTH($column)) = 5 and (UPPER($column) LIKE '$start2%' OR UPPER($column) LIKE '%$end3'))
           
                 OR (LEAST($str_len, LENGTH($column)) > 5 and (UPPER($column) LIKE '$start3%' OR UPPER($column) LIKE '%$end3'))))";

        }

        $this->debug($query);
        $result = $this->db->getMultipleSelectQuery($query);

        return $result;






    }




    /**
     *
     * finds all the names in the database that are an exact match with the input string
     *
     * @param string $str   input string
     * @param string $table table with the names
     * @param string $column column with the full normalized names
     * @param string $column_result column with the names
     * @param bool $upper if true both the input string and the names in the database are converted to uppercase
     * @return mixed names that are an exact match with the input string
     */

    public function selectExactMatch($str,$table,$column,$syn_column,$column_result,$upper=True)
    {
        $str = $this->db->escapeString($str);
        $table = $this->db->escapeString($table);
        $column = $this->db->escapeString($column);
        $syn_column = $this->db->escapeString($syn_column);
        $column_result = $this->db->escapeString($column_result);


        $query = "SELECT $column_result, $syn_column
                  FROM $table";
        if ($upper)
        {
        $query .= " WHERE UPPER($column) = UPPER('$str')";
        }
        else
        {
        $query .=" WHERE $column = '$str'";
        }
        $query .= "LIMIT 1";


        $this->debug($query);
        $result_check = $this->db->getSingleSelectQuery($query);

        return $result_check;
    }







    public function getSimilarStrings2($str,$table_name,$table_column)
    {
        $str_splitted = str_split($str);
        $str_length = strlen($str);

        $str_2plus = array();
        $str_2min = array();
        $str_2neut = array();
        $str_end_half = array();
        $str_yn = array();
        $str_yynn = array();


        // 2 +
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 1){
                $str_2plus[$i] = $str_splitted[$i];
            }
            elseif ($i == 3){
                $str_2plus[$i] = $str_splitted[$i+1];
            }
            else
                $str_2plus[$i] = '_';
        }

        // 2 -
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 1){
                $str_2min[$i] = $str_splitted[$i];
            }
            elseif ($i == 3){
                $str_2min[$i] = $str_splitted[$i-1];
            }
            else
                $str_2min[$i] = '_';
        }

        // 2 neut
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 1){
                $str_2neut[$i] = $str_splitted[$i];
            }
            elseif ($i == 3){
                $str_2neut[$i] = $str_splitted[$i];
            }
            else
                $str_2neut[$i] = '_';
        }

        // for last half
        for ($i = 0; $i <= $str_length -1; $i++)
        {
            if ($i >= $str_length / 2){
                $str_end_half[$i] = $str_splitted[$i];
            }
            else
                $str_end_half[$i] = '_';
        }

        // 1 yes and one no
        for ($i = 0; $i <= $str_length -1; $i++)
        {
            if ($i == 0 or $i % 2 == 0){
                $str_yn[$i] = $str_splitted[$i];
            }
            else
                $str_yn[$i] = '_';

        }

        // 2 yes and 2 no
        for ($i = 0; $i <= $str_length -1; $i++)
        {
            if ($i == 0 or $i == 1 or $i % 4 == 0 or $i % 4 == 1){
                $str_yynn[$i] = $str_splitted[$i];
            }
            else
                $str_yynn[$i] = '_';

        }

        $str_2plus = strtolower(implode('',$str_2plus));
        $str_2min = strtolower(implode('',$str_2min));
        $str_2neut = strtolower(implode('',$str_2neut));
        $str_end_half = strtolower(implode('',$str_end_half));
        $str_yn = strtolower(implode('',$str_yn));
        $str_yynn = strtolower(implode('',$str_yynn));


        //now search in database

        $query = "SELECT $table_column FROM $table_name 
                  WHERE LOWER($table_column) LIKE '%$str_2plus%'
                  OR LOWER($table_column) LIKE '%$str_2min%'
                  OR LOWER($table_column) LIKE '%$str_2neut%'
                  OR LOWER($table_column) LIKE '%$str_end_half%'
                  OR LOWER($table_column) LIKE '%$str_yn%'
                  OR LOWER($table_column) LIKE '%$str_yynn%'";

        //echo $query;

        $result = $this->db->getMultipleSelectQuery($query);
        $result = array_column($result, 'original_name');
        //print_r($result);

        $return = array();
        //START STANDARD PATTERN
        //2+
        $str_2plus = str_replace('_', '.', $str_2plus);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_2plus.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                unset($result[$key]);
            }

        }
        // END STANDARD PATTERN
        // 2-
        $str_2min = str_replace('_', '.', $str_2min);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_2min.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                unset($result[$key]);
            }

        }
        // 2
        $str_2neut = str_replace('_', '.', $str_2neut);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_2neut.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                unset($result[$key]);
            }

        }
        // half end
        $str_end_half = str_replace('_', '.', $str_end_half);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_end_half.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                unset($result[$key]);
            }

        }
        // yn
        $str_yn = str_replace('_', '.', $str_yn);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_yn.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                unset($result[$key]);
            }

        }
        // yynn
        $str_yynn = str_replace('_', '.', $str_yynn);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_yynn.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                unset($result[$key]);
            }

        }

        //match part
        //print_r(array_unique($return));
        return $return;

    }

    public function getSimilarStrings($str,$table_name,$table_column)
    {
        $str_splitted = str_split($str);
        $str_length = strlen($str);

        $str_4plus = array();
        $str_4min = array();
        $str_4neut = array();
        $str_3plus = array();
        $str_3min = array();
        $str_3neut = array();
        $str_start_half = array();
        $str_2plus = array();
        $str_2min = array();
        $str_2neut = array();


        // for first half
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= $str_length / 2 -1){
                $str_start_half[$i] = $str_splitted[$i];
            }
            else
                $str_start_half[$i] = '_';
        }

        // 4 +
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 3){
                $str_4plus[$i] = $str_splitted[$i];
            }
            elseif ($i == 5){
                $str_4plus[$i] = $str_splitted[$i+1];
            }
            else
                $str_4plus[$i] = '_';
        }

        // 4 -
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 3){
                $str_4min[$i] = $str_splitted[$i];
            }
            elseif ($i == 5){
                $str_4min[$i] = $str_splitted[$i-1];
            }
            else
                $str_4min[$i] = '_';
        }

        // 4 neut
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 3){
                $str_4neut[$i] = $str_splitted[$i];
            }
            elseif ($i == 5){
                $str_4neut[$i] = $str_splitted[$i];
            }
            else
                $str_4neut[$i] = '_';
        }

        // 3 +
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 2){
                $str_3plus[$i] = $str_splitted[$i];
            }
            elseif ($i == 4){
                $str_3plus[$i] = $str_splitted[$i+1];
            }
            else
                $str_3plus[$i] = '_';
        }

        // 3 -
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 2){
                $str_3min[$i] = $str_splitted[$i];
            }
            elseif ($i == 4){
                $str_3min[$i] = $str_splitted[$i-1];
            }
            else
                $str_3min[$i] = '_';
        }

        // 3 neut
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 2){
                $str_3neut[$i] = $str_splitted[$i];
            }
            elseif ($i == 4){
                $str_3neut[$i] = $str_splitted[$i];
            }
            else
                $str_3neut[$i] = '_';
        }

        // 2 +
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 1){
                $str_2plus[$i] = $str_splitted[$i];
            }
            elseif ($i == 3 or $i == 4){
                $str_2plus[$i] = $str_splitted[$i+1];
            }
            else
                $str_2plus[$i] = '_';
        }

        // 2 -
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 1){
                $str_2min[$i] = $str_splitted[$i];
            }
            elseif ($i == 3 or $i == 4){
                $str_2min[$i] = $str_splitted[$i-1];
            }
            else
                $str_2min[$i] = '_';
        }

        // 2 neut
        for ($i = 0; $i <= $str_length - 1; $i++)
        {
            if ($i <= 1){
                $str_2neut[$i] = $str_splitted[$i];
            }
            elseif ($i == 3 or $i == 4){
                $str_2neut[$i] = $str_splitted[$i];
            }
            else
                $str_2neut[$i] = '_';
        }

        $str_4plus = strtolower(implode('',$str_4plus));
        $str_4min = strtolower(implode('',$str_4min));
        $str_4neut = strtolower(implode('',$str_4neut));
        $str_3plus = strtolower(implode('',$str_3plus));
        $str_3min = strtolower(implode('',$str_3min));
        $str_3neut = strtolower(implode('',$str_3neut));
        $str_start_half = strtolower(implode('',$str_start_half));
        $str_2plus = strtolower(implode('',$str_2plus));
        $str_2min = strtolower(implode('',$str_2min));
        $str_2neut = strtolower(implode('',$str_2neut));


        //now search in database

        $query = "SELECT $table_column FROM $table_name 
                  WHERE LOWER($table_column) LIKE '%$str_start_half%'
                  OR LOWER($table_column) LIKE '%$str_4plus%'
                  OR LOWER($table_column) LIKE '%$str_4min%'
                  OR LOWER($table_column) LIKE '%$str_4neut%'
                  OR LOWER($table_column) LIKE '%$str_3plus%'
                  OR LOWER($table_column) LIKE '%$str_3min%'
                  OR LOWER($table_column) LIKE '%$str_3neut%'
                  OR LOWER($table_column) LIKE '%$str_2plus%'
                  OR LOWER($table_column) LIKE '%$str_2min%'
                  OR LOWER($table_column) LIKE '%$str_2neut%'";

        //echo $query;

        $result = $this->db->getMultipleSelectQuery($query);
        $result = array_column($result, 'original_name');
        //print_r($result);

        $return = array();

        //START STANDARD PATTERN
        // half start
        //echo $str_start_half;
        $str_start_half = str_replace('_', '.', $str_start_half);
        //echo $str_start_half;
        foreach ($result as $key=>&$res) {
            //print_r($res);
            preg_match('/'.$str_start_half.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            //print_r($matches);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $valid);

                $validmin1 = substr($res, $matches[0][1], $str_length-1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin1);
                $validplus1 = substr($res, $matches[0][1], $str_length+1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus1);

                $validmin2 = substr($res, $matches[0][1], $str_length-2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin2);
                $validplus2 = substr($res, $matches[0][1], $str_length+2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus2);

                unset($result[$key]);
            }

        }

        //START STANDARD PATTERN
        //4+
        $str_4plus = str_replace('_', '.', $str_4plus);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_4plus.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                $validmin1 = substr($res, $matches[0][1], $str_length-1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin1);
                $validplus1 = substr($res, $matches[0][1], $str_length+1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus1);

                $validmin2 = substr($res, $matches[0][1], $str_length-2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin2);
                $validplus2 = substr($res, $matches[0][1], $str_length+2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus2);
                unset($result[$key]);
            }

        }
        // END STANDARD PATTERN
        // 4-
        $str_4min = str_replace('_', '.', $str_4min);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_4min.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                $validmin1 = substr($res, $matches[0][1], $str_length-1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin1);
                $validplus1 = substr($res, $matches[0][1], $str_length+1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus1);

                $validmin2 = substr($res, $matches[0][1], $str_length-2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin2);
                $validplus2 = substr($res, $matches[0][1], $str_length+2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus2);
                unset($result[$key]);
            }

        }
        // 4
        $str_4neut = str_replace('_', '.', $str_4neut);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_4neut.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                $validmin1 = substr($res, $matches[0][1], $str_length-1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin1);
                $validplus1 = substr($res, $matches[0][1], $str_length+1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus1);

                $validmin2 = substr($res, $matches[0][1], $str_length-2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin2);
                $validplus2 = substr($res, $matches[0][1], $str_length+2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus2);
                unset($result[$key]);
            }

        }
        // 3+
        $str_3plus = str_replace('_', '.', $str_3plus);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_3plus.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                $validmin1 = substr($res, $matches[0][1], $str_length-1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin1);
                $validplus1 = substr($res, $matches[0][1], $str_length+1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus1);

                $validmin2 = substr($res, $matches[0][1], $str_length-2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin2);
                $validplus2 = substr($res, $matches[0][1], $str_length+2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus2);
                unset($result[$key]);
            }

        }

        // 3-
        $str_3min = str_replace('_', '.', $str_3min);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_3min.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                $validmin1 = substr($res, $matches[0][1], $str_length-1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin1);
                $validplus1 = substr($res, $matches[0][1], $str_length+1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus1);

                $validmin2 = substr($res, $matches[0][1], $str_length-2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin2);
                $validplus2 = substr($res, $matches[0][1], $str_length+2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus2);
                unset($result[$key]);
            }

        }

        // 3
        $str_3neut = str_replace('_', '.', $str_3neut);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_3neut.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                $validmin1 = substr($res, $matches[0][1], $str_length-1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin1);
                $validplus1 = substr($res, $matches[0][1], $str_length+1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus1);

                $validmin2 = substr($res, $matches[0][1], $str_length-2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin2);
                $validplus2 = substr($res, $matches[0][1], $str_length+2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus2);
                unset($result[$key]);
            }

        }

        //2+
        $str_2plus = str_replace('_', '.', $str_2plus);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_2plus.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                $validmin1 = substr($res, $matches[0][1], $str_length-1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin1);
                $validplus1 = substr($res, $matches[0][1], $str_length+1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus1);

                $validmin2 = substr($res, $matches[0][1], $str_length-2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin2);
                $validplus2 = substr($res, $matches[0][1], $str_length+2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus2);
                unset($result[$key]);
            }

        }
        // 2-
        $str_2min = str_replace('_', '.', $str_2min);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_2min.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                $validmin1 = substr($res, $matches[0][1], $str_length-1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin1);
                $validplus1 = substr($res, $matches[0][1], $str_length+1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus1);

                $validmin2 = substr($res, $matches[0][1], $str_length-2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin2);
                $validplus2 = substr($res, $matches[0][1], $str_length+2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus2);
                unset($result[$key]);
            }

        }
        // 2
        $str_2neut = str_replace('_', '.', $str_2neut);
        foreach ($result as $key=>&$res) {
            preg_match('/'.$str_2neut.'/', strtolower($res), $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches))
            {
                $valid = substr($res, $matches[0][1], $str_length);
                array_push($return, $valid);
                $validmin1 = substr($res, $matches[0][1], $str_length-1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin1);
                $validplus1 = substr($res, $matches[0][1], $str_length+1);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus1);

                $validmin2 = substr($res, $matches[0][1], $str_length-2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validmin2);
                $validplus2 = substr($res, $matches[0][1], $str_length+2);
                //echo '<br><br>'.$valid.'<br><br>';
                array_push($return, $validplus2);
                unset($result[$key]);
            }

        }

        //match part
        //print_r(array_unique($return));
        return $return;

    }
    



}