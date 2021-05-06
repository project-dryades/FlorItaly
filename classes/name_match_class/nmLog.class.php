<?php


class NmLog
{
    public function log($mixed,$active=false)
    {
        if ($active)
        {
            echo '<p>';
            print_r($mixed);
            echo '<p>';
        }
    }
    public function logAdv($keep_false=false)
    {
        if ($keep_false){
            return True;
        }
        else{
            return False;
        }
    }
}