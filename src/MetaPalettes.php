<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @link      https://github.com/bit3/contao-meta-palettes
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2017 Contao Community Alliance.
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @author    Tristan Lins <tristan.lins@bit3.de>
 * @package   MetaPalettes
 * @license   LGPL-3.0+
 */

namespace ContaoCommunityAlliance\MetaPalettes;

/**
 * Generates the palettes from the meta information.
 */
class MetaPalettes
{
    /**
     * Dynamic append a meta palette definition to the dca.
     *
     * @static
     *
     * @param string $strTable
     * The table name.
     * @param mixed  $varArg1
     * The palette name or the meta definition. In last case, the meta will be appended to the default palette.
     * @param mixed  $varArg2
     * The meta definition, only needed if the palette name is given as second parameter.
     *
     * @return void
     */
    public static function appendTo($strTable, $varArg1, $varArg2 = null)
    {
        if (is_array($varArg1)) {
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1] .= ';' . self::generatePalette($varArg2);
    }

    /**
     * Dynamic append a meta palette definition to the dca, before a block.
     *
     * @static
     *
     * @param string $strTable
     * The table name.
     * @param mixed  $varArg1
     * The palette name or the legend name (without trailing _legend, e.a. title and NOT title_legend) the palette
     * should appended after. In last case, the meta will be appended to the default palette.
     * @param mixed  $varArg2
     * The legend name the palette should appended after or the meta definition.
     * @param mixed  $varArg3
     * The meta definition, only needed if the palette name is given as third parameter.
     *
     * @return void
     */
    public static function appendBefore($strTable, $varArg1, $varArg2, $varArg3 = null)
    {
        if (is_array($varArg2)) {
            $varArg3 = $varArg2;
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        $strRegexp = sprintf('#\{%s_legend(?::hide)?\}(.*?;|.*)#i', $varArg2);

        if (preg_match($strRegexp, $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1])) {
            $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1] = preg_replace(
                $strRegexp,
                sprintf('%s;$0', self::generatePalette($varArg3)),
                $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1]
            );
        } else {
            self::appendTo($strTable, $varArg1, $varArg3);
        }
    }

    /**
     * Dynamic append a meta palette definition to the dca, after a block.
     *
     * @static
     *
     * @param string $strTable
     * The table name.
     * @param mixed  $varArg1
     * The palette name or the legend name (without trailing _legend, e.a. title and NOT title_legend) the palette
     * should appended after. In last case, the meta will be appended to the default palette.
     * @param mixed  $varArg2
     * The legend name the palette should appended after or the meta definition.
     * @param mixed  $varArg3
     * The meta definition, only needed if the palette name is given as third parameter.
     *
     * @return void
     */
    public static function appendAfter($strTable, $varArg1, $varArg2, $varArg3 = null)
    {
        if (is_array($varArg2)) {
            $varArg3 = $varArg2;
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        $strRegexp = sprintf('#\{%s_legend(?::hide)?\}(.*?;|.*)#i', $varArg2);
        if (preg_match($strRegexp, $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1])) {
            $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1] = preg_replace(
                $strRegexp,
                sprintf('$0;%s', self::generatePalette($varArg3)),
                $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1]
            );
        } else {
            self::appendTo($strTable, $varArg1, $varArg3);
        }
    }

    /**
     * Dynamic append fields to a group in the palette definition.
     *
     * @static
     *
     * @param string $strTable
     * The table name.
     * @param mixed  $varArg1
     * The palette name or the legend name (without trailing _legend, e.a. title and NOT title_legend). In last case,
     * the meta will be appended to the default palette.
     * @param mixed  $varArg2
     * The legend name the fields should appended or the list of fields.
     * @param mixed  $varArg3
     * List of fields to append.
     *
     * @return void
     */
    public static function appendFields($strTable, $varArg1, $varArg2, $varArg3 = null)
    {
        if (is_array($varArg2)) {
            $varArg3 = $varArg2;
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        $strFields = implode(',', $varArg3);
        $strRegexp = sprintf('#(\{%s_legend(?::hide)?\})((.*?);|.*)#i', $varArg2);

        if (preg_match($strRegexp, $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1], $match)) {
            $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1] = preg_replace(
                $strRegexp,
                sprintf(isset($match[3]) ? '$1$3,%s;' : '$1$2,%s', $strFields),
                $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1]
            );
        } else {
            self::appendTo($strTable, $varArg1, array($varArg2 => $varArg3));
        }
    }

    /**
     * Dynamic prepend fields to a group in the palette definition.
     *
     * @static
     *
     * @param string $strTable
     * The table name.
     * @param mixed  $varArg1
     * The palette name or the legend name (without trailing _legend, e.a. title and NOT title_legend). In last case,
     * the meta will be appended to the default palette.
     * @param mixed  $varArg2
     * The legend name the fields should appended or the list of fields.
     * @param mixed  $varArg3
     * List of fields to append.
     *
     * @return void
     */
    public static function prependFields($strTable, $varArg1, $varArg2, $varArg3 = null)
    {
        if (is_array($varArg2)) {
            $varArg3 = $varArg2;
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        $strFields = implode(',', $varArg3);
        $strRegexp = sprintf('#(\{%s_legend(?::hide)?\})(.*);#Ui', $varArg2);

        if (preg_match($strRegexp, $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1])) {
            $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1] = preg_replace(
                $strRegexp,
                sprintf('$1,%s$2;', $strFields),
                $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1]
            );
        } else {
            self::appendTo($strTable, $varArg1, array($varArg2 => $varArg3));
        }
    }

    /**
     * Dynamic prepend fields to a group in the palette definition.
     *
     * @static
     *
     * @param string $strTable
     * The table name.
     * @param mixed  $varArg1
     * The palette name or the list of fields to remove. In last case, the fields will be removed from the default
     * palette.
     * @param mixed  $varArg2
     * List of fields to remove.
     *
     * @return void
     */
    public static function removeFields($strTable, $varArg1, $varArg2 = null)
    {
        if (is_array($varArg1)) {
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        $varArg2 = array_map(
            function ($item) {
                return preg_quote($item, '#');
            },
            $varArg2
        );

        $strRegexp = sprintf('#[,;](%s)([,;]|$)#Ui', implode('|', $varArg2));

        $strPalette = $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1];

        do {
            $strPalette = preg_replace($strRegexp, '$2', $strPalette, -1, $count);
        } while ($count);

        $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1] = $strPalette;
    }

    public static function generatePalette($arrMeta)
    {
        $arrBuffer = array();
        // walk over the chapters
        foreach ($arrMeta as $strLegend => $arrFields) {
            if (is_array($arrFields)) {
                // generate palettes legend
                $strBuffer = sprintf('{%s_legend%s},', $strLegend, in_array(':hide', $arrFields) ? ':hide' : '');

                // filter meta description (fields starting with ":")
                $arrFields = array_filter($arrFields, array(__CLASS__, 'filterFields'));

                // only generate chapter if there are any fields
                if (count($arrFields) > 0) {
                    $strBuffer .= implode(',', $arrFields);
                    $arrBuffer[] = $strBuffer;
                }
            }
        }

        return implode(';', $arrBuffer);
    }

    /**
     * Filter meta fields, starting with ":" from an array.
     *
     * @param $strField string
     *
     * @return bool
     */
    public static function filterFields($strField)
    {
        return $strField[0] != ':';
    }
}
