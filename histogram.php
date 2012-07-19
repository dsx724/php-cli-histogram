#!/usr/bin/php
<?php
//Simple Bash/CLI Wrapped PHP Histogram 
//Usage: iostat -dx 1 /dev/sda | gawk '/sda/ {print $14 | "./histogram.php 0 100 20" }'
//Usage: cat someprogram.log | ./histogram.php 0 1 100 5 1 1
/* Parameters:
 * x_min
 * x_max
 * buckets - dynamic merging if your window size is too small for the number of buckets
 * x_log
 * y_log
 * significant digits (> 5 otherwise 5 is used)
 */
/*
 * Features:
 * Mean Variance Standard Deviation Input Tracking
 * Bin Merging on Small Window
 * Vertical/Horizontal Window Adjustments
 */
/* Requires: 
 * PHP CLI (sudo apt-get install php5-cli)
 * tput
 */
/* Cautionary Tails:
 * Not designed look pretty after 999,999 elements in 1 bin (11 days of same bin at 1/s)
 * Wasn't tested with negative numbers
 * More refinement with sig figs
 * Bin size locked on init
 * Bin merging's effect on non-divisible bin numbers
 * Beware of overrun issues with $t on large numerical inputs esp on 32 bit machines - may throw means off
 * Redraws on each input - For high iteration jobs, add a few lines to slow down the redraw
 * Window calculations were done half hazardly
 * Not responsible for breaking ur shit (as always)
 * Feel free to contribute back with a pull request
 * Copyleft please - GPL3
 */
$x_min = $argc > 1 ? $argv[1] : 0;
$x_max = $argc > 2 ? $argv[2] : 100;
$buckets = $argc > 3 ? $argv[3] : exec('tput lines') - 3;
$x_log = $argc > 4 ? $argv[4] : false;//incomplete
$y_log = $argc > 5 ? $argv[5] : false;
$digits = $argc > 6 && $argv[6] > 3 ? $argv[4] : 5;

if ($x_max <= $x_min) die('The minimum and the maximum X values are equal or inverted.'.PHP_EOL);
if ($x_log && ($x_min <= 0 || $x_max <= 0)) die('The minimum and the maximum X values cannot be less than or equal to 0 in the x log scale.'.PHP_EOL);

$x_step = $x_log ? pow($x_max / $x_min,1 / $buckets) : ($x_max - $x_min) / $buckets;
var_dump($x_step);

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
	$t += $fv; //mean calc
	
	//accurate one pass variance algo - http://www.cs.berkeley.edu/~mhoemmen/cs194/Tutorials/variance.pdf
	$diff = $fv - $Mk;
	$Qk += $diff * $diff * $k++ / $k;
	$Mk += $diff / $k;
	
	$width = exec('tput cols');
	$width_border = ($digits << 1) + 2; //margins
	$width_length = $width - $width_border - 7; //max bar length
	
	$height = exec('tput lines');
	$height_usable = $height - 2;
	$height_bucket_ratio = $height_usable / $buckets;
	$height_line_spacing = floor($height_bucket_ratio) - 1;
	$merge_buckets = ceil($buckets / $height_usable);
	if ($height_line_spacing < 0) $height_line_spacing = floor($height_bucket_ratio * $merge_buckets) - 1;
	$height_footer_spacing = $height_usable % ceil($buckets / $merge_buckets);
	$height_spacer = str_repeat(PHP_EOL,$height_line_spacing); //blank lines between rows
	$height_footer_spacer = str_repeat(PHP_EOL,floor($height_footer_spacing)); //end padding to move graph to window size, leaves 1 for ^C
	
	//printing output
	$var = $Qk/($k-1);
	echo PHP_EOL.$width.'x'.$height.'	Mean:'.number_format($t/$k,2).'	Var:'.number_format($var,2).'	STD:'.number_format(sqrt($var),2).'	Input:'.$input;
	
	if ($fv >= $x_max) $data[$buckets - 1]++;
	else if ($fv < $x_min) $data[0]++;
	else $data[floor($x_log ? log($fv,$x_step) - log($x_min,$x_step) : ($fv - $x_min) / $x_step)]++;
	
	$peak = max(array_map('array_sum',array_chunk($data,$merge_buckets)));
	
	$bucket_pass = 0;
	foreach ($data as $index => $bucket){
		if ($index % $merge_buckets == $merge_buckets - 1 || $index == $buckets - 1){
			$bucket += $bucket_pass;
			$bucket_pass = 0;
			
			$scaler = floor($index / $merge_buckets) * $merge_buckets;
			
			$lower_bound = $x_log ? $x_min * pow($x_step, $scaler) : $x_min + $x_step * $scaler;
			$lower_bound_str_length = strlen(sprintf('%d',$lower_bound));
			$lower_bound_str = ($lower_bound_str_length > $digits) ? sprintf('%'.$digits.'.'.($digits - 4).'e',$lower_bound) : sprintf('%'.$digits.'.'.($digits - $lower_bound_str_length - 1).'f',$lower_bound);
			
			$upper_bound = $index == $buckets - 1 ? $x_max : ($x_log ? $lower_bound * pow($x_step,$merge_buckets) : $lower_bound + $merge_buckets * $x_step);
			$upper_bound_str_length = strlen(sprintf('%d',$upper_bound));
			$upper_bound_str = ($upper_bound_str_length > $digits) ? sprintf('%'.$digits.'.'.($digits - 4).'e',$upper_bound) : sprintf('%'.$digits.'.'.($digits - $upper_bound_str_length - 1).'f',$upper_bound);
			
			$bar_length = floor($bucket == 0 ? 0 : $y_log ? ($peak == 1 ? $width_length : log($bucket,$peak) * $width_length) : $bucket * $width_length / $peak);
			
			echo PHP_EOL.$lower_bound_str.'-'.$upper_bound_str.':'.str_repeat('#',$bar_length).':'.$bucket.$height_spacer;
		} else {
			$bucket_pass += $bucket;
		}
	}
	echo $height_footer_spacer;
}
//time_sleep_until
//pcntl_fork
?>