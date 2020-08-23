<?php

namespace M2Boilerplate\LazyLoad\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{

    const CONFIG_PATH_ENABLED = 'dev/lazy_load/enabled';
    const CONFIG_PATH_CSS_CLASS = 'dev/lazy_load/css_class';
    const CONFIG_PATH_BLOCKED_CSS_CLASSES = 'dev/lazy_load/blocked_css_classes';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {

        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->isSetFlag(self::CONFIG_PATH_ENABLED);
    }

    public function getCssClass(): string
    {
        return (string) $this->scopeConfig->getValue(self::CONFIG_PATH_CSS_CLASS);
    }

    public function getBlockedCssClasses(): array
    {
        $blockedCssClasses = $this->scopeConfig->getValue(self::CONFIG_PATH_BLOCKED_CSS_CLASSES);
        if (!$blockedCssClasses) {
            return [];
        }
        $blockedCssClasses = explode(',', $blockedCssClasses);
        $blockedCssClasses = array_map('trim', $blockedCssClasses);
        return $blockedCssClasses;
    }

}