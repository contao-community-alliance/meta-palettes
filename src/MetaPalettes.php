<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @author    Tristan Lins <tristan.lins@bit3.de>
 * @author    Sven Baumann <baumann.sv@gmail.com>
 * @author    Ingolf Steinhardt <info@e-spin.de>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2022 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/bit3/contao-meta-palettes
 */

namespace ContaoCommunityAlliance\MetaPalettes;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use InvalidArgumentException;
use RuntimeException;
use function is_array;
use function is_string;

/**
 * Generates the palettes from the meta information.
 */
class MetaPalettes
{
    /**
     * Dynamic append a meta palette definition to the dca.
     *
     * @param string $strTable The table name.
     * @param mixed  $varArg1  The palette name or the meta definition. In last case, the meta will be appended to the
     *                         default palette.
     * @param mixed  $varArg2  The meta definition, only needed if the palette name is given as second parameter.
     *
     * @return void
     *
     * @throws InvalidArgumentException When meta definition is not an array.
     */
    public static function appendTo($strTable, $varArg1, $varArg2 = null)
    {
        if (is_array($varArg1)) {
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        if (!is_array($varArg2)) {
            throw new InvalidArgumentException('Meta definition has to be an array.');
        }

        $manipulator = PaletteManipulator::create();

        foreach ($varArg2 as $legend => $fields) {
            $legend .= '_legend';

            $manipulator->addLegend($legend, []);
            $manipulator->addField($fields, $legend);
        }

        $manipulator->applyToPalette($varArg1, $strTable);
    }

    /**
     * Dynamic append a meta palette definition to the dca, before a block.
     *
     * @param string $strTable The table name.
     * @param mixed  $varArg1  The palette name or the legend name (without trailing _legend, e.a. title and NOT
     *                         title_legend) the palette should appended after. In last case, the meta will be appended
     *                         to the default palette.
     * @param mixed  $varArg2  The legend name the palette should appended after or the meta definition.
     * @param mixed  $varArg3  The meta definition, only needed if the palette name is given as third parameter.
     *
     * @return void
     *
     * @throws InvalidArgumentException When meta definition is not an array.
     */
    public static function appendBefore($strTable, $varArg1, $varArg2, $varArg3 = null)
    {
        if (is_array($varArg2)) {
            $varArg3 = $varArg2;
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        if (!is_array($varArg3)) {
            throw new InvalidArgumentException('Meta definition has to be an array.');
        }

        $manipulator = PaletteManipulator::create();
        $varArg2    .= '_legend';

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
     * @param string $strTable The table name.
     * @param mixed  $varArg1  The palette name or the legend name (without trailing _legend, e.a. title and NOT
     *                         title_legend) the palette should appended after. In last case, the meta will be appended
     *                         to the default palette.
     * @param mixed  $varArg2  The legend name the palette should appended after or the meta definition.
     * @param mixed  $varArg3  The meta definition, only needed if the palette name is given as third parameter.
     *
     * @return void
     *
     * @throws InvalidArgumentException When meta definition is not an array.
     */
    public static function appendAfter($strTable, $varArg1, $varArg2, $varArg3 = null)
    {
        if (is_array($varArg2)) {
            $varArg3 = $varArg2;
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        if (!is_array($varArg3)) {
            throw new InvalidArgumentException('Meta definition has to be an array.');
        }

        $manipulator = PaletteManipulator::create();
        $varArg2    .= '_legend';

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
     * @param string $strTable The table name.
     * @param mixed  $varArg1  The palette name or the legend name (without trailing _legend, e.a. title and NOT
     *                         title_legend). In last case,the meta will be appended to the default palette.
     * @param mixed  $varArg2  The legend name the fields should appended or the list of fields.
     * @param mixed  $varArg3  List of fields to append.
     *
     * @return void
     *
     * @throws InvalidArgumentException When list of fields is not an array or a string.
     */
    public static function appendFields($strTable, $varArg1, $varArg2, $varArg3 = null)
    {
        if (is_array($varArg2)) {
            $varArg3 = $varArg2;
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        if (!is_string($varArg3) && !is_array($varArg3)) {
            throw new InvalidArgumentException('List of fields has to be an error or string');
        }

        PaletteManipulator::create()
            ->addField($varArg3, $varArg2 . '_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette($varArg1, $strTable);
    }

    /**
     * Dynamic prepend fields to a group in the palette definition.
     *
     * @param string $strTable The table name.
     * @param mixed  $varArg1  The palette name or the legend name (without trailing _legend, e.a. title and NOT
     *                         title_legend). In last case, the meta will be appended to the default palette.
     * @param mixed  $varArg2  The legend name the fields should appended or the list of fields.
     * @param mixed  $varArg3  List of fields to append.
     *
     * @return void
     *
     * @throws InvalidArgumentException When list of fields is not an array or a string.
     */
    public static function prependFields($strTable, $varArg1, $varArg2, $varArg3 = null)
    {
        if (is_array($varArg2)) {
            $varArg3 = $varArg2;
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        if (!is_string($varArg3) && !is_array($varArg3)) {
            throw new InvalidArgumentException('List of fields has to be an error or string');
        }

        PaletteManipulator::create()
            ->addField($varArg3, $varArg2 . '_legend', PaletteManipulator::POSITION_PREPEND)
            ->applyToPalette($varArg1, $strTable);
    }

    /**
     * Dynamic prepend fields to a group in the palette definition.
     *
     * @param string $strTable The table name.
     * @param mixed  $varArg1  The palette name or the list of fields to remove. In last case, the fields will be
     *                         removed from the default palette.
     * @param mixed  $varArg2  List of fields to remove.
     *
     * @return void
     *
     * @throws InvalidArgumentException When list of fields is not an array.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function removeFields($strTable, $varArg1, $varArg2 = null)
    {
        if (is_array($varArg1)) {
            $varArg2 = $varArg1;
            $varArg1 = 'default';
        }

        if (!is_array($varArg2)) {
            throw new InvalidArgumentException('Meta definition has to be an array.');
        }

        $varArg2 = array_map(
            function ($item) {
                return preg_quote($item, '#');
            },
            $varArg2
        );

        $strRegexp  = sprintf('#[,;](%s)([,;]|$)#Ui', implode('|', $varArg2));
        $strPalette = $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1];

        do {
            $strPalette = preg_replace($strRegexp, '$2', $strPalette, -1, $count);
        } while ($count);

        $GLOBALS['TL_DCA'][$strTable]['palettes'][$varArg1] = $strPalette;
    }

    /**
     * Generate a palette.
     *
     * @param array $arrMeta Palette definition.
     *
     * @return string
     */
    public static function generatePalette($arrMeta)
    {
        $manipulator = PaletteManipulator::create();

        foreach ($arrMeta as $strLegend => $arrFields) {
            $hide      = in_array(':hide', $arrFields);
            $arrFields = array_filter(
                $arrFields,
                static function (string $strField): bool {
                    return substr($strField, 0, 1) !== ':';
                }
            );

            $manipulator->addLegend($strLegend . '_legend', [], $manipulator::POSITION_APPEND, $hide);
            $manipulator->addField($arrFields, $strLegend . '_legend', $manipulator::POSITION_APPEND);
        }

        return $manipulator->applyToString('');
    }
}
