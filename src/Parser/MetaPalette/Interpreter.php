<?php

/**
 * @package    meta-palettes
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2017 netzmacht David Molineus. All rights reserved.
 * @filesource
 *
 */


namespace ContaoCommunityAlliance\MetaPalettes\Parser\MetaPalette;

use ContaoCommunityAlliance\MetaPalettes\Parser\MetaPaletteParser;

/**
 * Interface Interpreter
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter
 */
interface Interpreter
{
    /**
     * Create a new palette.
     *
     * @param string $tableName   Table name.
     * @param string $paletteName Palette name.
     *
     * @return void
     */
    public function start($tableName, $paletteName);

    /**
     * Inherit from a parent palette.
     *
     * @param string            $parent Parent palette name.
     * @param MetaPaletteParser $parser Parser reference.
     *
     * @return void
     */
    public function inherit($parent, MetaPaletteParser $parser);

    /**
     * Add a new legend.
     *
     * @param string    $name     Legend name.
     * @param bool      $override If true existing fields get overridden.
     * @param bool|null $hide     If true the hide flag is set. Null means inherit from parent.
     *
     * @return void
     */
    public function addLegend($name, $override, $hide);

    /**
     * Add a field to a legend.
     *
     * @param string $legend    Legend name.
     * @param string $name      Field name.
     * @param string $position  Position. Valid values are MetaPalettesParser::POSITION_BEFORE or
     *                          MetaPalettesParser::POSITION_AFTER
     * @param string $reference Reference field for inserting at a position.
     *
     * @return void
     */
    public function addFieldTo($legend, $name, $position = null, $reference = null);

    /**
     * Remove a field from a legend.
     *
     * @param string $legend    Legend name.
     * @param string $name      Field name.
     *
     * @return void
     */
    public function removeFieldFrom($legend, $name);

    /**
     * Finish is called when a palette is finished.
     *
     * @return void
     */
    public function finish();
}
