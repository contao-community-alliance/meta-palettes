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

namespace ContaoCommunityAlliance\MetaPalettes\Parser;

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
    public function startPalette($tableName, $paletteName);

    /**
     * Inherit from a parent palette.
     *
     * @param string $parent Parent palette name.
     * @param Parser $parser Parser reference.
     *
     * @return void
     */
    public function inherit($parent, Parser $parser);

    /**
     * Add a new legend.
     *
     * @param string    $name      Legend name.
     * @param bool      $override  If true existing fields get overridden.
     * @param bool|null $hide      If true the hide flag is set. Null means inherit from parent.
     * @param string    $position  Position. Valid values are MetaPalettesParser::POSITION_BEFORE or
     *                             MetaPalettesParser::POSITION_AFTER.
     * @param string    $reference Reference legend for inserting at a position.
     *
     * @return void
     */
    public function addLegend($name, $override, $hide, $position = null, $reference = null);

    /**
     * Add a field to a legend.
     *
     * @param string $legend    Legend name.
     * @param string $name      Field name.
     * @param string $position  Position. Valid values are MetaPalettesParser::POSITION_BEFORE or
     *                          MetaPalettesParser::POSITION_AFTER.
     * @param string $reference Reference field for inserting at a position.
     *
     * @return void
     */
    public function addFieldTo($legend, $name, $position = null, $reference = null);

    /**
     * Remove a field from a legend.
     *
     * @param string $legend Legend name.
     * @param string $name   Field name.
     *
     * @return void
     */
    public function removeFieldFrom($legend, $name);

    /**
     * Finish is called when a palette is finished.
     *
     * @return void
     */
    public function finishPalette();

    /**
     * Add a sub palette.
     *
     * @param string $tableName Table name.
     * @param string $name      Sub palette name.
     * @param array  $fields    List of fields.
     *
     * @return void
     */
    public function addSubPalette($tableName, $name, array $fields);
}
