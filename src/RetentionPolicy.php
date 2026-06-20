<?php

declare(strict_types=1);

namespace AichaDigital\LaraPrivacyCore;

use AichaDigital\LaraPrivacyCore\Enums\RetentionBasis;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Declarative retention policy: a legal basis and a duration.
 *
 * The core applies the duration to an anchor; the domain that owns the object
 * (e.g. larabill) decides the anchor — fiscal-year-end, event date — and passes
 * it to resolveRetainedUntil(). The duration defaults to the basis default and
 * may be overridden (the lara-privacy mechanism feeds configured overrides).
 *
 * ExpiryMode (delete / anonymize / legal_hold) is intentionally absent: it is
 * only acted on by the v1.1 prune, so it enters when that behaviour does.
 */
final class RetentionPolicy
{
    private readonly DateInterval $duration;

    public function __construct(
        public readonly RetentionBasis $basis,
        ?DateInterval $duration = null,
    ) {
        $this->duration = $duration ?? $basis->defaultDuration();
    }

    /**
     * The instant until which an object anchored at $anchor must be retained.
     * Pure: $anchor is copied, never mutated.
     */
    public function resolveRetainedUntil(DateTimeInterface $anchor): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($anchor)->add($this->duration);
    }
}
