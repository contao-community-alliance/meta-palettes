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

        $matcher = $this->exactly(2);
        $interpreter
            ->expects($matcher)
            ->method('addLegend')
            ->willReturnCallback(function (...$args) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals(['foo', true, false, null, null], $args),
                    2 => $this->assertEquals(['baz', true, true, null, null], $args),
                };
            });

        $matcher2 = $this->exactly(2);
        $interpreter
            ->expects($matcher2)
            ->method('addFieldTo')
            ->willReturnCallback(function (...$args) use ($matcher2) {
                match ($matcher2->numberOfInvocations()) {
                    1 => $this->assertEquals(['foo', 'bar', null, null], $args),
                    2 => $this->assertEquals(['baz', 'test', null, null], $args),
                };
            });

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

        $matcher = $this->exactly(3);
        $interpreter
            ->expects($matcher)
            ->method('addLegend')
            ->willReturnCallback(function (...$args) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals(['foo', false, null, null, null], $args),
                    2 => $this->assertEquals(['baz', true, true, null, null], $args),
                    3 => $this->assertEquals(['legend', false, null, null, null], $args),
                };
            });

        $matcher2 = $this->exactly(3);
        $interpreter
            ->expects($matcher2)
            ->method('addFieldTo')
            ->willReturnCallback(function (...$args) use ($matcher2) {
                match ($matcher2->numberOfInvocations()) {
                    1 => $this->assertEquals(['foo', 'add', null, null], $args),
                    2 => $this->assertEquals(['baz', 'test', null, null], $args),
                    3 => $this->assertEquals(['legend', 'field', null, null], $args),
                };
            });

        $matcher3 = $this->exactly(3);
        $interpreter
            ->expects($matcher3)
            ->method('removeFieldFrom')
            ->willReturnCallback(function (...$args) use ($matcher3) {
                match ($matcher3->numberOfInvocations()) {
                    1 => $this->assertEquals(['foo', 'bar'], $args),
                    2 => $this->assertEquals(['baz', 'test2'], $args),
                    3 => $this->assertEquals(['legend', 'remove'], $args),
                };
            });

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
        $matcher = $this->exactly(2);
        $interpreter
            ->expects($matcher)
            ->method('startPalette')
            ->willReturnCallback(function (...$args) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals(['tl_test', 'default'], $args),
                    2 => $this->assertEquals(['tl_test', 'test'], $args),
                };
            });

        $matcher2 = $this->exactly(4);
        $interpreter
            ->expects($matcher2)
            ->method('addLegend')
            ->willReturnCallback(function (...$args) use ($matcher2) {
                match ($matcher2->numberOfInvocations()) {
                    1 => $this->assertEquals(['foo', true, false, null, null], $args),
                    2 => $this->assertEquals(['title', true, false, null, null], $args),
                    3 => $this->assertEquals(['foo', false, null, null, null], $args),
                    4 => $this->assertEquals(['title', true, null, null, null], $args),
                };
            });

        $matcher3 = $this->exactly(4);
        $interpreter
            ->expects($matcher3)
            ->method('addFieldTo')
            ->willReturnCallback(function (...$args) use ($matcher3) {
                match ($matcher3->numberOfInvocations()) {
                    1 => $this->assertEquals(['foo', 'bar', null, null], $args),
                    2 => $this->assertEquals(['title', 'headline', null, null], $args),
                    3 => $this->assertEquals(['foo', 'baz', null, null], $args),
                    4 => $this->assertEquals(['title', 'title', null, null], $args),
                };
            });

        $interpreter
            ->expects($this->once())
            ->method('removeFieldFrom')
            ->with('title', 'headline');

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
        $matcher = $this->exactly(3);
        $interpreter
            ->expects($matcher)
            ->method('startPalette')
            ->willReturnCallback(function (...$args) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals(['tl_test', 'base'], $args),
                    2 => $this->assertEquals(['tl_test', 'default'], $args),
                    3 => $this->assertEquals(['tl_test', 'test'], $args),
                };
            });

        $matcher2 = $this->exactly(5);
        $interpreter
            ->expects($matcher2)
            ->method('addLegend')
            ->willReturnCallback(function (...$args) use ($matcher2) {
                match ($matcher2->numberOfInvocations()) {
                    1 => $this->assertEquals(['config', true, false, null, null], $args),
                    2 => $this->assertEquals(['foo', true, false, null, null], $args),
                    3 => $this->assertEquals(['title', true, false, null, null], $args),
                    4 => $this->assertEquals(['foo', false, null, null, null], $args),
                    5 => $this->assertEquals(['title', true, null, null, null], $args),
                };
            });

        $matcher3 = $this->exactly(5);
        $interpreter
            ->expects($matcher3)
            ->method('addFieldTo')
            ->willReturnCallback(function (...$args) use ($matcher3) {
                match ($matcher3->numberOfInvocations()) {
                    1 => $this->assertEquals(['config', 'config', null, null], $args),
                    2 => $this->assertEquals(['foo', 'bar', null, null], $args),
                    3 => $this->assertEquals(['title', 'headline', null, null], $args),
                    4 => $this->assertEquals(['foo', 'baz', null, null], $args),
                    5 => $this->assertEquals(['title', 'title', null, null], $args),
                };
            });

        $interpreter
            ->expects($this->once())
            ->method('removeFieldFrom')
            ->with('title', 'headline');

        $matcher4 = $this->exactly(2);
        $interpreter
            ->expects($matcher4)
            ->method('inherit')
            ->willReturnCallback(function (...$args) use ($matcher4, $parser) {
                match ($matcher4->numberOfInvocations()) {
                    1 => $this->assertEquals(['base', $parser], $args),
                    2 => $this->assertEquals(['default', $parser], $args),
                };
            });

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
        $matcher = $this->exactly(3);
        $interpreter
            ->expects($matcher)
            ->method('startPalette')
            ->willReturnCallback(function (...$args) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals(['tl_test', 'test'], $args),
                    2 => $this->assertEquals(['tl_test', 'custom'], $args),
                    3 => $this->assertEquals(['tl_test', 'default'], $args),
                };
            });

        $matcher2 = $this->exactly(3);
        $interpreter
            ->expects($matcher2)
            ->method('addLegend')
            ->willReturnCallback(function (...$args) use ($matcher2) {
                match ($matcher2->numberOfInvocations()) {
                    1 => $this->assertEquals(['test', true, false, null, null], $args),
                    2 => $this->assertEquals(['custom', true, false, null, null], $args),
                    3 => $this->assertEquals(['default', true, false, null, null], $args),
                };
            });

        $matcher3 = $this->exactly(3);
        $interpreter
            ->expects($matcher3)
            ->method('addFieldTo')
            ->willReturnCallback(function (...$args) use ($matcher3) {
                match ($matcher3->numberOfInvocations()) {
                    1 => $this->assertEquals(['test', 'test', null, null], $args),
                    2 => $this->assertEquals(['custom', 'custom', null, null], $args),
                    3 => $this->assertEquals(['default', 'default', null, null], $args),
                };
            });

        $matcher4 = $this->exactly(2);
        $interpreter
            ->expects($matcher4)
            ->method('inherit')
            ->willReturnCallback(function (...$args) use ($matcher4, $parser) {
                match ($matcher4->numberOfInvocations()) {
                    1 => $this->assertEquals(['custom', $parser], $args),
                    2 => $this->assertEquals(['default', $parser], $args),
                };
            });

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
