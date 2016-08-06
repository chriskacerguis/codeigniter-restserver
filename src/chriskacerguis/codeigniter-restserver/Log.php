<?php

namespace Restserver\Log;

class Log {

    var $requestStart   = 0;
    var $requestEnd     = 0;
    var $requestTotal   = 0;

    /**
     * Constructor for the Log class
     *
     * @access public
     * @author Chris Kacerguis
     * @param string $config Configuration filename minus the file extension
     * @return void
     */
    public function __construct($config = 'rest')
    {
        // Is logged enabled?
        // How are we logging (file, table)
        // if table, does it exist, make it if needed
    }

    /**
     * Create the table in SQL if it does not exist
     *
     * @access public
     * @author Chris Kacerguis
     * @return bool
     */
    private function create_table()
    {

    }


    /**
     * Check to see if we have logging enabled
     *
     * @access public
     * @author Chris Kacerguis
     * @return bool
     */
    private function enabled()
    {

    }

    /**
     * Create an entry in the db log table
     *
     * @access public
     * @author Chris Kacerguis
     * @return bool
     */
    private function dbLog()
    {

    }

    /**
     * Create an entry in the file log
     *
     * @access public
     * @author Chris Kacerguis
     * @return bool
     */
    private function fileLog()
    {

    }

    /**
     * check to see if we have logging enabled
     *
     * @access public
     * @author Chris Kacerguis
     * @return bool
     */
    public function entry()
    {
        // Create an entry in the log
    }

    /**
     * get the time in microseconds at the start of the request
     *
     * @access public
     * @author Chris Kacerguis
     * @return void
     */
    public function startRequest()
    {
        $this->requestStart = microtime(true);
    }

    /**
     * get the time in microseconds at the end of the request
     *
     * @access public
     * @author Chris Kacerguis
     * @return void
     */
    public function endRequest()
    {
        $this->requestEnd = microtime(true);
    }

    /**
     * get the total time of the request in microseconds
     *
     * @access public
     * @author Chris Kacerguis
     * @return void
     */
    public function totalRequest()
    {
        $this->requestTotal = $this->requestStart - $this->requestEnd;
    }


}
