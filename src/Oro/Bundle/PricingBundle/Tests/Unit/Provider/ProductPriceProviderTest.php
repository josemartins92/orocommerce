<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const TEST_CURRENCY = 'USD';

    /** @var ProductPriceScopeCriteriaInterface */
    private $productPriceScopeCriteria;

    /** @var ProductPriceProvider */
    protected $provider;

    /** @var ProductPriceStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $priceStorage;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $currencyManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->priceStorage = $this->createMock(ProductPriceStorageInterface::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);

        $this->provider = new ProductPriceProvider($this->priceStorage, $this->currencyManager);
    }

    /**
     * @dataProvider getSupportedCurrenciesProvider
     * @param array $availableCurrencies
     * @param array $supportedCurrencies
     * @param array $expectedResult
     */
    public function testGetSupportedCurrencies(
        array $availableCurrencies,
        array $supportedCurrencies,
        array $expectedResult
    ) {
        $this->currencyManager
            ->expects($this->once())
            ->method('getAvailableCurrencies')
            ->willReturn($availableCurrencies);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->expects($this->once())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($supportedCurrencies);

        $result = $this->provider->getSupportedCurrencies($scopeCriteria);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getSupportedCurrenciesProvider()
    {
        return [
            'one supported currency exists' => [
                'availableCurrencies' => [self::TEST_CURRENCY],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'expectedResult' => [self::TEST_CURRENCY]
            ],
            'no available currencies' => [
                'availableCurrencies' => [self::TEST_CURRENCY],
                'supportedCurrencies' => ['EUR'],
                'expectedResult' => []
            ]
        ];
    }

    /**
     * @dataProvider getPricesByScopeCriteriaAndProductsProvider
     *
     * @param array $currencies
     * @param array $supportedCurrencies
     * @param array $availableCurrencies
     * @param array $finalCurrencies
     * @param string $unitCode
     * @param array $products
     * @param array $prices
     * @param array $expectedResult
     */
    public function testGetPricesByScopeCriteriaAndProducts(
        array $currencies,
        array $supportedCurrencies,
        array $availableCurrencies,
        array $finalCurrencies,
        $unitCode,
        array $products,
        array $prices,
        array $expectedResult
    ) {
        $this->currencyManager
            ->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn($availableCurrencies);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->expects($this->any())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($supportedCurrencies);

        $productUnitCodes = $unitCode ? [$unitCode] : null;
        $this->priceStorage
            ->expects($this->once())
            ->method('getPrices')
            ->with($scopeCriteria, $products, $productUnitCodes, $finalCurrencies)
            ->willReturn($prices);

        $result = $this->provider->getPricesByScopeCriteriaAndProducts(
            $scopeCriteria,
            $products,
            $currencies,
            $unitCode
        );

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getPricesByScopeCriteriaAndProductsProvider()
    {
        return [
            'with allowed currency' => [
                'currencies' => [self::TEST_CURRENCY],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'availableCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'finalCurrencies' => [self::TEST_CURRENCY],
                'unitCode' => 'unit',
                'products' => [$this->getEntity(Product::class, ['id' => 1])],
                'prices' => $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['unit']),
                'expectedResult' => [
                    1 => $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['unit'])
                ]
            ],
            'without unit code' => [
                'currencies' => [self::TEST_CURRENCY],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'availableCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'finalCurrencies' => [self::TEST_CURRENCY],
                'unitCode' => null,
                'products' => [$this->getEntity(Product::class, ['id' => 1])],
                'prices' => $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['unit']),
                'expectedResult' => [
                    1 => $this->getPricesArray(10, 10, self::TEST_CURRENCY, ['unit'])
                ]
            ]
        ];
    }

    /**
     * @dataProvider getPricesByScopeCriteriaAndProductsWhenGetPricesNotCalledProvider
     *
     * @param array $currencies
     * @param array $supportedCurrencies
     * @param array $availableCurrencies
     * @param string $unitCode
     * @param array $products
     * @param array $expectedResult
     */
    public function testGetPricesByScopeCriteriaAndProductsWhenGetPricesNotCalled(
        array $currencies,
        array $supportedCurrencies,
        array $availableCurrencies,
        $unitCode,
        array $products,
        array $expectedResult
    ) {
        $this->currencyManager
            ->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn($availableCurrencies);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->expects($this->any())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($supportedCurrencies);

        $this->priceStorage
            ->expects($this->never())
            ->method('getPrices');

        $result = $this->provider->getPricesByScopeCriteriaAndProducts(
            $scopeCriteria,
            $products,
            $currencies,
            $unitCode
        );

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getPricesByScopeCriteriaAndProductsWhenGetPricesNotCalledProvider()
    {
        return [
            'without currencies' => [
                'currencies' => [],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'availableCurrencies' => [self::TEST_CURRENCY, 'EUR'],
                'unitCode' => 'unit',
                'products' => [$this->getEntity(Product::class, ['id' => 1])],
                'expectedResult' => []
            ],
            'with not allowed currency' => [
                'currencies' => [self::TEST_CURRENCY],
                'supportedCurrencies' => ['EUR'],
                'availableCurrencies' => [self::TEST_CURRENCY],
                'unitCode' => 'unit',
                'products' => [$this->getEntity(Product::class, ['id' => 1])],
                'expectedResult' => []
            ],
        ];
    }

    /**
     * @dataProvider getMatchedPricesProvider
     *
     * @param array $productPriceCriteria
     * @param array $products
     * @param array $productUnitCodes
     * @param array $prices
     * @param array $supportedCurrencies
     * @param array $availableCurrencies
     * @param array $finalCurrencies
     * @param array $expectedResult
     */
    public function testGetMatchedPrices(
        array $productPriceCriteria,
        array $products,
        array $productUnitCodes,
        array $prices,
        array $supportedCurrencies,
        array $availableCurrencies,
        array $finalCurrencies,
        array $expectedResult
    ) {
        $this->currencyManager
            ->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn($availableCurrencies);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->expects($this->any())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($supportedCurrencies);

        $this->priceStorage
            ->expects($this->once())
            ->method('getPrices')
            ->with($scopeCriteria, $products, $productUnitCodes, $finalCurrencies)
            ->willReturn($prices);

        $result = $this->provider->getMatchedPrices(
            $productPriceCriteria,
            $scopeCriteria
        );

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getMatchedPricesProvider()
    {
        return [
            'with price criteria that contains allowed currencies' => [
                'productPriceCriteria' => [
                    $this->getProductPriceCriteria(1, 'item', 10, self::TEST_CURRENCY)
                ],
                'products' => [
                    $this->getEntity(Product::class, ['id' => 1])
                ],
                'productUnitCodes' => ['item'],
                'prices' => [
                    $this->createPrice(15, self::TEST_CURRENCY, 5, 'item'),
                    $this->createPrice(10, self::TEST_CURRENCY, 10, 'item')
                ],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'UAH'],
                'availableCurrencies' => [self::TEST_CURRENCY, 'UAH'],
                'finalCurrencies' => [self::TEST_CURRENCY],
                'expectedResult' => [
                    '1-item-10-USD' => Price::create(1, 'USD'),
                ]
            ],
            'no matched prices' => [
                'productPriceCriteria' => [
                    $this->getProductPriceCriteria(1, 'item', 5, self::TEST_CURRENCY)
                ],
                'products' => [
                    $this->getEntity(Product::class, ['id' => 1])
                ],
                'productUnitCodes' => ['item'],
                'prices' => [$this->createPrice(10, self::TEST_CURRENCY, 10, 'item')],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'UAH'],
                'availableCurrencies' => [self::TEST_CURRENCY, 'UAH'],
                'finalCurrencies' => [self::TEST_CURRENCY],
                'expectedResult' => [
                    '1-item-5-USD' => null
                ]
            ]
        ];
    }

    /**
     * @dataProvider getMatchedPricesWhenGetPricesNotCalledProvider
     *
     * @param array $productPriceCriteria
     * @param array $supportedCurrencies
     * @param array $availableCurrencies
     * @param array $expectedResult
     */
    public function testGetMatchedPricesWhenGetPricesNotCalled(
        array $productPriceCriteria,
        array $supportedCurrencies,
        array $availableCurrencies,
        array $expectedResult
    ) {
        $this->currencyManager
            ->expects($this->any())
            ->method('getAvailableCurrencies')
            ->willReturn($availableCurrencies);

        $scopeCriteria = $this->getProductPriceScopeCriteria();
        $this->priceStorage
            ->expects($this->any())
            ->method('getSupportedCurrencies')
            ->with($scopeCriteria)
            ->willReturn($supportedCurrencies);

        $this->priceStorage
            ->expects($this->never())
            ->method('getPrices');

        $result = $this->provider->getMatchedPrices(
            $productPriceCriteria,
            $scopeCriteria
        );

        $this->assertEquals($expectedResult, $result);
    }


    /**
     * @return array
     */
    public function getMatchedPricesWhenGetPricesNotCalledProvider()
    {
        return [
            'with no price criteria' => [
                'productPriceCriteria' => [],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'UAH'],
                'availableCurrencies' => [self::TEST_CURRENCY, 'UAH'],
                'expectedResult' => []
            ],
            'with price criteria that contains only not allowed currency' => [
                'productPriceCriteria' => [
                    $this->getProductPriceCriteria(1, 'item', 10, 'EUR')
                ],
                'supportedCurrencies' => [self::TEST_CURRENCY, 'UAH'],
                'availableCurrencies' => [self::TEST_CURRENCY, 'UAH'],
                'expectedResult' => [
                    '1-item-10-EUR' => null
                ]
            ],
        ];
    }

    /**
     * @param int    $productId
     * @param string $unitCode
     * @param int $quantity
     * @param string $currency
     *
     * @return ProductPriceCriteria
     */
    private function getProductPriceCriteria(int $productId, string $unitCode, int $quantity, string $currency)
    {
        $unitDefaultPrecision = 1;
        $productUnit = new ProductUnit();
        $productUnit
            ->setCode($unitCode)
            ->setDefaultPrecision($unitDefaultPrecision);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        return new ProductPriceCriteria(
            $product,
            $productUnit,
            $quantity,
            $currency
        );
    }

    /**
     * @param float  $price
     * @param int    $quantity
     * @param string $currency
     * @param array  $unitCodes
     * @return array|ProductPriceDTO[]
     */
    private function getPricesArray($price, $quantity, $currency, array $unitCodes)
    {
        return array_map(function ($unitCode) use ($price, $quantity, $currency) {
            return $this->createPrice($price, $currency, $quantity, $unitCode);
        }, $unitCodes);
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param string $currency
     * @param string $unitCode
     * @return ProductPriceDTO
     */
    private function createPrice($price, $currency, $quantity, $unitCode)
    {
        return new ProductPriceDTO(
            $this->getEntity(Product::class, ['id' => 1]),
            Price::create($price, $currency),
            $quantity,
            $this->getEntity(ProductUnit::class, ['code' => $unitCode])
        );
    }

    /**
     * @return ProductPriceScopeCriteria|ProductPriceScopeCriteriaInterface
     */
    private function getProductPriceScopeCriteria()
    {
        if (null !== $this->productPriceScopeCriteria) {
            return $this->productPriceScopeCriteria;
        }

        $this->productPriceScopeCriteria = new ProductPriceScopeCriteria();
        $this->productPriceScopeCriteria->setCustomer($this->getEntity(Customer::class, ['id' => 1]));
        $this->productPriceScopeCriteria->setWebsite($this->getEntity(Website::class, ['id' => 1]));

        return $this->productPriceScopeCriteria;
    }
}
