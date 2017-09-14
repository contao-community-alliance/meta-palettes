<?php

/**
 * @package    meta-palettes
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2017 netzmacht David Molineus. All rights reserved.
 * @filesource
 *
 */

namespace ContaoCommunityAlliance\MetaPalettes\Test\Parser;

use ContaoCommunityAlliance\MetaPalettes\Parser\MetaPalette\Interpreter;
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

    protected function setUp()
    {
        $GLOBALS['TL_DCA']['tl_test']['metapalettes'] = [];

        $this->definition = & $GLOBALS['TL_DCA']['tl_test']['metapalettes'];

    }

    function testReturnFalseIfDefinitionNotExist()
    {
        $interpreter = $this->getMockBuilder(Interpreter::class)->getMock();
        $parser      = new MetaPaletteParser();
        $success     = $parser->parse('tl_example', $interpreter);

        $this->assertFalse($success);
    }

    function testSimplePaletteIsParsed()
    {
        $interpreter = $this->getMockBuilder(Interpreter::class)->getMock();
        $interpreter
            ->expects($this->once())
            ->method('start')
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
            ->method('finish');

        $this->definition['default'] = [
            'foo' => ['bar'],
            'baz' => [':hide', 'test']
        ];

        $parser  = new MetaPaletteParser();
        $success = $parser->parse('tl_test', $interpreter);

        $this->assertTrue($success);
    }

    function testInsertModesAreDetected()
    {
        $this->definition['default'] = [
            '-foo' => ['bar', '+add'],
            'baz' => [':hide', 'test', '-test2'],
            '+legend' => ['field', '-remove'],
        ];

        $interpreter = $this->getMockBuilder(Interpreter::class)->getMock();
        $interpreter
            ->expects($this->once())
            ->method('start')
            ->with('tl_test', 'default');

        $interpreter
            ->expects($this->exactly(3))
            ->method('addLegend')
            ->withConsecutive(
                ['foo', true, false],
                ['baz', true, true],
                ['legend', true, false]
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
            ->method('finish');

        $parser  = new MetaPaletteParser();
        $success = $parser->parse('tl_test', $interpreter);

        $this->assertTrue($success);
    }

    function testInheritance()
    {
        $this->definition['default'] = [
            'foo' => ['bar'],
            'title' => ['headline']
        ];

        $this->definition['test extends default'] = [
            '+foo'  => ['baz'],
            'title' => ['title', '-headline']
        ];

        $parser      = new MetaPaletteParser();
        $interpreter = $this->getMockBuilder(Interpreter::class)->getMock();
        $interpreter
            ->expects($this->exactly(2))
            ->method('start')
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
            ->expects($this->once())
            ->method('finish');

        $success = $parser->parse('tl_test', $interpreter);

        $this->assertTrue($success);
    }
}
