<?php


class genusfilter

{
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







    public function genusPreFilter($parsed_string, $array_names)
    {

        $gen_match = array();


        // Tiene solo le stringhe con corrispondenza esatta di genere
        foreach ($array_names as $str) {
            if ($str['genus'] == $parsed_string['genus']) {
                array_push($gen_match, $str);
            }
        }


        // Se non c'è corrispondenza esatta seguo le regole di taxamatch
        //print_r($gen_match);
        if (count($gen_match) == 0) {


            // creo un array con i generi di lunghezza simile (da -2 a 2) a quello cercato
            $array_names_similar_length = array();

            foreach ($array_names as $str) {

                // se la differenza di lunghezza tra il genere è minore o uguale a 2
                $genus_distance = strlen($parsed_string['genus']) - strlen($str['genus']);


                if ((-2 <= $genus_distance) and ($genus_distance <= 2)) {

                    //echo print_r($str['genus']) . '<br>' . $genus_distance . '<br><br><br>';
                    array_push($array_names_similar_length, $str);
                }
            }


            // applico le diverse condizioni previste da taxamatch
            //print_r($array_names_similar_length);


            // condizione 1: lunghezza < 5 (o prima o ultima lettera uguali)

            if (strlen($parsed_string['genus']) < 5){
               foreach ($array_names_similar_length as $str) {
                   if (($this->startsWith($str['genus'], substr($parsed_string['genus'], 0,1))) OR $this->endsWith($str['genus'], substr($parsed_string['genus'], -1)))
                   {
                        //echo print_r($str['genus']).'<br>';
                       array_push($gen_match, $str);
                   }
               }
            }


            // condizione 2: lunghezza = 5 (o prime 2 o ultime 3 lettere uguali)

            elseif (strlen($parsed_string['genus']) == 5){
                foreach ($array_names_similar_length as $str) {
                    if (($this->startsWith($str['genus'], substr($parsed_string['genus'], 0,2))) OR $this->endsWith($str['genus'], substr($parsed_string['genus'], -3)))
                    {
                        //echo print_r($str['genus']).'<br>';
                        array_push($gen_match, $str);
                    }
                }
            }


            // condizione 3: lunghezza > 5 (o prime 2 o ultime 3 lettere uguali)

            elseif (strlen($parsed_string['genus']) > 5){
                foreach ($array_names_similar_length as $str) {
                    if (($this->startsWith($str['genus'], substr($parsed_string['genus'], 0,3))) OR $this->endsWith($str['genus'], substr($parsed_string['genus'], -5)))
                    {
                        //echo print_r($str['genus']).'<br>';
                        array_push($gen_match, $str);
                    }
                }
            }

        }
        //print_r($gen_match);
        return $gen_match;
    }


    public function genusPostFilter($genus, $array_names)
    {
        $gen_match = array();
        foreach ($array_names as $str) {

            // condizione 1: tieni solo se la distanza è <= 4
            if ($str['ED_genus'] <= 4) {

                if ($str['ED_genus'] == 'P' OR $str['ED_genus'] == 0)
                {
                    array_push($gen_match, $str);
                }
                else
                {
                    // condizione 2: tieni solo se almeno il 50% dei caratteri corrispondono
                    //echo 'bene'.$str['ED_genus'];
                    $halflength_str = intval(strlen($str['genus']) / 2);
                    //echo '<br>'.$halflength_str;


                    if ($str['ED_genus'] <= $halflength_str) {
                        // condizione 2.1: tieni, in caso di distanza >= 2, solo se la prima lettera del genere è la stessa
                        if ($str['ED_genus'] >= 2) {
                            //echo $str['ED_genus'].' da verificare'.$str['genus'].'<br>';
                            if ($this->startsWith($str['genus'], substr($genus, 0, 1))) {
                                //echo 'lungo e simile'.print_r($str).'<br><br><br>';
                                array_push($gen_match, $str);
                            }

                        } else {
                            //echo 'corto e simile'.print_r($str).'<br><br><br>';
                            array_push($gen_match, $str);
                        }
                    }


                }
            }
        }
        return $gen_match;
    }

}