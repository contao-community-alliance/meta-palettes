<?php

/**
 * @package    meta-palettes
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2017 netzmacht David Molineus. All rights reserved.
 * @filesource
 *
 */


namespace ContaoCommunityAlliance\MetaPalettes\Parser\MetaPalette;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
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
    public function start($tableName, $paletteName)
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
    public function addLegend($name, $override, $hide)
    {
        if (!isset($this->definition[$name])) {
            $this->definition[$name] = [
                'fields' => [],
                'hide'   => (bool) $hide,
            ];

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
            unset ($this->definition[$legend]['fields'][$position]);
            $this->definition[$legend]['fields'] = array_values($this->definition[$legend]['fields']);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function finish()
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
}
