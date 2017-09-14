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
 * Class MetaSubPalettesParser
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Parser
 */
class MetaSubPalettesParser implements Parser
{
    /**
     * {@inheritdoc}
     */
    public function parse($tableName, array $definition, Interpreter $interpreter)
    {
        // walk over the meta palette
        foreach ($definition as $palette => $fields) {
            if (is_array($fields)) {
                $interpreter->addSubPalette($tableName, $palette, $fields);
            }
        }

        return true;
    }
}
