<?php

//Aqui tengo que poner una carga de clases básica para que se pueda ejecturar el commons?
require_once 'snapshot.base.php';

 
$_SERVER[ 'CF_SNAPSHOT' ] =  0;
$autoloadComposer = false;
$autoloadDefault = false;
$thereAreAlternatives = isset( $_SERVER[ 'SNAPSHOT_JSON' ][ 'snapshot' ] ) && is_array( $_SERVER[ 'SNAPSHOT_JSON' ][ 'snapshot' ] );


if( $thereAreAlternatives ){
    
    krsort( $_SERVER[ 'SNAPSHOT_JSON' ][ 'snapshot' ] );
    
    if( !isset( $_SERVER[ 'SNAPSHOT_JSON' ][ 'spanshot' ][ 'default' ] ) ){
        
        foreach ( $_SERVER[ 'SNAPSHOT_JSON' ][ 'spanshot' ] as $snapshot => $users ){
            
            if( in_array( 'default', $users ) ){
                $_SERVER[ 'SNAPSHOT_JSON' ][ 'spanshot' ][ 'default' ] = (int) $snapshot;
                break;
            }
        }
    }
}

if( 
    !empty( $_SERVER[ 'SNAPSHOT_JSON' ][ 'spanshot' ][ 'default' ] )  &&
    file_exists( CF_SNAPSHOTS_PATH . $_SERVER[ 'SNAPSHOT_JSON' ][ 'spanshot' ][ 'default' ] . DIRECTORY_SEPARATOR . 'snapshot.time' ) 
){ //Si existe build defautl y es distinto de 0

    $autoloadDefault = true;
    $_SERVER[ 'CF_SNAPSHOT' ] =  $_SERVER[ 'SNAPSHOT_JSON' ][ 'spanshot' ][ 'default' ];    
    
    function cfFrameworkDefaultClassPath( $className )
    {

        require_once( CF_SNAPSHOTS_PATH . $_SERVER[ 'CF_SNAPSHOT' ] . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace( "\\", DIRECTORY_SEPARATOR,
                        $className ) . '.php');
    }
    
    spl_autoload_register( 'cfFrameworkDefaultClassPath' );
}
else{
    $autoloadComposer = true;
    include CF_BASE_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}


if ( CF_HTTP_MODE ) {//Estamos via protocolo http (no consola), buscamos usuario
    
    if ( !function_exists( 'apache_request_headers' ) ) {

        $headerArray = array( );
        foreach ( $_SERVER as $key => $value )
                if ( substr( $key, 0, 5 ) == 'HTTP_' )
                    $headerArray[ str_replace( ' ', '-',
                                ucwords( str_replace( '_', ' ',
                                                strtolower( substr( $key, 5 ) ) ) ) ) ] = $value;

        $_SERVER[ 'HTTP_HEADERS' ] = $headerArray;

        unset( $headerArray );
        
    }
    else $_SERVER[ 'HTTP_HEADERS' ] = apache_request_headers();

    if ( in_array( strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ),
                    array( 'DELETE', 'PATCH', 'PUT' ) ) && substr( $_SERVER[ 'HTTP_HEADERS' ][ 'Content-Type' ],
                    0, 33 ) == 'application/x-www-form-urlencoded' )
            parse_str( file_get_contents( 'php://input' ), $_POST ); //SI NO HAY METODO POST EXISTE VARIABLE $_POST???

    
    if ( get_magic_quotes_gpc() ) {

        $_POST = array_map( 'stripslashes_deep', $_POST );
        $_GET = array_map( 'stripslashes_deep', $_GET );
        $_COOKIE = array_map( 'stripslashes_deep', $_COOKIE );
        $_REQUEST = array_map( 'stripslashes_deep', $_REQUEST );
    }

    
    //$_SERVER[ 'SCRIPT_NAME '] = rtrim( $_SERVER['SCRIPT_NAME'], '/' );
    
    define( 'CF_HTTP_PATH',
            ( dirname( $_SERVER[ 'SCRIPT_NAME'] ) == '/' ) ? '' : dirname( $_SERVER[ 'SCRIPT_NAME'] )   ); //dirname?
    
    $host = '';
    $port = 80;
    
    if( empty( $_SERVER[ 'HTTP_HOST' ] ) ){
        $host = $_SERVER[ 'SERVER_ADDR' ];
    }
    else{
        $posPort = strpos( $_SERVER[ 'HTTP_HOST' ], ':' );
        if( $posPort!== false ){
            $host = substr( $_SERVER[ 'HTTP_HOST' ], 0, $posPort );
        }
        else{
            $host = $_SERVER[ 'HTTP_HOST' ];
        }
    }
    
    define( 'CF_HTTP_BASE',
            'http'. ( empty( $_SERVER[ 'HTTPS' ] ) ? '' : 's' ) . '://' . $host . ':' . $_SERVER[ 'SERVER_PORT' ]  );

    // CF_HTTP_BASE . CF_HTTP_PATH is the entery URL
    
    //I don't know why but when the htaccess  is in the document_root directory the apache doesn't define PATH_INFO
    /* REQUEST_URI
    $positionQuestionMark = strpos( $_SERVER[ 'REQUEST_URI' ], '?' );
    if( $positionQuestionMark === false) {
        $positionQuestionMark = strlen( $_SERVER[ 'REQUEST_URI' ] );
    }
    
    $pathInfo = substr( $_SERVER[ 'REQUEST_URI' ], 0, $positionQuestionMark );
    $_SERVER[ 'PATH_INFO' ] = '/' . substr( $pathInfo, strlen( CF_HTTP_PATH ) + 1 );
    
    
    unset( $pathInfo, $positionQuestionMark );
    */
    
   
    if( 
        isset( $_SERVER[ 'SNAPSHOT_JSON' ][ 'session' ][ 'handlers' ] ) &&
        is_array( $_SERVER[ 'SNAPSHOT_JSON' ][ 'session' ][ 'handlers' ] ) &&
        !empty( $_SERVER[ 'SNAPSHOT_JSON' ][ 'session' ][ 'handlers' ] )
    ){
        
        $numHandlers = count( $_SERVER[ 'SNAPSHOT_JSON' ][ 'session' ][ 'handlers' ] );
        
         if( $numHandlers == 0 ){
            session_start ( );
         }
         else{
             
            $handlerNumber = null;
            
            for( $i = 0 ; $i < $numHandlers ; $i++ ){
                $handler = $_SERVER[ 'SNAPSHOT_JSON' ][ 'session' ][ 'handlers' ][ $i ];

                if( !isset( $handler[ 'port' ] )  || $handler[ 'port' ] == $_SERVER[ 'SERVER_PORT' ]  ){

                    if( 
                        !isset( $handler[ 'domain' ] )  ||
                        $handler[ 'domain' ] == $host   ||
                        substr( $host, -1 * strlen( $handler[ 'domain' ] ) - 1 ) == '.' . $handler[ 'domain' ]
                    ){
                        if(
                            !isset( $handler[ 'path' ] )  ||
                            $handler[ 'path' ] == ''      ||
                            $handler[ 'path' ] == '/'     ||
                            strpos( CF_HTTP_PATH . '/' , $handler[ 'path' ] . '/' ) == 0
                        ){
                            $handlerNumber = $i;
                            break;
                        }
                    }
                }
            }

            if ( $handlerNumber !== null ) {

                if( !empty( $_SERVER[ 'SNAPSHOT_JSON' ][ 'session' ][ 'handlers' ][ $handlerNumber ][ 'save_handler' ] ) ){

                    $_SESSION = new  $_SERVER[ 'SNAPSHOT_JSON' ][ 'session' ][ 'handlers' ][ $handlerNumber ][ 'save_handler' ]( $_SERVER[ 'SNAPSHOT_JSON' ][ 'session' ][ 'handlers' ][ $handlerNumber ] );

                    register_shutdown_function( array( $_SESSION, 'writeClose' ) ); //por si acaso la otra no llega a ejecutarse
                }
                else{
                    session_start ( );
                }
            }
            else{
                throw new \Exception( CF_HTTP_BASE . CF_HTTP_PATH . ' doesn\'t match any session handler' );
            }
        }
    }
    else{
        session_start ( );
    }
    
    $sessionInterface = ( $_SESSION instanceof \copperforest\snapshot\authentication\SessionHandlerInterface );
    $sessionObject = ( $_SESSION instanceof \copperforest\snapshot\authentication\SessionHandler );
             
    if( ( $sessionInterface || $sessionObject ) && $thereAreAlternatives  ){
        
        $userId = $_SESSION->getUserId();
        $storedUserId = $_SESSION->getPreviousUserId();
        $snapshot = $_SESSION->getPreviousSanpshot();

        if( $snapshot == null || $userId != $storedUserId ){
            //si no cambio el usuario y hay establecido un build en session seguimos utilizandolos

            foreach( $_SERVER[ 'SNAPSHOT_JSON' ][ 'snapshot' ] as $s => $users ){

                if( in_array( $userId, $users ) ){
                    $_SESSION->setPreviousSnapshot( $s );

                    if( $sessionObject ){
                        $_SESSION->commit();
                    }

                    break;
                }
            }            

            if( $storedUserId != $userId ){  //EN QUE cIRCUNSTANCIAS PASA ESTO? CUANDO storedUserId esta a vacio y UserId no? Y en el constructor de la session pueden pasar muchas cosas
                $_SESSION->setPreviousUserId( $userId );
                
                if( $sessionObject ){
                    $_SESSION->commit();
                }
            }
        }

        $_SERVER[ 'CF_SNAPSHOT' ] = $_SESSION->getPreviousSanpshot();

        unset( $userId );

        if( $sessionObject ){
            $_SESSION->fireInitialEvent();
        }
    }
}
else if ( $thereAreAlternatives  ){

    foreach( $_SERVER[ 'SNAPSHOT_JSON' ][ 'snapshot' ] as $s => $users ){

        if( in_array( 'cli', $users ) ){
            $_SERVER[ 'CF_SNAPSHOT' ] = $s;
            break;
        }
    }
}
//Si aqui hay un error que pasa? De hecho tiene que haber un error al crear el objeto SESSION!!!

function cfFrameworkClassPath( $className )
{
   require_once( CF_CLASS_PATH . str_replace( "\\", DIRECTORY_SEPARATOR,
                   $className ) . '.php');
}

if( $_SERVER[ 'CF_SNAPSHOT' ] == 0 ) { //el autoload del composer
    
    define( 'CF_CLASS_PATH', CF_BASE_PATH . 'src'  . DIRECTORY_SEPARATOR );
    define( 'CF_INCLUDE_PATH', CF_BASE_PATH . 'include'  . DIRECTORY_SEPARATOR );
    define( 'CF_CONFIG_PATH', CF_BASE_PATH .  'config'  . DIRECTORY_SEPARATOR );
    set_include_path( get_include_path() . PATH_SEPARATOR . CF_INCLUDE_PATH );
    define( 'CF_SNAPSHOT_TIME', time() );
}
else {
    
    define( 'CF_SNAPSHOT_TIME', file_get_contents(  CF_SNAPSHOTS_PATH . $_SERVER[ 'CF_SNAPSHOT' ] . DIRECTORY_SEPARATOR . 'sanpshot.time' ) . ( isset( $_SERVER[ 'SNAPSHOT_JSON' ][ 'subversion' ] )?( '.' . $_SERVER[ 'SNAPSHOT_JSON' ][ 'subversion' ] ) : '' ) );
    //AQUI tienen que estar creada la estructura de directorios, log, temp, config, include, además del src
    define( 'CF_CLASS_PATH', CF_SNAPSHOTS_PATH . $_SERVER[ 'CF_SNAPSHOT' ] . DIRECTORY_SEPARATOR . 'src'  . DIRECTORY_SEPARATOR );
    define( 'CF_INCLUDE_PATH', CF_SNAPSHOTS_PATH . $_SERVER[ 'CF_SNAPSHOT' ] . DIRECTORY_SEPARATOR . 'include'  . DIRECTORY_SEPARATOR );
    define( 'CF_CONFIG_PATH', CF_SNAPSHOTS_PATH . $_SERVER[ 'CF_SNAPSHOT' ] . DIRECTORY_SEPARATOR . 'config'  . DIRECTORY_SEPARATOR );
    
    set_include_path( get_include_path() . PATH_SEPARATOR . CF_INCLUDE_PATH . PATH_SEPARATOR . CF_SNAPSHOTS_PATH . $_SERVER[ 'CF_SNAPSHOT' ] . DIRECTORY_SEPARATOR . 'vendor'   );
        
    if( $autoloadComposer ){        //TEngo que quitar el autoload del composer

        $autoloadFunctions = spl_autoload_functions();
        
        foreach( $autoloadFunctions as $f ){
            spl_autoload_unregister( $f );
        }
        unset( $autoloadFunctions );
    }
    else if ( $autoloadDefault ){
        spl_autoload_unregister( 'cfFrameworkDefaultClassPath' );
    }

    spl_autoload_register( 'cfFrameworkClassPath' );

    include CF_INCLUDE_PATH . '..' . DIRECTORY_SEPARATOR . 'autoload_files.php';
}

unset( $previous, $autoloadComposer, $autoloadDefault, $thereAreAlternatives );