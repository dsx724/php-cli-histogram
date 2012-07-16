<?php
$array = array(0,1,2,3,4,5,6,7,8,9);

$array = array(0,1,2,3,4);

$Ex = 0;
$Ex2 = 0;
$n = 0;
foreach ($array as $input){
	$Ex += $input;
	$Ex2 += $input*$input;
	$n++;
}
echo $Ex.PHP_EOL.$Ex2.PHP_EOL.$n.PHP_EOL;
$m = $Ex/$n;
$std = sqrt(abs(($Ex2 - 2*$Ex*$m + $m*$m)/($n-1)));
echo 'Mean:'.number_format($m,2).PHP_EOL;
echo 'STD:'.number_format($std,2).PHP_EOL;
?>