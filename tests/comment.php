<?php
#
# Test program making sure that comments and empty lines are skipped
# properly.
#
# Copyright (c) 2015 Toni Ronkko
# See the file LICENSE for copying permissions.
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

# Create configuration file
file_put_contents ($fn, <<<EOF

# Regular option
username= jeke   // C++ comment

pass=12# End of line comment

# Commented option
#id=234

/*
* Commented options
sup=234
jeke=3
*/

# C-style comments allow option to be broken into several lines
/*comment1*/ optionx /*comment2
continued*/ = /*comment3
continued*/ 2/*comment in the middle of value?!?*/5

EOF
);

# Read configuration file
$conf = Config::getInstance ($fn);

# Options username, pass and optionx are defined
assert ('$conf->isDefined("username")');
assert ('$conf->isDefined("pass")');
assert ('$conf->isDefined("optionx")');

# Commented options are ignored
assert ('!$conf->isDefined("id")');
assert ('!$conf->isDefined("sup")');
assert ('!$conf->isDefined("jeke")');

# White space before and after value is stripped
assert ('$conf->getOption ("username") == "jeke"');
assert ('$conf->username == "jeke"');
assert ('$conf["username"] == "jeke"');

# Comment at the end of line is not considered part of the value
assert ('$conf->pass == "12"');

# C-style comments in the line are ignored
assert ('$conf->optionx == "25"');


### CLEANUP ###

# Remove temporary file
unlink ($fn);


### END TEST ###
echo "OK\n";

