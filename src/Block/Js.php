<?php

namespace M2Boilerplate\LazyLoad\Block;

use M2Boilerplate\LazyLoad\Config\Config;
use Magento\Framework\View\Element\Template;

class Js extends Template
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $lazyLoadConfig;

    public function __construct(Template\Context $context, Config $config, array $lazyLoadConfig = [], array $data = [])
    {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->lazyLoadConfig = $lazyLoadConfig;
        $this->lazyLoadConfig['elements_selector'] = $this->getLazyLoadSelector();
    }

    public function getLazyLoadSelector()
    {
        return '.'.$this->config->getCssClass();
    }

    public function getLazyLoadConfig()
    {

        return json_encode($this->lazyLoadConfig);
    }

}