<?php

namespace Symfony\Config;

use Symfony\Component\Config\Loader\ParamConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class is automatically generated to help in creating a config.
 */
class WebpackEncoreConfig implements \Symfony\Component\Config\Builder\ConfigBuilderInterface
{
    private $outputPath;
    private $crossorigin;
    private $preload;
    private $cache;
    private $strictMode;
    private $builds;
    private $scriptAttributes;
    private $linkAttributes;
    private $_usedProperties = [];
    private $_hasDeprecatedCalls = false;

    /**
     * The path where Encore is building the assets - i.e. Encore.setOutputPath()
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function outputPath($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['outputPath'] = true;
        $this->outputPath = $value;

        return $this;
    }

    /**
     * crossorigin value when Encore.enableIntegrityHashes() is used, can be false (default), anonymous or use-credentials
     * @default false
     * @param ParamConfigurator|false|'anonymous'|'use-credentials' $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function crossorigin($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['crossorigin'] = true;
        $this->crossorigin = $value;

        return $this;
    }

    /**
     * preload all rendered script and link tags automatically via the http2 Link header.
     * @default false
     * @param ParamConfigurator|bool $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function preload($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['preload'] = true;
        $this->preload = $value;

        return $this;
    }

    /**
     * Enable caching of the entry point file(s)
     * @default false
     * @param ParamConfigurator|bool $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function cache($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['cache'] = true;
        $this->cache = $value;

        return $this;
    }

    /**
     * Throw an exception if the entrypoints.json file is missing or an entry is missing from the data
     * @default true
     * @param ParamConfigurator|bool $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function strictMode($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['strictMode'] = true;
        $this->strictMode = $value;

        return $this;
    }

    /**
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function builds(string $name, mixed $value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['builds'] = true;
        $this->builds[$name] = $value;

        return $this;
    }

    /**
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function scriptAttributes(string $name, mixed $value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['scriptAttributes'] = true;
        $this->scriptAttributes[$name] = $value;

        return $this;
    }

    /**
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function linkAttributes(string $name, mixed $value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['linkAttributes'] = true;
        $this->linkAttributes[$name] = $value;

        return $this;
    }

    public function getExtensionAlias(): string
    {
        return 'webpack_encore';
    }

    public function __construct(array $config = [])
    {
        if (array_key_exists('output_path', $config)) {
            $this->_usedProperties['outputPath'] = true;
            $this->outputPath = $config['output_path'];
            unset($config['output_path']);
        }

        if (array_key_exists('crossorigin', $config)) {
            $this->_usedProperties['crossorigin'] = true;
            $this->crossorigin = $config['crossorigin'];
            unset($config['crossorigin']);
        }

        if (array_key_exists('preload', $config)) {
            $this->_usedProperties['preload'] = true;
            $this->preload = $config['preload'];
            unset($config['preload']);
        }

        if (array_key_exists('cache', $config)) {
            $this->_usedProperties['cache'] = true;
            $this->cache = $config['cache'];
            unset($config['cache']);
        }

        if (array_key_exists('strict_mode', $config)) {
            $this->_usedProperties['strictMode'] = true;
            $this->strictMode = $config['strict_mode'];
            unset($config['strict_mode']);
        }

        if (array_key_exists('builds', $config)) {
            $this->_usedProperties['builds'] = true;
            $this->builds = $config['builds'];
            unset($config['builds']);
        }

        if (array_key_exists('script_attributes', $config)) {
            $this->_usedProperties['scriptAttributes'] = true;
            $this->scriptAttributes = $config['script_attributes'];
            unset($config['script_attributes']);
        }

        if (array_key_exists('link_attributes', $config)) {
            $this->_usedProperties['linkAttributes'] = true;
            $this->linkAttributes = $config['link_attributes'];
            unset($config['link_attributes']);
        }

        if ($config) {
            throw new InvalidConfigurationException(sprintf('The following keys are not supported by "%s": ', __CLASS__).implode(', ', array_keys($config)));
        }
    }

    public function toArray(): array
    {
        $output = [];
        if (isset($this->_usedProperties['outputPath'])) {
            $output['output_path'] = $this->outputPath;
        }
        if (isset($this->_usedProperties['crossorigin'])) {
            $output['crossorigin'] = $this->crossorigin;
        }
        if (isset($this->_usedProperties['preload'])) {
            $output['preload'] = $this->preload;
        }
        if (isset($this->_usedProperties['cache'])) {
            $output['cache'] = $this->cache;
        }
        if (isset($this->_usedProperties['strictMode'])) {
            $output['strict_mode'] = $this->strictMode;
        }
        if (isset($this->_usedProperties['builds'])) {
            $output['builds'] = $this->builds;
        }
        if (isset($this->_usedProperties['scriptAttributes'])) {
            $output['script_attributes'] = $this->scriptAttributes;
        }
        if (isset($this->_usedProperties['linkAttributes'])) {
            $output['link_attributes'] = $this->linkAttributes;
        }
        if ($this->_hasDeprecatedCalls) {
            trigger_deprecation('symfony/config', '7.4', 'Calling any fluent method on "%s" is deprecated; pass the configuration to the constructor instead.', $this::class);
        }

        return $output;
    }

}
