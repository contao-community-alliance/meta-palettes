<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @link      https://github.com/bit3/contao-meta-palettes SCM
 * @copyright 2013 bit3 UG
 * @author    Tristan Lins <tristan.lins@bit3.de>
 * @package   MetaPalettes
 * @license   LGPL-3.0+
 */

use DcGeneral\Contao\Dca\Builder\Legacy\DcaReadingDataDefinitionBuilder;
use DcGeneral\Contao\Dca\Palette\LegacyPalettesParser;
use DcGeneral\DataDefinition\Definition\DefaultPalettesDefinition;
use DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use DcGeneral\DataDefinition\Palette\Legend;
use DcGeneral\DataDefinition\Palette\Palette;
use DcGeneral\DataDefinition\Palette\Property;
use DcGeneral\DataDefinition\Palette\Condition\Property\NotCondition;
use DcGeneral\DataDefinition\Palette\Condition\Property\PropertyTrueCondition;
use DcGeneral\DataDefinition\Palette\Condition\Property\PropertyValueCondition;

/**
 * Class MetaPalettesBuilder
 *
 * Generates the palettes from the meta information.
 *
 * @copyright 2013 bit3 UG
 * @author    Tristan Lins <tristan.lins@bit3.de>
 * @package   MetaPalettes
 */
class MetaPalettesBuilder extends DcaReadingDataDefinitionBuilder
{
	const PRIORITY = 200;

	/**
	 * Build a data definition and store it into the environments container.
	 *
	 * @param \DcGeneral\DataDefinition\ContainerInterface $container
	 *
	 * @return void
	 */
	public function build(
		\DcGeneral\DataDefinition\ContainerInterface $container,
		\DcGeneral\Factory\Event\BuildDataDefinitionEvent $event
	) {
		$parser = new LegacyPalettesParser();

		$selectorFieldNames   = (array) $this->getFromDca('palettes/__selector__');
		$palettesDca          = (array) $this->getFromDca('metapalettes');
		$subPalettesDca       = (array) $this->getFromDca('metasubpalettes');
		$subSelectPalettesDca = (array) $this->getFromDca('metasubselectpalettes');

		// extend the selector field names with subpalettes field names
		$selectorFieldNames = array_merge(
			$selectorFieldNames,
			array_keys($subPalettesDca)
		);

		$subSelectPalettes = $this->parseSubSelectPalettes($subSelectPalettesDca);
		$subPalettes       = $this->parseSubPalettes($parser, $subPalettesDca, $selectorFieldNames);
		$palettes          = $this->parsePalettes($parser, $palettesDca, $subPalettes, $subSelectPalettes, $selectorFieldNames);

		if (empty($palettes)) {
			return;
		}

		if ($container->hasDefinition(PalettesDefinitionInterface::NAME))
		{
			$palettesDefinition = $container->getDefinition(PalettesDefinitionInterface::NAME);
		}
		else
		{
			$palettesDefinition = new DefaultPalettesDefinition();
			$container->setDefinition(PalettesDefinitionInterface::NAME, $palettesDefinition);
		}

		$palettesDefinition->addPalettes($palettes);
	}

	protected function parsePalettes(
		LegacyPalettesParser $parser,
		array $palettesDca,
		array $subPalettes,
		array $subSelectPalettes,
		array $selectorFieldNames
	) {
		$palettes = array();

		// TODO support inheritance

		if (is_array($palettesDca)) {
			foreach ($palettesDca as $selector => $legendPropertyNames) {
				$palette = new Palette();
				$palette->setCondition($parser->createPaletteCondition($selector, $selectorFieldNames));

				foreach ($legendPropertyNames as $legend => $propertyNames) {
					$legend = new Legend($legend);

					foreach ($propertyNames as $propertyName) {
						$property = new Property($propertyName);
						$legend->addProperty($property);

						// add subpalette properties
						if (isset($subPalettes[$propertyName])) {
							$legend->addProperties($subPalettes[$propertyName]);
						}
						// add subselect properties
						if (isset($subSelectPalettes[$propertyName])) {
							$legend->addProperties($subSelectPalettes[$propertyName]);
						}
					}

					$palette->addLegend($legend);
				}

				$palettes[] = $palette;
			}
		}

		return $palettes;
	}

	protected function parseSubPalettes(LegacyPalettesParser $parser, array $subPalettesDca, array $selectorFieldNames)
	{
		$subPalettes = array();

		if (is_array($subPalettesDca)) {
			foreach ($subPalettesDca as $selector => $propertyNames) {
				$properties = array();

				foreach ($propertyNames as $propertyName) {
					$property = new Property($propertyName);
					$property->setVisibleCondition(new PropertyTrueCondition($selector));
					$properties[] = $property;
				}

				if (count($properties)) {
					$selectorPropertyName               = $parser->createSubpaletteSelectorFieldName(
						$selector,
						$selectorFieldNames
					);
					$subPalettes[$selectorPropertyName] = $properties;
				}
			}
		}

		return $subPalettes;
	}

	protected function parseSubSelectPalettes(array $subSelectPalettesDca)
	{
		$subSelectPalettes = array();

		if (is_array($subSelectPalettesDca)) {
			foreach ($subSelectPalettesDca as $selectPropertyName => $valuePropertyNames) {
				$properties = array();

				foreach ($valuePropertyNames as $value => $propertyNames) {
					if ($value[0] == '!') {
						$negate = true;
						$value  = substr($value, 1);
					}
					else {
						$negate = false;
					}

					$condition = new PropertyValueCondition($selectPropertyName, $value);

					if ($negate) {
						$condition = new NotCondition($condition);
					}

					foreach ($propertyNames as $propertyName) {
						$property = new Property($propertyName);
						$property->setVisibleCondition(clone $condition);
						$properties[] = $property;
					}
				}

				if (count($properties)) {
					$subSelectPalettes[$selectPropertyName] = $properties;
				}
			}
		}

		return $subSelectPalettes;
	}
}
