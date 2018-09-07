<?php
/*
 * This file is part of the Snapshot plugin.
 *
 * (c) Alejandro Gama Castro <alex@copperforest.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * Carga el fichero de configuracion
 * Establece los niveles de logueo y redirige el reporte de errores al logger
 */

if ( empty( $_SERVER[ 'REQUEST_TIME' ] ) ){
    $_SERVER[ 'REQUEST_TIME' ] = time();
}

$thisDirectory = dirname( __FILE__ ). DIRECTORY_SEPARATOR; //snapshots directory

define(
    'CF_HTTP_MODE',
    isset( $_SERVER[ 'REQUEST_METHOD' ] ) &&
    in_array(
        strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ),
        array( 'GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'PATCH' )
    ) 
);
define( 
    'CF_BASE_PATH',
    realpath( $thisDirectory . '..' ) . DIRECTORY_SEPARATOR
);
define( 'CF_SNAPSHOTS_PATH', realpath( $thisDirectory ) . DIRECTORY_SEPARATOR );
define( 'CF_CACHE_PATH', CF_SNAPSHOTS_PATH . 'cache' . DIRECTORY_SEPARATOR );


$tmp = sys_get_temp_dir();
if( empty( $tmp ) ){
    define( 'CF_TEMP_PATH', CF_SNAPSHOTS_PATH . 'temp' . DIRECTORY_SEPARATOR );
}
else{
    define( 'CF_TEMP_PATH', $tmp . DIRECTORY_SEPARATOR );
}

//CUANDO ESTOY EJECUTANDO EL PLUGIN. NO TENGO HABILITADO TODO EL AUTOLOAD DEL composer???

//OJO, AHORA EN EL SRC NO TENEMOS TODO EL VENDOR
$_SERVER[ 'CF_SNAPSHOT' ] = 0; //Este valor quizá debería estar en una cosntante.

//CARGAMOS EL FICHERO DE CONFIGURACION
$_SERVER[ 'SANPSHOT_JSON' ] = array();

//El ficheoro .json lleva toda la información que necesitaremos
$result = false;

if( file_exists( $thisDirectory . 'snapshot.json' ) ){

    $result = json_decode( $thisDirectory . 'snapshot.json' );
   
    if( $result === false ){
        $error = json_last_error();
        throw new \Exception( 'Syntax error in snapshot.json: ' . "\n" . $error );
    }
    else{
        $_SERVER[ 'SANPSHOT_JSON' ] =  $result;
    }
    
}
else{
    //debería crear el archivo en blanco o con una configuracion mínima
}
unset( $result, $thisDirectory );


define( 'CF_LOG_ERROR', 1 );
define( 'CF_LOG_WARNING', 2 );
define( 'CF_LOG_INFO', 4 );
define( 'CF_LOG_DEBUG', 8 );
define( 'CF_LOG_TRACE', 16 );
define( 'CF_LOG_ALL', 1 | 2 | 4 | 8 | 16 );


function cfEvalueCostants( $string ){
    $constants = explode( "|", strtr( $string, " ", "" ) );
    $value = 0;
    
    foreach( $constants as $c ){
       $value = $value | constant( $c );
    }
}

if( isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'exception_level' ] ) ){
    $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'exception_level' ] = cfEvalueCostants( $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'exception_level' ] );
}
else{
    $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'exception_level' ]  = E_ERROR;
}
if( isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'log_level' ] ) ){
    $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'log_level' ] =  cfEvalueCostants( $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'log_level' ] );
}
else{
    $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'log_level' ]  = E_ERROR;
}
if( isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'mail_level' ] ) ){
    $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'mail_level' ] =  cfEvalueCostants( $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'mail_level' ] );
}
else{
    $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'mail_level' ]  = E_ERROR;
}

//ESTA funcion solo permite ORs en la definición de los niveles
foreach( $_SERVER[ 'SANPSHOT_JSON' ][ 'log' ][ 'level' ] as $logLevel => $users  ){ 
    
    $logLevel = cfEvalueCostants( $logLevel );
    
    foreach($users as $user ){
        $user = ( $user == "default" || $user == 'cli' ) ? $user : (int) $user ;

        $_SERVER[ 'SANPSHOT_JSON' ][ 'log' ][ 'level' ][ $user ] = $logLevel;
    }
}

unset( $logLevel , $users );
//CAMBIAMOS REPORTE DE ERRORES POR LOGUEO
error_reporting( 0 );


//TO LOG FALTAL ERRRORS
function cfFatalErrorShutdown()
{
    $error = error_get_last();
    
    if ( $error ) {

        $e = new \ErrorException( $error[ 'message' ], 0, $error[ 'type' ],
                $error[ 'file' ], $error[ 'line' ] );

        \copperforest\sanpshot\logger\Logger::getLogger( CF_LOG_ERROR )->log( $e );
    }
}


register_shutdown_function( 'cfFatalErrorShutdown' );


//Si no se emite excepcion que 
function cfErrorHandlingFunction( $errno, $errstr, $errfile, $errline )
{

    $e = new \ErrorException( $errstr, 0, $errno, $errfile, $errline );

    if( isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'log_level' ] ) ){

        if ( $errno & ( (int) $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'log_level' ] ) ){
            \copperforest\sanpshot\logger\Logger::getLogger( CF_LOG_ERROR )->log( $e );
        }
    }
    else{
        \copperforest\sanpshot\logger\Logger::getLogger( CF_LOG_ERROR )->log( $e );
    }

    if( isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'exception_level' ] ) ){
        if ( $errno & ( (int) $_SERVER[ 'SANPSHOT_JSON' ][ 'error' ][ 'exception_level' ] ) ){
            throw $e;
        }
    }
    else if ( $errno & E_ERROR ){
        throw $e;
    }
}

set_error_handler( 'cfErrorHandlingFunction' );


if ( isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'date.timezone' ] ) ) {
    date_default_timezone_set( $_SERVER[ 'SANPSHOT_JSON' ][ 'date.timezone' ] );
}

if ( isset( $_SERVER[ 'SANPSHOT_JSON' ][ 'intl.default_locale' ] ) ) {
    setlocale( LC_ALL, $_SERVER[ 'SANPSHOT_JSON' ][ 'intl.default_locale' ] );
}

//The anti injection function
//anti_injection( $string, $type )
//JS   : var texto='< ? = string2hex( $string, "\\u", '', 4, 'UCS-2' )? >';
//MySQL: blog=' . string2hex( $string, "0x" ). ' WHERE ..
//PgSQL: blog=E\'' . string2hex( $string, "\\x", '', 2 ) , '\' WHERE ...
//HTML : value="< ? = string2hex( $string, "&#x", ";", 2 ) ? >"

function string2hex( $string, $prefix='', $subfix='', $intercalate = 0, $hex_encoding = null, $from_encoding = null ){
    if( $hex_encoding !== null ){
        $string = mb_convert_encoding( $string , $hex_encoding, $from_encoding );
    }

    $hex = bin2hex( $string );

    if( !empty( $intercalate ) && ( !empty( $prefix ) ||  !empty( $subfix ) ) ){
         $hex = implode( $subfix . $prefix, str_split( bin2hex( $string ), $intercalate ) ) ;
    }
    
    return ( empty( $prefix ) ? '' : $prefix ) . $hex . ( empty( $subfix ) ? '' : $subfix ) ;
}


function js_encode( $string, $charset = 'UTF-8' )
{
    $string = mb_convert_encoding($string, 'UCS-2', $charset );
    if (empty($string))
        return '';

    return "\\u" . implode("\\u", str_split(bin2hex($string), 4));
}
