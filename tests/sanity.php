<?php
#
# tests/sanity.php
# Test program for primary functions of Php-config.
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

### BEGIN TEST ###

# Create configuration file
file_put_contents ($fn, <<<EOF
x:4
second:hep
EOF
);

# Read configuration file
$conf = Config::getInstance ($fn);

# Function isset confirms that the option x is defined
assert ('$conf->isDefined("x")');
assert ('isset ($conf->x)');
assert ('isset ($conf["x"])');

# Value for option x is found in default section
assert ('$conf->getOption("x", null) == 4');
assert ('$conf->x == 4');
assert ('$conf["x"] == 4');

# Option second is also defined
assert ('$conf->isDefined("second")');

# Value for the second option is retrieved intact
assert ('$conf->second == "hep"');

# Function isset confirms that the option dummy is NOT defined
assert ('!$conf->isDefined("dummy")');
assert ('!isset ($conf->dummy)');
assert ('!isset ($conf["dummy"])');

# Value for option dummy is not found
assert ('$conf->getOption("dummy", "default") == "default"');
assert ('$conf->getOption("dummy") == ""');

# Function isset confirms that there is no x in section spa
assert ('!$conf->isDefined("spa.x")');

# Value for option x is not found from spa
assert ('$conf->getOption("spa.x") == ""');


### CLEANUP ###

# Remove temporary file
unlink ($fn);


### END TEST ###
echo "OK\n";

