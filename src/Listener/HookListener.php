<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @link      https://github.com/bit3/contao-meta-palettes
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2017 Contao Community Alliance.
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @package   MetaPalettes
 * @license   LGPL-3.0+
 */

namespace ContaoCommunityAlliance\MetaPalettes\Listener;

use ContaoCommunityAlliance\MetaPalettes\MetaPalettes;

/**
 * Hook listener
 */
class HookListener
{
    /**
     * @param $strTable
     *
     * @return void
     */
    public function generatePalettes($strTable)
    {
        // The MetaPalettesBuilder is used for DC_General
        if (isset($GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'])
            && $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'] == 'General'
        ) {

            return;
        }

        $this->invokePalettesCallbacks($strTable);
        $this->buildPalettes($strTable);
        $this->buildSubPalettes($strTable);
        $this->registerSubSelectPalettesCallback($strTable);
    }

    /**
     * @param string $strTable
     * @param string $strPalette
     * @param array  $arrMeta
     *
     * @return void
     */
    private function extendPalette($strTable, &$strPalette, array &$arrMeta)
    {
        if (preg_match('#^(\w+) extends (\w+)$#', $strPalette, $arrMatch)) {
            if (!is_array($GLOBALS['TL_DCA'][$strTable]['metapalettes'][$arrMatch[2]])) {
                return;
            }
            $arrBaseMeta = array_slice($GLOBALS['TL_DCA'][$strTable]['metapalettes'][$arrMatch[2]], 0);
            $this->extendPalette($strTable, $arrMatch[2], $arrBaseMeta);
            $strPalette = $arrMatch[1];

            // walk over the extending palette
            foreach ($arrMeta as $strGroup => $arrFields) {
                // palette should be extended
                if (preg_match('#^([\+-])(\w+)$#', $strGroup, $arrMatch)) {
                    $strOperator = $arrMatch[1];
                    $strGroup    = $arrMatch[2];

                    if (empty($arrBaseMeta[$strGroup])) {
                        $arrBaseMeta[$strGroup] = array();
                    }

                    foreach ($arrFields as $strField) {
                        // test for field operator
                        if (preg_match('#^([\+-])#', $strField, $arrMatch)) {
                            $strFieldOperator = $arrMatch[1];
                            $strField         = substr($strField, 1);
                        } else {
                            // use default operator
                            $strFieldOperator = $strOperator;
                        }

                        // remove a field
                        if ($strFieldOperator == '-') {
                            $intPos = array_search($strField, $arrBaseMeta[$strGroup]);

                            if ($intPos !== false) {
                                unset($arrBaseMeta[$strGroup][$intPos]);
                                $arrBaseMeta[$strGroup] = array_values($arrBaseMeta[$strGroup]);
                            }
                        } else {
                            // insert at position
                            if (preg_match('#^(\w+) (before|after) (\w+)$#', $strField, $arrMatch)) {
                                $strPosition = $arrMatch[2];
                                $strRefField = $arrMatch[3];
                                $strField    = $arrMatch[1];

                                // search position
                                $intPos = array_search($strRefField, $arrBaseMeta[$strGroup]);

                                // append because position could not be determinated
                                if ($intPos === false) {
                                    $arrBaseMeta[$strGroup][] = $strField;
                                } else {
                                    // insert into position
                                    if ($strPosition == 'after') {
                                        $intPos++;
                                    }

                                    $arrBaseMeta[$strGroup] = array_merge(
                                        array_slice($arrBaseMeta[$strGroup], 0, $intPos),
                                        array($strField),
                                        array_slice($arrBaseMeta[$strGroup], $intPos)
                                    );
                                }
                            } else {
                                // append field
                                $arrBaseMeta[$strGroup][] = $strField;
                            }
                        }
                    }
                } else {
                    // palette should be inserted at position
                    if (preg_match('#^(\w+) (before|after) (\w+)$#', $strGroup, $arrMatch)) {
                        $strPosition   = $arrMatch[2];
                        $strRefPalette = $arrMatch[3];
                        $strGroup      = $arrMatch[1];

                        // remove existing palette to make it possible to add at a new position
                        if (isset($arrBaseMeta[$strGroup])) {
                            unset($arrBaseMeta[$strGroup]);
                        }

                        // search position and insert
                        $intPos = array_search($strRefPalette, array_keys($arrBaseMeta));

                        // append because position could not be determinated
                        if ($intPos === false) {
                            $arrBaseMeta[$strGroup] = $arrFields;
                        } else {
                            // insert into position
                            if ($strPosition == 'after') {
                                $intPos++;
                            }

                            $arrBaseMeta = array_merge(
                                array_slice($arrBaseMeta, 0, $intPos),
                                array(
                                    $strGroup => $arrFields
                                ),
                                array_slice($arrBaseMeta, $intPos)
                            );
                        }
                    } else {
                        // palette should be appended or overwritten
                        $arrBaseMeta[$strGroup] = $arrFields;
                    }
                }
            }

            $arrMeta = $arrBaseMeta;
            // keep result for derived palettes to use.
            $GLOBALS['TL_DCA'][$strTable]['metapalettes'][$strPalette] = $arrMeta;
        }
    }



    /**
     * @param $strTable
     */
    private function invokePalettesCallbacks($strTable)
    {
        // check if palette callback is registered
        if (
            isset($GLOBALS['TL_DCA'][$strTable]['config']['palettes_callback'])
            && is_array($GLOBALS['TL_DCA'][$strTable]['config']['palettes_callback'])
        ) {
            // call callbacks
            foreach ($GLOBALS['TL_DCA'][$strTable]['config']['palettes_callback'] as $callback) {
                if (is_array($callback) && count($callback) == 2) {
                    if (!is_object($callback[0])) {
                        $callback[0] = \System::importStatic($callback[0]);
                    }
                }

                call_user_func($callback);
            }
        }
    }

    /**
     * @param $strTable
     *
     * @return int|string
     */
    private function buildPalettes($strTable)
    {
        // check if any meta palette information exists
        if (
            isset($GLOBALS['TL_DCA'][$strTable]['metapalettes'])
            && is_array($GLOBALS['TL_DCA'][$strTable]['metapalettes'])
        ) {
            // walk over the meta palette
            foreach ($GLOBALS['TL_DCA'][$strTable]['metapalettes'] as $strPalette => $arrMeta) {
                // extend palettes
                $this->extendPalette($strTable, $strPalette, $arrMeta);

                // only generate if not palette exists
                if (!isset($GLOBALS['TL_DCA'][$strTable]['palettes'][$strPalette]) && is_array($arrMeta)) {
                    // set the palette
                    $GLOBALS['TL_DCA'][$strTable]['palettes'][$strPalette] = MetaPalettes::generatePalette($arrMeta);
                }
            }
        }
    }

    /**
     * @param $strTable
     */
    private function buildSubPalettes($strTable)
    {
        // check if any meta palette information exists
        if (
            isset($GLOBALS['TL_DCA'][$strTable]['metasubpalettes'])
            && is_array($GLOBALS['TL_DCA'][$strTable]['metasubpalettes'])
        ) {
            // walk over the meta palette
            foreach ($GLOBALS['TL_DCA'][$strTable]['metasubpalettes'] as $strPalette => $arrFields) {
                // only generate if not palette exists
                if (!isset($GLOBALS['TL_DCA'][$strTable]['subpalettes'][$strPalette]) && is_array($arrFields)) {
                    // only generate if there are any fields
                    if (is_array($arrFields) && count($arrFields) > 0) {
                        // generate subpalettes selectors
                        if (!is_array($GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'])) {
                            $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'] = [$strPalette];
                        } else {
                            if (!in_array($strPalette, $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'])) {
                                $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'][] = $strPalette;
                            }
                        }

                        // set the palette
                        $GLOBALS['TL_DCA'][$strTable]['subpalettes'][$strPalette] = implode(',', $arrFields);
                    }
                }
            }
        }
    }

    /**
     * @param $strTable
     */
    private function registerSubSelectPalettesCallback($strTable)
    {
        if (!empty($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'])) {
            // add callback to generate subselect palettes
            $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'] = array_merge(
                [['cca.meta_palettes.listener.sub_select_palettes_listener', 'generateSubSelectPalettes']],
                (isset($GLOBALS['TL_DCA'][$strTable]['config']['onload_callback']) && is_array(
                    $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback']
                ) ? $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'] : [])
            );
        }
    }
}
