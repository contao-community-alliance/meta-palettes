<?php

/**
 * @package    meta-palettes
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2017 netzmacht David Molineus. All rights reserved.
 * @filesource
 *
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

    }
}
