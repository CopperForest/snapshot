<?php
/*
 * This file is part of the Snapshot plugin.
 *
 * (c) Alejandro Gama Castro <alex@copperforest.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace copperforest\snapshot\authentication;

/**
 * This class defines an interface to be implemented to perform actions for
 * session events.
 * @author Alejandro Gama Castro <alex@copperforest.org>
 */
interface SessionEventHandler{
    
    /**
     * Actions to perform when a new session is created
     */
    function onCreate();
    
    /**
     * Actions to perform when a session is resumed
     */
    function onResume();
    
    /**
     * Actions to perform when a user is authenticated in the session
     */
    function onAuthenticate();
}
