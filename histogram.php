#!/usr/bin/php
<?php
//Simple CLI Wrapped PHP Histogram 
//Variance algorithm based on http://www.cs.berkeley.edu/~mhoemmen/cs194/Tutorials/variance.pdf
//Usage: iostat -dx 1 /dev/sda | gawk '/sda/ {print $14 | "./histogram.php" }'
//Usage: cat someprogram.log | ./histogram.php 0 100 10
/* Parameters:
 * x_min
 * x_max
 * buckets
 * digits
 */

/* Requires: 
 * PHP CLI (sudo apt-get install php5-cli)
 */
/* Cautionary Tails:
 * Beware of overrun issues with $t on large numerical inputs esp on 32 bit machines - may throw means off
 * Incomplete log axis control
 * Vertical rescaling doesn't work
 * Binning cannot change after initialization
 * Redraws on each input - For high iteration jobs, add a few lines to slow down the redraw
 * Window calculations were done half hazardly
 * Not responsible for breaking ur shit (as always)
 * Feel free to contribute back with a pull request
 * Copyleft please - GPL3
 */
$x_min = $argc > 1 ? $argv[1] : 0;
$x_max = $argc > 2 ? $argv[2] : 100;
$buckets = $argc > 3 ? $argv[3] : exec('tput lines') - 3;
$digits = $argc > 4 && $argv[4] > 3 ? $argv[4] : 5;
$x_log = $argc > 5 ? $argv[5] : false;//incomplete
$y_log = $argc > 6 ? $argv[6] : false;//incomplete

if ($x_log) $x_step = ($x_max - $x_min) / $buckets; // revise algorithm for logorithmic indepdent vars
else $x_step = ($x_max - $x_min) / $buckets;

$data = array_fill(0,$buckets,0);

$stdin = fopen('php://stdin','r');
if ($stdin === false) die('Unable to open STDIN'.PHP_EOL);

$input = rtrim(fgets($stdin));
if ($input === false || !is_numeric($input)) die('Invalid Input:'.$input.PHP_EOL);

$fv = floatval($input);
$Mk = $fv;
$Qk = 0;
$k = 1;
$t = $fv;
while (($input = rtrim(fgets($stdin))) !== false || !is_numeric($input)){
	$fv = floatval($input);
	$t += $fv;
	$diff = $fv - $Mk;
	$Qk += ($k++) * ($diff) * ($diff) / $k;
	$Mk += $diff / $k;
	
	$width = exec('tput cols');
	$width_border = 2*($digits+1);
	$width_length = $width - 2*$width_border - 1;
	$height = exec('tput lines');
	$height_spacer = str_repeat(PHP_EOL,floor(($height - 2) / $buckets) - 1);
	$height_padding = str_repeat(PHP_EOL,floor(($height - 2) % $buckets) - 1);
	$var = $Qk/($k-1);
	echo $width.'x'.$height.'	Mean:'.number_format($t/$k,2).'	Var:'.number_format($var,2).'	STD:'.number_format(sqrt($var),2).'	Input:'.$input.PHP_EOL;
	if ($x_log){
		
	} else {
		if ($fv >= $x_max) $data[$buckets - 1]++;
		else if ($fv < $x_min) $data[0]++;
		else $data[floor(($fv - $x_min))/$x_step]++;
		$peak = max($data);
		if ($y_log){
			//incomplete
		} else {
			foreach ($data as $index => $bucket){
				$lower_bound = $x_min + $index * $x_step;
				$lower_bound_str = sprintf('%d',$lower_bound);
				$lower_bound_str_length = strlen($lower_bound_str);
				$lower_bound_str = ($lower_bound_str_length > $digits) ? sprintf('%'.$digits.'.'.($digits - 4).'e',$lower_bound) : sprintf('%'.$digits.'.'.($digits - $lower_bound_str_length - 1).'f',$lower_bound);
				
				$upper_bound = $lower_bound + $x_step;
				$upper_bound_str = sprintf('%d',$upper_bound);
				$upper_bound_str_length = strlen($upper_bound_str);
				$upper_bound_str = ($upper_bound_str_length > $digits) ? sprintf('%'.$digits.'.'.($digits - 4).'e',$upper_bound) : sprintf('%'.$digits.'.'.($digits - $upper_bound_str_length - 1).'f',$upper_bound); 
				
				$x_axis = $lower_bound_str.'-'.$upper_bound_str.':';
				echo $x_axis;
				//echo $x_axis.str_repeat(' ',$width_border - strlen($x_axis));
				echo str_repeat('#',floor($bucket * $width_length / $peak)).':'.$bucket.PHP_EOL.$height_spacer;
			}
		}
		echo $height_padding;
	}
}
//time_sleep_until
//pcntl_fork
?>