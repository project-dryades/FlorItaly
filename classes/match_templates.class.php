<?php


class NmTemplates
{
    // FUNZIONI GIA' ESISTENTI

    public function __construct($testo)
    {
        $this->testo = $testo;
    }
    public function getCounter($array)
        // necessita delle myfunction, in finalnamelist
    {
        $sum_names = $array[0] + $array[1] + $array[2];
        $con = '<p>'.$this->testo->intro[47].$sum_names.'<br>';

        // wrong names
        $con.= $this->testo->intro[37].$array[0];
        $con.= '&nbsp&nbsp&nbsp&nbsp';
        $con.= '<input type="radio" onclick="myFunctionWrong()" value="'.$this->testo->intro[45].'" name="wrong" checked >';
        $con.= '<label for="'.$this->testo->intro[45].'">'.$this->testo->intro[45].'</label>';
        $con.= '&nbsp&nbsp';
        $con.= '<input type="radio" onclick="myFunctionWrong2()" value="'.$this->testo->intro[46].'" name="wrong">';
        $con.= '<label for="'.$this->testo->intro[46].'">'.$this->testo->intro[46].'</label>';
        $con.='<br>';

        // valid names
        $con.= $this->testo->intro[36].$array[1];
        $con.= '&nbsp&nbsp&nbsp&nbsp';
        $con.= '<input type="radio" onclick="myFunctionValid()" value="'.$this->testo->intro[45].'" name="valid" checked >';
        $con.= '<label for="'.$this->testo->intro[45].'">'.$this->testo->intro[45].'</label>';
        $con.= '&nbsp&nbsp';
        $con.= '<input type="radio" onclick="myFunctionValid2()" value="'.$this->testo->intro[46].'" name="valid">';
        $con.= '<label for="'.$this->testo->intro[46].'">'.$this->testo->intro[46].'</label>';
        $con.='<br>';


        // ambiguous names
        $con.=$this->testo->intro[38].$array[2];
        $con.= '&nbsp&nbsp&nbsp&nbsp';
        $con.= '<input type="radio" onclick="myFunctionAmb()" value="'.$this->testo->intro[45].'" name="ambiguous" checked >';
        $con.= '<label for="'.$this->testo->intro[45].'">'.$this->testo->intro[45].'</label>';
        $con.= '&nbsp&nbsp';
        $con.= '<input type="radio" onclick="myFunctionAmb2()" value="'.$this->testo->intro[46].'" name="ambiguous">';
        $con.= '<label for="'.$this->testo->intro[46].'">'.$this->testo->intro[46].'</label>';
        $con.='</p>';


        return $con;

    }




    public function getNameSearch($onlyadvanced=0)
    {


        //ADVANCED


        if ($onlyadvanced == 1){
            $con = '';
        }
        else {
            // test more checklist
            //$con = '<form action="" method="post">';
            /*$con = '<input type="checkbox" id="synonym_tables1" name="synonym_tables1" value="1" checked disabled>
                    <label for="synonym_tables1">Bertolucci e Galasso 2020</label><br>
                    <input type="checkbox" id="synonym_tables2" name="synonym_tables2" value="2">
                    <label for="synonym_tables2">Pignatti 1983</label><br>';*/
            $con = '<button onclick="myFunction()">'.$this->testo->intro[21].'</button>';
            $con .= '<form action="" method="post">';
        }

        $con .= '<div id="myDIV" style="display: none">';

        $con .=$this->testo->intro[19].' <select id="score" name="score">';
        //$con .= '<option value="" disabled selected></option>';

        for ($i = 100; $i >= 0; $i = $i - 5)
        {
            if ($i == 0){
                $con .= '<option value="'.$i.'" selected>'.$i.'</option>';
            } else {
                $con .= '<option value="' . $i . '">' . $i . '</option>';
            }

        }
        $con .='</select>';
        $con .='<br>';

        //$con .=$this->testo->intro[22].'<select id="n_result" name="n_result">';
        //$con .= '<option value="" disabled selected></option>';
/*
        for ($j = 1; $j <= 10; ++$j)
        {
            if ($j == 5){
                $con .= '<option value="'.$j.'" selected>' . $j . '</option>';
            } else {
                $con .= '<option value="' . $j . '">' . $j . '</option>';
            }
        }
        $con .='</select>';
        $con .='<br>';
*/
        $con .=$this->testo->intro[23].' <select id="id_subsp" name="id_subsp">';
        $con .= '<option value="" disabled selected></option>';
        $con .= '<option value="subsp.">subsp.</option>';
        $con .= '<option value="s.">ssp.</option>';
        $con .= '<option value="s.">s.</option>';
	$con .='</select>';

        $con .='<br>';
        $con .=$this->testo->intro[24].' <select id="id_var" name="id_var">';
        $con .= '<option value="" disabled selected></option>';
        $con .= '<option value="var.">var.</option>';
        $con .= '<option value="v.">v.</option>';
        $con .='</select>';

        $con .='<br>';
        $con .=$this->testo->intro[25].' <select id="id_form" name="id_form">';
        $con .= '<option value="" disabled selected></option>';
        $con .= '<option value="form.">form.</option>';
        $con .= '<option value="f.">f.</option>';
        $con .='</select>';

        $con .='<br>';
        $con .=$this->testo->intro[26].' <select id="id_cv" name="id_cv">';
        $con .= '<option value="" disabled selected></option>';
        $con .= '<option value="cv.">cv.</option>';
        $con .= '<option value="c.">c.</option>';
        $con .='</select>';

        $con .='<br>';
        $con .= $this->testo->intro[27];
        $con .= '<input type="radio" id="yes" value="yes" name="phonetic" checked>
                <label for="yes">'.$this->testo->intro[7].'</label>
                <input type="radio" id="no" value="no" name="phonetic">
                <label for="no">'.$this->testo->intro[8].'</label>';

        $con .='<br>';
        $con .= $this->testo->intro[28];
        $con .= '<input type="radio" id="yes" value="yes" name="insensitive">
                <label for="yes">'.$this->testo->intro[7].'</label>
                <input type="radio" id="no" value="no" name="insensitive" checked>
                <label for="no">'.$this->testo->intro[8].'</label>';


        $con .='<br>';
        $con .='<br>';

        $con .= '</div>';

        $con .= '<script>
        function myFunction() {
        var x = document.getElementById("myDIV");
        if (x.style.display === "none") {
        x.style.display = "block";
        } else {
        x.style.display = "none";
        }
        }
        </script>';


                //END ADVANCED
        if ($onlyadvanced == 1){
            $con .= '';
        }
        else {
            $con .= '<textarea id="sp_name" name="sp_name" placeholder="' . $this->testo->intro[40] . '" required style="display: block"></textarea><br><br>
                <button name="search" type="submit" value="' . $this->testo->intro[15] . '">' . $this->testo->intro[15] . '</button>
                </form>';
        }




        return $con;
    }



    public function getDownloadButton2()
    {
        $con='<form action="index.php?procedure=download2" method="post">
        <button name="d2" type="submit" value="Download">Download</button>
        </form>';
        return $con;
    }

    public function getNameListFinal($array1,$id_file='')
    {
        //A
        $con = $this->testo->intro[29];

        // vecchio sistema
        //$con .= '<p><button onclick="myFunction()">'.$this->testo->intro[34].'</button></p>';
        //$con .= '<p><button onclick="myFunction2()">'.$this->testo->intro[34].'</button></p>';



        //A

        $con .= '<form action="" method="post">';

        foreach ($array1 as $array)
        {



            if (empty($array['match'])== TRUE)
            {
                $con .= '<div class="wrongDIV" style="display: block">';
                $con .= '<p>'.$this->testo->intro[48].$array['input_name'] . '<br>' ;
                $con .= $this->testo->intro[31].'</p>';
                $con .='</div>';
            }

            elseif (count($array['match']) == 1)
            {
                $con .= '<div class="validDIV" style="display: block">';
                $con .= '<p>'.$this->testo->intro[48].$array['input_name'] .$this->testo->intro[30];
                $con .= $array['match'][0]['original_name']. ' [' . $this->testo->intro[32] .$array['match'][0]['score'].'%';

                if (array_key_exists('synonym_of', $array['match'][0]) and $array['match'][0]['synonym_of'] != '')
                {
                	$con .='; '.$this->testo->intro[33] .$array['match'][0]['synonym_of'].']';
                }
		else
			$con .= ']';
                $con .='</p>';
                $con .='</div>';

            }

            else {
                $con .= '<div class="ambDIV" style="display: block">';
                $con .= '<p>' . $this->testo->intro[48] . $array['input_name'] .$this->testo->intro[49];
                $con .= '<select name="' .  $array['input_name'] . '" id="' . $array['input_name'] . '">';
                $con .= '<option value="" disabled selected>'.$this->testo->intro[12].'</option>';

                foreach ($array['match'] as $key=>$match)
                {
                    //aggiungo per problemi con 82
                    if ($key == 1 and $match['original_name'] == '')
                    {
                        //$con .= '<option disabled></option>';
                    }
                    elseif (array_key_exists('synonym_of', $match) and $match['synonym_of'] != '')
                    {
			$stringona = $match['original_name']. ' [' . $this->testo->intro[32] .$match['score'].'%' . '; '. $this->testo->intro[33] .$match['synonym_of'] . ']';
			$conto = strlen($stringona);
			if ($conto>=100) 
			{
				$stringhina = substr($stringona, 0, 95);
				$stringhina .= '.....';
			}
			else
				$stringhina=$stringona;
			$con .= '<option value="' . $match['original_name'] . '" title="' . $stringona . '">' . $stringhina . '</option>';
                        //$con .= '<option value="' . $match['original_name'] . '" title="' . $match['original_name']. $this->testo->intro[32] .$match['score'].'%' .$this->testo->intro[33] .$match['synonym_of']. '">' . $match['original_name']. $this->testo->intro[32] .$match['score'].'%' .$this->testo->intro[33] .$match['synonym_of']. '</option>'; //$value
                    }
                    else
                    {
                        $stringona = $match['original_name']. ' [' . $this->testo->intro[32] .$match['score'].'%' . ']';
                        $conto = strlen($stringona);
                        if ($conto>=100)
                        {
                                $stringhina = substr($stringona, 0, 95);
                                $stringhina .= '.....';
                        }
			else
                                $stringhina=$stringona;
                        $con .= '<option value="' . $match['original_name'] . '" title="' . $stringona . '">' . $stringhina . '</option>';
                        //$con .= '<option value="' . $match['original_name'] . '" title="' . $match['original_name']. $this->testo->intro[32] .$match['score'].'%' . '">' . $match['original_name']. $this->testo->intro[32] .$match['score'].'%' . '</option>'; //$value
                    }
                }
                $con .= '<option value="qwertyuiop"></option>';
                $con .= '</select></p>';
                $con .='</div>';

            }

        }
        if ($id_file != '')
        {
            $con .='<button name="names" type="submit" value='.$id_file.'>Applica modifiche / Apply changes</button> </form>';
        }
        else
        {
            $con .='<button type="submit" name="names" value="Applica modifiche / Apply changes">Applica modifiche / Apply changes</button></form>';
        }

        /*$con .= '<script>
        function myFunction() {
        var x = document.getElementsByClassName("myDIV");
        for (var i = 0; i < x.length; i++){
        if (x[i].style.display === "none") {
        x[i].style.display = "block";
        } else {
        x[i].style.display = "none";
        }
        }
        }
        </script>'*/;
        $con .= '<script>';
        $con .='function myFunctionAmb() {
        var x = document.getElementsByClassName("ambDIV");
        for (var i = 0; i < x.length; i++){
            x[i].style.display = "block";
        }
        }
        ';
        $con .= '
        function myFunctionAmb2() {
        var x = document.getElementsByClassName("ambDIV");
        for (var i = 0; i < x.length; i++){
            x[i].style.display = "none";
        }
        }';
        $con .='function myFunctionValid() {
        var x = document.getElementsByClassName("validDIV");
        for (var i = 0; i < x.length; i++){
            x[i].style.display = "block";
        }
        }
        ';
        $con .= '
        function myFunctionValid2() {
        var x = document.getElementsByClassName("validDIV");
        for (var i = 0; i < x.length; i++){
            x[i].style.display = "none";
        }
        }';
        $con .='function myFunctionWrong() {
        var x = document.getElementsByClassName("wrongDIV");
        for (var i = 0; i < x.length; i++){
            x[i].style.display = "block";
        }
        }
        ';
        $con .= '
        function myFunctionWrong2() {
        var x = document.getElementsByClassName("wrongDIV");
        for (var i = 0; i < x.length; i++){
            x[i].style.display = "none";
        }
        }';

        $con.='</script>';


        //echo htmlentities($con);
        return $con;
    }



}
