<?php

declare(strict_types=1);

namespace AichaDigital\LaraPrivacyCore\Contracts;

use DateTimeInterface;

/**
 * A domain object that carries a legal retention obligation.
 *
 * The domain (e.g. an Invoice in a billing package) implements this and owns
 * the obligation; lara-privacy only reads it to decide whether the object may
 * be touched. The privacy layer type-hints this interface, never the concrete
 * model.
 */
interface LegallyRetainable
{
    /**
     * The instant until which the object must be retained under an ordinary
     * statutory retention hold, or null when it is not under retention
     * (e.g. a proforma invoice).
     *
     * This is the ordinary legal retention hold, NOT a litigation or
     * inspection hold — that is a separate, future concept.
     */
    public function retainedUntil(): ?DateTimeInterface;
}
