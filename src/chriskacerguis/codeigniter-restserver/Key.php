<?php

namespace Restserver\Key;

/**
 * This class is for the manipulation of keys, generally speaking it would be used by the 
 * developers own process for managing API keys.  It is not used in the normal request or
 * response.
 *
 * @package         CodeIgniter
 * @license         MIT
 * @version         4.0.0
 */

class Key {

    /**
     * Constructor for the Key class
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
     * Creates a new API key, and returns the string
     *
     * @access public
     * @author Chris Kacerguis
     * @return string
     */
    public function create()
    {

    }

    /**
     * shows all the API keys, or just a single key if a key is provider
     *
     * @access public
     * @author Chris Kacerguis
     * @return array
     */
    public function show($key = false)
    {

    }

    /**
     * totally deletes a key
     *
     * @access public
     * @author Chris Kacerguis
     * @return bool
     */
    public function delete($key)
    {

    }

    /**
     * disables a key
     *
     * @access public
     * @author Chris Kacerguis
     * @return bool
     */
    public function disable($key)
    {

    }

}