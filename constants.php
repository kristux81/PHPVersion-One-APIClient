<?php

# Version one login methods
define('BASIC_AUTH', 1);
define('ACCESS_TOKEN', 2);

# system logging
#  logging levels, can be OR'd together
define( 'LOG_NONE',     0 );  # no logging
define( 'LOG_MIN',      1 );  # minimum
define( 'LOG_AJAX',     2 );  # logging for AJAX / XmlHttpRequests
define( 'LOG_LDAP',     4 );  # logging for ldap
define( 'LOG_DATABASE', 8 );  # logging for database
