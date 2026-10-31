<?php

namespace VentureDrake\LaravelCrm\Contracts;

/**
 * Issues the sequential `number` of CRM documents (leads, deals, quotes,
 * orders, invoices, deliveries, purchase orders).
 *
 * Bound by class name in `laravel-crm.number_generator`. When the key is null
 * NumberGeneratorService keeps its own cache-counter implementation. An
 * implementation does not open transactions of its own: the caller that
 * creates the document owns the transaction the number is issued in.
 */
interface GeneratesNumbers
{
    public function next(string $modelClass, int $startAt = 1000, int $lockSeconds = 10, int $waitSeconds = 5): int;

    public function reset(string $modelClass): void;
}
