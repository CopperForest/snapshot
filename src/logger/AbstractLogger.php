<?php
/*
 * This file is part of the Snapshot plugin.
 *
 * (c) Alejandro Gama Castro <alex@copperforest.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace copperforest\snapshot\logger;

/**
 * @author Alejandro Gama Castro <alex@copperforest.org>
 */
abstract class AbstractLogger {
    protected $options;
    protected $data = array();
    protected $success = false;
    protected $userId = 0;
    protected $script;
            
    
    function __construct( $options )
    {
        $this->options = $options;
        $this->script = ( isset( $_SERVER[ 'REQUEST_URI' ] ) ? $_SERVER[ 'REQUEST_URI' ]  : $_SERVER[ 'SCRIPT_NAME' ] );
    }
    
    function __destruct()
    {
        if( !empty( $this->data ) ){
            $this->flush();
        }
    }
    
    function setSuccess( $success )
    {
        $this->success = $success;
    }
    
    function log( $data, $append = false )
    {
       if( is_string( $data ) ){
            $text = $data;
       }
       else if( is_object( $data ) && method_exists( $data, '__toString' ) ){
            $text = $data->__toString();
            $text .= "\n" . var_export( $data, true );
        }
        else{
            $text = var_export( $data, true );
        }
     
        if( !CF_HTTP_MODE ){
            $this->userId = 'cli';
        }
        else if ( isset( $_SESSION ) && is_object( $_SESSION )  ) {
            $this->userId = $_SESSION->getUserId();
        }
       
        $this->data[] = new LogLine( $this->userId, $text );
               
        if( !$append ){
            $this->success = true;
            $this->flush();
        }
    }

    public function flush()
    {
        $this->send();
            
        $this->data = array();
        $this->success = false;
    }
    
    abstract protected function send();
}