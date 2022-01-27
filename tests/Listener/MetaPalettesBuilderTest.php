<?php

/**
 * MetaPalettes for the Contao Open Source CMS
 *
 * @link      https://github.com/bit3/contao-meta-palettes
 * @copyright 2013-2014 bit3 UG
 * @copyright 2015-2018 Contao Community Alliance.
 * @author    Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author    Tristan Lins <tristan.lins@bit3.de>
 * @author    David Molineus <david.molineus@netzmacht.de>
 * @author    Sven Baumann <baumann.sv@gmail.com>
 * @package   MetaPalettes
 * @license   LGPL-3.0+
 */

namespace ContaoCommunityAlliance\MetaPalettes\Test\Listener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\DefaultContainer;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\PalettesDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyConditionChain;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyValueCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyVisibleCondition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Legend;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Palette;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Property;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\BuildDataDefinitionEvent;
use ContaoCommunityAlliance\MetaPalettes\Listener\MetaPalettesBuilder;
use ContaoCommunityAlliance\MetaPalettes\Parser\MetaPaletteParser;
use PHPUnit\Framework\TestCase;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Palette\Condition\Property\PropertyTrueCondition;

/**
 * Test the meta palettes builder.
 */
class MetaPalettesBuilderTest extends TestCase
{
    /**
     * Create a mocking DCA reading builder.
     *
     * @param array $dca The DCA to inject into the builder.
     *
     * @return MetaPalettesBuilder
     */
    public function mockBuilder($dca)
    {
        $framework = $this->getMockBuilder(ContaoFrameworkInterface::class)->getMock();
        $builder   = $this->getMockBuilder(MetaPalettesBuilder::class)
            ->setMethods(array('loadDca'))
            ->setConstructorArgs([$framework, new MetaPaletteParser()])
            ->getMock();

        $reflection = new \ReflectionProperty(MetaPalettesBuilder::class, 'dca');
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
     * @return PalettesDefinitionInterface
     */
    protected function parsePalette($dca)
    {
        $builder   = $this->mockBuilder($dca);
        $container = new DefaultContainer(uniqid('MetaPalettesBuilderTest-', true));
        $event     = new BuildDataDefinitionEvent($container);

        $builder->build($event);

        return $container->getPalettesDefinition();
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
        $this->assertEquals(
            $name,
            $property->getName(),
            'property name mismatch'
        );

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
            PropertyConditionChain::class
        );

        /** @var array{0: PropertyValueCondition, 1: PropertyVisibleCondition} $conditions */
        $conditions = $property->getVisibleCondition()->getConditions();

        $this->assertInstanceOf(
            PropertyValueCondition::class,
            $conditions[0]
        );

        self::assertSame(
            $dependsOn,
            $conditions[0]->getPropertyName(),
            $property->getName() . ' getVisibleCondition() check dependant field'
        );

        self::assertSame(
            $dependedValue,
            $conditions[0]->getPropertyValue(),
            $property->getName() . ' getVisibleCondition() check dependant value'
        );

        $this->assertInstanceOf(
            PropertyVisibleCondition::class,
            $conditions[1],
            $property->getName() . ' getVisibleCondition() check dependant field'
        );

        self::assertSame(
            $dependsOn,
            $conditions[1]->getPropertyName(),
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
            PropertyConditionChain::class
        );

        /** @var array{0: PropertyTrueCondition, 1: PropertyVisibleCondition} $conditions */
        $conditions = $properties[2]->getVisibleCondition()->getConditions();

        $this->assertInstanceOf(
            PropertyTrueCondition::class,
            $conditions[0]
        );

        self::assertSame(
            'field_two',
            $conditions[0]->getPropertyName(),
            'field_three getVisibleCondition() check dependant field'
        );

        $this->assertInstanceOf(
            PropertyVisibleCondition::class,
            $conditions[1],
            'field_three getVisibleCondition() check dependant field'
        );

        self::assertSame(
            'field_two',
            $conditions[1]->getPropertyName(),
            'field_three getVisibleCondition() check dependant field'
        );

        $this->assertProperty(
            $properties[3],
            'field_four',
            PropertyConditionChain::class
        );

        $conditions = $properties[3]->getVisibleCondition()->getConditions();

        $this->assertInstanceOf(
            PropertyTrueCondition::class,
            $conditions[0]
        );

        self::assertSame(
            'field_two',
            $conditions[0]->getPropertyName(),
            'field_four getVisibleCondition() check dependant field'
        );

        $this->assertInstanceOf(
            PropertyVisibleCondition::class,
            $conditions[1],
            'field_four getVisibleCondition() check dependant field'
        );

        self::assertSame(
            'field_two',
            $conditions[1]->getPropertyName(),
            'field_four getVisibleCondition() check dependant field'
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

    /**
     * Test sub palettes with anonymous legends depending on each other.
     *
     * @return void
     */
    public function testRecursiveSubSelectPalette()
    {
        $palettes = $this->parsePalette(array(
            'metapalettes' => array(
                'default' => array(
                    'legend1' => array('field_one', 'field_two'),
                )
            ),
            'metasubselectpalettes' => array(
                'field_two' => array(
                    'value1' => array(
                        'field_three',
                    )
                ),
                'field_three' => array(
                    'value2' => array(
                        'field_four'
                    )
                ),
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
        $this->assertEquals('legend1', $legend->getName());

        $properties = $legend->getProperties();
        $this->assertCount(4, $properties, 'Amount of properties ' . $legend->getName());

        $this->assertProperty(
            $properties[0],
            'field_one'
        );

        $this->assertProperty(
            $properties[1],
            'field_two'
        );

        $this->assertPropertyDependantValue($properties[2], 'field_three', 'field_two', 'value1');

        $this->assertPropertyDependantValue($properties[3], 'field_four', 'field_three', 'value2');
    }


    /**
     * Test sub palettes with anonymous legends depending on each other.
     *
     * @return void
     */
    public function testRecursiveSubSelectPaletteWithPosition()
    {
        $palettes = $this->parsePalette(array(
            'metapalettes' => array(
                'default' => array(
                    'legend1' => array('field_one', 'field_two'),
                )
            ),
            'metasubselectpalettes' => array(
                'field_two' => array(
                    'value1' => array(
                        'legend1 after field_one' => array('field_three'),
                    )
                ),
                'field_three' => array(
                    'value2' => array(
                        'field_four'
                    )
                ),
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
        $this->assertEquals('legend1', $legend->getName());

        $properties = $legend->getProperties();
        $this->assertCount(4, $properties, 'Amount of properties ' . $legend->getName());

        $this->assertProperty(
            $properties[0],
            'field_one'
        );

        $this->assertProperty(
            $properties[2],
            'field_two'
        );

        $this->assertPropertyDependantValue($properties[1], 'field_three', 'field_two', 'value1');

        $this->assertPropertyDependantValue($properties[3], 'field_four', 'field_three', 'value2');
    }

    /**
     * Test that extending an palette with an empty palette will create a copy of the original palette.
     *
     * @return void
     */
    public function testExtendPaletteAlias()
    {
        $palettes = $this->parsePalette(array(
            'metapalettes' => array(
                'default' => array(
                    'legend' => array('field_one'),
                ),
                'palette2 extends default' => array()
            )
        ));

        $array = $palettes->getPalettes();

        $this->assertCount(2, $array, 'Amount of palettes.');

        /** @var Palette $palette */
        $palette = $array[1];

        $this->assertCount(1, $palette->getLegends(), 'Amount of legends.');

        $legends = $palette->getLegends();

        /** @var Legend $legend */
        $legend = $legends[0];
        $this->assertEquals('legend', $legend->getName());

        $properties = $legend->getProperties();
        $this->assertCount(1, $properties, 'Amount of properties ' . $legend->getName());

        $this->assertProperty(
            $properties[0],
            'field_one'
        );
    }

    public function testAddBefore()
    {
        $palettes = $this->parsePalette(array(
            'metapalettes' => array(
                'default' => array(
                    'legend' => array('nop1', 'field_one', 'nop2'),
                ),
                'palette2 extends default' => array(
                    '+legend' => array('field_two before field_one')
                )
            )
        ));

        $array = $palettes->getPalettes();

        $this->assertCount(2, $array, 'Amount of palettes.');

        /** @var Palette $palette */
        $palette = $array[1];

        $this->assertCount(1, $palette->getLegends(), 'Amount of legends.');

        $legends = $palette->getLegends();

        /** @var Legend $legend */
        $legend = $legends[0];
        $this->assertEquals('legend', $legend->getName());

        $properties = $legend->getProperties();
        $this->assertCount(4, $properties, 'Amount of properties ' . $legend->getName());

        $this->assertProperty(
            $properties[1],
            'field_two'
        );

        $this->assertProperty(
            $properties[2],
            'field_one'
        );
    }

    public function testAddAfter()
    {
        $palettes = $this->parsePalette(array(
            'metapalettes' => array(
                'default' => array(
                    'legend' => array('nop1', 'field_one', 'nop2'),
                ),
                'palette2 extends default' => array(
                    '+legend' => array('field_two after field_one')
                )
            )
        ));

        $array = $palettes->getPalettes();

        $this->assertCount(2, $array, 'Amount of palettes.');

        /** @var Palette $palette */
        $palette = $array[1];

        $this->assertCount(1, $palette->getLegends(), 'Amount of legends.');

        $legends = $palette->getLegends();

        /** @var Legend $legend */
        $legend = $legends[0];
        $this->assertEquals('legend', $legend->getName());

        $properties = $legend->getProperties();
        $this->assertCount(4, $properties, 'Amount of properties ' . $legend->getName());

        $this->assertProperty(
            $properties[1],
            'field_one'
        );

        $this->assertProperty(
            $properties[2],
            'field_two'
        );
    }
}
