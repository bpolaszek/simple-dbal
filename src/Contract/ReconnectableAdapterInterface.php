<?php

namespace BenTools\SimpleDBAL\Contract;

interface ReconnectableAdapterInterface
{
    const OPT_MAX_RECONNECT_ATTEMPTS     = 'max_reconnect_attempts';
    const OPT_USLEEP_AFTER_FIRST_ATTEMPT = 'usleep_after_first_attempt';
    const DEFAULT_USLEEP_AFTER_FIRST_ATTEMPT = 50000;
    const DEFAULT_MAX_RECONNECT_ATTEMPTS = 0;

    /**
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * @return bool
     */
    public function shouldReconnect(): bool;
}
