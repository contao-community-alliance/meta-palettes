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
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Property;
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

    public function testAddsSubSelectPropertiesWhenSelectorPropertyExists(): void
    {
        $interpreter = $this->createInterpreterWithRteSubSelect();

        $interpreter->startPalette('tl_example', 'default');
        $interpreter->addLegend('advanced', true, false);
        $interpreter->addFieldTo('advanced', 'rte');
        $interpreter->finishPalette();

        $palette = $interpreter->getPalettes()[0];

        self::assertTrue($palette->hasLegend('presentation'));
        self::assertTrue($palette->getLegend('presentation')->hasProperty('highlight'));
    }

    public function testDoesNotAddSubSelectPropertiesWhenSelectorPropertyIsMissing(): void
    {
        $interpreter = $this->createInterpreterWithRteSubSelect();

        // Palette without the selector property "rte" (e.g. a text/select attribute setting).
        $interpreter->startPalette('tl_example', 'default');
        $interpreter->addLegend('advanced', true, false);
        $interpreter->addFieldTo('advanced', 'mandatory');
        $interpreter->finishPalette();

        $palette = $interpreter->getPalettes()[0];

        self::assertFalse(
            $palette->hasLegend('presentation'),
            'The sub select legend must not be created when the selector property is absent from the palette.'
        );
    }

    private function createInterpreterWithRteSubSelect(): PalettesDefinitionInterpreter
    {
        $definition = $this->createMock(PalettesDefinitionInterface::class);
        $parser     = $this->createPartialMock(LegacyPalettesParser::class, []);

        // Mirrors a parsed metasubselectpalettes entry where selecting "ace" for the "rte"
        // property reveals the "highlight" property within the "presentation" legend.
        $subSelectPalettes = [
            'rte' => [
                'presentation after rte' => [new Property('highlight')],
            ],
        ];

        return new PalettesDefinitionInterpreter(
            $definition,
            $parser,
            ['rte'],
            [],
            $subSelectPalettes
        );
    }
}
