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
 * Class DataContainerParser parses all Metapalettes related content from the data container definition.
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Parser
 */
class DataContainerParser implements Parser
{
    /**
     * Meta palettes parser.
     *
     * @var MetaPaletteParser
     */
    private $palettesParser;

    /**
     * Sub palettes parser.
     *
     * @var MetaSubPalettesParser
     */
    private $subPalettesParser;

    /**
     * DataContainerParser constructor.
     *
     * @param MetaPaletteParser     $palettesParser    Meta palettes parser.
     * @param MetaSubPalettesParser $subPalettesParser Sub palettes parser.
     */
    public function __construct(MetaPaletteParser $palettesParser, MetaSubPalettesParser $subPalettesParser)
    {
        $this->palettesParser    = $palettesParser;
        $this->subPalettesParser = $subPalettesParser;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($tableName, array $definition, Interpreter $interpreter)
    {
        if (isset($definition['metapalettes'])) {
            $this->palettesParser->parse($tableName, $definition['metapalettes'], $interpreter);
        }

        if (isset($definition['metasubpalettes'])) {
            $this->subPalettesParser->parse($tableName, $definition['metasubpalettes'], $interpreter);
        }
    }
}
