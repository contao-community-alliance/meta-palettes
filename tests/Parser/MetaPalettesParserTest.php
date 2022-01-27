<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @author    Sven Baumann <baumann.sv@gmail.com>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2018 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/bit3/contao-meta-palettes
 */

namespace ContaoCommunityAlliance\MetaPalettes\Test\Parser;

use ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter;
use ContaoCommunityAlliance\MetaPalettes\Parser\MetaPaletteParser;
use PHPUnit\Framework\TestCase;

/**
 * Class MetaPalettesParserTest.
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Test\Parser
 */
class MetaPalettesParserTest extends TestCase
{
    private $definition;

    protected function setUp(): void
    {
        $GLOBALS['TL_DCA']['tl_test']['metapalettes'] = [];

        $this->definition = & $GLOBALS['TL_DCA']['tl_test'];

    }

    protected function assertPaletteEquals($expected, $palette = 'default')
    {
        $this->assertEquals($expected, $this->definition['palettes'][$palette]);
    }

    function testSimplePaletteIsParsed()
    {
        $interpreter = $this->getMockBuilder(Interpreter::class)->getMock();
        $interpreter
            ->expects($this->once())
            ->method('startPalette')
            ->with('tl_test', 'default');

        $interpreter
            ->expects($this->exactly(2))
            ->method('addLegend')
            ->withConsecutive(['foo', true, false], ['baz', true, true]);

        $interpreter
            ->expects($this->exactly(2))
            ->method('addFieldTo')
            ->withConsecutive(['foo', 'bar'], ['baz', 'test']);

        $interpreter
            ->expects($this->once())
            ->method('finishPalette');

        $this->definition['metapalettes']['default'] = [
            'foo' => ['bar'],
            'baz' => [':hide', 'test']
        ];

        $parser  = new MetaPaletteParser();
        $success = $parser->parse('tl_test', $this->definition, $interpreter);

        $this->assertTrue($success);
    }

    function testInsertModesAreDetected()
    {
        $this->definition['metapalettes']['default'] = [
            '-foo' => ['bar', '+add'],
            'baz' => [':hide', 'test', '-test2'],
            '+legend' => ['field', '-remove'],
        ];

        $interpreter = $this->getMockBuilder(Interpreter::class)->getMock();
        $interpreter
            ->expects($this->once())
            ->method('startPalette')
            ->with('tl_test', 'default');

        $interpreter
            ->expects($this->exactly(3))
            ->method('addLegend')
            ->withConsecutive(
                ['foo', false, false],
                ['baz', true, true],
                ['legend', false, false]
            );

        $interpreter
            ->expects($this->exactly(3))
            ->method('addFieldTo')
            ->withConsecutive(['foo', 'add'], ['baz', 'test'], ['legend', 'field']);

        $interpreter
            ->expects($this->exactly(3))
            ->method('removeFieldFrom')
            ->withConsecutive(['foo', 'bar'], ['baz', 'test2'], ['legend', 'remove']);

        $interpreter
            ->expects($this->once())
            ->method('finishPalette');

        $parser  = new MetaPaletteParser();
        $success = $parser->parse('tl_test', $this->definition, $interpreter);

        $this->assertTrue($success);
    }

    function testInheritance()
    {
        $this->definition['metapalettes']['default'] = [
            'foo' => ['bar'],
            'title' => ['headline']
        ];

        $this->definition['metapalettes']['test extends default'] = [
            '+foo'  => ['baz'],
            'title' => ['title', '-headline']
        ];

        $parser      = new MetaPaletteParser();
        $interpreter = $this->getMockBuilder(Interpreter::class)->getMock();
        $interpreter
            ->expects($this->exactly(2))
            ->method('startPalette')
            ->withConsecutive(['tl_test', 'default'], ['tl_test', 'test']);

        $interpreter
            ->expects($this->exactly(4))
            ->method('addLegend')
            ->withConsecutive(
                ['foo', true, false],
                ['title', true, false],
                ['foo', false, false],
                ['title', true, false]
            );

        $interpreter
            ->expects($this->exactly(4))
            ->method('addFieldTo')
            ->withConsecutive(
                ['foo', 'bar'],
                ['title', 'headline'],
                ['foo', 'baz'],
                ['title', 'title']
            );

        $interpreter
            ->expects($this->exactly(1))
            ->method('removeFieldFrom')
            ->withConsecutive(['title', 'headline']);

        $interpreter
            ->expects($this->once())
            ->method('inherit')
            ->with('default', $parser);

        $interpreter
            ->expects($this->exactly(2))
            ->method('finishPalette');

        $success = $parser->parse('tl_test', $this->definition, $interpreter);

        $this->assertTrue($success);
    }

    function testMultiInheritance()
    {
        $this->definition['metapalettes']['base'] = [
            'config' => ['config']
        ];

        $this->definition['metapalettes']['default'] = [
            'foo' => ['bar'],
            'title' => ['headline']
        ];

        $this->definition['metapalettes']['test extends default extends base'] = [
            '+foo'  => ['baz'],
            'title' => ['title', '-headline']
        ];

        $parser      = new MetaPaletteParser();
        $interpreter = $this->getMockBuilder(Interpreter::class)->getMock();
        $interpreter
            ->expects($this->exactly(3))
            ->method('startPalette')
            ->withConsecutive(
                ['tl_test', 'base'],
                ['tl_test', 'default'],
                ['tl_test', 'test']
            );

        $interpreter
            ->expects($this->exactly(5))
            ->method('addLegend')
            ->withConsecutive(
                ['config', true, false],
                ['foo', true, false],
                ['title', true, false],
                ['foo', false, false],
                ['title', true, false]
            );

        $interpreter
            ->expects($this->exactly(5))
            ->method('addFieldTo')
            ->withConsecutive(
                ['config', 'config'],
                ['foo', 'bar'],
                ['title', 'headline'],
                ['foo', 'baz'],
                ['title', 'title']
            );

        $interpreter
            ->expects($this->exactly(1))
            ->method('removeFieldFrom')
            ->withConsecutive(['title', 'headline']);

        $interpreter
            ->expects($this->exactly(2))
            ->method('inherit')
            ->withConsecutive(
                ['base', $parser],
                ['default', $parser]
            );

        $interpreter
            ->expects($this->exactly(3))
            ->method('finishPalette');

        $success = $parser->parse('tl_test', $this->definition, $interpreter);

        $this->assertTrue($success);
    }

    function testInheritanceWhereParentClassAlsoInheritFromBaseClass()
    {
        // See order. It doesn't matter which palette is defined first.

        $this->definition['metapalettes']['test extends custom'] = [
            'test'  => ['test'],
        ];

        $this->definition['metapalettes']['custom extends default'] = [
            'custom' => ['custom'],
        ];

        $this->definition['metapalettes']['default'] = [
            'default' => ['default'],
        ];

        $parser      = new MetaPaletteParser();
        $interpreter = $this->getMockBuilder(Interpreter::class)->getMock();
        $interpreter
            ->expects($this->exactly(3))
            ->method('startPalette')
            ->withConsecutive(['tl_test', 'test'], ['tl_test', 'custom'], ['tl_test', 'default']);

        $interpreter
            ->expects($this->exactly(3))
            ->method('addLegend')
            ->withConsecutive(
                ['test', true, false],
                ['custom', true, false],
                ['default', true, false]
            );

        $interpreter
            ->expects($this->exactly(3))
            ->method('addFieldTo')
            ->withConsecutive(
                ['test', 'test'],
                ['custom', 'custom'],
                ['default', 'default']
            );

        $interpreter
            ->expects($this->exactly(2))
            ->method('inherit')
            ->withConsecutive(
                ['custom', $parser],
                ['default', $parser]
            );

        $interpreter
            ->expects($this->exactly(3))
            ->method('finishPalette');

        $success = $parser->parse('tl_test', $this->definition, $interpreter);

        $this->assertTrue($success);

        // Add real test
        $parser->parse('tl_test', $this->definition, new Interpreter\StringPalettesInterpreter());
        $this->assertPaletteEquals('{default_legend},default', 'default');
        $this->assertPaletteEquals('{default_legend},default;{custom_legend},custom', 'custom');
        $this->assertPaletteEquals('{default_legend},default;{custom_legend},custom;{test_legend},test', 'test');
    }
}
