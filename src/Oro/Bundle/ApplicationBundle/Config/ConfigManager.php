<?php

namespace Oro\Bundle\ApplicationBundle\Config;

class ConfigManager
{
    /**
     * @var array
     */
    protected $config = [
        'oro_b2b_rfp_admin.default_request_status' => 'open'
    ];

    /**
     * @param $configName
     *
     * @return mixed
     */
    public function get($configName)
    {
        return (array_key_exists($configName, $this->config)) ? $this->config[$configName] : null;
    }
}
