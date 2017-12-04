<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2017 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/bit3/contao-meta-palettes
 */

namespace ContaoCommunityAlliance\MetaPalettes\Test\Listener;

use ContaoCommunityAlliance\MetaPalettes\Listener\SubSelectPalettesListener;
use PHPUnit\Framework\TestCase;

class SubSelectPalettesListenerTest extends TestCase
{
    private $dca;

    /**
     * @var SubSelectPalettesListener
     */
    private $listener;

    protected function setUp()
    {
        $GLOBALS['TL_DCA']['tl_test'] = [
            'metapalettes'    => [],
            'metasubpalettes' => [],
            'metasubselectpalettes' => [],
            'subpalettes'     => [],
            'palettes'        => [
                '__selector__' => [],
            ],
        ];

        $this->dca      = &$GLOBALS['TL_DCA']['tl_test'];
        $this->listener = new SubSelectPalettesListener();
    }

    function testApply()
    {
        $this->assertTrue(true);
    }
}
