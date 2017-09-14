<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @author    Tristan Lins <tristan@lins.io>
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2017 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/bit3/contao-meta-palettes
 */

namespace ContaoCommunityAlliance\MetaPalettes\Listener;

use ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter;
use ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter\StringPalettesInterpreter;
use ContaoCommunityAlliance\MetaPalettes\Parser\MetaPaletteParser;

/**
 * Hook listener
 */
class BuildPalettesListener
{
    /**
     * Meta palettes parser.
     *
     * @var MetaPaletteParser
     */
    private $metaPalettesParser;

    /**
     * Interpreter of the parser.
     *
     * @var Interpreter
     */
    private $interpreter;

    /**
     * BuildPalettesListener constructor.
     *
     * @param MetaPaletteParser $metaPalettesParser Meta palettes parser.
     */
    public function __construct(MetaPaletteParser $metaPalettesParser, Interpreter $interpreter)
    {
        $this->metaPalettesParser = $metaPalettesParser;
        $this->interpreter        = $interpreter;
    }

    /**
     * Listen to the onload data container callback.
     *
     * @param string $strTable Table name.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function onLoadDataContainer($strTable)
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
     * Invoke the palettes callback.
     *
     * @param string $strTable Data container table name.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function invokePalettesCallbacks($strTable)
    {
        // check if palette callback is registered
        if (isset($GLOBALS['TL_DCA'][$strTable]['config']['palettes_callback'])
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
     * Build all palettes.
     *
     * @param string $strTable Data container table name.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function buildPalettes($strTable)
    {
        $this->metaPalettesParser->parse($strTable, new StringPalettesInterpreter());
    }

    /**
     * Build the sub palettes.
     *
     * @param string $strTable Data container table name.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function buildSubPalettes($strTable)
    {
        // check if any meta palette information exists
        if (!isset($GLOBALS['TL_DCA'][$strTable]['metasubpalettes'])
            || !is_array($GLOBALS['TL_DCA'][$strTable]['metasubpalettes'])
        ) {
            return;
        }

        // walk over the meta palette
        foreach ($GLOBALS['TL_DCA'][$strTable]['metasubpalettes'] as $strPalette => $arrFields) {
            // only generate if not palette exists
            if (!isset($GLOBALS['TL_DCA'][$strTable]['subpalettes'][$strPalette]) && is_array($arrFields)) {
                // only generate if there are any fields
                if (is_array($arrFields) && count($arrFields) > 0) {
                    $this->addSelector($strTable, $strPalette);

                    // set the palette
                    $GLOBALS['TL_DCA'][$strTable]['subpalettes'][$strPalette] = implode(',', $arrFields);
                }
            }
        }
    }

    /**
     * Register the subselect palettes callback if any metasubselectpalettes are defined.
     *
     * @param string $strTable Data container table name.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
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

    /**
     * Add a selector.
     *
     * @param string $strTable   Data container table name.
     * @param string $strPalette Palette selector field.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function addSelector($strTable, $strPalette)
    {
        // generate subpalettes selectors
        if (!is_array($GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'])) {
            $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'] = [$strPalette];
        } else {
            if (!in_array($strPalette, $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'])) {
                $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'][] = $strPalette;
            }
        }
    }
}
