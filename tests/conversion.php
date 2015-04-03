<?php
#
# Test program that makes sure that numbers, strings and boolean values
# are converted to their native data types but quoted values are left as is.
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
beta:true
gamma:false
theta:"false"
xray:"null"
val:24
flt:3.33
hexa:0xff
oct:0100
EOF
);

# Read back the file
$conf = Config::getInstance ($fn);

# Make sure that values are converted to their native types
assert ('is_bool ($conf->beta)');
assert ('is_bool ($conf->gamma)');
assert ('is_string ($conf->theta)');
assert ('is_string ($conf["xray"])');
assert ('is_int ($conf->val)');
assert ('is_float ($conf->flt)');

# Boolean values
assert ('$conf->beta === true');
assert ('$conf->gamma === false');

# String values
assert ('$conf->theta === "false"');
assert ('$conf["xray"] === "null"');

# Integer values
assert ('$conf["val"] === 24');

# Floating point values
assert ('abs ($conf->flt - 3.33) < 0.0001');

# Hexadecimal values are converted to integers
assert ('$conf["hexa"] === 255');

# Octal values are converted to integers
assert ('$conf["oct"] === 64');


### CLEANUP ###

# Remove temporary file
unlink ($fn);


### END TEST ###
echo "OK\n";

