<?php

namespace BenTools\SimpleDBAL\Model;

trait ConfigurableTrait
{

    protected $options;

    /**
     * @param $key
     * @return mixed
     */
    public function getOption($key)
    {
        if (null === $this->options) {
            $this->options = $this->getDefaultOptions();
        }
        return $this->options[$key] ?? null;
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasOption($key): bool
    {
        if (null === $this->options) {
            $this->options = $this->getDefaultOptions();
        }
        return array_key_exists($key, $this->options);
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        if (null === $this->options) {
            $this->options = $this->getDefaultOptions();
        }
        return $this->options;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setOption($key, $value)
    {
        if (null === $this->options) {
            $this->options = $this->getDefaultOptions();
        }
        $this->options[$key] = $value;
    }

    /**
     * @param $key
     */
    public function unsetOption($key)
    {
        if (null === $this->options) {
            $this->options = $this->getDefaultOptions();
        }
        unset($this->options[$key]);
    }

    /**
     * @return array
     */
    public function getDefaultOptions(): array
    {
        return [];
    }
}
