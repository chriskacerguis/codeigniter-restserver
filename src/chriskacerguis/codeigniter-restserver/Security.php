<?php

namespace Restserver\Security;

class Security {
    
    /**
     * Constructor for the Security class
     *
     * @access public
     * @author Chris Kacerguis
     * @param string $config Configuration filename minus the file extension
     * @return void
     */
    public function __construct($config = 'rest')
    {

    }

    /**
     * is the IP address provided whitelisted
     *
     * @access public
     * @author Chris Kacerguis
     * @return bool
     */
    public function isWhitelisted($ip)
    {

    }

    /**
     * is the IP address provided blacklisted
     *
     * @access public
     * @author Chris Kacerguis
     * @return bool
     */
    public function isBlacklisted($id)
    {

    }

    /**
     * checks to see if we have a valid session
     *
     * @access public
     * @author Chris Kacerguis
     * @return bool
     */
    public function isSecure()
    {

    }


}