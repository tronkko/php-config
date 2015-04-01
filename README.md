# tmos-config
Library for handling configuration files in PHP.

# Rationale
Before PHP code is run on servers, the code often has to be configured just for that server's enviroment.  For example, production servers may have distinct user names and passwords for accessing databases while development servers may have dummy email addresses and urls to prevent development code from interacting with the world or to facilitate debugging.

Tmos-config a library that allows you to store server specific data in a configuration file that is kept outside of version control.  Having configuration data outside source code allows you to update or re-install your program on a number of pre-configured servers without modifying code.


# Anatomy of a Configuration File

## Simple Example
A simple configuration file might contain key-value pairs such as

```
myuser = mysqladmin
mypass = really_secret_password
production = true
```


## Sections
If the same configuration file is used by a number of programs or one program has great many options, then the file may be further divided into sections.  A configuration file with sections might look like

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

Section names should refer to installation directories.  For example, an application installed to directory calendar would see options first from the calender section and then from the global section.  Likewise, application installed to directory webshop would see options from the webshop section by default but not from the calendar section.


## Special Characters
If a value contains spaces or special characters, then surround the value in double quotes as

```
mypasswd = "%really hard passwrd"
```

Special characters like \ and " within quoted strings are prefixed with backslash such that \ becomes  \ \ (two backslashes with no space in between) and " becomes \".


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



# Reading configuration file

## Construct Config object
In order to use options from a configuration file, first construct a Config object with the getInstance function
```
$conf = Config::getInstance ('server.conf')
```
where ''server.conf'' is the name of the configuration file.  

Tmos-config tries to locate the configuration file first from the directory containing config.php file, then from its parent directory and so on up until the disk root.  Ideally, you should place the configuration file just above the web root so that the file cannot be read from web accidentially and you don't need to re-create the file if you decide to wipe out the program directory before installing a new version.


## Access Values
With a Config object, you can access options in three ways:

1. with getOption function as ``$value = $conf->getOption ('myuser', 'defaultuser');``
2. using object notation as ``$value = $conf->myuser;``
3. using array notation as ``$value = $conf['myuser'];``

By default, options are retrieved from the program's own section and then from the global section.  In order to retrieve options from other sections, prepend the option name with a section.  For example, to retrieve option myuser from the calendar section, you could write
```
$value = $conf->getOption ('calendar.myuser');
```

## Testing if a Value is Defined

In order to test if an option is defined and has a non-null value, you can use the function isDefined
```
if ($conf->isDefined ('myuser')) {
    # Yes
} else {
    # No
}
```
or isset
```
if (isset ($conf->myuser)) {
    # Yes
} else {
    # No
}
```


# Using tmos-config in your own programs

Tmos-config is fully contained in the file ``class/config.php``.  In order to use tmos-config in you own programs, copy the file to your own source tree and set up autoloader to load the class implicitly or load the class explicitly by adding the following line to beginning of each PHP file where configuration data is used
```
require_once (__DIR__ . '/config.php'):
```
