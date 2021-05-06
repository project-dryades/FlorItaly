<?php
 
class DamerauLevenshteinMod {

	/**
  * Function: distance
  * Purpose: this function either uses mdld algorithm written in C represented by distance_utf function from module mdld.so, or the same algorithm writtein in php.
  * Inputs: string1 as $ustr1, string2 as ustr2, numeric limit of length of transposed block to be searched for as block_size, maximum edit distance after which calculations abort (for performance increase)
  * Outputs: computed edit distance between the input stings (0=identical, 1..n = amount of editing events required to make strings identical)
  * @param string $ustr1
  * @param string $ustr2
  * @param integer $block_size
  * @param integer $max_distance
  * @return integer : computed edit distance between two strings
  */
  static function distance($ustr1, $ustr2, $block_size=2, $max_distance=4) {
		if (function_exists('distance_utf')) {
			$a1 = self::utf8_to_unicode_code($ustr1);
			$a2 = self::utf8_to_unicode_code($ustr2);
			return distance_utf($a1, $a2, $block_size, $max_distance);
		} else return self::mdld_php( $ustr1, $ustr2, $block_size, $max_distance);
	}
  

  /**
  * Function: utf8_to_unicode_code
  * Purpose: Convert UTF-8 string into array of integers for furhter manipulations.
  * Inputs: string in ascii or utf-8
  * Outputs: array of integers. Each integer uniquely represents one of utf-8 character.
  * @param string $utf8_string
  * @return array
  */
	static function utf8_to_unicode_code($utf8_string){
		$expanded = iconv("UTF-8", "UTF-32", $utf8_string);
		$converted = unpack("L*", $expanded);
		return $converted;
	}
	
	/**
	 * Function: mdld
	 * Purpose: Performs Damerau-Levenshtein Distance test on two input strings, supporting block
	 *   transpositions of multiple characters
	 * Inputs: string 1 as p_str1, string 2 as p_str2, numeric limit on length of transposed block to be searched for as p_block_limit
	 * Outputs: computed edit distance between the input strings (0=identical on this measure, 1..n=increasing dissimilarity)
	 * @param string $p_str1
	 * @param string $p_str2
	 * @param integer $p_block_limit
	 * @return integer : computed edit distance between the input strings
	 */
	public function mdld_php( $p_str1, $p_str2, $p_block_limit = null, $max_distance= null ) {
		$p_block_limit = is_null($p_block_limit) ? 1 : $p_block_limit;
		$len1 = strlen($p_str1);$len2 = strlen($p_str2);
		$current_distance = 10000;

		if($p_str1 == $p_str2) {
			return 0;
		} elseif( $len1 == 0 || $len2 == 0 ) {
			return max($len1,$len2);
		} elseif( $len1 == 1 && $len2 == 1 &&  $p_str1 != $p_str2 ) {
			return 1;
		} else {
			$temp_str1 = $p_str1;
			$temp_str2 = $p_str2;			
			#first trim common leading characters
			while ( substr ($temp_str1, 0, 1) == substr ($temp_str2, 0, 1) ) {
				$temp_str1 = substr ($temp_str1, 1);
				$temp_str2 = substr ($temp_str2, 1);
			}
			#then trim common trailing characters
			while ( substr ($temp_str1, -1, 1) == substr ($temp_str2, -1, 1) ) {
				$temp_str1 = substr ($temp_str1, 0, strlen($temp_str1) - 1);
				$temp_str2 = substr ($temp_str2, 0, strlen($temp_str2) - 1);
			}
			$len1 = strlen($temp_str1);
			$len2 = strlen($temp_str2);
			#then calculate standard Levenshtein Distance
			if ( $len1 == 0 OR $len2 == 0 ) {
				return max($len1,$len2);
			} elseif ($len1 == 1 && $len2 == 1 && $p_str2 != $p_str1) {
				return 1;
			} else {

				#enter values in first (leftmost) column
				for( $t = 0; $t<= $len2; $t++ ) {
					$v_my_columns[0][$t] = $t;
				}
				
				#populate remaining columns
				for( $s = 1; $s<= $len1; $s++ ) {
					$v_my_columns[$s][0] = $s;
					
					#populate each cell of one column:
					for( $t = 1; $t<= $len2; $t++ ) {
						$v_my_columns[$s][$t] = 0;
						#calculate cost
						if(substr($temp_str1, $s-1, 1) == substr($temp_str2, $t-1, 1)) {
							$v_this_cost = 0;
						} else {
							$v_this_cost = 1;
						}
						#extension to cover multiple single, double, triple, etc character transpositions
						#that includes caculation of original Levenshtein distance when no transposition found
						$v_temp_block_length = min( ($len1 / 2), ($len2 / 2), $p_block_limit);			
$print = 0;						
if ($print) {							
						print "<pre>";
						print_r($v_my_columns);								
						print "</pre>";
}
						while( $v_temp_block_length >= 1) {
if ($print) {							
							print "<br>";
							print "$p_str1 $p_str2<br>";
							print "$temp_str1 $temp_str2<br>";
							print "$s >= " .  ($v_temp_block_length * 2). "<br>";
							print "$t >= " .  ($v_temp_block_length * 2). "<br>";
							print substr($temp_str1, ($s-1) - ( ($v_temp_block_length * 2) - 1), $v_temp_block_length) . "==" . substr($temp_str2, ($t-1) - ($v_temp_block_length - 1), $v_temp_block_length) . "<br>";
							print substr($temp_str1, ($s-1) - ($v_temp_block_length - 1), $v_temp_block_length) . "==" . substr($temp_str2, ($t-1) - ( ($v_temp_block_length * 2) - 1), $v_temp_block_length) . "<br>";
							print $v_temp_block_length . "<br>";
}
							if( ($s >= ($v_temp_block_length * 2))													
								&& ($t >= ($v_temp_block_length * 2))
								&& (substr($temp_str1, ($s-1) - ( ($v_temp_block_length * 2) - 1), $v_temp_block_length) == substr($temp_str2, ($t-1) - ($v_temp_block_length - 1), $v_temp_block_length))
								&& (substr($temp_str1, ($s-1) - ($v_temp_block_length - 1), $v_temp_block_length) == substr($temp_str2, ($t-1) - ( ($v_temp_block_length * 2) - 1), $v_temp_block_length))
							) {
if ($print) {															
								print "Transpostion Found<hr>";
}
								#transposition found
								$v_my_columns[$s][$t] = min(
										$v_my_columns[$s][$t - 1] + 1
									, $v_my_columns[$s - 1][$t] + 1
									, ($v_my_columns[$s - ($v_temp_block_length * 2)][$t - ($v_temp_block_length * 2)] + $v_this_cost + ($v_temp_block_length - 1))
								);
								$v_temp_block_length = 0;
							} elseif ($v_temp_block_length == 1) {
								#no transposition
if ($print) {															
								print "No Transpostion<br>";
								print $v_my_columns[$s][$t - 1] . "<br>"; 
								print $v_my_columns[$s-1][$t] . "<br>"; 
								print $v_my_columns[$s - 1][$t - 1] + $v_this_cost . "<br>";
								print "<pre>";
								print_r($v_my_columns);								
								print "</pre>";
}
								$v_my_columns[$s][$t] = min(
										$v_my_columns[$s][$t - 1] + 1
									, $v_my_columns[$s - 1][$t] + 1
									, $v_my_columns[$s - 1][$t - 1] + $v_this_cost
								);
							} else {
								$v_my_columns[$s][$t] = 0;
							}
							$v_temp_block_length -= 1;
						}
					//	if ($current_distance > $v_my_columns[$s][$t]) $current_distance = $v_my_columns[$s][$t];
					}
					//if ($current_distance > $max_distance) return $current_distance;
				}
			}

			if (!isset($v_my_columns[$s-1][$t-1])) {
				print "$s, $t<br><pre>";
				print_r($v_my_columns);
				print "</pre>";
			}

			if(isset($v_my_columns[$len1-1][$len2-1])) {
				return $v_my_columns[$len1-1][$len2-1];
			} else {
				return(-1);
			}
		}

	}

	/**
		 * Function: ngram
		 * Purpose: Perform n-gram comparison of two input strings
		 * Author: Tony Rees (Tony.Rees@csiro.au)
		 * Date created: March 2008
		 * Inputs: string 1 as source_string, string 2 as target_string, required value of n to be
		 *   incorporated for as n_used
		 * Outputs: computed similarity between the input strings, on 0-1 scale (1=identical on this measure, 0=no similarity)
		 * Remarks:
		 *   (1) Input parameter n_used determines whether the similarity is calculated using unigrams (n=1),
		 *   bigrams (n=2), trigrams (n=3), etc; defaults to n=1 if not supplied.
		 *   (2) Input strings are padded with (n-1) spaces, to avoid under-weighting of terminal characters.
		 *   (3) Repeat instances of any n-gram substring in the same input string are treated as new substrings,
		 *   for comparison purposes (up to 9 handled in this implementation)
		 *   (4) Is case sensitive (should translate input strings to same case externally to render case-insensitive)
		 *   (5) Similarity is calculated using Diceís coefficient.
		 * @param string $source_string
		 * @param string $target_string
		 * @param string $n_used : determines whether the similarity is calculated using unigrams (n=1),bigrams (n=2), trigrams (n=3), etc; defaults to n=1 if not supplied.
		 * @return number : between 0 - 1 : (1 = identical - typically after normalization; 0 = no similarity)
		 */
		public function ngram( $source_string = NULL, $target_string = NULL, $n_used = 1 ) {

			$match_count = 0;

			$this->input = array($source_string,$target_string);

			$this->debug['ngram'][] = "1 (n_used:$n_used) (source_string:$source_string) (target_string:$target_string)";
		
			$padding=str_repeat(" ", $n_used -1);

			$this_source_string = $padding . $source_string . $padding;
			$this_target_string = $padding . $target_string . $padding;
			// build strings of n-grams plus occurrence counts
	
			$source_ngram_number=mb_strlen($source_string)+$n_used-1;
			$target_ngram_number=mb_strlen($target_string)+$n_used-1;

			$source_ngram=array();
			$target_ngram=array();

			for ($i=0; $i < $source_ngram_number; $i++) {
				$ngram=mb_substr($this_source_string, $i, $n_used);
				if (! isset($source_ngram[$ngram])) {
					$source_ngram[$ngram]=0;
				}
				$source_ngram[$ngram]++;
			}
			for ($i=0; $i < $target_ngram_number; $i++) {
				$ngram=mb_substr($this_target_string, $i, $n_used);
				if (! isset($target_ngram[$ngram])) {
					$target_ngram[$ngram]=0;
				}
				$target_ngram[$ngram]++;
			}

			while (list($ngram, $source_ngram_count) = each($source_ngram)){
				if (array_key_exists($ngram, $target_ngram)) {
					$target_ngram_count = $target_ngram[$ngram];
					$match_count+=$target_ngram_count < $source_ngram_count ? $target_ngram_count : $source_ngram_count;
				}
			}

			$result = round(( 2 * $match_count ) / ( $source_ngram_number + $target_ngram_number ), 4);

			return $result;
		}


	
}