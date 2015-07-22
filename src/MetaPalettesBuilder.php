<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @link      https://github.com/bit3/contao-meta-palettes SCM
 * @copyright 2013 bit3 UG
 * @author    Tristan Lins <tristan.lins@bit3.de>
 * @package   MetaPalettes
 * @license   LGPL-3.0+
 */

namespace Bit3\Contao\MetaPalettes;

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
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Legend;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\LegendInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Palette;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Property;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\PropertyInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;

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
     * Build a data definition and store it into the environments container.
     *
     * @param \ContaoCommunityAlliance\DcGeneral\DataDefinition\ContainerInterface $container
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
        $palettesDca          = (array) $this->getFromDca('metapalettes');
        $subPalettesDca       = (array) $this->getFromDca('metasubpalettes');
        $subSelectPalettesDca = (array) $this->getFromDca('metasubselectpalettes');

        // extend the selector field names with subpalettes field names
        $selectorFieldNames = array_merge(
            $selectorFieldNames,
            array_keys($subPalettesDca)
        );

        $subSelectPalettes = $this->parseSubSelectPalettes($subSelectPalettesDca);
        $subPalettes       = $this->parseSubPalettes($parser, $subPalettesDca, $selectorFieldNames);
        $palettes          = $this->parsePalettes(
            $palettesDefinition,
            $parser,
            $palettesDca,
            $subPalettes,
            $subSelectPalettes,
            $selectorFieldNames
        );

        if (empty($palettes)) {
            return;
        }

        $palettesDefinition->addPalettes($palettes);
    }

    protected function parsePalettes(
        PalettesDefinitionInterface $palettesDefinition,
        LegacyPalettesParser $parser,
        array $palettesDca,
        array $subPalettes,
        array $subSelectPalettes,
        array $selectorFieldNames
    ) {
        $palettes = array();

        if (is_array($palettesDca)) {
            foreach ($palettesDca as $selector => $legendPropertyNames) {
                if (preg_match('#^(\w+) extends (\w+)$#', $selector, $matches)) {
                    $parentSelector = $matches[2];
                    $selector       = $matches[1];

                    if (isset($palettes[$parentSelector])) {
                        $palette = clone $palettes[$parentSelector];
                        $palette->setName($selector);
                    } else {
                        if ($palettesDefinition->hasPaletteByName($parentSelector)) {
                            $palette = clone $palettesDefinition->getPaletteByName($parentSelector);
                        } else {
                            $palette = null;
                        }
                    }

                    if (!$palette) {
                        throw new \RuntimeException('Parent palette ' . $parentSelector . ' does not exists');
                    }

                    // We MUST NOT retain the DefaultPaletteCondition.
                    $palette->setCondition($parser->createPaletteCondition($selector, $selectorFieldNames));

                    $extended = true;
                } else {
                    $palette = new Palette();
                    $palette->setName($selector);
                    $palette->setCondition($parser->createPaletteCondition($selector, $selectorFieldNames));
                    $extended = false;
                }

                $paletteCallbacks = array();

                foreach ($legendPropertyNames as $legendName => $propertyNames) {
                    if ($propertyNames instanceof \Closure) {
                        $paletteCallbacks[] = $propertyNames;
                        continue;
                    }

                    $additive      = false;
                    $subtractive   = false;
                    $insert        = false;
                    $refLegendName = null;

                    if ($extended) {
                        // add properties to existing legend
                        if ($legendName[0] == '+') {
                            $additive   = true;
                            $legendName = substr($legendName, 1);
                        } else {
                            // subtract properties from existing legend
                            if ($legendName[0] == '-') {
                                $subtractive = true;
                                $legendName  = substr($legendName, 1);
                            }
                        }

                        if (preg_match('#^(\w+) (before|after) (\w+)$#', $legendName, $matches)) {
                            $legendName    = $matches[1];
                            $insert        = $matches[2];
                            $refLegendName = $matches[3];
                        }
                    }

                    if ($palette->hasLegend($legendName)) {
                        $legend = $palette->getLegend($legendName);
                    } else {
                        $legend = new Legend($legendName);

                        // insert a legend before or after another one
                        if ($insert) {
                            $existingLegends = $palette->getLegends();
                            $refLegend       = null;

                            // search the referenced legend
                            /** @var LegendInterface $existingLegend */
                            reset($existingLegends);
                            while ($existingLegend = next($existingLegends)) {
                                if ($existingLegend->getName() == $refLegendName) {
                                    if ($insert == 'after') {
                                        // if insert after, get to next
                                        $refLegend = next($existingLegends);
                                    } else {
                                        $refLegend = $existingLegend;
                                    }
                                    break;
                                }
                            }

                            $palette->addLegend($legend, $refLegend);
                        } else {
                            // just append the legend
                            $palette->addLegend($legend);
                        }
                    }

                    // if extend a palette, but not add or remove fields, clear the legend but only when we have fields.
                    if ($extended && !($additive || $subtractive) && count($propertyNames)) {
                        $legend->clearProperties();
                    }

                    $legendCallbacks = array();

                    foreach ($propertyNames as $propertyName) {
                        if ($propertyName instanceof \Closure) {
                            $legendCallbacks[] = $propertyName;
                            continue;
                        }

                        if ($propertyName[0] == ':') {
                            // skip modifiers
                            continue;
                        }

                        if ($additive || $subtractive) {
                            $action          = $additive ? 'add' : 'sub';
                            $insert          = false;
                            $refPropertyName = null;

                            if ($propertyName[0] == '+') {
                                $action       = 'add';
                                $propertyName = substr($propertyName, 1);
                            } else {
                                if ($propertyName[0] == '-') {
                                    $action       = 'sub';
                                    $propertyName = substr($propertyName, 1);
                                }
                            }

                            if (preg_match('#^(\w+) (before|after) (\w+)$#', $propertyName, $matches)) {
                                $propertyName    = $matches[1];
                                $insert          = $matches[2];
                                $refPropertyName = $matches[3];
                            }

                            if ($action == 'add') {
                                $property = new Property($propertyName);

                                if ($insert) {
                                    $existingProperties = $legend->getProperties();
                                    $refProperty        = null;

                                    reset($existingProperties);
                                    $existingProperty = current($existingProperties);
                                    /** @var PropertyInterface $existingProperty */
                                    while ($existingProperty !== false) {
                                        if ($existingProperty->getName() == $refPropertyName) {
                                            if ($insert == 'after') {
                                                $refProperty = next($existingProperties);

                                                if ($refProperty === false) {
                                                    $refProperty = null;
                                                }
                                            } else {
                                                $refProperty = $existingProperty;
                                            }

                                            break;
                                        }

                                        $existingProperty = next($existingProperties);
                                    }

                                    $legend->addProperty($property, $refProperty);
                                } else {
                                    $legend->addProperty($property);
                                }
                            } else {
                                /** @var PropertyInterface $property */
                                foreach ($legend->getProperties() as $property) {
                                    if ($property->getName() == $propertyName) {
                                        $legend->removeProperty($property);
                                        break;
                                    }
                                }
                            }
                        } else {
                            $property = new Property($propertyName);
                            $legend->addProperty($property);
                        }

                        // add sub palette properties
                        if (isset($subPalettes[$propertyName])) {
                            $legend->addProperties($subPalettes[$propertyName]);
                        }

                        // add sub select properties for unspecified legend names.
                        $this->addSubSelectProperties($legend, $subSelectPalettes, $propertyName);
                    }

                    foreach ($legendCallbacks as $callback) {
                        call_user_func($callback, $legendName, $legend, $palette, $palettesDefinition);
                    }
                }

                foreach ($paletteCallbacks as $callback) {
                    call_user_func($callback, $palette, $palettesDefinition);
                }

                $palettes[$selector] = $palette;
            }
        }

        // now add sub select properties that are for specific legend names.
        foreach ($subSelectPalettes as $propertyName => $legendInformation) {
            $subpaletteCallbacks = array();

            foreach ($legendInformation as $properties) {
                if ($properties instanceof \Closure) {
                    $subpaletteCallbacks[] = $properties;
                }
            }

            foreach ($legendInformation as $fullLegendName => $properties) {
                if ($fullLegendName === '') {
                    continue;
                }

                foreach ($palettes as $palette) {
                    if (preg_match('#^(\w+) (before|after) (\w+)$#', $fullLegendName, $matches)) {
                        $legendName = $matches[1];
                        $insert     = $matches[2];
                        $refName    = $matches[3];
                    } else {
                        $legendName = $fullLegendName;
                        $insert     = '';
                        $refName    = null;
                    }

                    /** @var Palette $palette */
                    if (!$palette->hasLegend($legendName)) {
                        $palette->addLegend(new Legend($legendName));
                    }

                    /** @var Legend $legend */
                    $legend = $palette->getLegend($legendName);
                    $this->addSubSelectProperties(
                        $legend,
                        $subSelectPalettes,
                        $propertyName,
                        $fullLegendName,
                        $insert,
                        $refName
                    );

                    foreach ($subpaletteCallbacks as $callback) {
                        call_user_func($callback, $legendName, $properties, $legend, $palette, $palettesDefinition);
                    }
                }
            }
        }

        return $palettes;
    }

    /**
     * Recursively add all subselect properties to their parent. Even when they are member of a subselect themselves.
     *
     * @param Legend $legend            The legend to add the properties to.
     *
     * @param array  $subSelectPalettes All subselect palettes.
     *
     * @param string $propertyName      The name of the property for which all dependant properties shall get added.
     *
     * @param string $legendName        The name of the legend for which properties shall get retrieved.
     */
    protected function addSubSelectProperties(
        Legend $legend,
        $subSelectPalettes,
        $propertyName,
        $legendName = '',
        $insert = 'before',
        $reference = null
    ) {
        if (!isset($subSelectPalettes[$propertyName][$legendName])) {
            return;
        }

        $position = null;
        if ($insert && $reference) {
            $properties = $legend->getProperties();
            if (!empty($properties)) {
                $property   = current($properties);
                do {
                    if ($property->getName() == $reference) {
                        if ($insert == 'before') {
                            $position = $property;
                        } elseif ($insert == 'after') {
                            $position = next($properties);
                        }
                        break;
                    }
                } while ($property = next($properties));
            }
        }

        if ($position === false) {
            $position = null;
        }

        $legend->addProperties($subSelectPalettes[$propertyName][$legendName], $position);
        foreach ((array) $subSelectPalettes[$propertyName][$legendName] as $property) {
            /** @var Property $property */
            // Add anonymous legends for this property.
            if (isset($subSelectPalettes[$property->getName()][''])) {
                $legend->addProperties($subSelectPalettes[$property->getName()]['']);
            }
            if (isset($subSelectPalettes[$property->getName()][$legendName])) {
                $this->addSubSelectProperties(
                    $legend,
                    $subSelectPalettes,
                    $property->getName(),
                    $legendName,
                    $insert,
                    $reference
                );
            }
        }
    }

    protected function parseSubPalettes(
        LegacyPalettesParser $parser,
        array $subPalettesDca,
        array $selectorFieldNames
    ) {
        $subPalettes = array();

        if (is_array($subPalettesDca)) {
            foreach ($subPalettesDca as $selector => $propertyNames) {
                $properties = array();

                foreach ($propertyNames as $propertyName) {

                    // Check if it is a valid property name.
                    if (!is_string($propertyName)) {
                        throw new \InvalidArgumentException(
                            'Invalid property name in sub palette: ' . var_export($propertyName, true)
                        );
                    }

                    $and = new PropertyConditionChain();
                    $and->addCondition(new PropertyTrueCondition($selector));
                    $and->addCondition(new PropertyVisibleCondition($selector));

                    $property = new Property($propertyName);
                    $property->setVisibleCondition($and);
                    $properties[] = $property;
                }

                if (count($properties)) {
                    $selectorPropertyName               = $parser->createSubpaletteSelectorFieldName(
                        $selector,
                        $selectorFieldNames
                    );
                    $subPalettes[$selectorPropertyName] = $properties;
                }
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
     * @throws \InvalidArgumentException
     */
    protected function parseSubSelectPalettes(array $subSelectPalettesDca)
    {
        $subSelectPalettes = array();

        if (is_array($subSelectPalettesDca)) {
            foreach ($subSelectPalettesDca as $selectPropertyName => $valuePropertyNames) {
                $properties = array();

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
        }

        return $subSelectPalettes;
    }
}
