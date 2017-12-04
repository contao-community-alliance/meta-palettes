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

namespace ContaoCommunityAlliance\MetaPalettes\Test\Parser\Interpreter;

use ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter\StringPalettesInterpreter;
use ContaoCommunityAlliance\MetaPalettes\Parser\Parser;
use PHPUnit\Framework\TestCase;

class StringPalettesInterpreterTest extends TestCase
{
    function testSimplePalette()
    {
        $interpreter = new StringPalettesInterpreter();
        $interpreter->startPalette('tl_test', 'default');
        $interpreter->addLegend('title', true, false);
        $interpreter->addFieldTo('title', 'headline');
        $interpreter->addLegend('config', true, true);
        $interpreter->addFieldTo('config', 'config');
        $interpreter->finishPalette();

        $this->assertEquals(
            '{title_legend},headline;{config_legend:hide},config',
            $GLOBALS['TL_DCA']['tl_test']['palettes']['default']
        );
    }

    function testInheritance()
    {
        $interpreter = new StringPalettesInterpreter();
        $parser = $this->getMockBuilder(Parser::class)
            ->setMethods(['parse', 'parsePalette'])
            ->getMock();

        $parser
            ->expects($this->once())
            ->method('parsePalette')
            ->with('tl_test', 'default', $interpreter, true);


        $interpreter->startPalette('tl_test', 'test');
        $interpreter->inherit('default', $parser);

        // Parent config.
        $interpreter->addLegend('title', true, false);
        $interpreter->addFieldTo('title', 'headline');
        $interpreter->addLegend('config', true, true);
        $interpreter->addFieldTo('config', 'config');

        // Extended config
        $interpreter->addLegend('title', true, true);
        $interpreter->addFieldTo('title', 'title');
        $interpreter->addLegend('config', false, null);
        $interpreter->addFieldTo('config', 'config2', Parser::POSITION_BEFORE, 'config');

        $interpreter->finishPalette();

        $this->assertEquals(
            '{title_legend:hide},title;{config_legend:hide},config2,config',
            $GLOBALS['TL_DCA']['tl_test']['palettes']['test']
        );
    }

    function testMultipleInheritance()
    {
        $interpreter = new StringPalettesInterpreter();
        $parser = $this->getMockBuilder(Parser::class)
            ->setMethods(['parse', 'parsePalette'])
            ->getMock();

        $parser
            ->expects($this->exactly(2))
            ->method('parsePalette')
            ->withConsecutive(
                ['tl_test', 'default', $interpreter, true],
                ['tl_test', 'custom', $interpreter, true]
            );

        $interpreter->startPalette('tl_test', 'test');

        // Parent config.
        $interpreter->inherit('default', $parser);
        $interpreter->addLegend('title', true, false);
        $interpreter->addFieldTo('title', 'headline');
        $interpreter->addLegend('config', true, true);
        $interpreter->addFieldTo('config', 'config');

        // 2nd parent config
        $interpreter->inherit('custom', $parser);
        $interpreter->addLegend('custom', true, true);
        $interpreter->addFieldTo('custom', 'customField');

        // Extended config
        $interpreter->addLegend('title', true, true);
        $interpreter->addFieldTo('title', 'title');
        $interpreter->addLegend('config', false, null);
        $interpreter->addFieldTo('config', 'config2', Parser::POSITION_BEFORE, 'config');

        $interpreter->finishPalette();

        $this->assertEquals(
            '{title_legend:hide},title;{config_legend:hide},config2,config;{custom_legend:hide},customField',
            $GLOBALS['TL_DCA']['tl_test']['palettes']['test']
        );
    }

}
