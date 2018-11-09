<?php
namespace Restserver\Libraries;
use Exception;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * IP class
 * For IP based checks; rate limiting, IP whitelist, IP blacklist 
 *
 * @author    Chris Kacerguis
 */
class Ip {

    /**
     * Constructor
     *
     * @access public
     * @param string $config Configuration filename minus the file extension
     */
    public function __construct($config = 'rest')
    {
        parent::__construct();
    }

    public function is_whitelisted() 
    {

    }

    public function is_blacklisted() 
    {

    }

}