<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace copperforest\snapshot\authentication;


interface SessionHandlerInterface{
    
    /**
     * Must return the ID of the current user
     * @return int
     */
    public function getUserId();
    
    /**
     * Must store the parameter $id in session
     * @param int $id
     */
    public function setPreviousUserId( $id );
    
    /**
     * Return the value stored in the previous method
     * @return int|null
     */
    public function getPreviousUserId();
    
    
    /**
     * Must store the parameter $snapshot in session
     * @param int $snapshot
     */
    public function setPreviousSnapshot( $snapshot );
    
    /**
     * Return the value stored in the previous method
     * @return int|null
     */
    public function getPreviousSanpshot();
   
}