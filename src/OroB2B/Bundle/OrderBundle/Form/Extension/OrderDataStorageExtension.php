<?php

namespace OroB2B\Bundle\OrderBundle\Form\Extension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class OrderDataStorageExtension extends AbstractProductDataStorageExtension
{
    /**
     * {@inheritdoc}
     */
    protected function fillItemsData($entity, array $itemsData = [])
    {
        $repository = $this->getProductRepository();
        foreach ($itemsData as $dataRow) {
            if (!array_key_exists(ProductDataStorage::PRODUCT_SKU_KEY, $dataRow)) {
                continue;
            }

            $product = $repository->findOneBySku($dataRow[ProductDataStorage::PRODUCT_SKU_KEY]);
            if (!$product) {
                continue;
            }

            $this->addItem($product, $entity, $dataRow);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addItem(Product $product, $entity, array $itemData = [])
    {
        if (!$entity instanceof Order) {
            return;
        }

        $lineItem = new OrderLineItem();
        $lineItem
            ->setProduct($product)
            ->setProductSku($product->getSku());

        if (array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $itemData)) {
            $lineItem->setQuantity($itemData[ProductDataStorage::PRODUCT_QUANTITY_KEY]);
        }

        $this->fillEntityData($lineItem, $itemData);

        if (!$lineItem->getProductUnit()) {
            /** @var ProductUnitPrecision $unitPrecision */
            $unitPrecision = $product->getUnitPrecisions()->first();
            if (!$unitPrecision) {
                return;
            }

            /** @var ProductUnit $unit */
            $unit = $unitPrecision->getUnit();
            if (!$unit) {
                return;
            }

            $lineItem->setProductUnit($unit);
            $lineItem->setProductUnitCode($unit->getCode());
        }

        if ($lineItem->getProduct()) {
            $entity->addLineItem($lineItem);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->data || !isset($this->data['withOffers'])) {
            return;
        }

        foreach ($view->offsetGet('lineItems')->children as $rowView) {
            foreach ($this->data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $dataRow) {
                if ($rowView->vars['value']->getProductSku() == $dataRow[ProductDataStorage::PRODUCT_SKU_KEY]) {
                    $rowView->vars['offers'] = $dataRow['offers'];
                }
            }
        }
    }
}
