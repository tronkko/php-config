<?php
#
# Test program for measuring performance of Config class.
#
# Copyright (c) 2015 Toni Ronkko
# See the file LICENSE for copying permissions.
#
require_once (__DIR__ . '/../class/config.php');



### INITIALIZE ###

# How many times to repeat the test
$repeat = 1000;

# Enable error reporting
error_reporting (E_ALL | E_STRICT);
ini_set ('display_errors', 0);
assert_options (ASSERT_ACTIVE, 1);
assert_options (ASSERT_WARNING, 1);
assert_options (ASSERT_BAIL, 1);



### BEGIN TEST ###

# Read and write file repeatedly to compute the elapsed time
$start = microtime (true);
for ($i = 0; $i < $repeat; $i++) {

    # Create temporary configuration file
    $fn = tempnam (sys_get_temp_dir (), 'conf');

    # Create configuration file
    file_put_contents ($fn, <<<EOF
[section1]
id = $i
path = "/usr/local/bin"

[section2]
URL = "http://www.example.com/~username"

EOF
);

    # Read the file
    $data = file_get_contents ($fn);
    if ($data == '') {
        die ('Error');
    }

    # Delete temporary file
    unlink ($fn);
}
$stop = microtime (true);
$filetime = ($stop - $start) / $repeat;


# Read a configuration file repeatedly with Config class
$start = microtime (true);
for ($i = 0; $i < $repeat; $i++) {

    # Create temporary configuration file
    $fn = tempnam (sys_get_temp_dir (), 'conf');

    # Create configuration file
    file_put_contents ($fn, <<<EOF
[section1]
id = $i
path = "/usr/local/bin"

[section2]
URL = "http://www.example.com/~username"

EOF
);

    # Read and analyze the configuration file.  Function getInstance is
    # not used here so that file is read and analyzed each time: reading
    # configuration file from cache would skew the results.
    $conf = new Config ($fn);

    # Make sure that values were read from the file
    assert ('$conf["section1.id"] == $i');
    assert ('$conf["section1.path"] == "/usr/local/bin"');
    assert ('$conf["section2.URL"] == "http://www.example.com/~username"');

    # Delete temporary file
    unlink ($fn);
}
$stop = microtime (true);
$configtime = ($stop - $start) / $repeat;

# This test is considered a failure if it takes on average more than
# one millisecond to read the configuration file.
assert ('$configtime < 0.001');


# Read a configuration file repeatedly with parse_ini_file function
$start = microtime (true);
for ($i = 0; $i < $repeat; $i++) {

    # Create temporary configuration file
    $fn = tempnam (sys_get_temp_dir (), 'conf');

    # Create configuration file
    file_put_contents ($fn, <<<EOF
[section1]
id = $i
path = "/usr/local/bin"

[section2]
URL = "http://www.example.com/~username"

EOF
);

    # Read and analyze the configuration file
    $conf = parse_ini_file ($fn, true);

    # Make sure that values were read from the file
    assert ('$conf["section1"]["id"] == $i');
    assert ('$conf["section1"]["path"] == "/usr/local/bin"');
    assert ('$conf["section2"]["URL"] == "http://www.example.com/~username"');

    # Delete temporary file
    unlink ($fn);
}
$stop = microtime (true);
$parsetime = ($stop - $start) / $repeat;

# Test is considered a failure if parse_ini_file can do roughly the same
# task in one fourth of the effort
assert ('$configtime * 0.25 < $parsetime');

#echo "file=". ($filetime) . "\n";
#echo "conf=". ($configtime) . "\n";
#echo "pars=" . ($parsetime) . "\n";


### CLEANUP ###



### END TEST ###
echo "OK\n";


