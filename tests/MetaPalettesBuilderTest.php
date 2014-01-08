<?php
/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @link      https://github.com/bit3/contao-meta-palettes SCM
 * @copyright 2013 bit3 UG
 * @author    Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package   MetaPalettes
 * @license   LGPL-3.0+
 */


use DcGeneral\DataDefinition\DefaultContainer;
use DcGeneral\DataDefinition\Palette\Legend;
use DcGeneral\DataDefinition\Palette\Palette;
use DcGeneral\DataDefinition\Palette\Property;
use DcGeneral\Factory\Event\BuildDataDefinitionEvent;

/**
 * Test the meta palettes builder.
 */
class MetaPalettesBuilderTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Create a mocking DCA reading builder.
	 *
	 * @param array $dca The DCA to inject into the builder.
	 *
	 * @return \MetaPalettesBuilder
	 */
	public function mockBuilder($dca)
	{
		$builder = $this->getMockBuilder('\MetaPalettesBuilder')
		->setMethods(array('loadDca'))
		->getMock();

		$reflection = new ReflectionProperty('\MetaPalettesBuilder', 'dca');

		$reflection->setAccessible(true);

		$reflection->setValue($builder, $dca);

		$builder
			->expects($this->any())
			->method('loadDca')
			->will($this->returnValue(true));

		return $builder;
	}

	/**
	 * Generate a mock object containing the given dca value.
	 *
	 * @param array $dca The dca to absorb.
	 *
	 * @return \DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface
	 */
	protected function parsePalette($dca)
	{
		$builder   = $this->mockBuilder($dca);
		$container = new DefaultContainer(uniqid('MetaPalettesBuilderTest-', true));
		$event     = new BuildDataDefinitionEvent($container);

		$builder->build($container, $event);

		$palettes = $container->getPalettesDefinition();

		return $palettes;
	}

	/**
	 * Assert that the property information is matching.
	 *
	 * @param Property    $property               The property info to check.
	 *
	 * @param string      $name                   The property name.
	 *
	 * @param null|string $visibleConditionClass  The class for the visible condition.
	 *
	 * @param null|string $editableConditionClass The class for the editable condition.
	 *
	 * @return void
	 */
	protected function assertProperty($property, $name, $visibleConditionClass = null, $editableConditionClass = null)
	{
		$this->assertEquals($name, $property->getName());

		if ($visibleConditionClass === null) {
			$this->assertNull($property->getVisibleCondition());
		} else {
			$this->assertInstanceOf(
				$visibleConditionClass,
				$property->getVisibleCondition(),
				$property->getName() . ' getVisibleCondition()'
			);
		}

		if ($editableConditionClass === null) {
			$this->assertNull($property->getEditableCondition());
		} else {
			$this->assertInstanceOf(
				$editableConditionClass,
				$property->getEditableCondition(),
				$property->getName() . ' getVisibleCondition()'
			);
		}
	}

	protected function assertPropertyIndependant($property, $name)
	{
		$this->assertProperty($property, $name);
	}

	protected function assertPropertyDependantValue($property, $name, $dependsOn, $dependedValue)
	{
		$this->assertProperty(
			$property,
			$name,
			'\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyValueCondition'
		);

		$this->assertAttributeEquals(
			$dependsOn,
			'propertyName',
			$property->getVisibleCondition(),
			$property->getName() . ' getVisibleCondition() check dependant field'
		);

		$this->assertAttributeEquals(
			$dependedValue,
			'propertyValue',
			$property->getVisibleCondition(),
			$property->getName() . ' getVisibleCondition() check dependant value'
		);
	}

	/**
	 * Basic tests.
	 *
	 * @return void
	 */
	public function testBase()
	{
		$palettes = $this->parsePalette(array(
			'metapalettes' => array(
				'default' => array(
					'legend' => array('prop1', 'prop2')
				)
			)
		));

		$array = $palettes->getPalettes();

		$this->assertCount(1, $array, 'Amount of palettes.');

		/** @var Palette $palette */
		$palette = $array[0];

		$this->assertCount(1, $palette->getLegends(), 'Amount of legends.');

		$legends = $palette->getLegends();

		/** @var Legend $legend */
		$legend = $legends[0];

		$this->assertEquals('legend', $legend->getName());

		$properties = $legend->getProperties();
		$this->assertCount(2, $properties, 'Amount of properties.');

		$this->assertProperty($properties[0], 'prop1');
		$this->assertProperty($properties[1], 'prop2');
	}

	/**
	 * Test sub palette parsing.
	 *
	 * @return void
	 */
	public function testSubPalettes()
	{
		$palettes = $this->parsePalette(array(
			'metapalettes' => array(
				'default' => array(
					'legend' => array('field_one', 'field_two')
				)
			),
			'metasubpalettes' => array(
				'field_two' => array('field_three', 'field_four')
			)
		));

		$array = $palettes->getPalettes();

		$this->assertCount(1, $array, 'Amount of palettes.');

		/** @var Palette $palette */
		$palette = $array[0];

		$this->assertCount(1, $palette->getLegends(), 'Amount of legends.');

		$legends = $palette->getLegends();

		/** @var Legend $legend */
		$legend = $legends[0];

		$this->assertEquals('legend', $legend->getName());

		/** @var Property[] $properties */
		$properties = $legend->getProperties();
		$this->assertCount(4, $properties, 'Amount of properties.');

		$this->assertProperty($properties[0], 'field_one');
		$this->assertProperty($properties[1], 'field_two');

		$this->assertProperty(
			$properties[2],
			'field_three',
			'\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyTrueCondition'
		);

		$this->assertAttributeEquals(
			'field_two',
			'propertyName',
			$properties[2]->getVisibleCondition(),
			'field_three getVisibleCondition() check dependant field'
		);

		$this->assertProperty(
			$properties[3],
			'field_four',
			'\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyTrueCondition'
		);

		$this->assertAttributeEquals(
			'field_two',
			'propertyName',
			$properties[3]->getVisibleCondition(),
			'field_three getVisibleCondition() check dependant field'
		);
	}

	/**
	 * Test sub palettes.
	 *
	 * @return void
	 */
	public function testSubSelectPalette()
	{
		$palettes = $this->parsePalette(array(
			'metapalettes' => array(
				'default' => array(
					'legend' => array('field_one', 'field_two')
				)
			),
			'metasubselectpalettes' => array(
				'field_two' => array(
					'value' => array('field_three', 'field_four')
				)
			)
		));

		$array = $palettes->getPalettes();

		$this->assertCount(1, $array, 'Amount of palettes.');

		/** @var Palette $palette */
		$palette = $array[0];

		$this->assertCount(1, $palette->getLegends(), 'Amount of legends.');

		$legends = $palette->getLegends();

		/** @var Legend $legend */
		$legend = $legends[0];

		$this->assertEquals('legend', $legend->getName());

		/** @var Property[] $properties */
		$properties = $legend->getProperties();
		$this->assertCount(4, $properties, 'Amount of properties.');

		$this->assertProperty(
			$properties[0],
			'field_one'
		);

		$this->assertProperty(
			$properties[1],
			'field_two'
		);

		$this->assertPropertyDependantValue($properties[2], 'field_three', 'field_two', 'value');
		$this->assertPropertyDependantValue($properties[3], 'field_four', 'field_two', 'value');
	}

	/**
	 * Test sub palettes spanning legends.
	 *
	 * @return void
	 */
	public function testSubSelectPalette2()
	{
		$palettes = $this->parsePalette(array(
			'metapalettes' => array(
				'default' => array(
					'legend1' => array('field_one', 'field_two'),
					'legend2' => array()
				)
			),
			'metasubselectpalettes' => array(
				'field_two' => array(
					'value' => array(
						'legend1' => array('field_three'),
						'legend2' => array('field_four')
					)
				)
			)
		));

		$array = $palettes->getPalettes();

		$this->assertCount(1, $array, 'Amount of palettes.');

		/** @var Palette $palette */
		$palette = $array[0];

		$this->assertCount(2, $palette->getLegends(), 'Amount of legends.');

		$legends = $palette->getLegends();

		/** @var Legend $legend */
		$legend = $legends[0];
		$this->assertEquals('legend1', $legend->getName());

		$properties = $legend->getProperties();
		$this->assertCount(3, $properties, 'Amount of properties ' . $legend->getName());

		$this->assertProperty(
			$properties[0],
			'field_one'
		);

		$this->assertProperty(
			$properties[1],
			'field_two'
		);

		$this->assertPropertyDependantValue($properties[2], 'field_three', 'field_two', 'value');

		/** @var Legend $legend */
		$legend = $legends[1];
		$this->assertEquals('legend2', $legend->getName());

		$properties = $legend->getProperties();
		$this->assertCount(1, $properties, 'Amount of properties ' . $legend->getName());

		$this->assertPropertyDependantValue($properties[0], 'field_four', 'field_two', 'value');
	}
}
