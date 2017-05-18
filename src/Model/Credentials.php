<?php

namespace BenTools\SimpleDBAL\Model;

use BenTools\SimpleDBAL\Contract\CredentialsInterface;

class Credentials implements CredentialsInterface
{
    /**
     * @var string
     */
    private $hostname;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $platform;

    /**
     * @var string
     */
    private $port;

    /**
     * Credentials constructor.
     * @param string $hostname
     * @param string $user
     * @param string|null $password
     * @param string|null $database
     * @param string $platform
     * @param int $port
     */
    public function __construct(string $hostname, string $user, string $password = null, string $database = null, string $platform = 'mysql', int $port = 3306)
    {
        $this->hostname = $hostname;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->platform = $platform;
        $this->port = $port;
    }

    /**
     * @inheritdoc
     */
    public function getHostname(): string
    {
        return $this->hostname;
    }

    /**
     * @inheritdoc
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @inheritdoc
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @inheritdoc
     */
    public function getDatabase(): ?string
    {
        return $this->database;
    }

    /**
     * @inheritdoc
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * @inheritdoc
     */
    public function getPort(): int
    {
        return $this->port;
    }
}
