<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @author    Christopher Bölter <christopher@boelter.eu>
 * @author    Sven Baumann <baumann.sv@gmail.com>
 * @author    Ingolf Steinhardt <info@e-spin.de>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2023 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/bit3/contao-meta-palettes
 */

namespace ContaoCommunityAlliance\MetaPalettes\Listener;

use Contao\System;
use ContaoCommunityAlliance\DcGeneral\DC\General;
use ContaoCommunityAlliance\MetaPalettes\Parser\Interpreter;
use ContaoCommunityAlliance\MetaPalettes\Parser\Parser;

/**
 * Hook listener
 */
class BuildPalettesListener
{
    /**
     * Meta palettes parser.
     *
     * @var Parser
     */
    private $parser;

    /**
     * Interpreter of the parser.
     *
     * @var Interpreter
     */
    private $interpreter;

    /**
     * BuildPalettesListener constructor.
     *
     * @param Parser      $parser      Meta palettes parser.
     * @param Interpreter $interpreter Interpreter.
     */
    public function __construct(Parser $parser, Interpreter $interpreter)
    {
        $this->parser      = $parser;
        $this->interpreter = $interpreter;
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
        // We can not work without DCA.
        if (!(isset($GLOBALS['TL_DCA'][$strTable]) && is_array($GLOBALS['TL_DCA'][$strTable]))) {
            return;
        }

        // The MetaPalettesBuilder is used for DC_General
        if (isset($GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'])
            && in_array(
                $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'],
                ['General', General::class],
                true
            )
        ) {
            return;
        }

        $this->invokePalettesCallbacks($strTable);
        $this->parser->parse($strTable, $GLOBALS['TL_DCA'][$strTable], $this->interpreter);
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
                        $callback[0] = System::importStatic($callback[0]);
                    }
                }

                /** @psalm-suppress PossiblyInvalidFunctionCall */
                call_user_func($callback);
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
                [['cca.meta_palettes.listener.sub_select_palettes_listener', 'onLoad']],
                (isset($GLOBALS['TL_DCA'][$strTable]['config']['onload_callback']) && is_array(
                    $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback']
                ) ? $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'] : [])
            );
        }
    }
}
