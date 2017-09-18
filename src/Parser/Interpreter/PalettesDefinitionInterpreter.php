<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2017 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/contao-community-alliance/meta-palettes
 */

namespace ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter;

use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Palette\LegacyPalettesParser;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyConditionChain;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyTrueCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyVisibleCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Legend;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\LegendInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Palette;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\PaletteInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Property;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\PropertyInterface;
use ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter;
use ContaoCommunityAlliance\MetaPalettes\Parser\Parser;

/**
 * Interpreter class creating the palettes definition used by the DC General.
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter
 */
class PalettesDefinitionInterpreter implements Interpreter
{
    /**
     * Palettes definition.
     *
     * @var PalettesDefinitionInterface
     */
    private $palettesDefinition;

    /**
     * Current palette.
     *
     * Palette.
     *
     * @var PaletteInterface
     */
    private $palette;

    /**
     * Legacy palettes parser.
     *
     * @var LegacyPalettesParser
     */
    private $legacyPalettesParser;

    /**
     * Selector field names.
     *
     * @var array
     */
    private $selectorFieldNames;

    /**
     * List of built palettes.
     *
     * @var Palette[]
     */
    private $palettes = [];

    /**
     * Sub palettes.
     *
     * @var array
     */
    private $subPalettes;

    /**
     * Sub select palettes.
     *
     * @var array
     */
    private $subSelectPalettes;

    /**
     * Table name.
     *
     * @var string
     */
    private $tableName;

    /**
     * DcGeneralInterpreter constructor.
     *
     * @param PalettesDefinitionInterface $palettesDefinition   Palettes definition.
     * @param LegacyPalettesParser        $legacyPalettesParser Legacy palettes parser.
     * @param array                       $selectorFieldNames   Selector field names.
     * @param array                       $subPalettes          Sub palettes.
     * @param array                       $subSelectPalettes    Sub select palettes.
     */
    public function __construct(
        PalettesDefinitionInterface $palettesDefinition,
        LegacyPalettesParser $legacyPalettesParser,
        array $selectorFieldNames,
        array $subPalettes,
        array $subSelectPalettes
    ) {
        $this->palettesDefinition   = $palettesDefinition;
        $this->legacyPalettesParser = $legacyPalettesParser;
        $this->selectorFieldNames   = $selectorFieldNames;
        $this->subPalettes          = $subPalettes;
        $this->subSelectPalettes    = $subSelectPalettes;
    }

    /**
     * {@inheritDoc}
     */
    public function startPalette($tableName, $paletteName)
    {
        $this->tableName = $tableName;
        $this->palette   = null;
        $this->palette   = new Palette();
        $this->palette->setName($paletteName);
    }

    /**
     * {@inheritDoc}
     */
    public function inherit($parent, Parser $parser)
    {
        $parser->parsePalette($this->tableName, $parent, $this);
    }

    /**
     * {@inheritDoc}
     */
    public function addLegend($name, $override, $hide, $position = null, $reference = null)
    {
        if ($this->palette->hasLegend($name)) {
            $legend = $this->palette->getLegend($name);

            if ($override) {
                $this->palette->removeLegend($legend);
            } elseif ($hide !== null) {
                $legend->setInitialVisibility(!$hide);
            }

            if (!$override) {
                return;
            }
        }

        $legend = new Legend($name);
        $legend->setInitialVisibility(!$hide);

        if (!$reference) {
            $this->palette->addLegend($legend);

            return;
        }

        $existingLegends = $this->palette->getLegends();
        $refLegend       = null;

        // search the referenced legend
        /** @var LegendInterface $existingLegend */
        reset($existingLegends);
        while ($existingLegend = next($existingLegends)) {
            if ($existingLegend->getName() === $reference) {
                if ($position == Parser::POSITION_AFTER) {
                    // if insert after, get to next
                    $refLegend = next($existingLegends);
                } else {
                    $refLegend = $existingLegend;
                }
                break;
            }
        }

        $this->palette->addLegend($legend, $refLegend);
    }

    /**
     * {@inheritDoc}
     */
    public function addFieldTo($legendName, $name, $position = null, $reference = null)
    {
        $property = new Property($name);
        $legend   = $this->palette->getLegend($legendName);

        if ($reference) {
            $existingProperties = $legend->getProperties();
            $refProperty        = null;

            reset($existingProperties);
            $existingProperty = current($existingProperties);

            /** @var PropertyInterface $existingProperty */
            while ($existingProperty !== false) {
                if ($existingProperty->getName() === $reference) {
                    if ($position === Parser::POSITION_AFTER) {
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

        // add sub select properties for unspecified legend names.
        $this->addSubSelectProperties($legend, $name);
    }

    /**
     * {@inheritDoc}
     */
    public function removeFieldFrom($legend, $name)
    {
        if (!$this->palette->hasLegend($legend)) {
            return;
        }

        $legend = $this->palette->getLegend($legend);
        if ($legend->hasProperty($name)) {
            $property = $legend->getProperty($name);
            $legend->removeProperty($property);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function finishPalette()
    {
        $this->palettes[] = $this->palette;
        $this->palette->setCondition(
            $this->legacyPalettesParser->createPaletteCondition($this->palette->getName(), $this->selectorFieldNames)
        );

        $this->applySubSelectPalettes();
    }

    /**
     * Add a sub palette.
     *
     * @param string $tableName Table name.
     * @param string $name      Sub palette name.
     * @param array  $fields    List of fields.
     *
     * @return void
     */
    public function addSubPalette($tableName, $name, array $fields)
    {
        $properties = [];

        foreach ($fields as $field) {
            $this->guardValidPropertyName($field);

            $and = new PropertyConditionChain();
            $and->addCondition(new PropertyTrueCondition($name));
            $and->addCondition(new PropertyVisibleCondition($name));

            $property = new Property($field);
            $property->setVisibleCondition($and);
            $properties[] = $property;
        }

        if (count($properties)) {
            $selectorPropertyName = $this->legacyPalettesParser->createSubpaletteSelectorFieldName(
                $name,
                $this->selectorFieldNames
            );

            foreach ($this->palettes as $palette) {
                /** @var LegendInterface $legend */
                foreach ($palette->getLegends() as $legend) {
                    if ($legend->hasProperty($selectorPropertyName)) {
                        $legend->addProperties($properties);
                    }
                }
            }
        }
    }

    /**
     * Get all generated palettes.
     *
     * @return array
     */
    public function getPalettes()
    {
        return $this->palettes;
    }

    /**
     * Recursively add all subselect properties to their parent. Even when they are member of a subselect themselves.
     *
     * @param LegendInterface $legend       The legend to add the properties to.
     * @param string          $propertyName The name of the property for which all dependant properties shall get added.
     * @param string          $legendName   The name of the legend for which properties shall get retrieved.
     * @param string          $insert       Position where to insert the properties.
     * @param string|null     $reference    Reference.
     *
     * @return void
     */
    protected function addSubSelectProperties(
        LegendInterface $legend,
        $propertyName,
        $legendName = '',
        $insert = 'before',
        $reference = null
    ) {
        if (!isset($this->subSelectPalettes[$propertyName][$legendName])) {
            return;
        }

        $position = $this->calculateInsertPosition($legend, $insert, $reference);
        if ($position === false) {
            $position = null;
        }

        $legend->addProperties($this->subSelectPalettes[$propertyName][$legendName], $position);
        foreach ((array) $this->subSelectPalettes[$propertyName][$legendName] as $property) {
            /** @var Property $property */
            // Add anonymous legends for this property.
            if (isset($this->subSelectPalettes[$property->getName()][''])) {
                $legend->addProperties($this->subSelectPalettes[$property->getName()]['']);
            }
            if (isset($this->subSelectPalettes[$property->getName()][$legendName])) {
                $this->addSubSelectProperties(
                    $legend,
                    $property->getName(),
                    $legendName,
                    $insert,
                    $reference
                );
            }
        }
    }

    /**
     * Calculate the insert position.
     *
     * @param LegendInterface $legend    Legend definition.
     * @param string          $insert    Insert mode.
     * @param string          $reference Reference column.
     *
     * @return int|null|false
     */
    protected function calculateInsertPosition(LegendInterface $legend, $insert, $reference)
    {
        $position = null;

        if ($insert && $reference) {
            $properties = $legend->getProperties();
            if (!empty($properties)) {
                $property = current($properties);

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

        return $position;
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

    /**
     * Translate a sub palette selector into the real name of a property.
     *
     * This method supports the following cases for the sub palette selector:
     *
     * Case 1: the sub palette selector contain a combination of "property name" + '_' + value
     *         in which we require that the "property name" is contained within $selectorFieldNames.
     *         In this cases a select/radio sub palette is in place.
     *
     * Case 2: the sub palette selector is only a "property name", the value is then implicated to be true.
     *         In this cases a checkbox sub palette is in place.
     *
     * @param string $subPaletteSelector The selector being evaluated.
     *
     * @param array  $selectorFieldNames The names of the properties to be used as selectors [optional].
     *
     * @return string
     */
    public function createSubPaletteSelectorFieldName($subPaletteSelector, array $selectorFieldNames = [])
    {
        $selectorValues     = explode('_', $subPaletteSelector);
        $selectorFieldName  = array_shift($selectorValues);
        $selectorValueCount = count($selectorValues);
        while ($selectorValueCount) {
            if (in_array($selectorFieldName, $selectorFieldNames)) {
                break;
            }
            $selectorFieldName .= '_' . array_shift($selectorValues);
            $selectorValueCount = count($selectorValues);
        }

        return $selectorFieldName;
    }

    /**
     * Apply sub select palettes.
     *
     * @return void
     */
    private function applySubSelectPalettes()
    {
        // now add sub select properties that are for specific legend names.
        foreach ($this->subSelectPalettes as $propertyName => $legendInformation) {
            $subPaletteCallbacks = $this->getSubPalettesCallbacks($legendInformation);

            foreach ($legendInformation as $fullLegendName => $properties) {
                if ($fullLegendName === '') {
                    continue;
                }

                foreach ($this->palettes as $palette) {
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
                        $propertyName,
                        $fullLegendName,
                        $insert,
                        $refName
                    );

                    foreach ($subPaletteCallbacks as $callback) {
                        call_user_func(
                            $callback,
                            $legendName,
                            $properties,
                            $legend,
                            $palette,
                            $this->palettesDefinition
                        );
                    }
                }
            }
        }
    }

    /**
     * Get sub palette callbacks from the legend information.
     *
     * @param array $legendInformation Legend information.
     *
     * @return array
     */
    private function getSubPalettesCallbacks($legendInformation)
    {
        $subPaletteCallbacks = [];

        foreach ($legendInformation as $properties) {
            if ($properties instanceof \Closure) {
                $subPaletteCallbacks[] = $properties;
            }
        }

        return $subPaletteCallbacks;
    }
}
