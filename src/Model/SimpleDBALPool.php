<?php

namespace BenTools\SimpleDBAL\Model;

use BenTools\SimpleDBAL\Contract\ConnectionInterface;
use BenTools\SimpleDBAL\Contract\ResultInterface;
use BenTools\SimpleDBAL\Contract\StatementInterface;

class SimpleDBALPool
{

    const READ_WRITE = 1;
    const READ_ONLY  = 2;
    const WRITE_ONLY = 3;

    protected $rConnections  = [];
    protected $wConnections  = [];

    /**
     * @param ConnectionInterface $connection
     * @param int $access
     * @param int $weight
     */
    public function attach(ConnectionInterface $connection, int $access = self::READ_WRITE, int $weight = 1)
    {
        if ($weight >= 1) {
            switch ($access) {
                case self::READ_ONLY:
                    $this->rConnections[] = ['c' => $connection, 'w' => $weight];
                    break;
                case self::WRITE_ONLY:
                    $this->wConnections[] = ['c' => $connection, 'w' => $weight];
                    break;
                case self::READ_WRITE:
                    $this->rConnections[] = ['c' => $connection, 'w' => $weight];
                    $this->wConnections[] = ['c' => $connection, 'w' => $weight];
                    break;
                default:
                    throw new \InvalidArgumentException("Invalid access argument.");
            }
        }
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function detach(ConnectionInterface $connection)
    {
        $reorderReadConnections = false;
        $reorderWriteConnections = false;
        foreach ($this->rConnections as $c => $connectionBag) {
            if ($connectionBag['c'] === $connection) {
                unset($this->rConnections[$c]);
                $reorderReadConnections = true;
            }
        }
        foreach ($this->wConnections as $c => $connectionBag) {
            if ($connectionBag['c'] === $connection) {
                unset($this->wConnections[$c]);
                $reorderWriteConnections = true;
            }
        }

        if ($reorderReadConnections) {
            $this->rConnections = array_values($this->rConnections);
        }

        if ($reorderWriteConnections) {
            $this->wConnections = array_values($this->wConnections);
        }
    }

    /**
     * @inheritDoc
     */
    public function prepareReadStmt(string $query, array $values = null): StatementInterface
    {
        return $this->selectReadConnection()->prepare($query, $values);
    }

    /**
     * @inheritDoc
     */
    public function prepareWriteStmt(string $query, array $values = null): StatementInterface
    {
        return $this->selectWriteConnection()->prepare($query, $values);
    }

    /**
     * @inheritdoc
     */
    public function read($stmt, array $values = null): ResultInterface
    {
        if ($stmt instanceof StatementInterface) {
            return $stmt->getConnection()->execute($stmt);
        } else {
            return $this->selectReadConnection()->execute($stmt, $values);
        }
    }

    /**
     * @inheritdoc
     */
    public function write($stmt, array $values = null): ResultInterface
    {
        if ($stmt instanceof StatementInterface) {
            return $stmt->getConnection()->execute($stmt);
        } else {
            return $this->selectWriteConnection()->execute($stmt, $values);
        }
    }

    /**
     * @return ConnectionInterface
     */
    private function selectReadConnection(): ConnectionInterface
    {
        $nbConnections = count($this->rConnections);
        switch ($nbConnections) {
            case 0:
                throw new \RuntimeException("No connection available to read database.");
            case 1:
                return $this->rConnections[0];
            default:
                $fill = [];
                foreach ($this->rConnections as $c => $connection) {
                    $fill = array_merge($fill, array_fill(0, $connection['w'], $c));
                }
                $cnt = count($fill);
                return $this->rConnections[$fill[random_int(0, $cnt)]];
        }
    }

    /**
     * @return ConnectionInterface
     */
    private function selectWriteConnection(): ConnectionInterface
    {
        $nbConnections = count($this->wConnections);
        switch ($nbConnections) {
            case 0:
                throw new \RuntimeException("No connection available to write on database.");
            case 1:
                return $this->wConnections[0];
            default:
                $fill = [];
                foreach ($this->wConnections as $c => $connection) {
                    $fill = array_merge($fill, array_fill(0, $connection['w'], $c));
                }
                $cnt = count($fill);
                return $this->wConnections[$fill[random_int(0, $cnt)]];
        }
    }
}
