<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    Tristan Lins <tristan.lins@infinitysoft.de>
 * @author    Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2017 Contao Community Alliance
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/bit3/contao-meta-palettes
 */

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = [
    'cca.meta_palettes.listener.build_palettes_listener',
    'onLoadDataContainer',
];
