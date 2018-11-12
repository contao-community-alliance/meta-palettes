<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @author    Christopher Bölter <christopher@boelter.eu>
 * @author    Sven Baumann <baumann.sv@gmail.com>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2018 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/bit3/contao-meta-palettes
 */

namespace ContaoCommunityAlliance\MetaPalettes\Listener;

use Contao\Config;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\MetaPalettes\MetaPalettes;

/**
 * Class SubSelectPalettesListener
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Listener
 */
class SubSelectPalettesListener
{
    /**
     * Handle the onlaod callback.
     *
     * @param DataContainer|null $dataContainer Data container driver.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function onLoad($dataContainer = null)
    {
        // Break if no data container driver is given.
        if (!$dataContainer instanceof DataContainer) {
            return;
        }

        $strTable = $dataContainer->table;

        // No subselect palettes registered.
        if (empty($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'])) {
            return;
        }

        // Trigger the error.
        if (!is_array($GLOBALS['TL_DCA'][$strTable]['metasubselectpalettes'])) {
            $this->triggerInvalidSubSelectPalettesError($strTable);

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
     * Trigger the sub select palette error.
     *
     * @param string $strTable Table name.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function triggerInvalidSubSelectPalettesError($strTable)
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
     * Trigger the subselect palette error.
     *
     * @param string $strTable    Table name.
     * @param string $strSelector Selector field name.
     * @param array  $arrPalettes Given palettes value.
     *
     * @return void
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
     * Get the value.
     *
     * @param DataContainer $dataContainer Data container driver.
     * @param string        $strTable      Table name.
     * @param string        $strSelector   Selector field name.
     *
     * @return mixed|null
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function getValue($dataContainer, $strTable, $strSelector)
    {
        $strValue = null;

        // try getting getCurrentModel value, provided by DC_General which is not neccessarily installed
        // therefore no instanceof check, do NOT(!) try to load via post if DC_General is in use, as it
        // has already updated the current model.
        if (method_exists($dataContainer, 'getEnvironment')) {
            $objModel = $dataContainer->getEnvironment()->getCurrentModel();
            return $this->getValueFromDcGeneralModel($objModel, $strSelector);
        }

        if (method_exists($dataContainer, 'getCurrentModel')) {
            $objModel = $dataContainer->getCurrentModel();
            return $this->getValueFromDcGeneralModel($objModel, $strSelector);
        }

        // on post, use new value
        if (\Input::post('FORM_SUBMIT') == $strTable) {
            return \Input::post($strSelector);
        }

        // support for TL_CONFIG data container
        if ($dataContainer instanceof \DC_File) {
            return Config::get($strSelector);
        }

        // try getting activeRecord value
        if ($dataContainer->activeRecord) {
            return $dataContainer->activeRecord->$strSelector;
        }

        // or break, when unable to handle data container
        if ($dataContainer instanceof \DC_Table
            && \Database::getInstance()->tableExists($dataContainer->table)
        ) {
            return $this->fetchValueFromDatabase($dataContainer, $strSelector);
        }

        return $strValue;
    }

    /**
     * Invoke the load callbacks.
     *
     * @param DataContainer $dataContainer Data container driver.
     * @param string        $strTable      Data container table name.
     * @param string        $strSelector   Selector field name.
     * @param string        $strValue      Selector value.
     *
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.Superglobals)
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
     * Build the sub select palette.
     *
     * @param array  $arrPalettes Sub select palettes definitions.
     * @param string $strValue    Selector value.
     * @param string $strTable    Data container table name.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function buildPalette($arrPalettes, $strValue, $strTable)
    {
        $strPalette = '';

        foreach ($arrPalettes as $strSelectValue => $arrSelectPalette) {
            // add palette if value is selected or not
            if (count($arrSelectPalette)
                && $this->isValueSelected($strValue, $strSelectValue)
            ) {
                foreach ($arrSelectPalette as $strLegend => $mixSub) {
                    if (is_array($mixSub)) {
                        // supporting sub sub palettes :)
                        foreach ($mixSub as $arrValue) {
                            foreach (array_keys($GLOBALS['TL_DCA'][$strTable]['palettes']) as $k) {
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

    /**
     * Apply generated subselect palette to the palettes.
     *
     * @param string $strTable    Name of the data container table.
     * @param string $strSelector Selector field name.
     * @param string $strPalette  Palette which should be applied.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
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

    /**
     * Fetch value from database.
     *
     * @param DataContainer $dataContainer Data container driver.
     * @param string        $strSelector   Selector field name.
     *
     * @return mixed
     */
    private function fetchValueFromDatabase($dataContainer, $strSelector)
    {
        $objRecord = \Database::getInstance()
            ->prepare(sprintf('SELECT `%s` FROM `%s` WHERE id=?', $strSelector, $dataContainer->table))
            ->limit(1)
            ->execute($dataContainer->id);

        if ($objRecord->next()) {
            return $objRecord->$strSelector;
        }

        return null;
    }

    /**
     * Get the value from the model.
     *
     * @param ModelInterface|null $objModel    Data model.
     * @param string              $strSelector Selector field name.
     *
     * @return mixed
     */
    private function getValueFromDcGeneralModel($objModel, $strSelector)
    {
        if ($objModel) {
            return $objModel->getProperty($strSelector);
        }

        return null;
    }

    /**
     * Check if value is selected.
     *
     * @param string $strValue       Actual value.
     * @param string $strSelectValue Select value definition.
     *
     * @return bool
     */
    protected function isValueSelected($strValue, $strSelectValue)
    {
        return ($strSelectValue == $strValue || $strSelectValue[0] == '!' && substr($strSelectValue, 1) != $strValue);
    }
}
