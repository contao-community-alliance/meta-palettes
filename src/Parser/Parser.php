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
 * Parser describes.
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Parser
 */
interface Parser
{
    const POSITION_AFTER  = 'after';
    const POSITION_BEFORE = 'before';

    const MODE_ADD      = 'add';
    const MODE_REMOVE   = 'remove';
    const MODE_OVERRIDE = 'override';

    /**
     * Parse a meta palettes definition.
     *
     * @param string      $tableName   Name of the data container table.
     * @param array       $definition  Definition array.
     * @param Interpreter $interpreter Interpreter which converts the definition.
     *
     * @return bool
     */
    public function parse($tableName, array $definition, Interpreter $interpreter);

    /**
     * Parse a palette.
     *
     * @param string      $tableName   Name of the data container table.
     * @param string      $paletteName Table name.
     * @param Interpreter $interpreter Interpreter which converts the definition.
     * @param bool        $base        If true palette is parsed as a parent palette.
     *
     * @return void
     *
     * @throws \InvalidArgumentException When meta palette definition does not exist.
     *
     * @internal
     */
    public function parsePalette($tableName, $paletteName, Interpreter $interpreter, $base = false);
}
