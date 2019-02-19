<?php
// Command that starts the built-in web server
$command = sprintf(
    'php -S %s:%d >/dev/null 2>&1 & echo $!',
    '127.0.0.1',
    8080
);
 
// Execute the command and store the process ID
$output = array(); 
exec($command, $output);
$pid = (int) $output[0];
 
echo sprintf(
    '%s - Web server started on %s:%d with PID %d', 
    date('r'),
    '127.0.0.1',
    8080, 
    $pid
) . PHP_EOL;
 
// Kill the web server when the process ends
register_shutdown_function(function() use ($pid) {
    echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;
    exec('kill ' . $pid);
});
 
