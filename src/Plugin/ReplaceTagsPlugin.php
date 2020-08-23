<?php

namespace M2Boilerplate\LazyLoad\Plugin;

use M2Boilerplate\LazyLoad\Config\Config;
use M2Boilerplate\LazyLoad\Service\HtmlReplacer;
use Magento\Framework\View\LayoutInterface;

class ReplaceTagsPlugin
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var HtmlReplacer
     */
    protected $htmlReplacer;

    public function __construct(Config $config, HtmlReplacer $htmlReplacer)
    {
        $this->config = $config;
        $this->htmlReplacer = $htmlReplacer;
    }


    public function afterGetOutput(LayoutInterface $layout, string $output)
    {
        if ($this->shouldModifyOutput($layout) === false) {
            return $output;
        }

        return $this->htmlReplacer->replaceLazyLoadedContentInHtml($layout, $output);
    }

    /**
     * @param LayoutInterface $layout
     * @return bool
     */
    protected function shouldModifyOutput(LayoutInterface $layout): bool
    {
        $handles = $layout->getUpdate()->getHandles();
        if (empty($handles)) {
            return false;
        }

        $skippedHandles = [
            'sales_email_order_invoice_items'
        ];

        if (array_intersect($skippedHandles, $handles)) {
            return false;
        }

        if ($this->config->isEnabled() === false) {
            return false;
        }

        return true;
    }
}