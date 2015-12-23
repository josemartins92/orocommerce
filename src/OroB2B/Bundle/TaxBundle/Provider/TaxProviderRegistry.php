<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

class TaxProviderRegistry
{
    /**
     * @var TaxProviderInterface[]
     */
    protected $providers = [];

    /**
     * Add provider to the registry
     *
     * @param string $name Name of provider
     * @param TaxProviderInterface $provider
     */
    public function addProvider($name, TaxProviderInterface $provider)
    {
        if (array_key_exists($name, $this->providers)) {
            throw new \LogicException(
                sprintf('Tax provider with name "%s" already registered', $name)
            );
        }
        $this->providers[$name] = $provider;
    }

    /**
     * @return TaxProviderInterface[]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Get provider by name
     *
     * @param string $name
     * @return TaxProviderInterface
     * @throws \LogicException Throw exception when provider with specified name not found
     */
    public function getProvider($name)
    {
        if (!array_key_exists($name, $this->providers)) {
            throw new \LogicException(
                sprintf('Tax provider with name "%s" does not exist', $name)
            );
        }

        return $this->providers[$name];
    }
}
