<?php
/*
 * This file is part of the Snapshot plugin.
 *
 * (c) Alejandro Gama Castro <alex@copperforest.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace copperforest\snapshot\plugin;

use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capable;

/**
 * @author Alejandro Gama Castro <alex@copperforest.org>
 */
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