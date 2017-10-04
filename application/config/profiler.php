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
$config['benchmarks']           = TRUE;
$config['config']               = TRUE;
$config['controller_info']      = TRUE;
$config['get']                  = TRUE;
$config['http_headers']         = TRUE;
$config['memory_usage']         = TRUE;
$config['post']                 = TRUE;
$config['queries']              = TRUE;
$config['eloquent']             = FALSE;
$config['uri_string']           = TRUE;
$config['view_data']            = TRUE;
$config['query_toggle_count']   = 1000;