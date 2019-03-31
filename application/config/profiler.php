<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Profiler Sections
| -------------------------------------------------------------------------
| This file lets you determine whether or not various sections of Profiler
| data are displayed when the Profiler is enabled.
| Please see the user guide for info:
|
|	http://codeigniter.com/user_guide/general/profiling.html
|
*/
$config['benchmarks']           = true;
$config['config']               = true;
$config['controller_info']      = true;
$config['get']                  = true;
$config['http_headers']         = true;
$config['memory_usage']         = true;
$config['post']                 = true;
$config['queries']              = true;
$config['eloquent']             = false;
$config['uri_string']           = true;
$config['view_data']            = true;
$config['query_toggle_count']   = 1000;