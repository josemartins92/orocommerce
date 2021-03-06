<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Symfony\Component\EventDispatcher\Event;

/**
 * Provides event arguments for the create event of CombinedPriceList.
 */
class CombinedPriceListCreateEvent extends Event
{
    public const NAME = 'oro_pricing.combined_price_list.create';

    /** @var CombinedPriceList */
    private $combinedPriceList;

    /**
     * @param CombinedPriceList $combinedPriceList
     */
    public function __construct(CombinedPriceList $combinedPriceList)
    {
        $this->combinedPriceList = $combinedPriceList;
    }

    /**
     * @return CombinedPriceList
     */
    public function getCombinedPriceList(): CombinedPriceList
    {
        return $this->combinedPriceList;
    }
}
