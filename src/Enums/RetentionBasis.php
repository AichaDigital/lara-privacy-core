<?php

declare(strict_types=1);

namespace AichaDigital\LaraPrivacyCore\Enums;

use DateInterval;

/**
 * Why a subject or object is retained, with the default duration for that reason.
 *
 * Durations are sane Spanish defaults (ADR-001); the lara-privacy mechanism may
 * override them by config. They are a convenience, never legal truth — final
 * correctness belongs to the controller/DPO. The computation anchor (when the
 * clock starts: e.g. fiscal-year-end vs the event date) is owned by the domain
 * that implements LegallyRetainable, not by this enum.
 */
enum RetentionBasis
{
    /** Accounting / commercial books and records — Código de Comercio art. 30 (6 years). */
    case COMMERCIAL;

    /** Tax prescription period — LGT art. 66 (4 years). */
    case TAX;

    /** Consent records, kept past withdrawal as proof of lawful processing. */
    case CONSENT;

    public function defaultDuration(): DateInterval
    {
        return new DateInterval(match ($this) {
            self::COMMERCIAL => 'P6Y',
            self::TAX => 'P4Y',
            self::CONSENT => 'P3Y',
        });
    }
}
