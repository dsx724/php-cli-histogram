[blog](http://www.xuetech.com/2012/07/long-awaited-cli-histogram.html)
======

php-cli-histogram
=================
Simple CLI histogram for numeric inputs written in PHP. See [example.png](https://raw.github.com/dsx724/php-cli-histogram/master/example.png).
[Apache 2.0 License](https://raw.github.com/dsx724/console-qrcode/master/LICENSE).

requires
========
* PHP CLI (sudo apt-get install php5-cli)
* tput - ioctls are not available in PHP

usage
=====
```bash
iostat -dx 1 /dev/sda | gawk '/sda/ {print $14 | "./histogram.php 0 100 20" }'
cat someprogram.log | ./histogram.php 0 1 100 5 1 1
```

arguments
=========
* x_min
* x_max
* buckets - dynamic merging if your window size is too small for the number of buckets
* x_log
* y_log
* significant digits - default 5

features
========
* Mean Variance Standard Deviation Input Tracking
* Bin Merging on Small Window
* Vertical/Horizontal Window Adjustments

cautionary tails
================
* Not designed look pretty after 999,999 elements in 1 bin - 11 days of same bin at 1/s
* Wasn't tested with negative numbers
* More refinement with sig figs
* Bin size locked on init
* Bin merging's effect on non-divisible bin numbers
* Beware of overrun issues with $t on large numerical inputs esp on 32 bit machines - may throw means off
* Redraws on each input - For high iteration jobs, add a few lines to slow down the redraw
* Window calculations were done half hazardly
