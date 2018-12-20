<?php
/*
 * This file is part of the Snapshot plugin.
 *
 * (c) Alejandro Gama Castro <alex@copperforest.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace copperforest\snapshot;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;

/**
 * @author Alejandro Gama Castro <alex@copperforest.org>
 */
class CreateSnapshotCommandProvider implements CommandProviderCapability
{
    public function getCommands()
    {
        return array( new CreateSnapshotCommand(), new DebugSnapShotCommand() );
    }
}
class DebugSnapShotCommand extends BaseCommand
{
    //TEngo que hacer una ejecución infinita, en la que tengo que tengo que comprobar si cambian algunos de los archivos que influyen ene el proyecto
    //por debajo del composer
    
    protected function configure()
    {
        $this
            ->setName('debug-snapshot')
            ->setDescription( 'Update automatically the last snapshot with the changes made in the filesystem. This activate the debug mode too.' )
            ->ignoreValidationErrors();
    }
}

class CreateSnapshotCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('create-snapshot')
            ->setDescription( 'Create a new snapshot of the project' )
            ->setDefinition( array(
                new InputArgument( 'rebuild', InputArgument::OPTIONAL, 'Rebuild last snapshot', 'false' ),
            ))

            ->ignoreValidationErrors();
    }

    
        
        /**
         * "scripts": {
         *   "post-create-snapshot-cmd": "MyVendor\\MyClass::postUpdate"
         * }
         * 
         */

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $config = new Config($this->getComposer());
        $vendorPath = $config->getTargetDirectory();
        
        $output->writeln( 'Creating Sanpshot' );
     
        $appPath = dirname( Factory::getComposerFile() );
        $snapshotsPath = $appPath. DIRECTORY_SEPARATOR . 'snapshots' . DIRECTORY_SEPARATOR ;
        
        if( !file_exists( $snapshotsPath ) ){
            
            if( mkdir( $snapshotsPath, 0777 ) ){
                
                chmod( $snapshotsPath, 0777 );
                $output->writeln( 'Created \'' . $snapshotsPath . '\' directory');
            }
            else{
                $output->writeln( 'Unable to create spanhosts directory in \'' . $snapshotsPath . '\'');
                return false;
            }
        }
        
        if( !file_exists( $snapshotsPath . '0' ) ){
            if( mkdir( $snapshotsPath . '0' ) ){
                chmod( $snapshotsPath . '0' , 0777 );

                mkdir( $snapshotsPath . '0'. DIRECTORY_SEPARATOR . 'log' );
                chmod( $snapshotsPath . '0'. DIRECTORY_SEPARATOR . 'log' , 0777 );

                $output->writeln( 'Created \'' . $snapshotsPath . '0' . DIRECTORY_SEPARATOR . 'log\' directory for logging purpouse');
            }
            else{
                $output->writeln( 'Unable to create directory \'' . $snapshotsPath . '0' . '\'');
                return false;
            }
        }
        
        $dirHandler = opendir( $snapshotsPath );
        $lastSnapshot = 0;

        while ( ( $file = readdir( $dirHandler ) ) !== false ) {

            if ( is_dir( $snapshotsPath . $file ) && preg_match( '/^\d+$/', $file ) ) {

                if( $file !== '0' && !file_exists( $snapshotsPath . $file . DIRECTORY_SEPARATOR . 'spanshot.time' ) )
                        self::rmtree( $snapshotsPath . $file ); //eliminamos ese build

                else if ( $lastSnapshot < ( ( int ) $file ) ) $lastSnapshot = ( int ) $file;
            }
        }

        closedir( $dirHandler );
        
        if( $input->getArgument( 'rebuild' ) === 'true' ){
            $newSnapshot = $lastSnapshot;
            rmtree( $snapshotsPath . $newSnapshot );
        }
        else{
            $newSnapshot = $lastSnapshot + 1;
        }
        
        $_SERVER[ 'CF_SNAPSHOT' ] = $newSnapshot;
        
        $newSpanshotPath = $snapshotsPath . $newSnapshot . DIRECTORY_SEPARATOR;
        
        mkdir( $newSpanshotPath, 0777 );
        chmod( $newSpanshotPath, 0777 );

        mkdir( $newSpanshotPath . 'src',
                0777 );
        chmod( $newSpanshotPath . 'src' , 0777 );

        mkdir( $newSpanshotPath . 'include',
                0777 );
        chmod( $newSpanshotPath . 'include' , 0777 );

        mkdir( $newSpanshotPath . 'vendor',
                0777 );
        chmod( $newSpanshotPath . 'vendor' , 0777 );

        mkdir( $newSpanshotPath . 'log',
                0777 );
        chmod( $newSpanshotPath . 'log' , 0777 );

        mkdir( $newSpanshotPath . 'config',
                0777 );
        chmod( $newSpanshotPath  . 'config' , 0777 );
        
         
        if( !file_exists( $snapshotsPath . DIRECTORY_SEPARATOR. 'snapshot.base.php' ) ){
            
            copy( $vendorPath . 'copperforest' .DIRECTORY_SEPARATOR . 'snapshot' .DIRECTORY_SEPARATOR  . 'src' .DIRECTORY_SEPARATOR .'snapshot.base.php',
                    $snapshotsPath . DIRECTORY_SEPARATOR. 'snapshot.base.php');
        }
        if( !file_exists( $snapshotsPath . DIRECTORY_SEPARATOR. 'snapshot.php' ) ){
            
            copy( $vendorPath . 'copperforest' .DIRECTORY_SEPARATOR . 'snapshot' .DIRECTORY_SEPARATOR  . 'src' .DIRECTORY_SEPARATOR .'snapshot.php',
                    $snapshotsPath . DIRECTORY_SEPARATOR. 'snapshot.php');
            
            $output->writeln( 'Copyed snapshot.php to \'' . $snapshotsPath . '\'. Include this file instead \'vendor/autoload.php\'');
        }
        
        
        if ( file_exists( $appPath . 'lib' ) && is_dir( $appPath . 'lib' ) ) {

            $libs = array_diff( scandir( $appPath . 'lib' ), array( '.', '..' ) );

            foreach ( $libs as $l ) {
                //los archivos pueden ser tar, tar.gz, tgz, bz o bz2 siempre que los modulos de compresion estén disponibles

                if ( 
                    substr( $l, -4 ) == '.tar'  ||
                    substr( $l, -7 ) == '.tar.gz' ||
                    substr( $l, -4 ) == '.tgz' ||
                    substr( $l, -4 ) == '.bz2' ||
                    substr( $l, -3 ) == '.bz' 
                ) { //|| substr( $l, -4 ) == '.zip'


                    $phar = new PharData( $appPath . 'lib' . DIRECTORY_SEPARATOR . $l );

                    if( $phar->extractTo( $newSpanshotPath, null, true ) ){
                        $output->writeln( 'Extracted \'lib' . DIRECTORY_SEPARATOR . $l . '\' to \'' . $newSpanshotPath .'\'' );
                    }
                    else{
                        throw new Exception( 'Unable to extract: ' . DIRECTORY_SEPARATOR . $l);
                    }

                    unset( $phar );
                }
            }
        }
       
        //@TODO check if all namespaces are in the correct location
         
        $composerPath = $vendorPath . 'composer' . DIRECTORY_SEPARATOR;
        $newVendorPath =  $newSpanshotPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
        $newClassPath = $newSpanshotPath  . 'src' . DIRECTORY_SEPARATOR;

        mkdir( $newVendorPath, 0777 );
        chmod( $newVendorPath, 0777 );

        if( file_exists( $vendorPath )  && is_dir( $vendorPath ) ){
            
            Utils::rcopy( $vendorPath,  $newVendorPath );
            $output->writeln( 'Copied  \'' . $vendorPath . '\' to \''.$newVendorPath.'\'' );

            if( file_exists( $composerPath ) ){

                if( file_exists( $composerPath . 'autoload_files.php' ) ){

                    $autoload_file = $newSpanshotPath . 'autoload_files.php';
                    $autoload_files  = ( include $composerPath . 'autoload_files.php' );
                    $vendorPathLength = strlen( $vendorPath );

                    foreach( $autoload_files as $file ){
                        $file = strtr( $file, '/', DIRECTORY_SEPARATOR );
                        file_put_contents( $autoload_file, 'include \'' . $newVendorPath . substr( $file ,$vendorPathLength ) . "';\n", FILE_APPEND );
                    }
                }
                $output->writeln( 'Processed \'' . $composerPath . 'autoload_files.php\'' );
                
                if( file_exists( $composerPath . 'autoload_classmap.php' ) ){


                    $autoload_classmap  = ( include $composerPath . '/autoload_classmap.php' );
                    //Tengo que mover los archivos desde vendor al nuevo vendor


                    //OJO AQUI PUEDE HABER COLISION DE CLASSES; SOLO SI HAY DOS CLASES QUE SE LLAMEN IGUAL Y ENTONCES TAMBIÉN LA HABRIA EN EL COMPOSER
                    foreach( $autoload_classmap as $class => $file ){

                        $file = strtr( $file, '/', DIRECTORY_SEPARATOR );

                        Utils::copyKeepConstants( $file, $newClassPath . $class . '.php' );
                    }
                }
                
                $output->writeln( 'Processed \'' . $composerPath . 'autoload_classmap.php\'' );

                //AQUI PODRÍA COMPROBRA SI EN CADA DIRECTORIO HAY UN BUILD!
                if( file_exists( $composerPath . 'autoload_namespaces.php' ) ){

                    $autoload_namespaces  = ( include $composerPath . '/autoload_namespaces.php' );

                    foreach( $autoload_namespaces as $namespace => $dirs ){

                        if( strpos( $namespace, '_' ) !== false ){ //PEAR-style namespace. All the class goes to src. We must to concat the name of the file recursiverly exploring the directory
                            self::pearStyleToCopperforestStyle( $dirs, $newClassPath );
                        }
                        else{ //PSR-0
                            self::psrStyleToCopperForestStyle( $dirs, $newClassPath );
                        }
                    }
                }
                
                $output->writeln( 'Processed \'' . $composerPath . 'autoload_namespaces.php\'' );

                //AQUI PODRÍA COMPROBRA SI EN CADA DIRECTORIO HAY UN BUILD!
                if( file_exists( $composerPath . 'autoload_psr4.php' ) ){

                    $autoload_psr4  = ( include $composerPath . '/autoload_psr4.php' );

                    foreach( $autoload_psr4 as $namespace => $dirs ){
                        $basePath = strtr( $namespace, "\\", DIRECTORY_SEPARATOR );

                        self::psrStyleToCopperForestStyle( $dirs, $newClassPath . $basePath );
                    }
                }
                
                $output->writeln( 'Processed \'' . $composerPath . 'autoload_psr4\'' );
            }
        }

        if ( file_exists( CF_APP_PATH ) && is_dir( CF_APP_PATH ) ) {
            
            Utils::rcopy( $appPath . 'src' ,$newSpanshotPath . 'src' );
            
            $output->writeln( 'Copied  \'' . $appPath . 'src\' to \'' . $newSpanshotPath . 'src\'' );
            
            Utils::rcopy( $appPath . 'include' , $newSpanshotPath . 'include' );
            
            $output->writeln( 'Copied  \'' . $appPath . 'include\' to \'' . $newSpanshotPath . 'include\'' );
        }


        file_put_contents( $newSpanshotPath . 'snapshot.time',
                time() ); //Ademas del tiempo deberá guardar que librerías de la anterior compilacion no están presentes

        $output->writeln( 'Use \'' . $snapshotsPath . 'snapshot.php\' instead \'' . $appPath . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php\'' );
        $output->writeln( 'Edit \'' . $snapshotsPath . 'snapshot.json\' to select wich snapshot do you want to run each user' );
        

        define( 'CF_SNAPSHOTS_PATH', $snapshotsPath );
        define( 'CF_SNAPSHOT_TIME', file_get_contents(  CF_SNAPSHOTS_PATH . $newSnapshot . DIRECTORY_SEPARATOR . 'sanpshot.time' ) . ( isset( $_SERVER[ 'SNAPSHOT_JSON' ][ 'subversion' ] )?( '.' . $_SERVER[ 'SNAPSHOT_JSON' ][ 'subversion' ] ) : '' ) );
        define( 'CF_CLASS_PATH', CF_SNAPSHOTS_PATH . $newSnapshot . DIRECTORY_SEPARATOR . 'src'  . DIRECTORY_SEPARATOR );
        define( 'CF_INCLUDE_PATH', CF_SNAPSHOTS_PATH . $newSnapshot . DIRECTORY_SEPARATOR . 'include'  . DIRECTORY_SEPARATOR );
        define( 'CF_CONFIG_PATH', CF_SNAPSHOTS_PATH . $newSnapshot . DIRECTORY_SEPARATOR . 'config'  . DIRECTORY_SEPARATOR );
    }
    
    //tengo que mover todos los archivos que encuentre al directorio destino renombrandolos según la ruta relativa al directorio origen
    static function pearStyleToCopperforestStyle( $directories, $dest, $character = '_' ){
        foreach( $directories as $d ){

            $d = strtr( $d, '/', DIRECTORY_SEPARATOR );

            foreach (
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator( $d , \RecursiveDirectoryIterator::SKIP_DOTS ),
                    \RecursiveIteratorIterator::SELF_FIRST )
                as $item
            ) {
                if ( !$item->isDir() ) {
                    $filename = strtr( $iterator->getSubPathName(), DIRECTORY_SEPARATOR, $character );

                    Utils::copyKeepConstants( $item, $dest . DIRECTORY_SEPARATOR . $filename );
                }
            }


        }
    }

    static function psrStyleToCopperForestStyle( $directories, $basePath ){

        foreach( $directories as $d ){

            $d = strtr( $d, '/', DIRECTORY_SEPARATOR );

            if ( $dh = opendir( $d ) ) {

                while ( ($file = readdir( $dh ) ) !== false ) {
                    if( $file != '.' && $file != '..' ){

                        $filepath = $d .  DIRECTORY_SEPARATOR . $file;

                        if( is_dir( $filepath ) ){
                            Utils::rcopy( $filepath , $basePath, true );
                        }
                        else{

                            Utils::copyKeepConstants( $filepath, $basePath . $file );
                        }
                    }
                }
            }
        }
    }

}
