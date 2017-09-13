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

use Contao\CoreBundle\DataContainer\PaletteManipulator;

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

        $manipulator = PaletteManipulator::create();

        foreach ($varArg2 as $legend => $fields) {
            $legend .= '_legend';

            $manipulator->addLegend($legend, null);
            $manipulator->addField($fields, $legend);
        }

        $manipulator->applyToPalette($varArg1, $strTable);
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

        $manipulator  = PaletteManipulator::create();
        $varArg2     .= '_legend';

        foreach ($varArg3 as $legend => $fields) {
            $legend .= '_legend';

            $manipulator->addLegend($legend, $varArg2, $manipulator::POSITION_BEFORE);
            $manipulator->addField($fields, $legend, $manipulator::POSITION_APPEND);
        }

        $manipulator->applyToPalette($varArg1, $strTable);
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

        $manipulator  = PaletteManipulator::create();
        $varArg2     .= '_legend';

        foreach ($varArg3 as $legend => $fields) {
            $legend .= '_legend';

            $manipulator->addLegend($legend, $varArg2, $manipulator::POSITION_APPEND);
            $manipulator->addField($fields, $legend, $manipulator::POSITION_APPEND);
        }

        $manipulator->applyToPalette($varArg1, $strTable);
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

        PaletteManipulator::create()
            ->addField($varArg3, $varArg2 . '_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette($varArg1, $strTable);
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

        PaletteManipulator::create()
            ->addField($varArg3, $varArg2 . '_legend', PaletteManipulator::POSITION_PREPEND)
            ->applyToPalette($varArg1, $strTable);
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
