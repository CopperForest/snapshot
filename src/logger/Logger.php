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
class Logger extends AbstractLogger
{

    static protected $loggers = array( );

    private $fileName = null;
    private $fp = null;


    function __destruct()
    {
        if( $this->fp != null ) {
            fclose( $this->fp );
        }
        parent::__destruct();
    }

    protected function send()
    {
        
        $errorLevel = E_ERROR;
        //aqui todabia non tengo el userID
        if( is_array( $_SERVER[ 'SANPSHOT_JSON' ] ) ){
            
            if( isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'log'][ 'level' ] ) && isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'log' ][ 'level' ][ $this->userId ] ) ) {

                $errorLevel = $_SERVER[ 'SANPSHOT_JSON' ][ 'log' ][ 'level' ][ $this->userId ];

            }
            else if( isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'log' ]['level' ][ 'default' ] ) ) {

                $errorLevel = $_SERVER[ 'SANPSHOT_JSON' ][ 'log' ]['level' ][ 'default' ];

            }
        }

        if ( $this->options & $errorLevel ) {


            $names = array(
                CF_LOG_ERROR => 'error', CF_LOG_WARNING => 'warning', CF_LOG_INFO => 'info',
                CF_LOG_DEBUG => 'debug', CF_LOG_TRACE => 'trace'
                );

            //Bueno, de ppo no vale 0?
            if( !is_numeric( $_SERVER[ 'CF_SNAPSHOT' ] ) ){
                $path = CF_SNAPSHOTS_PATH . '0';
            }
            else{
                $path = CF_SNAPSHOTS_PATH . $_SERVER[ 'CF_SNAPSHOT' ];
            }

            $path .= DIRECTORY_SEPARATOR . 'log';

            /*if( !file_exists( $path ) ){ //En el build deberían estar creados todos los directorios de log.
                mkdir( $path );
                chmod( $path, 0777 );
            }*/

            if( is_array( $_SERVER[ 'SANPSHOT_JSON' ] ) && isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'log' ][ 'file_per_user' ] ) && $_SERVER[ 'SANPSHOT_JSON' ][ 'log' ][ 'file_per_user' ] ) {

                $path .= DIRECTORY_SEPARATOR . $this->userId;

                if( !file_exists( $path ) ){
                    mkdir( $path );
                    chmod( $path, 0777 );
                }
            }

            $fileName =  $path . DIRECTORY_SEPARATOR  . $names[ $this->options ] . '.log';

            if( $fileName != $this->fileName ) {

                $this->fileName = $fileName;

                if( $this->fp != null ) {
                    fclose( $this->fp );
                }

                $this->fp = fopen( $this->fileName, 'a+' );
            }


            $text = '';

            $text .= $this->script."\n";
            
            foreach( $this->data as $line ){
                $text .=  $line[ 'user' ]. '@'.gmdate( 'Y-m-d H:i:s', $line[ 'time' ] ) . ': '. $line[ 'data' ] ."\n";
            }

            fwrite( $this->fp, $text );
        }
    }
    
    

    //Tiene sentido escribir lo mismo en distintos loggers?
    static function getLogger( $level )
    {

        if ( !in_array( $level, array( CF_LOG_ERROR, CF_LOG_WARNING, CF_LOG_INFO, CF_LOG_DEBUG, CF_LOG_TRACE ) ) ) {
                throw new \Exception( 'Unsupported parameter' );
        }

        if ( !isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'log' ][ 'level' ][ 'default' ] ) ) {
            $_SERVER[ 'SANPSHOT_JSON' ][ 'log' ][ 'level' ][ 'default' ] = CF_LOG_ERROR | CF_LOG_WARNING | CF_LOG_INFO | CF_LOG_DEBUG | CF_LOG_TRACE;
        }

        if ( !isset( self::$loggers[ $level ] ) ) {
            self::$loggers[ $level ] = new Logger( $level );
        }

        return self::$loggers[ $level ];
    }

}