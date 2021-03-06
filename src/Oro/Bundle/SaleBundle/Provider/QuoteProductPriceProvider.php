<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

/**
 * Handles logic for getting prices for certain quote
 */
class QuoteProductPriceProvider
{
    /**
     * @var ProductPriceProviderInterface
     */
    protected $productPriceProvider;

    /**
     * @var ProductPriceScopeCriteriaFactoryInterface
     */
    protected $priceScopeCriteriaFactory;

    /**
     * @var CurrencyProviderInterface
     */
    protected $currencyProvider;

    /**
     * @param ProductPriceProviderInterface $productPriceProvider
     * @param ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
     * @param CurrencyProviderInterface                 $currencyProvider
     */
    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        CurrencyProviderInterface $currencyProvider
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->currencyProvider = $currencyProvider;
    }

    /**
     * @param Quote $quote
     *
     * @return array
     */
    public function getTierPrices(Quote $quote)
    {
        $products = $quote->getQuoteProducts()->filter(
            function (QuoteProduct $quoteProduct) {
                return $quoteProduct->getProduct() !== null;
            }
        )->map(
            function (QuoteProduct $quoteProduct) {
                return $quoteProduct->getProduct();
            }
        );

        return $this->fetchTierPrices($quote, $products->toArray());
    }

    /**
     * @param Quote $quote
     * @param array|Product[] $products
     *
     * @return array
     */
    public function getTierPricesForProducts(Quote $quote, array $products)
    {
        return $this->fetchTierPrices($quote, $products);
    }

    /**
     * @param Quote $quote
     * @param array|Product[] $products
     *
     * @return array
     */
    protected function fetchTierPrices(Quote $quote, array $products)
    {
        $tierPrices = [];

        if (count($products) > 0) {
            $tierPrices = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
                $this->priceScopeCriteriaFactory->createByContext($quote),
                $products,
                $this->currencyProvider->getCurrencyList()
            );
        }

        return $tierPrices;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    public function getMatchedPrices(Quote $quote)
    {
        $matchedPrices = [];
        $productsPriceCriteria = $this->getProductsPriceCriteria($quote);

        if ($productsPriceCriteria) {
            $scopeCriteria = $this->priceScopeCriteriaFactory->createByContext($quote);
            $matchedPrices = $this->productPriceProvider->getMatchedPrices($productsPriceCriteria, $scopeCriteria);
        }

        return array_map(function ($price) {
            if ($price instanceof Price) {
                return [
                    'value' => $price->getValue(),
                    'currency' => $price->getCurrency()
                ];
            }

            return $price;
        }, $matchedPrices);
    }

    /**
     * @param Quote $quote
     * @return array
     */
    protected function getProductsPriceCriteria(Quote $quote)
    {
        $productsPriceCriteria = [];

        /** @var QuoteProduct $quoteProduct */
        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            if (!$quoteProduct->getProduct()) {
                continue;
            }

            $product = $quoteProduct->getProduct();

            /** @var QuoteProductOffer $quoteProductOffer */
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                if (!$quoteProductOffer->getProductUnit() ||
                    !$quoteProductOffer->getQuantity() ||
                    !$quoteProductOffer->getPrice()
                ) {
                    continue;
                }

                $productsPriceCriteria[] = new ProductPriceCriteria(
                    $product,
                    $quoteProductOffer->getProductUnit(),
                    $quoteProductOffer->getQuantity(),
                    $quoteProductOffer->getPrice()->getCurrency()
                );
            }
        }

        return $productsPriceCriteria;
    }

    /**
     * Checks whatever quote has line items with no prices set
     * @param Quote $quote
     * @return bool
     */
    public function hasEmptyPrice(Quote $quote)
    {
        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $product = $quoteProduct->getProduct();
            if (!$product) {
                continue;
            }

            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                if ($quoteProductOffer->getPrice() === null || $quoteProductOffer->getPrice()->getValue() === null) {
                    return true;
                }
            }
        }

        return false;
    }
}
