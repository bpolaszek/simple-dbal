<?php

namespace BenTools\SimpleDBAL\Model;

trait ConfigurableTrait
{
    protected $options;

    /**
     * @param string $key
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
     * @param string $key
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
     * @param string $key
     * @param mixed $value
     */
    public function setOption($key, $value): void
    {
        if (null === $this->options) {
            $this->options = $this->getDefaultOptions();
        }
        $this->options[$key] = $value;
    }

    /**
     * @return array
     */
    public function getDefaultOptions(): array
    {
        return [];
    }
}
