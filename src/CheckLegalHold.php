<?php

declare(strict_types=1);

namespace AichaDigital\LaraPrivacyCore;

use AichaDigital\LaraPrivacyCore\Contracts\LegallyRetainable;
use DateTimeInterface;

/**
 * Pure decision: is a subject under an active legal retention hold at `now`?
 *
 *   retainedUntil()   decision
 *   ---------------   --------
 *   null           -> false   (nothing to hold)
 *   > now          -> true    (hold active — block / restrict)
 *   <= now         -> false   (hold lapsed)
 *
 * No side effects and no system clock: `now` is injected by the caller, so the
 * decision is deterministic and testable. This is the read-only gate the
 * v1.0 RetentionPolicy relies on; it never deletes or anonymises anything.
 */
final class CheckLegalHold
{
    public function isUnderRetention(LegallyRetainable $subject, DateTimeInterface $now): bool
    {
        $retainedUntil = $subject->retainedUntil();

        return $retainedUntil !== null && $retainedUntil > $now;
    }
}
