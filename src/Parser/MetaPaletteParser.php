<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @package   MetaPalettes
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @author    Sven Baumann <baumann.sv@gmail.com>
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2018 Contao Community Alliance.
 * @license   LGPL-3.0+ https://github.com/contao-community-alliance/meta-palettes/license
 * @link      https://github.com/bit3/contao-meta-palettes
 */

namespace ContaoCommunityAlliance\MetaPalettes\Parser;

/**
 * Class MetaPaletteParser
 *
 * @package ContaoCommunityAlliance\MetaPalettes\Parser
 */
class MetaPaletteParser implements Parser
{
    /**
     * Current palettes.
     *
     * @var array
     */
    private $palettes = [];

    /**
     * Parse a meta palettes definition.
     *
     * @param string      $tableName   Name of the data container table.
     * @param array       $definition  Data container definition.
     * @param Interpreter $interpreter Interpreter which converts the definition.
     *
     * @return bool
     */
    public function parse($tableName, array $definition, Interpreter $interpreter)
    {
        if (isset($definition['metapalettes'])) {
            $this->preparePalettes($tableName, $definition['metapalettes']);

            foreach (array_keys($this->palettes[$tableName]) as $palette) {
                $this->parsePalette($tableName, $palette, $interpreter);
            }

            $this->palettes = [];
        }

        if (isset($definition['metasubpalettes'])) {
            // walk over the meta palette
            foreach ($definition['metasubpalettes'] as $palette => $fields) {
                if (is_array($fields)) {
                    $interpreter->addSubPalette($tableName, $palette, $fields);
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When meta palette definition does not exist.
     * @throws \RuntimeException         When recursive inheritance is detected.
     */
    public function parsePalette($tableName, $paletteName, Interpreter $interpreter, $base = false)
    {
        if (!isset($this->palettes[$tableName][$paletteName])) {
            throw new \InvalidArgumentException(
                sprintf('Metapalette definition of palette "%s" does not exist', $paletteName)
            );
        }

        if (!$base) {
            $interpreter->startPalette($tableName, $paletteName);
        }

        foreach ($this->palettes[$tableName][$paletteName]['parents'] as $parent) {
            if ($parent === $tableName) {
                throw new \RuntimeException(
                    sprintf('Circular recursion detected: Palette "%s" extends from itself', $paletteName)
                );
            }

            $interpreter->inherit($parent, $this);
        }

        foreach ($this->palettes[$tableName][$paletteName]['definition'] as $legend => $fields) {
            $this->parseLegend($legend, $fields, $interpreter);
        }

        if (!$base) {
            $interpreter->finishPalette();
        }
    }

    /**
     * Prepare the palettes.
     *
     * @param string $tableName    Table name.
     * @param array  $metaPalettes Data container definition.
     *
     * @return void
     */
    private function preparePalettes($tableName, array $metaPalettes)
    {
        $this->palettes[$tableName] = [];

        foreach ($metaPalettes as $paletteName => $definition) {
            $parents = $this->extractParents($paletteName);

            $this->palettes[$tableName][$paletteName] = [
                'definition' => $definition,
                'parents'    => $parents
            ];
        }
    }

    /**
     * Extract the parents from palette name and set referenced palette name to the new value.
     *
     * @param string $paletteName Palette name.
     *
     * @return array
     */
    private function extractParents(&$paletteName)
    {
        $parents     = explode(' extends ', $paletteName);
        $paletteName = array_shift($parents);

        return array_reverse($parents);
    }

    /**
     * Parse a legend.
     *
     * @param string      $legend      Raw name of the legend. Can contain the insert mode as first character.
     * @param array       $fields      List of fields.
     * @param Interpreter $interpreter The parser interpreter.
     *
     * @return void
     */
    private function parseLegend($legend, array $fields, Interpreter $interpreter)
    {
        $hide   = in_array(':hide', $fields);
        $fields = array_filter(
            $fields,
            function ($strField) {
                return $strField[0] != ':';
            }
        );

        $mode     = $this->extractInsertMode($legend, static::MODE_OVERRIDE);
        $override = $mode === static::MODE_OVERRIDE;

        if (!$override && !$hide) {
            $hide = null;
        }

        if (preg_match('#^(\w+) (before|after) (\w+)$#', $legend, $matches)) {
            $interpreter->addLegend($matches[1], $override, $hide, $matches[2], $matches[3]);
            $legend = $matches[1];
        } else {
            $interpreter->addLegend($legend, $override, $hide);
        }

        foreach ($fields as $field) {
            $fieldMode = $this->extractInsertMode($field, $mode);

            if ($fieldMode === self::MODE_REMOVE) {
                $interpreter->removeFieldFrom($legend, $field);
                continue;
            }

            if (preg_match('#^(\w+) (before|after) (\w+)$#', $field, $matches)) {
                $interpreter->addFieldTo($legend, $matches[1], $matches[2], $matches[3]);
            } else {
                $interpreter->addFieldTo($legend, $field);
            }
        }
    }

    /**
     * Extract insert mode from a name.
     *
     * @param string $name    Name passed as reference.
     * @param string $default Default insert mode.
     *
     * @return string
     */
    private function extractInsertMode(&$name, $default = self::MODE_ADD)
    {
        switch ($name[0]) {
            case '+':
                $mode = self::MODE_ADD;
                break;

            case '-':
                $mode = self::MODE_REMOVE;
                break;

            default:
                return $default;
        }

        $name = substr($name, 1);

        return $mode;
    }
}
