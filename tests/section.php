<?php
#
# tests/section.php
# Test program for section handling.
#
# Copyright (c) 2015 Toni Ronkko
# This file is part of Php-config.  Php-config may be freely distributed
# under the MIT license.  For all details and documentation, see
# https://github.com/tronkko/php-config
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

# Make sure that php-config is in path
assert ('strpos (__DIR__, "/php-config/") !== false');


### BEGIN TEST ###

# Create configuration file
file_put_contents ($fn, <<<EOF
[global]
x:4
second:2

[php-config]
x: "25"

[anothersection]
x: 999
second: 888

EOF
);

# Read configuration file to memory
$conf = Config::getInstance ($fn);

# Function isset tells that option x is defined in some accessible section
assert ('$conf->isDefined("x")');
assert ('isset ($conf->x)');
assert ('isset ($conf["x"])');

# The value of option x comes from the php-config section by default.
# Be ware that this test requires the config.php file to reside in a
# directory php-config for this test to succeed!
assert ('$conf->getOption("x", null) == "25"');
assert ('$conf->x == "25"');
assert ('$conf["x"] == "25"');

# Option second is also defined
assert ('$conf->isDefined("second")');
assert ('isset ($conf->second)');
assert ('isset ($conf["second"])');

# The value for the option second comes from global section
assert ('$conf->getOption("second") == 2');
assert ('$conf->second == 2');
assert ('$conf["second"] == 2');

# A value can be retrieved from any section by explicitly naming the
# section
assert ('$conf->getOption("anothersection.x", null) == 999');
assert ('$conf["anothersection.x"] == 999');

# The value of option x in the global section can also be retrieved by
# adding the section name global
assert ('$conf->getOption("global.x", null) == 4');
assert ('$conf["global.x"] == 4');


### CLEANUP ###

# Remove temporary file
unlink ($fn);


### END TEST ###
echo "OK\n";

