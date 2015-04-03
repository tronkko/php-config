<?php
#
# Test reading of multi-line strings
#
require_once (__DIR__ . '/../class/config.php');



### INITIALIZE ###

# Enable error reporting
error_reporting (E_ALL | E_STRICT);
ini_set ('display_errors', 0);
assert_options (ASSERT_ACTIVE, 1);
assert_options (ASSERT_WARNING, 1);
assert_options (ASSERT_BAIL, 1);

# Create temporary configuration file
$fn = tempnam (sys_get_temp_dir (), 'conf');


### BEGIN TEST ###

# Test value
$analytics = <<<EOF
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-XXXXX-X']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
EOF;

# Create configuration file
file_put_contents ($fn, <<<EOF
# Simple quoted multi-line value
quoted="r1
r2
3"

# Complex multi-line value with HTML
html: <<<HTML
<div class="x">x</div>
line2
HTML

# Multi-line value with fake terminator
value2: <<<HTML  
HTML HTML
 HTML

HTML

# Empty value
value3: <<<HTML
HTML;

# Long and complicated value
analytics: <<<HTML
$analytics
HTML

EOF
);


# Read back the file
$conf = Config::getInstance ($fn);

# All multi-line values are extracted from configuration file
assert ('isset ($conf->quoted)');
assert ('isset ($conf->html)');
assert ('isset ($conf->value2)');
assert ('isset ($conf->value3)');
assert ('isset ($conf->analytics)');

# Double-quoted values are stored with all line-feeds in place
assert ('$conf->quoted == "r1\nr2\n3"');

# Here-doc values are stored without terminating line-feed
assert ('$conf->html == "<div class=\"x\">x</div>\nline2"');

# Only separator string along terminates the value.  Moreover, empty line
# at the end of value is recognized as newline.
assert ('$conf->value2 == "HTML HTML\n HTML\n"');

# Here-doc can be empty
assert ('$conf->value3 == ""');

# Javascript code is passed as is
assert ('$conf->analytics == $analytics');


### CLEANUP ###

# Remove temporary file
unlink ($fn);


### END TEST ###
echo "OK\n";


