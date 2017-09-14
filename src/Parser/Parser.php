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
}
