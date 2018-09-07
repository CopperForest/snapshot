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
 * All the SessionHandler must implements this interface in order to manage de snapshot
 * 
 * @author Alejandro Gama Castro <alex@copperforest.org>
 */
interface SessionHandlerInterface{
    
    /**
     * Must return the ID of the current user
     * @return int
     */
    public function getUserId();
    
    /**
     * Must store the parameter $id in session.
     * For example:
     * <code>
     * $_SESSION[ 'PreviousUserId ' ] = $id;
     * </code>
     * 
     * @param int $id
     */
    public function setPreviousUserId( $id );
    
    /**
     * Return the value stored in the previous method
     * For example:
     * <code>
     * return $_SESSION[ 'PreviousUserId ' ];
     * </code>
     * 
     * @return int|null
     */
    public function getPreviousUserId();
    
    
    /**
     * Must store the parameter $snapshot in session
     * For example:
     * <code>
     * $_SESSION[ 'PreviousSnapshot ' ] = $snapshot;
     * </code>
     * 
     * @param int $snapshot
     */
    public function setPreviousSnapshot( $snapshot );
    
    /**
     * Return the value stored in the previous method
     * For example:
     * <code>
     * return $_SESSION[ 'PreviousSnapshot ' ];
     * </code>
     * 
     * @return int|null
     */
    public function getPreviousSanpshot();
   
}