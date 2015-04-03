# tmos-config
Library for handling configuration files in PHP.

# Rationale
Before PHP code is run on servers, the code often has to be configured just for that server's enviroment.  For example, production servers may have distinct user names and passwords for accessing databases while development servers may have dummy email addresses and urls to prevent development code from interacting with the world or to facilitate debugging.

Tmos-config is a library that allows you to store server specific data in a configuration file that is kept outside of version control.  Having configuration data outside source code allows you to update or re-install your program on a number of pre-configured servers without modifying code.


# Anatomy of a Configuration File

## Simple Example
A simple configuration file might contain key-value pairs such as

```
myuser = mysqladmin
mypass = really_secret_password
production = true
```


## Sections
If the same configuration file is used by a number of programs or one program has great many options, then the file may be further divided into sections.  Sections are marked with [].  For example, a configuration files with sections global, calendar and webshop might contain

```
[global]
production = true
lang = fi

[calendar]
myuser = kuitupuu
mypasswd = kanala%naksi
mydb = cal

[webshop]
myuser = www
mypasswd = kxkam3pari
mydb = shop
```

Section names refer to directories.  For example, an application installed to directory calendar would see options first from the calender section and then from the global section.  Likewise, application installed to directory webshop would see options from the webshop section by default but not from the calendar section.


## Special Characters
If a value contains spaces or special characters, then surround the value in double quotes as

```
mypasswd = "%really hard passwrd"
```

Special characters like \ and " within quoted strings must be prefixed with backslash such that \ becomes  \ \ (two backslashes with no space in between) and " becomes \".


## Multi-line Values
Values may span multiple lines.  For example, a configuration file with some Javascript code might look like

```
google_analytics = <<<EOF
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-XXXXX-X']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
EOF
```


## Comments
You can use #, // or /* */ to comment your configuration files.  For example, a commented configuration file might contain
```
# Enable debug output
debug = 1  // Set this to 0 on production servers

/*
 * Uncomment the following lines to enable
 * experimental feature x.
 */
// enablex = true
```


# Reading Configuration File

## Construct Config object
In order to use options from a configuration file, first construct a Config object with the getInstance function
```
$conf = Config::getInstance ('server.conf')
```
where ''server.conf'' is the name of the configuration file.  

Tmos-config tries to locate the configuration file first from the directory containing config.php file, then from its parent directory and so on up until the disk root.  Ideally, you should place the configuration file just above the web root so that the file cannot be read from web accidentially and you don't need to re-create the file if you decide to wipe out the program directory before installing a new version.


## Access Values
With a Config object at hand, you can access options in three ways:

1. with getOption function as ``$value = $conf->getOption ('myuser', 'defaultuser');``
2. using object notation as ``$value = $conf->myuser;``
3. using array notation as ``$value = $conf['myuser'];``

By default, options are retrieved from the program's own section and then from the global section.  In order to retrieve options from other sections, prepend the option name with a section.  For example, to retrieve option myuser from the calendar section, you could write
```
$value = $conf->getOption ('calendar.myuser');
```

## Testing if a Value is Defined
To see if an option is defined and has a non-null value, use either isDefined function
```
if ($conf->isDefined ('myuser')) {
    # Yes
} else {
    # No
}
```
or isset()
```
if (isset ($conf->myuser)) {
    # Yes
} else {
    # No
}
```


# Using Tmos-config in Your Own Programs

Tmos-config is fully contained in the file ``class/config.php``.  In order to use tmos-config in you own programs, copy the file to your own source tree and set up autoloader to load the class implicitly.  Alternatively, load the class explicitly by adding the following line to beginning of each PHP file where configuration data is used
```
require_once (__DIR__ . '/config.php'):
```


# Alternatives to Tmos-config
While Tmos-config is versatile, there are also other libraries and tools which solve the same problem.

## Use parse_ini_file Function
If you don't need comments or multi-line values in your configuration files, then you can use PHP's own  [parse_ini_file function](http://php.net/manual/en/function.parse-ini-file.php).  The parse_ini_file function has been around since PHP 4 and it is generally more efficient than Tmos-config.

## Store Configuration Data in a PHP File Along include_path
If you can set up your own ``php.ini``, then you could add a server specific directory outside of your regular source tree to [include_path](http://php.net/manual/en/ini.core.php#ini.include-path) and store configuration data to a program specific PHP file in this directory.  You could then summon configuration data from PHP code simply by including a file.

