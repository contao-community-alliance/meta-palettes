<?php

/**
 * @package    meta-palettes
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2017 netzmacht David Molineus. All rights reserved.
 * @filesource
 *
 */


namespace ContaoCommunityAlliance\MetaPalettes\Test;

use ContaoCommunityAlliance\MetaPalettes\MetaPalettes;
use PHPUnit\Framework\TestCase;

/**
 * Class MetaPalettesTest
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Test
 */
class MetaPalettesTest extends TestCase
{
    protected $palettes;

    protected function setUp()
    {
        $GLOBALS['TL_DCA']['tl_test']['palettes'] = [];

        $this->palettes =& $GLOBALS['TL_DCA']['tl_test']['palettes'];
    }


    protected function assertPaletteEquals($expected, $palette = 'default')
    {
        $this->assertEquals($expected, $this->palettes[$palette]);
    }

    function testAppendToDefaultPalette()
    {
        $this->palettes['default'] = '{test_legend},test';
        MetaPalettes::appendTo('tl_test', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},bar');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test;{bar_legend},bar';
        MetaPalettes::appendTo('tl_test', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{bar_legend},bar;{foo_legend},bar');
    }

    function testAppendToCustomPalette()
    {
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom'] = '{test_legend},test';

        MetaPalettes::appendTo('tl_test', 'custom', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},bar', 'custom');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom'] = '{test_legend},test;{bar_legend},bar';
        MetaPalettes::appendTo('tl_test', 'custom', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},test;{bar_legend},bar;{foo_legend},bar', 'custom');
    }

    function testAppendBeforeDefaultPalette()
    {
        // Existing legend
        $this->palettes['default'] = '{test_legend},test';

        MetaPalettes::appendBefore('tl_test', 'test', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{foo_legend},bar;{test_legend},test');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test;{baz_legend},baz';
        MetaPalettes::appendBefore('tl_test', 'baz', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},bar;{baz_legend},baz');

        // Non existing legend
        $this->palettes['default'] = '{test_legend},test';

        MetaPalettes::appendBefore('tl_test', 'test2', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},bar');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test;{baz_legend},baz';
        MetaPalettes::appendBefore('tl_test', 'baz2', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{baz_legend},baz;{foo_legend},bar');
    }

    function testAppendBeforeCustomPalette()
    {
        // Existing legend
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom']  = '{test_legend},test';

        MetaPalettes::appendBefore('tl_test', 'custom', 'test', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{foo_legend},bar;{test_legend},test', 'custom');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom'] = '{test_legend},test;{baz_legend},baz';
        MetaPalettes::appendBefore('tl_test', 'custom', 'baz', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},bar;{baz_legend},baz', 'custom');

        // Non existing legend
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom']  = '{test_legend},test';

        MetaPalettes::appendBefore('tl_test', 'custom', 'test2', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},bar', 'custom');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom'] = '{test_legend},test;{baz_legend},baz';
        MetaPalettes::appendBefore('tl_test', 'custom', 'baz2', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{baz_legend},baz;{foo_legend},bar', 'custom');
    }

    function testAppendAfterDefaultPalette()
    {
        // Existing legend
        $this->palettes['default'] = '{test_legend},test';

        MetaPalettes::appendAfter('tl_test', 'test', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},bar');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test;{baz_legend},baz';
        MetaPalettes::appendAfter('tl_test', 'baz', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{baz_legend},baz;{foo_legend},bar');

        // Non existing legend
        $this->palettes['default'] = '{test_legend},test';

        MetaPalettes::appendAfter('tl_test', 'test2', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},bar');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test;{baz_legend},baz';
        MetaPalettes::appendAfter('tl_test', 'baz2', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{baz_legend},baz;{foo_legend},bar');
    }

    function testAppendAfterCustomPalette()
    {
        // Existing legend
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom']  = '{test_legend},test';

        MetaPalettes::appendAfter('tl_test', 'custom', 'test', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},bar', 'custom');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom'] = '{test_legend},test;{baz_legend},baz';
        MetaPalettes::appendAfter('tl_test', 'custom', 'baz', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},test;{baz_legend},baz;{foo_legend},bar', 'custom');

        // Non existing legend
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom']  = '{test_legend},test';

        MetaPalettes::appendAfter('tl_test', 'custom', 'test2', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},bar', 'custom');
        $this->assertPaletteEquals('{test_legend},test');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom'] = '{test_legend},test;{baz_legend},baz';
        MetaPalettes::appendAfter('tl_test', 'custom', 'baz2', ['foo' => ['bar']]);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},test;{baz_legend},baz;{foo_legend},bar', 'custom');
    }

    function testAppendFieldsToDefaultPalette()
    {
        // Test with single field.
        $this->palettes['default'] = '{test_legend},test';
        MetaPalettes::appendFields('tl_test', 'test', ['foo']);
        $this->assertPaletteEquals('{test_legend},test,foo');

        // Test with multiple fields.
        $this->palettes['default'] = '{test_legend},test';
        MetaPalettes::appendFields('tl_test', 'test', ['foo', 'bar']);
        $this->assertPaletteEquals('{test_legend},test,foo,bar');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test;{baz_legend},baz';
        MetaPalettes::appendFields('tl_test', 'baz', ['bar']);
        $this->assertPaletteEquals('{test_legend},test;{baz_legend},baz,bar');
    }

    function testAppendFieldsToCustomPalette()
    {
        // Test with single field.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom']  = '{test_legend},test';

        MetaPalettes::appendFields('tl_test', 'custom', 'test', ['foo']);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},test,foo', 'custom');

        // Test with multiple fields.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom']  = '{test_legend},test';

        MetaPalettes::appendFields('tl_test', 'custom', 'test', ['foo', 'bar']);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},test,foo,bar', 'custom');

        // Test with complex palette and single field.
        $this->palettes['default']  = '{test_legend},test';
        $this->palettes['custom']   = '{test_legend},test;{baz_legend},baz';
        MetaPalettes::appendFields('tl_test', 'custom', 'test', ['bar']);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},test,bar;{baz_legend},baz', 'custom');
    }

    function testPrependFieldsToDefaultPalette()
    {
        // Test with single field.
        $this->palettes['default'] = '{test_legend},test';
        MetaPalettes::prependFields('tl_test', 'test', ['foo']);
        $this->assertPaletteEquals('{test_legend},foo,test');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test;{bar_legend},bar';
        MetaPalettes::prependFields('tl_test', 'test', ['foo']);
        $this->assertPaletteEquals('{test_legend},foo,test;{bar_legend},bar');

        // Test with multiple fields.
        $this->palettes['default'] = '{test_legend},test';
        MetaPalettes::prependFields('tl_test', 'test', ['foo', 'bar']);
        $this->assertPaletteEquals('{test_legend},foo,bar,test');

        // Test with complex palette and multiple field.
        $this->palettes['default'] = '{test_legend},test;{bar_legend},bar';
        MetaPalettes::prependFields('tl_test', 'test', ['foo', 'bar']);
        $this->assertPaletteEquals('{test_legend},foo,bar,test;{bar_legend},bar');
    }

    function testPrependFieldsToCustomPalette()
    {
        // Test with single field.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom'] = '{test_legend},test';
        MetaPalettes::prependFields('tl_test', 'custom', 'test', ['foo']);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},foo,test', 'custom');

        // Test with complex palette and single field.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom'] = '{test_legend},test;{bar_legend},bar';
        MetaPalettes::prependFields('tl_test', 'custom', 'test', ['foo']);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},foo,test;{bar_legend},bar', 'custom');

        // Test with multiple fields.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom'] = '{test_legend},test';
        MetaPalettes::prependFields('tl_test', 'custom', 'test', ['foo', 'bar']);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},foo,bar,test', 'custom');

        // Test with complex palette and multiple field.
        $this->palettes['default'] = '{test_legend},test';
        $this->palettes['custom'] = '{test_legend},test;{bar_legend},bar';
        MetaPalettes::prependFields('tl_test', 'custom', 'test', ['foo', 'bar']);
        $this->assertPaletteEquals('{test_legend},test');
        $this->assertPaletteEquals('{test_legend},foo,bar,test;{bar_legend},bar', 'custom');
    }

    function testRemoveFieldsDefaultPalette()
    {
        $this->palettes['default'] = '{test_legend},test,bar';
        MetaPalettes::removeFields('tl_test', ['bar']);
        $this->assertPaletteEquals('{test_legend},test');

        $this->palettes['default'] = '{test_legend},test,bar';
        MetaPalettes::removeFields('tl_test', ['test']);
        $this->assertPaletteEquals('{test_legend},bar');

        $this->palettes['default'] = '{test_legend},test,bar;{foo_legend},foo,test';
        MetaPalettes::removeFields('tl_test', ['bar']);
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},foo,test');

        $this->palettes['default'] = '{test_legend},test,bar;{foo_legend},foo,test';
        MetaPalettes::removeFields('tl_test', ['test']);
        $this->assertPaletteEquals('{test_legend},bar;{foo_legend},foo');
    }

    function testRemoveFieldsCustomPalette()
    {
        $this->palettes['custom'] = '{test_legend},test,bar';
        MetaPalettes::removeFields('tl_test', 'custom', ['bar']);
        $this->assertPaletteEquals('{test_legend},test', 'custom');

        $this->palettes['custom'] = '{test_legend},test,bar';
        MetaPalettes::removeFields('tl_test', 'custom', ['test']);
        $this->assertPaletteEquals('{test_legend},bar', 'custom');

        $this->palettes['custom'] = '{test_legend},test,bar;{foo_legend},foo,test';
        MetaPalettes::removeFields('tl_test', 'custom', ['bar']);
        $this->assertPaletteEquals('{test_legend},test;{foo_legend},foo,test', 'custom');

        $this->palettes['custom'] = '{test_legend},test,bar;{foo_legend},foo,test';
        MetaPalettes::removeFields('tl_test', 'custom', ['test']);
        $this->assertPaletteEquals('{test_legend},bar;{foo_legend},foo', 'custom');
    }

    function testGeneratePalette()
    {
        $this->assertEquals(
            '{test_legend},foo,bar',
            MetaPalettes::generatePalette(
                [
                    'test' => ['foo', 'bar']
                ]
            )
        );

        $this->assertEquals(
            '{test_legend:hide},foo,bar',
            MetaPalettes::generatePalette(
                [
                    'test' => [':hide', 'foo', 'bar']
                ]
            )
        );

        $this->assertEquals(
            '{test_legend:hide},foo,bar;{test2_legend},baz',
            MetaPalettes::generatePalette(
                [
                    'test' => [':hide', 'foo', 'bar'],
                    'test2' => ['baz']
                ]
            )
        );
    }

    function testFilterFields()
    {
        $this->assertFalse(MetaPalettes::filterFields(':hide'));
        $this->assertTrue(MetaPalettes::filterFields('hide'));
    }
}
