<?php
/*
 * This file is part of the Snapshot plugin.
 *
 * (c) Alejandro Gama Castro <alex@copperforest.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace copperforest\snapshot\utils;

/**
 * @author Alejandro Gama Castro <alex@copperforest.org>
 */
class Utils
{
    


    //Ondra Zizka at www.php.net/is_subclass_of
    static function is_subclass( $sClass, $sExpectedParentClass )
    {
        do
            if ( $sExpectedParentClass === $sClass ) return true;
        while ( false != ( $sClass = get_parent_class( $sClass ) ) );
        return false;
    }

    static function stripslashes_deep( $value )
    {
        $value = is_array( $value ) ?
                array_map( 'stripslashes_deep', $value ) :
                stripslashes( $value );

        return $value;
    }

    static function pathSegments( $base, $relativeRef ) //concateno
    {
        if( isset( $relativeRef[ 0 ] ) ){
            if( $relativeRef[ 0 ] == '/'){
                $path = $relativeRef;
            }
            else{
                $path = $base . '/' . $relativeRef;
            }
        }
        else{
            $path = $base;
        }


        $parts = array();// Array to build a new path from the good parts
        $path = str_replace('\\', '/', $path);// Replace backslashes with forwardslashes
        $path = preg_replace('/\/+/', '/', $path);// Combine multiple slashes into a single slash
        $path = trim($path, '/');
        $segments = explode('/', $path);// Collect path segments

        foreach( $segments as $segment ){

            if( $segment != '.' ){

                if($segment == '..'){
                    $count = count( $parts );

                    if(  $count > 0 ){
                        unset( $parts[ $count - 1 ] );
                    }
                    else{
                        $parts[] = $segment;
                    }
                }
                else{
                    $parts[] = $segment;
                }
            }
        }

        return $parts;
    }

    //http://www.php-security.org/2010/05/09/mops-submission-04-generating-unpredictable-session-ids-and-hashes/
    static function generateUniqueId( $maxLength = null )
    {
        $entropy = '';

        // try ssl first
        if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
            $entropy = openssl_random_pseudo_bytes( 64, $strong );
            // skip ssl since it wasn't using the strong algo
            if ( $strong !== true ) {
                $entropy = '';
            }
        }

        // add some basic mt_rand/uniqid combo
        $entropy .= uniqid( mt_rand(), true );

        // try to read from the windows RNG
        /* if (class_exists('\COM')) {
          try {
          $com = new \COM('CAPICOM.Utilities.1');
          $entropy .= base64_decode($com->GetRandom(64, 0));
          } catch (Exception $ex) {
          }
          } */

        // try to read from the unix RNG
        if ( is_readable( '/dev/urandom' ) ) {
            $h = fopen( '/dev/urandom', 'rb' );
            $entropy .= fread( $h, 64 );
            fclose( $h );
        }

        $hash = hash( 'whirlpool', $entropy );
        if ( $maxLength ) {
            return substr( $hash, 0, $maxLength );
        }
        return $hash;
    }

    static function c_uniqid( $prefix = '', $more_entropy = false )
    {
        return uniqid( $prefix . php_uname( 'n' ), $more_entropy );
    }

    static function makedir( $dest, $mode = 0777 ){
        //echo $dest."\n";

        $pieces = explode( DIRECTORY_SEPARATOR, $dest );

        if( PHP_OS_FAMILY != 'Windows' ){
            $path = DIRECTORY_SEPARATOR;
        }
        else{
            $path = '';
        }

        foreach( $pieces as $p ){
            if(!empty( $p ) ){

                $path .= $p . DIRECTORY_SEPARATOR;
                //echo "path: ".$path."\n";
                if( !file_exists( $path ) ){
                    mkdir( $path, $mode );
                    chmod( $path, $mode );
                }
                else if( !is_dir( $path ) ){
                    return false;
                }

            }
        }

        return true;
    }
    
    static function copyKeepConstants( $file, $newUbication ){
        $data = file_get_contents( $file );

        $data = preg_replace('/([\\s\\(,.;:=])__FILE__([\\s\\),.;:=])/', '${1}\'' . $file. '\'${2}', $data );
        $data = preg_replace('/([\\s\\(,.;:=])__DIR__([\\s\\),.;:=])/', '${1}\'' . dirname( $file ). '\'${2}'  , $data );

        self::makedir( dirname( $newUbication ) );

        file_put_contents( $newUbication, $data );
    }

    static function rcopy( $source, $dest, $keepConstans  = false ){   

        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS ),
                \RecursiveIteratorIterator::SELF_FIRST )
            as $item
        ) {
            if (!$item->isDir()) {

                if( $keepConstans && substr( $item, -4 ) == '.php' ){
                    self::copyKeepConstants( $item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName() );
                }
                else{
                    self::makedir( dirname( $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName() ) );
                    copy( $item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName() );
                }
            }
        }
    }
    
        //nbari at dalmp dot com
    static function rmtree( $dir )
    {
        $files = array_diff( scandir( $dir ), array( '.', '..' ) );

        foreach ( $files as $file )
                (is_dir( $dir . DIRECTORY_SEPARATOR . $file )) ? self::rmtree( $dir . DIRECTORY_SEPARATOR . $file ) : unlink( $dir . DIRECTORY_SEPARATOR . $file );

        return rmdir( $dir );
    }
}