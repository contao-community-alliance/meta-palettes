<?php

/**
 * @package    meta-palettes
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2017 netzmacht David Molineus. All rights reserved.
 * @filesource
 *
 */


namespace ContaoCommunityAlliance\MetaPalettes\Listener;

use Contao\Input;
use Contao\System;
use ContaoCommunityAlliance\MetaPalettes\MetaPalettes;

/**
 * Class SubSelectPalettesListener
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Listener
 */
class SubSelectPalettesListener
{
    /**
     * @param null $dataContainer
     */
    public function onLoad($dataContainer = null)
    {
        // Break if no data container driver is given.
        if (!$dataContainer instanceof \DataContainer) {
            return;
        }

        $strTable = $dataContainer->table;

        // No subselect palettes registered.
        if (empty($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'])) {
            return;
        }

        // Trigger the error.
        if (!is_array($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'])) {
            $this->triggerInvalidSubselectPalettesError($strTable);

            return;
        }

        foreach ($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'] as $strSelector => $arrPalettes) {
            if (!is_array($arrPalettes)) {
                $this->triggerSubselectPaletteError($strTable, $strSelector, $arrPalettes);
                continue;
            }

            $strValue = $this->getValue($dataContainer, $strTable, $strSelector);
            if ($strValue === null) {
                continue;
            }

            $strValue   = $this->invokeLoadCallback($dataContainer, $strTable, $strSelector, $strValue);
            $strPalette = $this->buildPalette($arrPalettes, $strValue, $strTable);
            $this->applyPalette($strTable, $strSelector, $strPalette);
        }
    }

    /**
     * @param $strTable
     */
    protected function triggerInvalidSubselectPalettesError($strTable)
    {
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

    /**
     * @param $strTable
     * @param $strSelector
     * @param $arrPalettes
     */
    protected function triggerSubselectPaletteError($strTable, $strSelector, $arrPalettes)
    {
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
    }

    /**
     * @param $dataContainer
     * @param $strTable
     * @param $strSelector
     *
     * @return mixed|null
     */
    private function getValue($dataContainer, $strTable, $strSelector)
    {
        $strValue = null;

        // try getting getCurrentModel value, provided by DC_General which is not neccessarily installed
        // therefore no instanceof check, do NOT(!) try to load via post if DC_General is in use, as it
        // has already updated the current model.
        if (method_exists($dataContainer, 'getEnvironment')) {
            $objModel = $dataContainer->getEnvironment()->getCurrentModel();
            if ($objModel) {
                $strValue = $objModel->getProperty($strSelector);
            }

            return $strValue;
        }

        if (method_exists($dataContainer, 'getCurrentModel')) {
            $objModel = $dataContainer->getCurrentModel();
            if ($objModel) {
                $strValue = $objModel->getProperty($strSelector);
            }

            return $strValue;
        }

        // on post, use new value
        if (\Input::getInstance()->post('FORM_SUBMIT') == $strTable) {
            return \Input::getInstance()->post($strSelector);
        }

        // support for TL_CONFIG data container
        if ($dataContainer instanceof \DC_File) {
            return $GLOBALS['TL_CONFIG'][$strSelector];
        }

        // try getting activeRecord value
        if ($dataContainer->activeRecord) {
            return $dataContainer->activeRecord->$strSelector;
        }

        // or break, when unable to handle data container
        if (
            $dataContainer instanceof \DC_Table
            && \Database::getInstance()->tableExists($dataContainer->table)
        ) {
            $objRecord = \Database::getInstance()
                ->prepare("SELECT $strSelector FROM {$dataContainer->table} WHERE id=?")
                ->limit(1)
                ->execute($dataContainer->id);
            if ($objRecord->next()) {
                return $objRecord->$strSelector;
            }
        }

        return $strValue;
    }

    /**
     * @param $dataContainer
     * @param $strTable
     * @param $strSelector
     * @param $strValue
     *
     * @return mixed
     */
    protected function invokeLoadCallback($dataContainer, $strTable, $strSelector, $strValue)
    {
        // call load callback if the value is not result of a submit.
        if ((Input::post('FORM_SUBMIT') != $strTable)
            && isset($GLOBALS['TL_DCA'][$strTable]['fields'][$strSelector]['load_callback'])
            && is_array($GLOBALS['TL_DCA'][$strTable]['fields'][$strSelector]['load_callback'])
        ) {
            $callbacks = $GLOBALS['TL_DCA'][$strTable]['fields'][$strSelector]['load_callback'];
            foreach ($callbacks as $callback) {
                if (is_array($callback)) {
                    $callback[0] = System::importStatic($callback[0]);
                    $strValue    = $callback[0]->{$callback[1]}($strValue, $dataContainer);
                } elseif (is_callable($callback)) {
                    $strValue = $callback($strValue, $dataContainer);
                }
            }
        }

        return $strValue;
    }

    /**
     * @param $arrPalettes
     * @param $strValue
     * @param $strTable
     *
     * @return string
     */
    protected function buildPalette($arrPalettes, $strValue, $strTable)
    {
        $strPalette = '';

        foreach ($arrPalettes as $strSelectValue => $arrSelectPalette) {
            // add palette if value is selected or not
            if (!count($arrSelectPalette) &&
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
                                MetaPalettes::appendFields($strTable, $k, $strLegend, [$arrValue]);
                            }
                        }
                    } else {
                        // legacy, simple array of fields.
                        $strPalette .= ',' . $mixSub;
                    }
                }
            }
        }

        return $strPalette;
    }

    private function applyPalette($strTable, $strSelector, $strPalette)
    {
        if (!strlen($strPalette)) {
            return;
        }

        foreach ($GLOBALS['TL_DCA'][$strTable]['palettes'] as $k => $v) {
            if ($k != '__selector__') {
                $GLOBALS['TL_DCA'][$strTable]['palettes'][$k] = preg_replace(
                    '#([,;]' . preg_quote($strSelector) . ')([,;].*)?$#',
                    '$1' . $strPalette . '$2',
                    $GLOBALS['TL_DCA'][$strTable]['palettes'][$k]
                );
            }
        }

        if (empty($GLOBALS['TL_DCA'][$strTable]['subpalettes'])
            || !is_array($GLOBALS['TL_DCA'][$strTable]['subpalettes'])
        ) {
            return;
        }

        foreach ($GLOBALS['TL_DCA'][$strTable]['subpalettes'] as $k => $v) {
            $GLOBALS['TL_DCA'][$strTable]['subpalettes'][$k] = preg_replace(
                '#([,;]?' . preg_quote($strSelector) . ')([,;].*)?$#',
                '$1' . $strPalette . '$2',
                $GLOBALS['TL_DCA'][$strTable]['subpalettes'][$k]
            );
        }
    }
}