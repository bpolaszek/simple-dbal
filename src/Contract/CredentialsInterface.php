<?php

namespace BenTools\SimpleDBAL\Contract;

interface CredentialsInterface
{
    /**
     * @return string
     */
    public function getHostname(): string;

    /**
     * @return string
     */
    public function getUser(): string;

    /**
     * @return string
     */
    public function getPassword(): ?string;

    /**
     * @return string
     */
    public function getDatabase(): ?string;

    /**
     * @return string
     */
    public function getPlatform(): string;

    /**
     * @return int
     */
    public function getPort(): int;
}
