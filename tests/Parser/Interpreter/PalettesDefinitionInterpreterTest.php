<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2022 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/bit3/contao-meta-palettes
 */

declare(strict_types=1);

namespace ContaoCommunityAlliance\MetaPalettes\Test\Parser\Interpreter;

use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Palette\LegacyPalettesParser;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\PropertyInterface;
use ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter\PalettesDefinitionInterpreter;
use PHPUnit\Framework\TestCase;

/** @covers \ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter\PalettesDefinitionInterpreter */
class PalettesDefinitionInterpreterTest extends TestCase
{
    public function testAddsSubPalettesAfterSelector(): void
    {
        $definition  = $this->createMock(PalettesDefinitionInterface::class);
        $parser      = $this->createPartialMock(LegacyPalettesParser::class, []);
        $interpreter = new PalettesDefinitionInterpreter(
            $definition,
            $parser,
            ['first'],
            [],
            []
        );

        $interpreter->startPalette('tl_example', 'default');
        $interpreter->addLegend('title_legend', true, false);
        $interpreter->addFieldTo('title_legend', 'first');
        $interpreter->addFieldTo('title_legend', 'second');
        $interpreter->finishPalette();

        $interpreter->addSubPalette('tl_example', 'first', ['injected']);
        $interpreter->addSubPalette('tl_example', 'second', ['last']);

        $palettes = $interpreter->getPalettes();
        self::assertCount(1, $palettes);
        $palette = $palettes[0];

        $propertyNames = array_map(
            static function (PropertyInterface $property) {
                return $property->getName();
            },
            $palette->getLegend('title_legend')->getProperties()
        );

        self::assertSame(['first', 'injected', 'second', 'last'], $propertyNames);
    }
}
