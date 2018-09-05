<?php
namespace copperforest\snapshot\logger;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
       if( is_string($data ) ){
            $text = $data;
       }
       else if( is_object( $data ) && method_exists( $data, '__toString' ) ){
            $text = $data->__toString();
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
       
        $this->data[] = array( 'time' => time(), 'user' => $this->userId, 'data' => $text );
               
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