<?php

/**
 * @package    meta-palettes
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2017 netzmacht David Molineus. All rights reserved.
 * @filesource
 *
 */


namespace ContaoCommunityAlliance\MetaPalettes\Listener;

/**
 * Class SubSelectPalettesListener
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Listener
 */
class SubSelectPalettesListener
{
    public function onLoad($dataContainer = null)
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
