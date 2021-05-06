<?php


class Adjust
{



    /**
     * This will reduce the string to allow only one space between characters
     * 
     * @param string $str : string to reduce space
     * @return string : string with only once space between characters
     */
    public function reduceSpaces($str)
    {

        $str = preg_replace("/ {2,}/", ' ', $str);
        $str = trim($str);

        return ($str);
    }

    public function removeSpecialCharacters($str)
    {
        $str = str_replace('?', ' ', $str);
        $str = str_replace('‘', ' ', $str);
        $str = str_replace('’', ' ', $str);
        $str = str_replace('(', ' ', $str);
        $str = str_replace(')', ' ', $str);
        $str = str_replace('[', ' ', $str);
        $str = str_replace(']', ' ', $str);
        $str = str_replace(',', ' ', $str);
        $str = str_replace(' - ', '', $str);
        $str = str_replace(' -', '', $str);
        $str = str_replace('- ', '', $str);
        $str = str_replace('-', '', $str);
        $str = str_replace('"', ' ', $str);
        $str = str_replace("'", ' ', $str);

        return $str;
    }

        public function utf8ToAscii($str)
        {
            $str = preg_replace("/[ÀÂÅÃÄÁẤẠ]/u", "A", $str);
            $str = preg_replace("/[ÉÈÊË]/u", "E", $str);
            $str = preg_replace("/[ÍÌÎÏ]/u", "I", $str);
            $str = preg_replace("/[ÓÒÔØÕÖỚỔ]/u", "O", $str);
            $str = preg_replace("/[ÚÙÛÜ]/u", "U", $str);
            $str = preg_replace("/[Ý]/u", "Y", $str);
            $str = preg_replace("/Æ/u", "AE", $str);
            $str = preg_replace("/[ČÇ]/u", "C", $str);
            $str = preg_replace("/[ŠŞ]/u", "S", $str);
            $str = preg_replace("/[Đ]/u", "D", $str);
            $str = preg_replace("/Ž/u", "Z", $str);
            $str = preg_replace("/Ñ/u", "N", $str);
            $str = preg_replace("/Œ/u", "OE", $str);
            $str = preg_replace("/ß/u", "B", $str);
            $str = preg_replace("/Ķ/u", "K", $str);
            $str = preg_replace("/[áàâåãäăãắảạậầằ]/u", "a", $str);
            $str = preg_replace("/[éèêëĕěếệểễềẻ]/u", "e", $str);
            $str = preg_replace("/[íìîïǐĭīĩỉï]/u", "i", $str);
            $str = preg_replace("/[óòôøõöŏỏỗộơọỡốơồờớổ]/u", "o", $str);
            $str = preg_replace("/[úùûüůưừựủứụ]/u", "u", $str);
            $str = preg_replace("/[žź]/u", "z", $str);
            $str = preg_replace("/[ýÿỹ]/u", "y", $str);
            $str = preg_replace("/[đ]/u", "d", $str);
            $str = preg_replace("/æ/u", "ae", $str);
            $str = preg_replace("/[čćç]/u", "c", $str);
            $str = preg_replace("/[ñńň]/u", "n", $str);
            $str = preg_replace("/œ/u", "oe", $str);
            $str = preg_replace("/[śšş]/u", "s", $str);
            $str = preg_replace("/ř/u", "r", $str);
            $str = preg_replace("/ğ/u", "g", $str);
            $str = preg_replace("/Ř/u", "R", $str);

            return $str;
        }

        public function removeNumbers($str)
        {
            $str = preg_replace('/[0-9]+/', '', $str);

            return $str;
        }

        public function spacePoints($str)
        {
            
            $str = str_replace('.', '. ', $str);

            return $str;
        }

}