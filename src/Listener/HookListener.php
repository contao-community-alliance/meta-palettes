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
        if (isset($GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'])
            && $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'] == 'General'
        ) {
            // The MetaPalettesBuilder is used for DC_General
            return;
        }

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
                            $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'] = array($strPalette);
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

        if (!empty($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'])) {
            // add callback to generate subselect palettes
            $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'] = array_merge(
                array(array('cca.meta_palettes.listener.hook_listener', 'generateSubSelectPalettes')),
                (isset($GLOBALS['TL_DCA'][$strTable]['config']['onload_callback']) && is_array(
                    $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback']
                ) ? $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'] : array())
            );
        }
    }

    /**
     * @param string $strTable
     * @param string $strPalette
     * @param array  $arrMeta
     *
     * @return void
     */
    public function extendPalette($strTable, &$strPalette, array &$arrMeta)
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
                                $arrBaseMeta[$strGroup] = array_delete($arrBaseMeta[$strGroup], $intPos);
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

    public function generateSubSelectPalettes($dataContainer = null)
    {
        if ($dataContainer instanceof \DataContainer) {
            $strTable = $dataContainer->table;
            if (!empty($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'])) {
                if (is_array($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'])) {
                    foreach ($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'] as $strSelector => $arrPalettes) {
                        if (!is_array($arrPalettes)) {
                            trigger_error(
                                sprintf(
                                    'The field $GLOBALS[\'TL_DCA\'][\'%s\'][\'metasubselectpalettes\'][\'%s\'] ' .
                                    'has to be an array, %s given!',
                                    $strTable,
                                    $strSelector,
                                    gettype($arrPalettes)
                                ),
                                E_ERROR
                            );
                            continue;
                        }

                        $strValue = null;

                        // try getting getCurrentModel value, provided by DC_General which is not neccessarily installed
                        // therefore no instanceof check, do NOT(!) try to load via post if DC_General is in use, as it
                        // has already updated the current model.
                        if (method_exists($dataContainer, 'getEnvironment')) {
                            $objModel = $dataContainer->getEnvironment()->getCurrentModel();
                            if ($objModel) {
                                $strValue = $objModel->getProperty($strSelector);
                            }
                        } else {
                            if (method_exists($dataContainer, 'getCurrentModel')) {
                                $objModel = $dataContainer->getCurrentModel();
                                if ($objModel) {
                                    $strValue = $objModel->getProperty($strSelector);
                                }
                            } else {
                                // on post, use new value
                                if (\Input::getInstance()->post('FORM_SUBMIT') == $strTable) {
                                    $strValue = \Input::getInstance()->post($strSelector);
                                } else {
                                    // support for TL_CONFIG data container
                                    if ($dataContainer instanceof \DC_File) {
                                        $strValue = $GLOBALS['TL_CONFIG'][$strSelector];
                                    } else {
                                        // try getting activeRecord value
                                        if ($dataContainer->activeRecord) {
                                            $strValue = $dataContainer->activeRecord->$strSelector;
                                        } else {
                                            // or break, when unable to handle data container
                                            if (
                                                $dataContainer instanceof \DC_Table
                                                && \Database::getInstance()->tableExists($dataContainer->table)
                                            ) {
                                                $objRecord = \Database::getInstance()
                                                    ->prepare("SELECT * FROM {$dataContainer->table} WHERE id=?")
                                                    ->execute($dataContainer->id);
                                                if ($objRecord->next()) {
                                                    $strValue = $objRecord->$strSelector;
                                                } else {
                                                    return;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($strValue === null) {
                            continue;
                        }

                        // call load callback if the value is not result of a submit.
                        if (
                            (\Input::getInstance()->post('FORM_SUBMIT') != $strTable)
                            && isset($GLOBALS['TL_DCA'][$strTable]['fields'][$strSelector]['load_callback'])
                            && is_array($GLOBALS['TL_DCA'][$strTable]['fields'][$strSelector]['load_callback'])
                        ) {
                            $callbacks = $GLOBALS['TL_DCA'][$strTable]['fields'][$strSelector]['load_callback'];
                            foreach ($callbacks as $callback) {
                                if (is_array($callback)) {
                                    $this->import($callback[0]);
                                    $strValue = $this->{$callback[0]}->{$callback[1]}($strValue, $dataContainer);
                                } elseif (is_callable($callback)) {
                                    $strValue = $callback($strValue, $dataContainer);
                                }
                            }
                        }

                        $strPalette = '';
                        foreach ($arrPalettes as $strSelectValue => $arrSelectPalette) {
                            // add palette if value is selected or not
                            if (count($arrSelectPalette) &&
                                ($strSelectValue == $strValue ||
                                    $strSelectValue[0] == '!' && substr($strSelectValue, 1) != $strValue)
                            ) {
                                foreach ($arrSelectPalette as $strLegend => $mixSub) {
                                    if (is_array($mixSub)) {
                                        // supporting sub sub palettes :)
                                        foreach ($mixSub as $arrValue) {
                                            foreach ($GLOBALS['TL_DCA'][$strTable]['palettes'] as $k => $v) {
                                                if ($k == '__selector__') {
                                                    continue;
                                                }
                                                MetaPalettes::appendFields($strTable, $k, $strLegend, array($arrValue));
                                            }
                                        }
                                    } else {
                                        // legacy, simple array of fields.
                                        $strPalette .= ',' . $mixSub;
                                    }
                                }
                            } else {
                                // continue with next
                                continue;
                            }
                        }

                        if (strlen($strPalette)) {
                            foreach ($GLOBALS['TL_DCA'][$strTable]['palettes'] as $k => $v) {
                                if ($k != '__selector__') {
                                    $GLOBALS['TL_DCA'][$strTable]['palettes'][$k] = preg_replace(
                                        '#([,;]' . preg_quote($strSelector) . ')([,;].*)?$#',
                                        '$1' . $strPalette . '$2',
                                        $GLOBALS['TL_DCA'][$strTable]['palettes'][$k]
                                    );
                                }
                            }
                            if (
                                !empty($GLOBALS['TL_DCA'][$strTable]['subpalettes']) &&
                                is_array($GLOBALS['TL_DCA'][$strTable]['subpalettes'])
                            ) {
                                foreach ($GLOBALS['TL_DCA'][$strTable]['subpalettes'] as $k => $v) {
                                    $GLOBALS['TL_DCA'][$strTable]['subpalettes'][$k] = preg_replace(
                                        '#([,;]?' . preg_quote($strSelector) . ')([,;].*)?$#',
                                        '$1' . $strPalette . '$2',
                                        $GLOBALS['TL_DCA'][$strTable]['subpalettes'][$k]
                                    );
                                }
                            }
                        }
                    }
                } else {
                    trigger_error(
                        sprintf(
                            'The field $GLOBALS[\'TL_DCA\'][\'%s\'][\'metasubselectpalettes\'] ' .
                            'has to be an array, %s given!',
                            $strTable,
                            gettype($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'])
                        ),
                        E_ERROR
                    );
                }
            }
        }
    }
}
