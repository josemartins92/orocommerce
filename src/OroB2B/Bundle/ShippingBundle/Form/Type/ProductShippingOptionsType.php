<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class ProductShippingOptionsType extends AbstractType
{
    const NAME = 'orob2b_shipping_product_shipping_options';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productUnit', ProductUnitSelectionType::NAME, [
                'label' => 'orob2b.shipping.product_shipping_options.product_unit.label',
            ])
            ->add('weight', WeightType::NAME, [
                'label' => 'orob2b.shipping.product_shipping_options.weight.label',
            ])
            ->add('dimensions', DimensionsType::NAME, [
                'label' => 'orob2b.shipping.product_shipping_options.dimensions.label',
            ])
            ->add('freightClass', FreightClassSelectType::NAME, [
                'label' => 'orob2b.shipping.product_shipping_options.freight_class.label',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention' => 'shipping_product_shipping_origin',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => ['view' => 'orob2bshipping/js/app/views/line-item-view'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
