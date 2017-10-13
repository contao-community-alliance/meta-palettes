<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author    Tristan Lins <tristan@lins.io>
 * @author    Stefan Heimes <stefan_heimes@hotmail.com>
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2017 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/contao-community-alliance/meta-palettes
 */

namespace ContaoCommunityAlliance\MetaPalettes\Listener;

use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Builder\Legacy\DcaReadingDataDefinitionBuilder;
use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Palette\LegacyPalettesParser;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ContainerInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\DefaultPalettesDefinition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\NotCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyConditionChain;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyTrueCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyValueCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyVisibleCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Property;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter\PalettesDefinitionInterpreter;
use ContaoCommunityAlliance\MetaPalettes\Parser\MetaPaletteParser;

/**
 * Class MetaPalettesBuilder
 *
 * Generates the palettes from the meta information.
 *
 * @copyright 2013 bit3 UG
 * @author    Tristan Lins <tristan.lins@bit3.de>
 * @package   MetaPalettes
 */
class MetaPalettesBuilder extends DcaReadingDataDefinitionBuilder
{
    const PRIORITY = 200;

    /**
     * Meta palettes parser.
     *
     * @var MetaPaletteParser
     */
    private $metaPalettesParser;

    /**
     * Construct.
     *
     * @param null|MetaPaletteParser $metaPaletteParser Meta palettes parser.
     */
    public function __construct(MetaPaletteParser $metaPaletteParser = null)
    {
        $this->metaPalettesParser = $metaPaletteParser ?: new MetaPaletteParser();
    }

    /**
     * Build a data definition and store it into the environments container.
     *
     * @param ContainerInterface       $container Definition container.
     * @param BuildDataDefinitionEvent $event     Build data definition event.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(
        ContainerInterface $container,
        BuildDataDefinitionEvent $event
    ) {
        if (!$this->loadDca($container->getName(), $this->getDispatcher())) {
            return;
        }

        if ($container->hasDefinition(PalettesDefinitionInterface::NAME)) {
            $palettesDefinition = $container->getDefinition(PalettesDefinitionInterface::NAME);
        } else {
            $palettesDefinition = new DefaultPalettesDefinition();
            $container->setDefinition(PalettesDefinitionInterface::NAME, $palettesDefinition);
        }

        $parser = new LegacyPalettesParser();

        $selectorFieldNames   = (array) $this->getFromDca('palettes/__selector__');
        $subPalettesDca       = (array) $this->getFromDca('metasubpalettes');
        $subSelectPalettesDca = (array) $this->getFromDca('metasubselectpalettes');

        // extend the selector field names with subpalettes field names
        $selectorFieldNames = array_merge(
            $selectorFieldNames,
            array_keys($subPalettesDca)
        );

        $subSelectPalettes = $this->parseSubSelectPalettes($subSelectPalettesDca);
        $subPalettes       = $this->parseSubPalettes($parser, $subPalettesDca, $selectorFieldNames);
        $interpreter       = new PalettesDefinitionInterpreter(
            $palettesDefinition,
            $parser,
            $selectorFieldNames,
            $subPalettes,
            $subSelectPalettes
        );

        $this->metaPalettesParser->parse($container->getName(), $this->dca, $interpreter);

        $palettes = $interpreter->getPalettes();
        if (empty($palettes)) {
            return;
        }

        $palettesDefinition->addPalettes($palettes);
    }

    /**
     * Parse the subpalettes.
     *
     * @param LegacyPalettesParser $parser             Legacy palette parser.
     * @param array                $subPalettesDca     Sub palettes dca.
     * @param array                $selectorFieldNames List of the selector fields.
     *
     * @return array
     *
     * @throws \InvalidArgumentException When an invalid property name is given.
     */
    protected function parseSubPalettes(
        LegacyPalettesParser $parser,
        array $subPalettesDca,
        array $selectorFieldNames
    ) {
        $subPalettes = [];

        foreach ($subPalettesDca as $selector => $propertyNames) {
            $properties = [];

            foreach ($propertyNames as $propertyName) {
                $this->guardValidPropertyName($propertyName);

                $and = new PropertyConditionChain();
                $and->addCondition(new PropertyTrueCondition($selector));
                $and->addCondition(new PropertyVisibleCondition($selector));

                $property = new Property($propertyName);
                $property->setVisibleCondition($and);
                $properties[] = $property;
            }

            if (count($properties)) {
                $selectorPropertyName = $parser->createSubpaletteSelectorFieldName(
                    $selector,
                    $selectorFieldNames
                );

                $subPalettes[$selectorPropertyName] = $properties;
            }
        }

        return $subPalettes;
    }

    /**
     * Parse the sub select palettes into a list of properties and set the corresponding condition.
     *
     * @param array $subSelectPalettesDca The sub select palettes.
     *
     * @return array
     *
     * @throws \InvalidArgumentException When an invalid subselect palette definition is given.
     */
    protected function parseSubSelectPalettes(array $subSelectPalettesDca)
    {
        $subSelectPalettes = [];

        foreach ($subSelectPalettesDca as $selectPropertyName => $valuePropertyNames) {
            $properties = [];

            foreach ($valuePropertyNames as $value => $propertyNames) {
                if ($value[0] == '!') {
                    $negate = true;
                    $value  = substr($value, 1);
                } else {
                    $negate = false;
                }

                $condition = new PropertyValueCondition($selectPropertyName, $value);

                if ($negate) {
                    $condition = new NotCondition($condition);
                }

                $and = new PropertyConditionChain();
                $and->addCondition($condition);
                $and->addCondition(new PropertyVisibleCondition($selectPropertyName));

                foreach ($propertyNames as $key => $propertyName) {
                    // Check if it is a legend information, if so add it to that one - use the empty legend name
                    // otherwise.
                    if (is_array($propertyName)) {
                        foreach ($propertyName as $propName) {
                            $property = new Property($propName);
                            $property->setVisibleCondition(clone $and);
                            $properties[$key][] = $property;
                        }
                    } else {
                        $property = new Property($propertyName);
                        $property->setVisibleCondition(clone $and);
                        $properties[''][] = $property;
                    }
                }
            }

            if (count($properties)) {
                $subSelectPalettes[$selectPropertyName] = $properties;
            }
        }

        return $subSelectPalettes;
    }

    /**
     * Guard that the property name is valid.
     *
     * @param mixed $propertyName Given value.
     *
     * @return void
     *
     * @throws \InvalidArgumentException When property name is no string.
     */
    protected function guardValidPropertyName($propertyName)
    {
        // Check if it is a valid property name.
        if (!is_string($propertyName)) {
            throw new \InvalidArgumentException(
                'Invalid property name in sub palette: ' . var_export($propertyName, true)
            );
        }
    }
}
