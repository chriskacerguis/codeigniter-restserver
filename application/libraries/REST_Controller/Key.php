<?php
namespace Restserver\Libraries;
use Exception;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Key class
 * For API Key checks
 *
 * @author    Chris Kacerguis
 */
class Key {

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

    public function is_valid() 
    {

    }

}