<?php


class PreCheck
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


    /**
     * check if the $str is all in uppercase or lowercase
     * @param string $str
     * @return string :
     * - 'upper' if it is all uppercase or if the first 2 letters are uppercase
     * - 'lower' if it is all lowercase
     * - 'mixed' otherwise
     */
    public function uplowCheck ($str)
    {
        if (strtoupper($str) == $str or strtoupper(substr($str,0,2)) == substr($str,0,2)){
            $result = 'upper';
        }
        elseif (strtolower($str) == $str){
            $result = 'lower';
        }
        else{
            $result = 'mixed';
        }
        $this->debug($result);
        return $result;
    }

    /**
     *
     * check if in the input string are present words that may cause parsing errors
     * NOTE: before entering an uppercase string, change it into lowercase, but $uplow_check must be 'upper'
     * NOTE: do not change mixed strings
     *
     * Addictional critical characters may be add in the array $crit_char
     *
     * For names not written in uppercase, only f. is considered a critical character
     *
     * @param string $str
     * @param string $subsp_mark
     * @param string $cv_mark
     * @param string $force_case_insensitive if True the function will be case insensitive (not recommended)
     * @return array an array with the words that may cause parsing errors
     */
    public function warningCheck($str,$uplow_check,$subsp_mark,$cv_mark,$force_case_insensitive=False)
    {
        if ($force_case_insensitive){
            $uplow_check = 'upper';
        }
        // critical array
        $crit_char = array('v','v.','f','f.');

        if ($subsp_mark == 's.')
        {
            array_push($crit_char, 's','s.');
        }

        if ($cv_mark == 'c.')
        {
            array_push($crit_char, 'c','c.');
        }


        $crit_char_check = $this->critCharCheck($str,$crit_char);

        if ($uplow_check != 'upper')
        {
            if (in_array('f.',$crit_char_check))
            {
                $crit_char_check = array('f.');
            }
            elseif (in_array('f',$crit_char_check))
            {
                $crit_char_check = array('f');
            }
            else

                $crit_char_check = array();
        }

        return $crit_char_check;


    }

    /**
     * check if in the input string are present words that may cause parsing errors
     * NOTE: this function is used by the function warningCheck
     * used only for uppercase strings
     * check if the string has a critical character (s,v,f,c) with or without a dot at the end
     *
     * @param string $str
     * @param array $crit_char
     * @return array an array with the words that may cause parsing errors
     */
    private function critCharCheck ($str, $crit_char)
    {
        $result = array();
        $str_exploded = explode(' ', $str);
        foreach ($str_exploded as $word)
        {
            if (in_array($word, $crit_char))
            {
                array_push($result,$word);
            }
        }

        $this->debug($result);
        return $result;

    }





}