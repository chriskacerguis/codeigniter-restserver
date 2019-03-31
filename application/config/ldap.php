<?php
$config['binduser'] = 'cn=Authentication,ou=Services,dc=example,dc=org';
$config['basedn'] = 'dc=example,dc=org';
$config['bindpw'] = 'E984asdy2';
/*
 * The host name parameter can be a space separated list of host names.
 * This means that the LDAP code will talk to a backup server if the main server is not operational.
 * There will be a delay while the code times out trying to talk to the main server but things will still work.
*/
$config['server'] = 'ldapserver1.example.org ldapserver2.example.org';
$config['port'] = null;
/*
 * Controls the LDAP_OPT_NETWORK_TIMEOUT option, this is how long the code will attempt to talk to the primary server if it is unreachable.
 */
$config['timeout'] = 5;