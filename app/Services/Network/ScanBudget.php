<?php

namespace App\Services\Network;

class ScanBudget
{
    public int $requests = 0;

    private float $deadline;

    public function __construct(public readonly int $maximum = 20, int $seconds = 20)
    {
        $this->deadline = microtime(true) + $seconds;
    }

    public function exhausted(): bool
    {
        return $this->requests >= $this->maximum || microtime(true) >= $this->deadline;
    }

    public function take(): int
    {
        if ($this->requests >= $this->maximum || microtime(true) >= $this->deadline) {
            throw new ResourceLimit('Batas jumlah permintaan atau waktu pemindaian tercapai.');
        }$this->requests++;

        return max(1, min(4000, (int) (($this->deadline - microtime(true)) * 1000)));
    }
}
