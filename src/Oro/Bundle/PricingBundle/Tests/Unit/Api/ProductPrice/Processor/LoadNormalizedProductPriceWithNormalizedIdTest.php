<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\LoadNormalizedProductPriceWithNormalizedId;
use Oro\Bundle\PricingBundle\Api\ProductPrice\ProductPriceIDByContextNormalizerInterface;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class LoadNormalizedProductPriceWithNormalizedIdTest extends FormProcessorTestCase
{
    /**
     * @var ActionProcessorBagInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processorBag;

    /**
     * @var ProductPriceIDByContextNormalizerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $normalizer;

    /**
     * @var LoadNormalizedProductPriceWithNormalizedId
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processorBag = $this->createMock(ActionProcessorBagInterface::class);
        $this->normalizer = $this->createMock(ProductPriceIDByContextNormalizerInterface::class);

        $this->processor = new LoadNormalizedProductPriceWithNormalizedId(
            $this->processorBag,
            $this->normalizer
        );
    }

    public function testProcessCompiles()
    {
        $productPriceId = 'test';
        $priceListId = 12;

        $getConfig = new EntityDefinitionConfig();
        $getConfig->set('config_key', 'config_value');
        $getMetadata = new EntityMetadata();
        $getMetadata->set('metadata_key', 'metadata_value');

        $this->normalizer->expects(self::once())
            ->method('normalize')
            ->with($productPriceId)
            ->willReturn('test-12');

        $processor = $this->createMock(ActionProcessorInterface::class);
        $this->processorBag->expects(self::once())
            ->method('getProcessor')
            ->willReturn($processor);
        $processor->expects(self::once())
            ->method('createContext')
            ->willReturn(new GetContext($this->configProvider, $this->metadataProvider));
        $processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (GetContext $getContext) use ($getConfig, $getMetadata) {
                self::assertEquals('test-12', $getContext->getId());
                $getContext->setConfig($getConfig);
                $getContext->setMetadata($getMetadata);
            });

        $this->context->set('price_list_id', $priceListId);
        $this->context->setId($productPriceId);
        $this->processor->process($this->context);

        self::assertEquals($getConfig, $this->context->getConfig());
        self::assertEquals($getMetadata, $this->context->getMetadata());
    }
}
