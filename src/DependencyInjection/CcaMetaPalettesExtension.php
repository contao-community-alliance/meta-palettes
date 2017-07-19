<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @link      https://github.com/bit3/contao-meta-palettes
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2017 Contao Community Alliance
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @package   MetaPalettes
 * @license   LGPL-3.0+
 */

namespace ContaoCommunityAlliance\MetaPalettes\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Dependency container extension.
 */
class CcaMetaPalettesExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');
        $loader->load('listeners.yml');
    }
}
