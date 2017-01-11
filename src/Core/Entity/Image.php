<?php

namespace Core\Entity;
/**
 * Class Image
 * @package Core\Entity
 */
class Image
{
    /** @var array */
    protected $options = [];

    /** @var string */
    protected $sourceFile;

    /** @var string */
    protected $newFileName;

    /** @var string */
    protected $newFilePath;

    /** @var string */
    protected $temporaryFile;

    /** @var string */
    protected $finalCommandStr;

    /** @var array */
    protected $defaultParams;

    /**
     * Image constructor.
     * @param string $options
     * @param $sourceFile
     * @param $defaultParams
     */
    public function __construct($options, $sourceFile, $defaultParams)
    {
        $this->defaultParams = $defaultParams;
        $this->options = $this->parseOptions($options);
        $this->sourceFile = $sourceFile;

        $this->newFileName = md5(implode('.', $this->options) . $sourceFile);
        $this->newFilePath = TMP_DIR . $this->newFileName;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * @param string $sourceFile
     */
    public function setSourceFile($sourceFile)
    {
        $this->sourceFile = $sourceFile;
    }

    /**
     * @return string
     */
    public function getNewFileName()
    {
        return $this->newFileName;
    }

    /**
     * @param string $newFileName
     */
    public function setNewFileName($newFileName)
    {
        $this->newFileName = $newFileName;
    }

    /**
     * @return string
     */
    public function getNewFilePath()
    {
        return $this->newFilePath;
    }

    /**
     * @param string $newFilePath
     */
    public function setNewFilePath($newFilePath)
    {
        $this->newFilePath = $newFilePath;
    }

    /**
     * @return string
     */
    public function getTemporaryFile()
    {
        return $this->temporaryFile;
    }

    /**
     * @param $commandStr
     */
    public function setFinalCommandStr($commandStr)
    {
        $this->finalCommandStr = $commandStr;
    }

    public function getFinalCommandStr()
    {
        return $this->finalCommandStr;
    }

    /**
     * Parse options: match options keys and merge default options with given ones
     *
     * @param $options
     * @return array
     */
    public function parseOptions($options)
    {
        $defaultOptions = $this->defaultParams['default_options'];
        $optionsKeys = $this->defaultParams['options_keys'];
        $optionsSeparator = !empty($this->defaultParams['options_separator']) ? $this->defaultParams['options_separator'] : ',';
        $optionsUrl = explode($optionsSeparator, $options);
        $options = [];
        foreach ($optionsUrl as $option) {
            $optArray = explode('_', $option);
            if (key_exists($optArray[0], $optionsKeys) && !empty($optionsKeys[$optArray[0]])) {
                $options[$optionsKeys[$optArray[0]]] = $optArray[1];
            }
        }
        return array_merge($defaultOptions, $options);
    }


    /**
     * Save given image to temporary file and return the path
     *
     * @throws \Exception
     */
    public function saveToTemporaryFile()
    {
        if (!$resource = @fopen($this->getSourceFile(), "r")) {
            throw  new \Exception('Error occurred while trying to read the file Url : ' . $this->getSourceFile());
        }
        $content = "";
        while ($line = fread($resource, 1024)) {
            $content .= $line;
        }
        $this->temporaryFile = TMP_DIR . uniqid("", true);
        file_put_contents($this->temporaryFile, $content);
    }

    /**
     * Extract a value from given array and unset it.
     *
     * @param $key
     * @return null
     */
    public function extractByKey($key)
    {
        $value = null;
        if (isset($this->options[$key])) {
            $value = $this->options[$key];
            unset($this->options[$key]);
        }
        return $value;
    }


    /**
     * Get the image Identity information
     *
     * @return string
     */
    public function getImageIdentity()
    {
        exec('/usr/bin/identify ' . $this->getNewFilePath(), $output);
        return !empty($output[0]) ? $output[0] : "";
    }

    /**
     * Remove the generated files
     */
    public function unlinkUsedFiles()
    {
        unlink($this->getTemporaryFile());
        unlink($this->getNewFilePath());
    }
}