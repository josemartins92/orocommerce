<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Form\Type;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\ActionBundle\Form\Type\ActionType;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionContext;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\WorkflowBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\AttributeManager;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class ActionTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionManager */
    protected $actionManager;

    /** @var RequiredAttributesListener */
    protected $requiredAttributesListener;

    /** @var ActionType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->actionManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requiredAttributesListener = new RequiredAttributesListener();

        $this->formType = new ActionType(
            $this->actionManager,
            $this->requiredAttributesListener,
            new ContextAccessor()
        );
    }

    protected function tearDown()
    {
        unset($this->formType, $this->actionManager, $this->requiredAttributesListener);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $defaultData
     * @param array $inputOptions
     * @param array $submittedData
     * @param ActionContext $expectedData
     * @param array $expectedChildrenOptions
     * @param ActionContext $expectedDefaultData
     */
    public function testSubmit(
        $defaultData,
        array $inputOptions,
        array $submittedData,
        ActionContext $expectedData,
        array $expectedChildrenOptions = [],
        ActionContext $expectedDefaultData = null
    ) {
        $form = $this->factory->create($this->formType, $defaultData, $inputOptions);

        foreach ($expectedChildrenOptions as $name => $options) {
            $this->assertTrue($form->has($name));

            $childFormConfig = $form->get($name)->getConfig();
            foreach ($options as $optionName => $optionValue) {
                $this->assertTrue($childFormConfig->hasOption($optionName));
                $this->assertEquals($optionValue, $childFormConfig->getOption($optionName));
            }
        }

        if ($expectedDefaultData) {
            $this->assertEquals($expectedDefaultData, $form->getData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'existing data' => [
                'defaultData' => $this->createActionContext(['field1' => 'data1', 'field2' => 'data2']),
                'inputOptions' => [
                    'action' => $this->createAction(),
                    'attribute_fields' => [
                        'field1'  => [
                            'form_type' => 'text',
                            'label' => 'Field1 Label',
                            'options' => ['required' => true]
                        ],
                        'field2' => [
                            'form_type' => 'text',
                            'label' => 'Field2 Label Orig'
                        ],
                    ],
                ],
                'submittedData' => ['field1' => 'data1', 'field2' => 'data2'],
                'expectedData' => $this->createActionContext(['field1' => 'data1', 'field2' => 'data2']),
                'expectedChildrenOptions' => [
                    'field1'  => [
                        'required' => true,
                        'label' => 'Field1 Label'
                    ],
                    'field2' => [
                        'required' => false,
                        'label' => 'Field2 Label Orig'
                    ]
                ]
            ],
            'new data' => [
                'defaultData' => $this->createActionContext(),
                'inputOptions' => [
                    'action' => $this->createAction(),
                    'attribute_fields' => [
                        'field1'  => [
                            'form_type' => 'text'
                        ],
                        'field2' => [
                            'form_type' => 'text'
                        ],
                    ],
                ],
                'submittedData' => ['field1' => 'data1', 'field2' => 'data2'],
                'expectedData' => $this->createActionContext(['field1' => 'data1', 'field2' => 'data2'], true),
                'expectedChildrenOptions' => [
                    'field1'  => [
                        'required' => false,
                        'label' => 'Field1 Label'
                    ],
                    'field2' => [
                        'required' => false,
                        'label' => 'Field2 Label'
                    ]
                ]
            ],
            'with default values' => [
                'defaultData' => $this->createActionContext(
                    [
                        'default_field1' => 'default_field1_value',
                        'default_field2' => 'default_field2_value'
                    ]
                ),
                'inputOptions' => [
                    'action' => $this->createAction(),
                    'attribute_fields' => [
                        'field1'  => [
                            'form_type' => 'text'
                        ],
                        'field2' => [
                            'form_type' => 'text'
                        ],
                    ],
                    'attribute_default_values' => [
                        'field1' => new PropertyPath('default_field1'),
                        'field2' => new PropertyPath('default_field2'),
                    ]
                ],
                'submittedData' => [],
                'expectedData' => $this->createActionContext(
                    [
                        'field1' => null,
                        'field2' => null,
                        'default_field1' => 'default_field1_value',
                        'default_field2' => 'default_field2_value'
                    ],
                    true
                ),
                'expectedChildrenOptions' => [
                    'field1'  => [
                        'required' => false,
                        'label' => 'Field1 Label'
                    ],
                    'field2' => [
                        'required' => false,
                        'label' => 'Field2 Label'
                    ]
                ],
                'expectedDefaultData' => $this->createActionContext(
                    [
                        'field1' => 'default_field1_value',
                        'field2' => 'default_field2_value'
                    ],
                    false
                )
            ],
        ];
    }

    /**
     * @dataProvider exceptionDataProvider
     *
     * @param array $options
     * @param string $exception
     * @param string $message
     * @param ActionContext $context
     */
    public function testException(array $options, $exception, $message, ActionContext $context = null)
    {
        $this->setExpectedException($exception, $message);

        $this->factory->create($this->formType, $context, $options);
    }

    /**
     * @return array
     */
    public function exceptionDataProvider()
    {
        return [
            [
                'options' => [
                    'action' => $this->createAction(),
                    'attribute_fields' => [
                        'field'  => [
                            'form_type' => 'text'
                        ]
                    ],
                ],
                'exception' => 'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                'message' => 'The required option "data" is missing.',
                'context' => null
            ],
            [
                'options' => [
                    'action' => $this->createAction(true),
                    'attribute_fields' => [
                        'field'  => [
                            'form_type' => 'text'
                        ]
                    ],
                ],
                'exception' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'message' => 'Invalid reference to unknown attribute "field" of action "test_action".',
                'context' => $this->createActionContext()
            ],
            [
                'options' => [
                    'action' => $this->createAction(),
                    'attribute_fields' => [
                        'field' => null
                    ],
                ],
                'exception' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'message' => 'Parameter "form_type" must be defined for attribute "field" in action "test_action".',
                'context' => $this->createActionContext()
            ]
        ];
    }

    /**
     * @param array $data
     * @param bool $modified
     * @return ActionContext
     */
    protected function createActionContext(array $data = [], $modified = false)
    {
        $context = new ActionContext($data);

        if ($modified) {
            $context->modifiedData = null;
            unset($context->modifiedData);
        }

        return $context;
    }

    /**
     * @param bool $noAttributes
     * @return Action|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAction($noAttributes = false)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeManager $attributeManager */
        $attributeManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\AttributeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $attributeManager->expects($this->any())
            ->method('getAttribute')
            ->willReturnCallback(
                function ($attributeName) use ($noAttributes) {
                    if ($noAttributes) {
                        return null;
                    }

                    $attribute = new Attribute();
                    $attribute
                        ->setName($attributeName)
                        ->setLabel(ucfirst($attributeName) . ' Label')
                        ->setType('text');

                    return $attribute;
                }
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject|Action $action */
        $action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->getMock();
        $action->expects($this->any())
            ->method('getAttributeManager')
            ->with($this->isInstanceOf('Oro\Bundle\ActionBundle\Model\ActionContext'))
            ->willReturn($attributeManager);
        $action->expects($this->any())
            ->method('getName')
            ->willReturn('test_action');

        return $action;
    }
}
