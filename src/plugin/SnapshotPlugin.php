<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace copperforest\snapshot\plugin;

use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capable;


class SnapshotPlugin  implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io)
    {
        //$installer = new TemplateInstaller($io, $composer);
        //$composer->getInstallationManager()->addInstaller($installer);
    }
    
    public function getCapabilities()
    {
        return array(
            'Composer\\Plugin\\Capability\\CommandProvider' => 'copperforest\\snapshot\\plugin\\CreateSnapshotCommandProvider',
        );
    }

}