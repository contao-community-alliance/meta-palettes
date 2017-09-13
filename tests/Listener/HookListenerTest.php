<?php

/**
 * @package    meta-palettes
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2017 netzmacht David Molineus. All rights reserved.
 * @filesource
 *
 */


namespace ContaoCommunityAlliance\MetaPalettes\Test\Listener;

use ContaoCommunityAlliance\MetaPalettes\Listener\HookListener;
use PHPUnit\Framework\TestCase;

class HookListenerTest extends TestCase
{
    private $dca;

    /**
     * @var HookListener
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
        $this->listener = new HookListener();
    }

    function testGeneratePalette()
    {
        $this->dca['metapalettes']['default'] = [
            'foo' => ['bar'],
            'baz' => [':hide', 'test'],
        ];

        $this->listener->generatePalettes('tl_test');

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

        $this->listener->generatePalettes('tl_test');

        $this->assertEquals(
            '{foo_legend},bar,aa;{baz_legend:hide},test2;{test_legend},b',
            $this->dca['palettes']['test']
        );
    }

    function testGenerateSubPalettes()
    {
        $this->dca['metasubpalettes']['foo'] = ['bar', 'baz'];
        $this->listener->generatePalettes('tl_test');

        $this->assertArrayHasKey('foo', $this->dca['subpalettes']);
        $this->assertEquals('bar,baz', $this->dca['subpalettes']['foo']);
        $this->assertArraySubset(['foo'], $this->dca['palettes']['__selector__']);
    }
}
