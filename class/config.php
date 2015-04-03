<?php
#
# Library for handling configuration files in PHP
# https://github.com/tronkko/tmos-config
# 
# Copyright (c) 2015 Toni Ronkko
# 
# Permission is hereby granted, free of charge, to any person obtaining a
# copy of this software and associated documentation files (the "Software"),
# to deal in the Software without restriction, including without limitation
# the rights to use, copy, modify, merge, publish, distribute, sublicense,
# and/or sell copies of the Software, and to permit persons to whom the
# Software is furnished to do so, subject to the following conditions:
# 
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
# 
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
# FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
# DEALINGS IN THE SOFTWARE.
# 

/**
 * Library for handling configuration files in PHP.
 *
 * Example:
 *
 *     # Load server.conf
 *     $conf = Config::getInstance ('server.conf');
 *
 *     # Retrieve value of an option from file
 *     $user = $config->mysql_user;
 *
 * See https://github.com/tronkko/tmos-config for documentation and more
 * examples.
 */
class Config implements ArrayAccess {
    static private $_cache = array ();
    private $_filename;
    private $_sections;
    private $_values;

    /*
     * Construct new configuration object from file.
     *
     * @param string $base Name of configuration file in disk
     */
    function __construct ($base) {
        # Reset member variables
        $this->_filename = '';
        $this->_sections = array ('');
        $this->_values = array ();

        # Locate configuration file from disk
        $fn = $this->_locateFile ($base);
        if ($fn) {

            # Read file to memory
            $this->_values = $this->_readFile ($fn);

            # Find default sections to look for
            $this->_sections = $this->_getSections ($fn);

        } else {
            throw new Exception ("File not found $base");
        }
    }

    /**
     * Retrieve Config object.
     *
     * The function expects a file name as a first parameter.  The file name
     * is usually given without directory component in which case the file
     * will be looked up starting from the location of this file up to the
     * root directory.  If the configuration file resides elsewhere, then you
     * must supply an absolute file name.
     *
     * The function returns a Config object.  The returned object may be
     * freshly constructed, or created earlier by another function call
     * requesting the same configuration file.
     *
     * Example:
     *
     *     # Load server.conf
     *     $conf = Config::getInstance ('server.conf');
     *
     * @param string $base Name of configuration file in disk
     * @return Config
     */
    static public function getInstance ($base) {
        # Is the configuration file already in memory?
        if (isset (self::$_cache[$base])) {

            # Yes, the file has already been loaded
            $obj = self::$_cache[$base];

        } else {

            # No, load the file from disk and save it to cache
            $obj = new Config ($base);
            self::$_cache[$base] = $obj;

        }
        return $obj;
    }

    /**
     * See if the configuration file provides a value for an option.
     *
     * The function expects an option name as the first parameter.  The option
     * name may be simple identifier such as "mysqluser" or compound name such
     * as "calendar.user" consisting of section and option identifiers.
     *
     * Example:
     *
     *     # Open configuration file
     *     $conf = Config::getInstance ('server.conf');
     *
     *     # Quit now if option username is not found
     *     if (!$conf->isDefined ('username')) {
     *         throw new Exception ('Missing username');
     *     }
     *
     * @param string $key Name of option
     * @return bool True if option is defined
     */
    public function isDefined ($key) {
        $result = false;

        # See if the option is defined in any section
        foreach ($this->_sections as $section) {
            if (isset ($this->_values[$section.$key])) {
                $result = true;
                break;
            }
        }

        return $result;
    }
    public function offsetExists ($key) {
        return $this->isDefined ($key);
    }
    public function __isset ($key) {
        return $this->isDefined ($key);
    }

    /**
     * Get option from configuration file.
     *
     * The function expects an option name as the first parameter.  The option
     * name may be simple identifier such as "mysqluser" or compound name such
     * as "calendar.user" consisting of section and option identifiers.
     * Additionally, the function accepts a second optional parameter which
     * gives the default value of the option.
     *
     * The function returns the value associated for that option.  By default,
     * sections matching the directory components will be tried from bottom to
     * top fashion.  If the option is not defined in any section along search
     * path, then the function returns the optional second argument or an
     * empty string.
     *
     * Example:
     *
     *     # Open configuration file
     *     $conf = Config::getInstance ('server.conf');
     *
     *     # Read option using function
     *     echo $conf->getOption ('username', 'default');
     *
     *     # Read option using object notation
     *     echo $conf->production;
     *
     *     # Read option using array notation
     *     echo $conf['production'];
     *
     *     # Read option user from section mysql
     *     echo $conf['mysql.user'];
     *
     * @param string $key 
     * @param string $default Optional default value
     */
    public function getOption ($key, $default = '') {
        $value = $default;

        # Search through sections and take the value from the first section
        foreach ($this->_sections as $section) {
            if (isset ($this->_values[$section.$key])) {
                $value = $this->_values[$section.$key];
                break;
            }
        }

        return $value;
    }
    public function offsetGet ($key) {
        return $this->getOption ($key);
    }
    public function __get ($key) {
        return $this->getOption ($key);
    }

    /*
     * Find location of configuration file.
     *
     * @param string $base Relative file name
     * @return string Absolute file name
     */
    protected function _locateFile ($base) {
        $filename = null;
        if (substr ($base, 0, 1) != '/') {

            # Relative file name supplied.  Traverse the directory
            # tree from current directory up to disk root while looking for
            # configuration file in each step
            $arr = explode ('/', __DIR__);
            for ($i = count ($arr); $i > 0; $i--) {

                # Construct absolute directory name from root to ith directory
                # component
                $dir = implode ('/', array_slice ($arr, 0, $i));

                # Does configuration file reside in directory $dir?
                if (file_exists ("$dir/$base")) {
                    # Yes, stop search
                    $filename = "$dir/$base";
                    break;
                }
            }

        } else {

            # Absolute pathname supplied so return the argument as is
            $filename = $base;

        }
        return $filename;
    }

    /*
     * Read options from configuration file.
     *
     * @param string $fn Absolute file name
     * @return array Associative array consisting of key-value pairs
     */
    protected function _readFile ($fn) {
        $options = array ();

        # Open configuration file for read
        $fp = fopen ($fn, "rb");
        if ($fp) {

            # Lock file against modifications
            flock ($fp, \LOCK_SH);

            # Read entire file to a string
            $data = '';
            while (($line = fgets ($fp)) !== false) {
                $data .= $line;
            }

            # Close file and release lock
            fclose ($fp);

            # Parse options
            $options = $this->_parseOptions ($data);

        } else {
            throw new Exception ("Cannot open file $fn");
        }
        return $options;
    }

    /*
     * Extract option-value pairs from file data.
     *
     * @param string $data Contents of a file
     * @return array Associative array consisting of key-value pairs
     */
    protected function _parseOptions ($data) {
        $mode = 0;
        $section = '';
        $values = array ();

        # Character classes
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $sectionchar = $letters . $digits . '_-.';
        $separatorchar = $sectionchar;

        # Convert Mac and MS-DOS line-feeds into Unix line-feeds.  This
        # simplifies code below.
        $data = preg_replace ('/\r\n|\r/', "\n", $data);

        # Read line of data into buffer
        $i = 0;
        $n = strlen ($data);
        $done = false;
        while (!$done) {

            # Peek next character
            if ($i < $n) {
                $c = substr ($data, $i, 1);
            } else {
                $c = 'EOF';
            }

            # Deal with line
            switch ($mode) {
            case 0:
                # Initial mode
                switch ($c) {
                case ' ':
                case "\n":
                case "\t":
                    # Ignore white space
                    $i += strspn ($data, " \n\t", $i);
                    break;

                case '[':
                    # Start of section name
                    $mode = 100;
                    break;

                case '#':
                case '/':
                    # Comment
                    $len = $this->_getComment ($data, $i);
                    if ($len > 0) {
                        # Valid comment
                        $i += $len;
                    } else {
                        # Not a comment
                        $mode = 999;
                    }
                    break;

                case 'EOF':
                    # Normal end of file
                    $done = true;
                    break;

                case ';':
                    # Ignore
                    $i++;
                    break;

                default:
                    if (strpos ($letters, $c) !== false) {
                        # Option name
                        $mode = 200;
                    } else {
                        # Invalid character
                        $mode = 999;
                    }
                }
                break;

            case 100:
                # Start of section name
                $i++;
                $section = '';
                $mode = 101;
                break;

            case 101:
                # Section name continued
                switch ($c) {
                case ']':
                    # End of section name
                    $mode = 0;
                    $i++;
                    break;

                case 'EOF':
                    # Unexpected end of file
                    $mode = 998;
                    break;

                default:
                    $len = strspn ($data, $sectionchar, $i);
                    if ($len > 0) {
                        # Character in section name
                        $section .= substr ($data, $i, $len);
                        $i += $len;
                    } else {
                        # Invalid character
                        $mode = 999;
                    }
                }
                break;

            case 200:
                # Start of option name
                $key = '';
                $value = '';
                $mode = 201;
                /*FALLTHROUGH*/

            case 201:
                # Option name continued
                switch ($c) {
                case ':':
                case '=':
                    # Start of value
                    $i++;
                    $mode = 300;
                    break;

                case ' ':
                case "\t":
                    # End of option name
                    $mode = 202;
                    break;

                case '#':
                case '/':
                    # Comment
                    $len = $this->_getComment ($data, $i);
                    if ($len > 0) {
                        # Valid comment
                        $i += $len;
                    } else {
                        # Not a comment
                        $mode = 999;
                    }
                    break;

                case "\n":
                    # Option name does not end in colon
                    $mode = 999;
                    break;

                case 'EOF':
                    # Unexpected end of file
                    $mode = 998;
                    break;

                default:
                    $len = strspn ($data, $letters . $digits, $i);
                    if ($len > 0) {
                        # Option name
                        $key .= substr ($data, $i, $len);
                        $i += $len;
                    } else {
                        # Invalid character
                        $mode = 999;
                    }
                }
                break;

            case 202:
                # Space after option name
                switch ($c) {
                case ':':
                case '=':
                    # Start of value
                    $i++;
                    $mode = 300;
                    break;

                case ' ':
                case "\t":
                    # Ignore white space
                    $i += strspn ($data, " \t", $i);
                    break;

                case '#':
                case '/':
                    # Comment
                    $len = $this->_getComment ($data, $i);
                    if ($len > 0) {
                        # Valid comment
                        $i += $len;
                    } else {
                        # Not a comment
                        $mode = 999;
                    }
                    break;

                case "\n":
                    # Option name does not end in colon
                    $mode = 999;
                    break;

                case 'EOF':
                    # Unexpected end of file
                    $mode = 998;
                    break;

                default:
                    # Invalid character
                    $mode = 999;
                }
                break;

            case 300:
                # Space before option value
                switch ($c) {
                case ' ':
                case "\t":
                    # Ignore white space before value
                    $i += strspn ($data, " \t", $i);
                    break;

                case ';':
                case "\n":
                    # Line feed means no value
                    $i++;
                    /*FALLTHROUGH*/

                case 'EOF':
                    # No value provided
                    $mode = 398;
                    break;

                case '"':
                    # Start of quoted string
                    $i++;
                    $mode = 320;
                    break;

                case '<':
                    # Start of multiline-data
                    $i++;
                    $mode = 330;
                    break;

                case '#':
                case '/':
                    # Comment
                    $len = $this->_getComment ($data, $i);
                    if ($len > 0) {
                        # Valid comment
                        $i += $len;
                    } else {
                        # Not a comment
                        $mode = 999;
                    }
                    break;

                default:
                    # Start of unquoted string
                    $mode = 310;
                }
                break;

            case 310:
                # Start of unquoted string
                switch ($c) {
                case ';':
                case "\n":
                    # Linefeed ends value
                    $i++;
                    /*FALLTHROUGH*/

                case 'EOF':
                    # EOF ends value
                    $mode = 398;
                    break;

                case ' ':
                case "\t":
                    # Space ends value
                    $i++;
                    $mode = 312;
                    break;

                case '#':
                case '/':
                    # Comment
                    $len = $this->_getComment ($data, $i);
                    if ($len > 0) {
                        # Valid comment
                        $i += $len;
                    } else {
                        # Slash in value
                        $value .= $c;
                        $i++;
                    }
                    break;

                default:
                    # Regular characters within unqouted string
                    $len = strcspn ($data, " \t\n/#;", $i);
                    if ($len > 0) {
                        $value .= substr ($data, $i, $len);
                        $i += $len;
                    } else {
                        # Invalid character
                        $mode = 999;
                    }
                }
                break;

            case 312:
                # Space after unquoted value
                switch ($c) {
                case ' ':
                case "\t":
                    # Ignore excess white space
                    $i++;
                    break;

                case ';':
                case "\n":
                    # End of value
                    $i++;
                    /*FALLTHROUGH*/

                case 'EOF':
                    # End of value
                    $mode = 399;
                    break;

                case '#':
                case '/':
                    # Comment
                    $len = $this->_getComment ($data, $i);
                    if ($len > 0) {
                        # Valid comment
                        $i += $len;
                    } else {
                        # Not a comment
                        $mode = 999;
                    }
                    break;

                default:
                    # Invalid character
                    $mode = 999;
                }
                break;

            case 320:
                # Inside quoted string
                switch ($c) {
                case 'EOF':
                    # Missing close quote
                    $mode = 998;
                    break;

                case '\\':
                    # Escape character
                    $i++;
                    $mode = 322;
                    break;

                case '"':
                    # End of quoted string
                    $i++;
                    $mode = 323;
                    break;

                default:
                    # Regular characters inside string
                    $len = strcspn ($data, '\\"', $i);
                    if ($len > 0) {
                        $value .= substr ($data, $i, $len);
                        $i += $len;
                    } else {
                        # Invalid character
                        $mode = 999;
                    }
                }
                break;

            case 322:
                # Escaped character inside quoted string
                switch ($c) {
                case 'EOF':
                    # Backslash at the end of file
                    $mode = 998;
                    break;

                default:
                    # Escaped character
                    $value .= $c;
                    $i++;
                    $mode = 320;
                }
                break;

            case 323:
                # Space after quoted value
                switch ($c) {
                case ' ':
                case "\t":
                    # Ignore excess white space after quoted value
                    $i++;
                    break;

                case ';':
                case "\n":
                    # End of value
                    $i++;
                    /*FALLTHROUGH*/

                case 'EOF':
                    # End of value
                    $mode = 399;
                    break;

                case '#':
                case '/':
                    # Comment
                    $len = $this->_getComment ($data, $i);
                    if ($len > 0) {
                        # Valid comment
                        $i += $len;
                    } else {
                        # Not a comment
                        $mode = 999;
                    }
                    break;

                default:
                    # Invalid character
                    $mode = 999;
                }
                break;

            case 330:
                # Start of multi-line value
                switch ($c) {
                case '<':
                    # Ignore any number of less than signs.  That is,
                    # sequences <EOF, <<<EOF and <<<<<<EOF all begin
                    # multi-line value alike.
                    $i++;
                    break;

                case ' ':
                case "\t":
                case "\n":
                    # Missing separator string
                    $mode = 999;
                    break;

                case 'EOF':
                    # Missing separator string
                    $mode = 998;
                    break;

                default:
                    # Any other character starts separator string
                    $separator = '';
                    $mode = 331;
                }
                break;

            case 331:
                # Separator string
                switch ($c) {
                case ' ':
                case "\t":
                    # Space ends separator
                    $mode = 332;
                    break;

                case "\n":
                    # Line-feed starts the value
                    $i++;
                    $mode = 333;
                    break;

                case 'EOF':
                    # Unterminated value
                    $mode = 998;
                    break;

                default:
                    # Collect separator string
                    $len = strspn ($data, $separatorchar, $i);
                    if ($len > 0) {
                        $separator .= substr ($data, $i, $len);
                        $i += $len;
                    } else {
                        # Invalid character
                        $mode = 999;
                    }
                }
                break;

            case 332:
                # Space after separator string
                switch ($c) {
                case ' ':
                case "\t":
                    # Ignore spaces
                    $i += strspn ($data, " \t", $i);
                    break;

                case "\n":
                    # Line-feed starts the multi-line value
                    $i++;
                    $mode = 333;
                    break;

                case 'EOF':
                    # Unterminated string
                    $mode = 998;
                    break;

                default:
                    # Invalid character
                    $mode = 999;
                }
                break;

            case 333:
                # Inside multi-line value
                $pos = strpos ($data, "\n$separator", $i - 1);
                if ($pos !== false) {

                    # Compute length of value
                    $len = $pos - $i;
                    if ($len > 0) {

                        # Extract value up until the possible separator
                        $value .= substr ($data, $i, $len);
                        $i += $len;

                    }

                    # Remember starting position of possible separator
                    $separatorpos = $i;

                    # Skip separator
                    if (substr ($data, $i, 1) == "\n") {
                        $i++;
                    }
                    $i += strlen ($separator);

                    # Continue parsing after separator
                    $mode = 334;

                } else {

                    # Unterminated string
                    $mode = 998;

                }
                break;

            case 334:
                # White space after possible separator
                switch ($c) {
                case ';':
                case "\n":
                    # End of row
                    $i++;
                    /*FALLTHROUGH*/

                case 'EOF':
                    # Store value as string
                    $mode = 399;
                    break;

                case ' ':
                case "\t":
                    # Ignore excess white space
                    $i += strspn ($data, " \t", $i);
                    break;

                case '#':
                case '/':
                    # Skip comments
                    $len = $this->_getComment ($data, $i);
                    if ($len > 0) {
                        # Valid comment
                        $i += $len;
                        break;
                    }
                    /*FALLTHROUGH*/

                default:
                    # Text follows separator so the separator was
                    # fake.  Store separator as value and continue
                    # looking for real separator.
                    $i = $separatorpos;
                    if (substr ($data, $i, 1) == "\n") {
                        $value .= "\n";
                        $i++;
                    }
                    $len = strlen ($separator);
                    $value .= substr ($data, $i, $len);
                    $i += $len;
                    $mode = 333;
                }
                break;

            case 398:
                # Convert value to proper data type and store
                $value = $this->_convertValue ($value);
                /*FALLTHROUGH*/

            case 399:
                # Store value as is
                if ($section != ''  &&  $section != 'global') {

                    # Store in named section
                    $key = $section . '.' . $key;

                } else {

                    # Store in global section
                    $key = "global.$key";

                }
                $values[$key] = $value;
                $mode = 0;
                break;

            case 998:
                throw new Exception ('Unexpected EOF');

            case 999:
                $str = substr ($data, $i, 15);
                throw new Exception ("Parse error near $str");

            default:
                throw new Exception ("Invalid mode $mode");
            }
        }
        return $values;
    }

    /*
     * Convert value to natural data type.
     *
     * @param string $value Value as string
     * @return mixed Converted value
     */
    protected function _convertValue ($value) {
        switch ($value) {
        case 'null':
            # Null value
            $value = null;
            break;

        case 'true':
            # Boolean
            $value = true;
            break;

        case 'false':
            # Boolean
            $value = false;
            break;

        default:
            if (is_numeric ($value)) {

                # Number
                if (preg_match ('/^-?[1-9][0-9]*$/', $value)) {

                    # Regular 10-base integer
                    $value = intval ($value, 10);

                } else if (preg_match ('/^-?0[0-7]*$/', $value)) {

                    # 8-base integer
                    $value = intval ($value, 8);

                } else if (preg_match ('/^-?0x[0-9a-z]*$/i', $value)) {

                    # 16-base integer
                    $value = intval ($value, 16);

                } else {

                    # Floating point number
                    $value = $value + 0;

                }

            } else {

                # String
                /*NOP*/;

            }
        }
        return $value;
    }

    /*
     * Compute length of comment string
     *
     * @param string $data Contents of a file
     * @param int $i File position
     * @return int Length of comment starting at i
     */
    protected function _getComment ($data, $i) {
        $c = substr ($data, $i, 1);
        if ($c == '#') {

            # Shell comment
            $len = strcspn ($data, "\n", $i);

        } else if ($c == '/') {

            # C or C++ comment
            $d = substr ($data, $i + 1, 1);
            if ($d == '/') {

                # C++ comment
                $len = strcspn ($data, "\n", $i);

            } else if ($d == '*') {

                # C-style comment
                $pos = strpos ($data, '*/', $i);
                if ($pos !== false) {
                    $len = $pos - $i + 2;
                } else {
                    throw new Exception ('Unterminated comment');
                }

            } else {

                # Not a comment at all
                $len = 0;
            }

        } else {

            # Not a comment
            $len = 0;

        }
        return $len;
    }

    /*
     * Construct list of default sections to search.
     *
     * @param string $fn Absolute file name
     * @return array Indexed array consisting of section labels
     */
    protected function _getSections ($fn) {
        # Starting from the configuration file, construct a relative
        # path to the directory where this file resides.  For example, if
        # the configuration file resides at /home/tronkko and this file
        # is located at /home/tronkko/public_html/config/class directory,
        # then the relative directory name is public_html/config/class.
        $root = dirname ($fn) . '/';
        $dir = __DIR__ . '/';
        $n = strlen ($root);
        if (substr ($dir, 0, $n) == $dir) {

            # This file resides in a subdirectory compared to
            # configuration file
            $relative = substr ($dir, $n - 1);

        } else {

            # No common root => use whole path
            $relative = $dir;

        }

        # Split relative directory name into subdirectories
        $subdirs = explode ('/', trim ($relative, '/'));

        # Reverse the order of subdirectories and make them sections.
        # For example, if the list of subdirectories is public_html,
        # config and class, then the list of searched sections will be
        # class, config and finally public_html.
        $sections = array ();
        foreach (array_reverse ($subdirs) as $subdir) {
            # Append dot to section name to simplify searches
            $sections[] = $subdir . '.';
        }

        # Append global section.  This ensures that options without an
        # explicit section name will be searched from global section.
        $sections[] = 'global.';

        # Append empty section.  This ensures that options with explicit
        # section names will be search from respective sections.
        $sections[] = '';

        return $sections;
    }

    # Unsupported functions that are needed for the interface
    public function offsetSet ($key, $value) {
        throw new Exception ('Not supported');
    }
    public function __set ($key, $value) {
        throw new Exception ('Not supported');
    }
    public function offsetUnset ($key) {
        throw new Exception ('Not supported');
    }
    public function __unset ($key) {
        throw new Exception ('Not supported');
    }

}

