<?php
/**
 * File.php
 *
 * @category    Leonex
 * @package     ???
 * @author      Thomas Hampe <hampe@leonex.de>
 * @copyright   Copyright (c) 2020, LEONEX Internet GmbH
 */


namespace M2Boilerplate\LazyLoad\Service;


use M2Boilerplate\LazyLoad\Config\Config;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;

class Image
{
    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var FileDriver
     */
    protected $fileDriver;
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $absolutePath;

    /**
     * @var int
     */
    protected $width = null;

    /**
     * @var int
     */
    protected $height = null;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * File constructor.
     *
     * @param DirectoryList              $directoryList
     * @param FileDriver                 $fileDriver
     * @param Escaper $escaper
     * @param Config                     $config
     * @param string                     $url
     */
    public function __construct(
        DirectoryList $directoryList,
        FileDriver $fileDriver,
        Escaper $escaper,
        Config $config,
        string $url
    ) {
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->absolutePath = $this->resolve($url);
        $this->url = $url;
        $this->config = $config;
        $this->escaper = $escaper;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected function resolve(string $url): string
    {
        // phpcs:disable Magento2.Functions.DiscouragedFunction
        $parsedUrl = parse_url($url);
        if (!$parsedUrl) {
            return '';
        }

        $path = $parsedUrl['path'];
        $path = preg_replace('/^\/pub\//', '/', (string)$path);
        $path = preg_replace('/\/static\/version([0-9]+\/)/', '/static/', (string)$path);
        $path = $this->getAbsolutePathFromImagePath((string)$path);

        return $path;
    }

    /**
     * @param string $imagePath
     *
     * @return string
     */
    protected function getAbsolutePathFromImagePath(string $imagePath): string
    {
        return $this->directoryList->getRoot() . '/pub' . $imagePath;
    }

    public function getWidth(): int
    {
        if ($this->width === null) {
            $this->calculateImageSize();
        }
        return $this->width;
    }

    public function getHeight(): int
    {
        if ($this->height === null) {
            $this->calculateImageSize();
        }
        return $this->height;
    }

    protected function calculateImageSize(): void
    {
        $path = $this->getImagePath();
        if ($path === null) {
            $this->width = 0;
            $this->height = 0;
            return;
        }
        $size = \getimagesize($path);
        $this->width = $size[0];
        $this->height = $size[1];
    }

    protected function getImagePath(): ?string
    {
        $path = $this->absolutePath;
        try {
            if (!$this->fileDriver->isExists($this->absolutePath)) {
                $path = $this->url;
            }
        }
        catch (FileSystemException $e) {
            return null;
        }

        return $path;
    }

}