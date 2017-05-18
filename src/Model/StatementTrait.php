<?php

namespace BenTools\SimpleDBAL\Model;

use BenTools\SimpleDBAL\Contract\StatementInterface;

trait StatementTrait
{
    /**
     * @var array
     */
    protected $values;

    /**
     * @inheritDoc
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    /**
     * @inheritDoc
     */
    public function withValues(array $values = null): StatementInterface
    {
        $clone = clone $this;
        $clone->values = $values;
        return $clone;
    }

    /**
     * @return bool
     */
    protected function hasValues(): bool
    {
        return !empty($this->values);
    }

    /**
     * @return bool
     */
    protected function hasAnonymousPlaceholders(): bool
    {
        return $this->hasValues() && 0 === array_keys($this->values)[0];
    }

    /**
     * @return bool
     */
    protected function hasNamedPlaceholders(): bool
    {
        return $this->hasValues() && 'string' === gettype(array_keys($this->values)[0]);
    }
}
