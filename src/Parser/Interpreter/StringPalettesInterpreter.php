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

namespace ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter;
use ContaoCommunityAlliance\MetaPalettes\Parser\MetaPaletteParser;

/**
 * The StringPalettesInterpreter converts the meta palette into the string representation used in Contao.
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter
 */
class StringPalettesInterpreter implements Interpreter
{
    /**
     * Name of the data container table.
     *
     * @var string
     */
    private $tableName;

    /**
     * Name of the palette.
     *
     * @var string
     */
    private $paletteName;

    /**
     * Palette definition.
     *
     * @var array
     */
    private $definition = [];

    /**
     * {@inheritdoc}
     */
    public function startPalette($tableName, $paletteName)
    {
        $this->tableName   = $tableName;
        $this->paletteName = $paletteName;
    }

    /**
     * {@inheritdoc}
     */
    public function inherit($parent, MetaPaletteParser $parser)
    {
        $parser->parsePalette($this->tableName, $parent, $this, true);
    }

    /**
     * {@inheritdoc}
     */
    public function addLegend($name, $override, $hide, $position = null, $reference = null)
    {
        if (!isset($this->definition[$name])) {
            $legend = [
                'fields' => [],
                'hide'   => (bool) $hide,
            ];

            if ($reference) {
                $referencePosition = array_search($reference, array_keys($this->definition));

                if ($referencePosition !== false) {
                    if ($position === MetaPaletteParser::POSITION_AFTER) {
                        $referencePosition++;
                    }
                }

                $tail = array_splice($this->definition, $referencePosition);
                $this->definition += [$name => $legend] + $tail;
            }

            $this->definition[$name] = $legend;

            return;
        }

        if ($override) {
            $this->definition[$name]['fields'] = [];
        }

        if ($hide !== null) {
            $this->definition[$name]['hide'] = $hide;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addFieldTo($legend, $name, $position = null, $reference = null)
    {
        if (!isset($this->definition[$legend]['fields'])) {
            $this->definition[$legend]['fields'] = [];
        }

        if ($reference) {
            $referencePosition = array_search($reference, $this->definition[$legend]['fields']);

            if ($referencePosition !== false) {
                if ($position === MetaPaletteParser::POSITION_BEFORE) {
                    $referencePosition--;
                }

                array_splice($this->definition[$legend]['fields'], $referencePosition, 0, $name);

                return;
            }
        }

        $this->definition[$legend]['fields'][] = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function removeFieldFrom($legend, $name)
    {
        if (!isset($this->definition[$legend])) {
            return;
        }

        $position = array_search($name, $this->definition[$legend]['fields']);
        if ($position !== false) {
            unset($this->definition[$legend]['fields'][$position]);
            $this->definition[$legend]['fields'] = array_values($this->definition[$legend]['fields']);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function finishPalette()
    {
        $GLOBALS['TL_DCA'][$this->tableName]['palettes'][$this->paletteName] = '';

        $manipulator = PaletteManipulator::create();

        foreach ($this->definition as $legend => $config) {
            $manipulator->addLegend($legend . '_legend', null, $manipulator::POSITION_AFTER, $config['hide']);
            $manipulator->addField($config['fields'], $legend . '_legend', $manipulator::POSITION_APPEND);
        }

        $manipulator->applyToPalette($this->paletteName, $this->tableName);

        $this->tableName   = null;
        $this->paletteName = null;
        $this->definition  = [];
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function addSubPalette($tableName, $name, array $fields)
    {
        // only generate if not palette exists
        if (isset($GLOBALS['TL_DCA'][$tableName]['subpalettes'][$name])) {
            return;
        }

        // only generate if there are any fields
        if (count($fields) > 0) {
            $this->addSelector($tableName, $name);

            // set the palette
            $GLOBALS['TL_DCA'][$tableName]['subpalettes'][$name] = implode(',', $fields);
        }
    }

    /**
     * Add a selector.
     *
     * @param string $tableName Data container table name.
     * @param string $fieldName Palette selector field.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function addSelector($tableName, $fieldName)
    {
        // generate subpalettes selectors
        if (!isset($GLOBALS['TL_DCA'][$tableName]['palettes']['__selector__']) ||
            !is_array($GLOBALS['TL_DCA'][$tableName]['palettes']['__selector__'])) {
            $GLOBALS['TL_DCA'][$tableName]['palettes']['__selector__'] = [$fieldName];
        } elseif (!in_array($fieldName, $GLOBALS['TL_DCA'][$tableName]['palettes']['__selector__'])) {
            $GLOBALS['TL_DCA'][$tableName]['palettes']['__selector__'][] = $fieldName;
        }
    }
}
