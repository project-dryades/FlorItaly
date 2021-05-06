<?php


class MatchSetup
{
    private $user;
    private $database;

    public function __construct($db)
    {
        if ($db)
            $this->setDatabase($db);
        else
            $this->raiseError('no database specified');
    }
    private function setDatabase($db)
    {

        $this->database = $db;
    }





    function countNamesGeneral($array)
    {
        // result order: wrong, valid, ambiguous
        $result = array();
        $wrong = 0;
        $valid = 0;
        $ambiguous = 0;
        foreach ($array as $names){
            if (count($names['match']) == 0 or $names['match'][0]['score'] == 0){
                $wrong++;
            }
            elseif (count($names['match']) == 1){
                $valid++;
            }
            if (count($names['match']) > 1){
                $ambiguous++;
            }

        }
        $result[0] = $wrong;
        $result[1] = $valid;
        $result[2] = $ambiguous;

        return $result;

    }





    function in_array_r($item , $array){
        return preg_match('/"'.preg_quote($item, '/').'"/i' , json_encode($array));
    }


    public function updateArrayFinal($array, $array_to_update)
    {
        /*print_r('array inserito');
        print_r('<br><br>');
        print_r($array);
        print_r('<br><br>');
        print_r('array match');
        print_r('<br><br>');
        print_r($array_to_update);
        print_r('<br><br>');*/
        //print_r($array);

        // remove unselected elements
        $array_temp = array();
        foreach ($array as $key=>$item) {
            //print_r($item);
            if ($item != 'qwertyuiop'){
                $array_temp[$key] = $item;
            }
        }
        $array = $array_temp;

        //print_r($array);

        foreach ($array as $input_name =>$selected_name)
        {
            /*print_r('array da trovare');
            print_r('<br><br>');
            print_r($input_name);
            print_r('<br><br>');*/

            foreach ($array_to_update as &$subarray_to_update)
            {
                /*print_r('inizio subarray');
                print_r('<br><br>');
                print_r($subarray_to_update);
                print_r('<br><br>');*/

                if ($subarray_to_update['input_name'] == $input_name)
                {
                    /*print_r('<br><br>');
                    print_r('nel sub sopra cè il nome');
                    print_r('<br><br>');
                    $correct_match = array();
                    //echo print_r($subarray_to_update).'<br><br><br><br>';*/
                

                    foreach ($subarray_to_update['match'] as $key=>$stu)
                    {
                        //echo print_r($stu).'<br><br><br><br>';
                        /*print_r('<br><br>');
                        print_r($stu);
                        print_r('<br><br>');*/
                        if ($stu['original_name'] == $selected_name)
                        {
                            //echo print_r($stu).'<br><br><br><br>';
                            $correct_match[0] = $stu;
                            break;
                        }
                    
                    }
                    if (count($correct_match) != 0) {
                        $subarray_to_update['match'] = $correct_match;
                        //print_r($subarray_to_update['match']);

                        /*print_r('fine subarray mod');
                        print_r('<br><br>');
                        print_r($subarray_to_update);
                        print_r('<br><br>');*/
                        break;
                    }


                }
                /*print_r('fine subarray non mod');
                print_r('<br><br>');
                print_r($subarray_to_update);
                print_r('<br><br>');*/
            }

        }
        //echo'&&&&&&&'. print_r($array_to_update).'<br>&&&&&&&&&&';
        //print_r('<br>');
        //print_r($array_to_update);
        return $array_to_update;
        
    }

    public function getArgumentsNameMatch($post){
        $result = array();

        if (isset($post['score'])){
        $result['score'] = $post['score'];
        } else {
        $result['score'] = 0;
        }

        if (isset($post['n_result'])){
            $result['n_result'] = $post['n_result'];
        } else {
            $result['n_result'] = 5;
        }

        if (isset($post['id_subsp'])){
            $result['id_subsp'] = $post['id_subsp'];
        } else {
            $result['id_subsp'] = false;
        }

        if (isset($post['id_var'])){
            $result['id_var'] = $post['id_var'];
        } else {
            $result['id_var'] = false;
        }

        if (isset($post['id_form'])){
            $result['id_form'] = $post['id_form'];
        } else {
            $result['id_form'] = false;
        }

        if (isset($post['id_cv'])){
            $result['id_cv'] = $post['id_cv'];
        } else {
            $result['id_cv'] = false;
        }

        if ($post['phonetic'] == 'yes'){
            $result['phonetic'] = True;
        } else {
            $result['phonetic'] = False;
        }

        if ($post['insensitive'] == 'yes'){
            $result['insensitive'] = True;
        } else {
            $result['insensitive'] = False;
        }

        // for extra synonyms

        $result['synonym_tables'] = array(0);
        if (isset($post['synonym_tables1']) and $post['synonym_tables1'] == 1 ){
            array_push($result['synonym_tables'], 1);
        }
        if (isset($post['synonym_tables2']) and $post['synonym_tables2'] == 1 ){
            array_push($result['synonym_tables'], 2);
        }

        return $result;


    }







}