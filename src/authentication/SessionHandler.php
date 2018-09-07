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
 * @author Alejandro Gama Castro <alex@copperforest.org>
 */
abstract class SessionHandler implements SessionHandlerInterface
{
    protected $id;
    protected $userId = 0;
    protected $defaultRelativeReference;
    
    protected $eventHandlers;
    protected $resumed = false;
    protected $commited = false;
    
    private $valid = false;
    protected $userData;

    public function getPreviousUserId();
    public function setPreviousUserId( $id );
    public function getSnapshot();
    public function setSnapshot( $snapshot );
    
    abstract public function sendAuthenticationRequest();
    
    abstract protected function evaluateAuthenticationReply();
    
    abstract protected function regenerateID();
    
    public function evalAuthReply(){
        $id = $this->userId;
        
        list( $this->userId, $this->defaultRelativeReference ) = $this->evaluateAuthenticationReply();
    
        if( !empty( $this->userId ) && $id != $this->userId ){
            
            $this->regenerateID();
            
            if( !empty( $this->event_handlers ) ){
                if( is_string( $this->event_handlers )){
                    $handleObject = new $this->event_handlers();
                    $handleObject->onAuthenticate();
                }
                else if( is_array( $this->event_handlers ) ){
                    foreach( $this->event_handlers as $h ){
                        $handleObject = new $h();
                        $handleObject->onAuthenticate();
                    }
                }
            }
        }
        
        return array( $this->userId, $this->defaultRelativeReference );
    }
    
    public function getUserId(){
        return $this->userId;
    }
    
    public function isAuthenticated(){
        return $this->userId <> 0;
    }
 
 
     public function fireInitialEvent(){
        if( !empty( $this->event_handlers ) ){
            
            if( is_string( $this->event_handlers ) ){
                
                $handleObject = new $this->event_handlers();
                
                if( $this->resumed ){
                    $handleObject->onResume();
                }
                else{
                    $handleObject->onCreate();
                }
                
            }
            else if( is_array( $this->event_handlers ) ){
                if( $this->resumed ){
                    foreach( $this->event_handlers as $h ){
                        $handleObject = new $h();
                        $handleObject->onResume();
                    }
                }
                else{
                    foreach( $this->event_handlers as $h ){
                        $handleObject = new $h();
                        $handleObject->onCreate();
                    }
                }
            }
        }
    }
    
    function commit(){
        $this->commited = true;
    }
    
    
      //coutable Method
    public function count()
    {
        return count( $this->userData );
    }

    //Serializable Methods
    public function rewind()
    {
        $this->valid = ( count( $this->userData ) > 0 );
        rewind( $this->userData );
    }

    public function key()
    {
        return key( $this->userData );
    }

    public function current()
    {
        return current( $this->userData );
    }

    public function next()
    {
        $this->valid = ( next( $this->userData ) !== false );
    }

    public function valid()
    {
        return $this->valid;
    }

    //ArrayAccess
    public function offsetSet( $k, $v )
    {
        $this->userData[ $k ] = $v;
    }

    public function offsetGet( $k )
    {
        return $this->userData[ $k ];
    }

    public function offsetUnset( $k )
    {
        unset( $this->userData[ $k ] );
    }

    public function offsetExists( $k )
    {
        return isset( $this->userData[ $k ] );
    }
    
    abstract function writeClose();
}