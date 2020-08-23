<?php

namespace M2Boilerplate\LazyLoad\Service;

use M2Boilerplate\LazyLoad\Config\Config;
use Magento\Framework\View\LayoutInterface;

class HtmlReplacer
{

    /**
     * 1x1 Pixel Gif as a placeholder
     */
    const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    /**
     * @var Config
     */
    protected $config;
    /**
     * @var ImageFactory
     */
    protected $imageFactory;

    public function __construct(Config $config, ImageFactory $imageFactory)
    {
        $this->config = $config;
        $this->imageFactory = $imageFactory;
    }

    public function replaceLazyLoadedContentInHtml(LayoutInterface $layout, string $html): string
    {
        // Images
        $regex = '/<img([^<]*)\ src=\"([^\"]+)\.(png|jpg|jpeg|gif|webp)([^>]+)>/msi';
        if (preg_match_all($regex, $html, $matches) !== false) {
            foreach ($matches[0] as $index => $match) {
                $fullSearchMatch = $matches[0][$index];
                $htmlTag = preg_replace('/>(.*)/msi', '>', $fullSearchMatch);
                $imageUrl = $matches[2][$index] . '.' . $matches[3][$index];
                if (!$this->isAllowedByImageUrl($imageUrl) || !$this->isAllowedByCssClass($htmlTag)) {
                    continue;
                }
                $newHtmlTag = $this->createLazyImageTag($htmlTag);
                $html = str_replace($fullSearchMatch, $newHtmlTag, $html);
            }
        }

        // src Tags
        $regex = '/<(iframe|video|source)([^<]*)\ src=\"([^\"]+)([^>]+)>/msi';
        if (preg_match_all($regex, $html, $matches) !== false) {
            foreach ($matches[0] as $index => $match) {
                $fullSearchMatch = $matches[0][$index];
                $htmlTag = preg_replace('/>(.*)/msi', '>', $fullSearchMatch);
                if (!$this->isAllowedByCssClass($htmlTag)) {
                    continue;
                }
                $newHtmlTag = $this->createLazySrcTag($htmlTag);
                $html = str_replace($fullSearchMatch, $newHtmlTag, $html);
            }
        }
        // srcset Tags
        $regex = '/<(img|source)([^<]*)\ srcset=\"([^\"]+)([^>]+)>/msi';
        if (preg_match_all($regex, $html, $matches) !== false) {
            foreach ($matches[0] as $index => $match) {
                $fullSearchMatch = $matches[0][$index];
                $htmlTag = preg_replace('/>(.*)/msi', '>', $fullSearchMatch);
                if (!$this->isAllowedByCssClass($htmlTag)) {
                    continue;
                }
                $newHtmlTag = $this->createLazySrcSetTag($htmlTag);
                $html = str_replace($fullSearchMatch, $newHtmlTag, $html);
            }
        }
        return $html;
    }

    protected function isAllowedByImageUrl(string $imageUrl): bool
    {
        if (strpos($imageUrl, '/media/captcha/') !== false) {
            return false;
        }

        if (strpos($imageUrl, 'http://') !== 0 && strpos($imageUrl, 'https://') !== 0) {
            return false;
        }

        return true;
    }

    protected function isAllowedByCssClass(string $htmlTag): bool
    {
        $cssClasses = $this->getAttribute($htmlTag, 'class');
        if (!$cssClasses) {
            return true;
        }
        $cssClasses = explode(' ', $cssClasses);
        $cssClasses = array_map('trim', $cssClasses);
        $blockedClasses = $this->config->getBlockedCssClasses();
        $foundBlockedClasses = array_intersect($blockedClasses, $cssClasses);
        return count($foundBlockedClasses) == 0;
    }

    protected function createLazyImageTag($imageTag): string
    {
        if ($this->getAttribute($imageTag, 'data-src')) {
            return $imageTag;
        }
        $imageTag = $this->addToAttribute($imageTag, 'class', $this->config->getCssClass());
        $imageTag = $this->addAttribute($imageTag, 'data-src', $this->getAttribute($imageTag, 'src'));

        $url = $this->getAttribute($imageTag, 'src');
        $imageTag = $this->replaceAttribute($imageTag, 'src', self::PLACEHOLDER);
        if (!$this->getAttribute($imageTag, 'width') || !$this->getAttribute($imageTag, 'height')) {
            /** @var Image $file */
            $file = $this->imageFactory->create(['url' => $url]);
            $imageTag = $this->replaceAttribute($imageTag, 'width', $file->getWidth());
            $imageTag = $this->replaceAttribute($imageTag, 'height', $file->getHeight());
        }


        return $imageTag;
    }

    protected function createLazySrcTag($srcTag): string
    {
        if ($this->getAttribute($srcTag, 'data-src')) {
            return $srcTag;
        }
        if (strpos($srcTag, '<source') !== 0) {
            $srcTag = $this->addToAttribute($srcTag, 'class', $this->config->getCssClass());
        }
        $srcTag = $this->addAttribute($srcTag, 'data-src', $this->getAttribute($srcTag, 'src'));
        $srcTag = $this->replaceAttribute($srcTag, 'src', self::PLACEHOLDER);
        if (strpos($srcTag, '<video') === 0 && $this->getAttribute($srcTag, 'poster')) {
            $srcTag = $this->addToAttribute($srcTag, 'data-poster', $this->getAttribute($srcTag, 'poster'));
            $srcTag = $this->removeAttribute($srcTag, 'poster');
        }

        return $srcTag;
    }

    protected function createLazySrcSetTag($imageTag): string
    {
        if ($this->getAttribute($imageTag, 'data-srcset')) {
            return $imageTag;
        }
        if (strpos($imageTag, '<img') === 0) {
            $imageTag = $this->addToAttribute($imageTag, 'class', $this->config->getCssClass());
        }
        $imageTag = $this->addAttribute($imageTag, 'data-srcset', $this->getAttribute($imageTag, 'srcset'));
        if ($this->getAttribute($imageTag, 'sizes')) {
            $imageTag = $this->addAttribute($imageTag, 'data-sizes', $this->getAttribute($imageTag, 'sizes'));
            $imageTag = $this->removeAttribute($imageTag, 'sizes');
        }
        $imageTag = $this->removeAttribute($imageTag, 'srcset');

        return $imageTag;
    }

    protected function getAttribute(string $htmlTag, string $attribute): ?string
    {
        if (preg_match('/\ ' . $attribute . '=\"([^\"]+)/', $htmlTag, $match)) {
            $altText = $match[1];
            $altText = strtr($altText, ['"' => '', "'" => '']);
            return $altText;
        }

        return null;
    }

    protected function addAttribute(string $htmlTag, string $attribute, string $attributeValue): string
    {
        $oldAttributeValue = $this->getAttribute($htmlTag, $attribute);
        if ($oldAttributeValue !== null) {
            return $this->replaceAttribute($htmlTag, $attribute, $attributeValue);
        }
        return preg_replace('/(<[^\s^>]+)/msi', sprintf('$1 %s="%s"', $attribute, $attributeValue), $htmlTag);
    }

    protected function addToAttribute(string $htmlTag, string $attribute, string $attributeValue): string
    {
        $oldAttributeValue = $this->getAttribute($htmlTag, $attribute);
        if ($oldAttributeValue === null) {
            return $this->addAttribute($htmlTag, $attribute, $attributeValue);
        }
        if (strpos($oldAttributeValue, $attributeValue) !== false) {
            return $htmlTag;
        }
        return preg_replace('/\ ' . $attribute . '=\"([^\"]+)/', sprintf(' %s="$1 %s', $attribute, $attributeValue), $htmlTag);
    }

    protected function replaceAttribute(string $htmlTag, string $attribute, string $attributeValue): string
    {
        $oldAttributeValue = $this->getAttribute($htmlTag, $attribute);
        if ($oldAttributeValue === null) {
            return $this->addAttribute($htmlTag, $attribute, $attributeValue);
        }
        return preg_replace('/\ ' . $attribute . '=\"([^\"]+)/', sprintf(' %s="%s', $attribute, $attributeValue), $htmlTag);
    }

    protected function removeAttribute(string $htmlTag, string $attribute): string
    {
        $oldAttributeValue = $this->getAttribute($htmlTag, $attribute);
        if ($oldAttributeValue === null) {
            return $htmlTag;
        }
        return preg_replace('/\ (' . $attribute . '=\"[^\"]+")/', '', $htmlTag);
    }
}