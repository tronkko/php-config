<?php
#
# tests/all.php
# Program for running full Php-config test suite.
#
# Copyright (c) 2015 Toni Ronkko
# This file is part of Php-config.  Php-config may be freely distributed
# under the MIT license.  For all details and documentation, see
# https://github.com/tronkko/php-config
#

# Available tests
$tests = array(
    'sanity',
    'section',
    'comment',
    'conversion',
    'multiline',
    'efficiency'
);

# Run tests
echo "Running tests:\n\n";
$fail = 0;
foreach ($tests as $testname) {

    # Print name of test
    printf ("%-24.24s", ucfirst ($testname));
    flush ();

    # Execute test.  The test is considered success if the script produces
    # the word "OK".
    $dir = __DIR__;
    $output = shell_exec ("php $dir/$testname.php 2>&1");

    # Print result
    if (trim ($output) == "OK") {

        # Test succeeded
        printf ("OK\n");

    } else if (is_null ($output)) {

        # Could not execute test
        printf ("FAIL\n");
        $fail++;

    } else {

        # Test failed with error message
        printf ("ERROR\n");
        echo $output;
        $fail++;

    }

}

echo "\n";
if (!$fail) {
    echo "All tests passed\n";
} else {
    echo "TEST FAILURE\n";
}

