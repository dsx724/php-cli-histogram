<?php
//Based on http://www.cs.berkeley.edu/~mhoemmen/cs194/Tutorials/variance.pdf
//iostat -dx 1 /dev/sda | gawk '/sda/ {print $14}' | php histogram.php

$stdin = fopen('php://stdin','r');
if ($stdin === false) die('Unable to open STDIN'.PHP_EOL);

$input = rtrim(fgets($stdin));
if ($input === false || !is_numeric($input)) die('Invalid Input:'.$input.PHP_EOL);

$fv = floatval($input);
$Mk = $fv;
$Qk = 0;
$k = 1;
$t = $fv;
while (($input = fgets($stdin)) !== false || !is_numeric($input)){
	$fv = floatval($input);
	$t += $fv;
	$diff = $fv - $Mk;
	$Qk += ($k++) * ($diff) * ($diff) / $k;
	$Mk += $diff / $k;
	
	echo 'Mean:'.number_format($t/$k,2).PHP_EOL;
	echo 'Var:'.number_format($Qk/($k-1),2).PHP_EOL;
	echo 'STD:'.number_format(sqrt($Qk/($k-1)),2).PHP_EOL;
}
//time_sleep_until
//pcntl_fork
?>