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
use DcGeneral\DataDefinition\ContainerInterface;
use DcGeneral\DataDefinition\Definition\DefaultPalettesDefinition;
use DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use DcGeneral\DataDefinition\Palette\Legend;
use DcGeneral\DataDefinition\Palette\Palette;
use DcGeneral\DataDefinition\Palette\Property;
use DcGeneral\DataDefinition\Palette\Condition\Property\NotCondition;
use DcGeneral\DataDefinition\Palette\Condition\Property\PropertyTrueCondition;
use DcGeneral\DataDefinition\Palette\Condition\Property\PropertyValueCondition;
use DcGeneral\Factory\Event\BuildDataDefinitionEvent;

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
		ContainerInterface $container,
		BuildDataDefinitionEvent $event
	) {
		if (!$this->loadDca($container->getName())) {
			return;
		}

		if ($container->hasDefinition(PalettesDefinitionInterface::NAME)) {
			$palettesDefinition = $container->getDefinition(PalettesDefinitionInterface::NAME);
		}
		else {
			$palettesDefinition = new DefaultPalettesDefinition();
			$container->setDefinition(PalettesDefinitionInterface::NAME, $palettesDefinition);
		}

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
		$palettes          = $this->parsePalettes(
			$palettesDefinition,
			$parser,
			$palettesDca,
			$subPalettes,
			$subSelectPalettes,
			$selectorFieldNames
		);

		if (empty($palettes)) {
			return;
		}

		$palettesDefinition->addPalettes($palettes);
	}

	protected function parsePalettes(
		PalettesDefinitionInterface $palettesDefinition,
		LegacyPalettesParser $parser,
		array $palettesDca,
		array $subPalettes,
		array $subSelectPalettes,
		array $selectorFieldNames
	) {
		$palettes = array();

		if (is_array($palettesDca)) {
			foreach ($palettesDca as $selector => $legendPropertyNames) {
				if (preg_match('#^(\w+) extends (\w+)$#', $selector, $matches)) {
					$parentSelector = $matches[2];
					$selector       = $matches[1];

					if (isset($palettes[$parentSelector])) {
						$palette = clone $palettes[$parentSelector];
					}
					else if ($palettesDefinition->hasPaletteByName($parentSelector)) {
						$palette = clone $palettesDefinition->getPaletteByName($parentSelector);
					}
					else {
						$palette = null;
					}

					if (!$palette) {
						throw new RuntimeException('Parent palette ' . $parentSelector . ' does not exists');
					}

					$extended = true;
				}
				else {
					$palette = new Palette();
					$palette->setCondition($parser->createPaletteCondition($selector, $selectorFieldNames));
					$extended = false;
				}

				foreach ($legendPropertyNames as $legendName => $propertyNames) {
					$additive      = false;
					$subtractive   = false;
					$insert        = false;
					$refLegendName = null;

					if ($extended) {
						// add properties to existing legend
						if ($legendName[0] == '+') {
							$additive   = true;
							$legendName = substr($legendName, 1);
						}
						// subtract properties from existing legend
						else if ($legendName[0] == '-') {
							$subtractive = true;
							$legendName  = substr($legendName, 1);
						}

						if (preg_match('#^(\w+) (before|after) (\w+)$#', $legendName, $matches)) {
							$legendName    = $matches[1];
							$insert        = $matches[2];
							$refLegendName = $matches[3];
						}
					}

					if ($palette->hasLegend($legendName)) {
						$legend = $palette->getLegend($legendName);
					}
					else {
						$legend = new Legend($legendName);

						// insert a legend before or after another one
						if ($insert) {
							$existingLegends = $palette->getLegends();
							$refLegend       = null;

							// search the referenced legend
							/** @var \DcGeneral\DataDefinition\Palette\LegendInterface $existingLegend */
							reset($existingLegends);
							while ($existingLegend = next($existingLegends)) {
								if ($existingLegend->getName() == $refLegendName) {
									if ($insert == 'after') {
										// if insert after, get to next
										$refLegend = next($existingLegends);
									}
									else {
										$refLegend = $existingLegend;
									}
									break;
								}
							}

							$palette->addLegend($legend, $refLegend);
						}

						// just append the legend
						else {
							$palette->addLegend($legend);
						}
					}

					// if extend a palette, but not add or remove fields, clear the legend but only when we have fields.
					if ($extended && !($additive || $subtractive) && count($propertyNames)) {
						$legend->clearProperties();
					}

					foreach ($propertyNames as $propertyName) {
						if ($propertyName[0] == ':') {
							// skip modifiers
							continue;
						}

						if ($additive || $subtractive) {
							$action      = $additive ? 'add' : 'sub';
							$insert      = false;
							$refPropertyName = null;

							if ($propertyName[0] == '+') {
								$action       = 'add';
								$propertyName = substr($propertyName, 1);
							}
							else if ($propertyName[0] == '-') {
								$action       = 'sub';
								$propertyName = substr($propertyName, 1);
							}

							if (preg_match('#^(\w+) (before|after) (\w+)$#', $propertyName, $matches)) {
								$propertyName = $matches[1];
								$insert       = $matches[2];
								$refPropertyName  = $matches[3];
							}

							if ($action == 'add') {
								$property = new Property($propertyName);

								if ($insert) {
									$existingProperties = $legend->getProperties();
									$refProperty = null;

									reset($existingProperties);
									$existingProperty = current($existingProperties);
									/** @var \DcGeneral\DataDefinition\Palette\PropertyInterface $existingProperty */
									while ($existingProperty !== false) {
										if ($existingProperty->getName() == $refPropertyName) {
											if ($insert == 'after') {
												$refProperty = next($existingProperties);

												if ($refProperty === false) {
													$refProperty = null;
												}
											}
											else {
												$refProperty = $existingProperty;
											}

											break;
										}

										$existingProperty = next($existingProperties);
									}

									$legend->addProperty($property, $refProperty);
								}
								else {
									$legend->addProperty($property);
								}
							}
							else {
								/** @var \DcGeneral\DataDefinition\Palette\PropertyInterface $property */
								foreach ($legend->getProperties() as $property) {
									if ($property->getName() == $propertyName) {
										$legend->removeProperty($property);
										break;
									}
								}
							}
						}
						else {
							$property = new Property($propertyName);
							$legend->addProperty($property);
						}

						// add sub palette properties
						if (isset($subPalettes[$propertyName])) {
							$legend->addProperties($subPalettes[$propertyName]);
						}

						// add sub select properties for unspecified legend names.
						if (isset($subSelectPalettes[$propertyName][''])) {
							$legend->addProperties($subSelectPalettes[$propertyName]['']);
						}
					}
				}

				$palettes[$selector] = $palette;
			}
		}

		// now add sub select properties that are for specific legend names.
		foreach ($subSelectPalettes as $legendInformation) {
			foreach ($legendInformation as $legendName => $properties) {
				if ($legendName === '') {
					continue;
				}

				foreach ($palettes as $palette) {
					/** @var Palette $palette */
					if ($palette->hasLegend($legendName)) {
						/** @var Legend $legend */
						$legend = $palette->getLegend($legendName);

						$legend->addProperties($properties);
					}
				}
			}
		}

		return $palettes;
	}

	protected function parseSubPalettes(
		LegacyPalettesParser $parser,
		array $subPalettesDca,
		array $selectorFieldNames
	) {
		$subPalettes = array();

		if (is_array($subPalettesDca)) {
			foreach ($subPalettesDca as $selector => $propertyNames) {
				$properties = array();

				foreach ($propertyNames as $propertyName) {

					// Check if it is a valid property name.
					if (!is_string($propertyName)) {
						throw new InvalidArgumentException(
							'Invalid property name in sub palette: ' . var_export($propertyName, true)
						);
					}

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

	/**
	 * Parse the sub select palettes into a list of properties and set the corresponding condition.
	 *
	 * @param array $subSelectPalettesDca The sub select palettes.
	 *
	 * @return array
	 *
	 * @throws InvalidArgumentException
	 */
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

					foreach ($propertyNames as $key => $propertyName) {
						// Check if it is a legend information, if so add it to that one - use the empty legend name
						// otherwise.
						if (is_array($propertyName)) {
							foreach ($propertyName as $propName) {
								$property = new Property($propName);
								$property->setVisibleCondition(clone $condition);
								$properties[$key][] = $property;
							}
						} else {
							$property = new Property($propertyName);
							$property->setVisibleCondition(clone $condition);
							$properties[''][] = $property;
						}
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
