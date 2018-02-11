<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @author    Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author    Sven Baumann <baumann.sv@gmail.com>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2018 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/bit3/contao-meta-palettes
 */

namespace ContaoCommunityAlliance\MetaPalettes\Test\Listener;

use ContaoCommunityAlliance\MetaPalettes\Listener\BuildPalettesListener;
use ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter\StringPalettesInterpreter;
use ContaoCommunityAlliance\MetaPalettes\Parser\MetaPaletteParser;
use PHPUnit\Framework\TestCase;

class BuildPalettesListenerTest extends TestCase
{
    private $dca;

    /**
     * @var BuildPalettesListener
     */
    private $listener;

    protected function setUp()
    {
        $GLOBALS['TL_DCA']['tl_test'] = [
            'metapalettes'    => [],
            'metasubpalettes' => [],
            'subpalettes'     => [],
            'palettes'        => [
                '__selector__' => [],
            ],
        ];

        $this->dca      =& $GLOBALS['TL_DCA']['tl_test'];
        $this->listener = new BuildPalettesListener(
            new MetaPaletteParser(),
            new StringPalettesInterpreter()
        );
    }

    function testGeneratePalette()
    {
        $this->dca['metapalettes']['default'] = [
            'foo' => ['bar'],
            'baz' => [':hide', 'test'],
        ];

        $this->listener->onLoadDataContainer('tl_test');

        $this->assertEquals('{foo_legend},bar;{baz_legend:hide},test', $this->dca['palettes']['default']);
    }

    function testGeneratePaletteWithInheritance()
    {
        $this->dca['metapalettes']['default'] = [
            'foo'  => ['bar'],
            'baz'  => [':hide', 'test'],
            'test' => ['b'],
        ];

        $this->dca['metapalettes']['test extends default'] = [
            '+foo' => ['aa'],
            '+baz' => ['-test', 'test2'],
        ];

        $this->listener->onLoadDataContainer('tl_test');

        $this->assertEquals(
            [
                '__selector__' => [],
                'default'      => '{foo_legend},bar;{baz_legend:hide},test;{test_legend},b',
                'test'         => '{foo_legend},bar,aa;{baz_legend:hide},test2;{test_legend},b'
            ],
            $this->dca['palettes']
        );
    }

    function testGenerateSubPalettes()
    {
        $this->dca['metasubpalettes']['foo'] = ['bar', 'baz'];
        $this->listener->onLoadDataContainer('tl_test');

        $this->assertArrayHasKey('foo', $this->dca['subpalettes']);
        $this->assertEquals('bar,baz', $this->dca['subpalettes']['foo']);
        $this->assertArraySubset(['foo'], $this->dca['palettes']['__selector__']);
    }

    /**
     * Test that everything works when no DCA has been defined.
     *
     * @return void
     */
    function testWorksWithoutDca()
    {
        unset($GLOBALS['TL_DCA']['tl_test']);

        $this->listener->onLoadDataContainer('tl_test');
        $this->assertArrayNotHasKey('tl_test', $GLOBALS['TL_DCA']);
    }
}
