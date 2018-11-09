<?php
namespace Restserver\Libraries;
use Exception;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ExternalAuth class
 * For External Auth (i.e. Auth outside of API keys)
 *
 * @author    Chris Kacerguis
 */
class ExternalAuth {

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

    public function digest() 
    {

    }

    public function ldap() 
    {

    }

}