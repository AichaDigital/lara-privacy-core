# Implementing `LegallyRetainable`

How any domain object declares a legal retention obligation that the privacy
layer will respect.

- **Package:** `aichadigital/lara-privacy-core` (universal, zero runtime deps)
- **Audience:** authors of domain packages (billing, medical, HR, education…)
- **Companion:** consumer-side wiring, jurisdictional presets and a full worked
  Eloquent adapter live in the `aichadigital/lara-privacy` Laravel package —
  see its `docs/contract-adapter-guide.md`.

---

## The rule that governs this

> lara-privacy defines privacy contracts. Domain packages implement them.
> **No domain package — and no jurisdiction — defines the shape of the core.**

The core owns one tiny universal contract. Your domain owns the *obligation*:
how long a record must be kept and why. The privacy layer only *reads* your
answer and decides whether the object may be touched. It never decides the life
of your data for you.

## The contract

```php
namespace AichaDigital\LaraPrivacyCore\Contracts;

use DateTimeInterface;

interface LegallyRetainable
{
    public function retainedUntil(): ?DateTimeInterface;
}
```

One method. It answers a single question: **until when must this object be
kept?**

| `retainedUntil()` returns | Meaning                                         |
| ------------------------- | ----------------------------------------------- |
| a future instant          | under an active retention hold — must be kept    |
| `null`                    | no retention obligation (e.g. a draft, a proforma) |
| a past instant            | the hold has lapsed — free to erase/anonymise    |

This is the **ordinary statutory retention hold** (an accounting period, a
clinical record minimum, a consent-evidence window). It is **not** a litigation
or inspection hold — an exceptional, indefinite block is a separate, future
concept and must not be squeezed into this method.

`DateTimeInterface` (not Carbon) keeps the contract framework-free. Carbon
implements `DateTimeInterface`, so an Eloquent accessor returning a `Carbon`
satisfies it without any adapter.

## How the privacy layer reads it

You implement "until when". The core derives "held right now":

```php
namespace AichaDigital\LaraPrivacyCore;

final class CheckLegalHold
{
    public function isUnderRetention(LegallyRetainable $subject, DateTimeInterface $now): bool
    {
        $retainedUntil = $subject->retainedUntil();

        return $retainedUntil !== null && $retainedUntil > $now;
    }
}
```

Pure and clock-free: `$now` is injected, so the decision is deterministic and
trivially testable. The hold is **derived**, never something your domain
implements. Your model says *when*; `CheckLegalHold` says *whether*.

## Three neutral examples

Plain PHP. No framework, no config, no jurisdiction baked into the core —
each domain computes its own instant however its law and business require.

### 1. A medical record

Kept for a minimum number of years after the episode of care closes.

```php
use AichaDigital\LaraPrivacyCore\Contracts\LegallyRetainable;
use DateTimeImmutable;
use DateTimeInterface;

final class MedicalRecord implements LegallyRetainable
{
    public function __construct(
        private readonly DateTimeImmutable $episodeClosedAt,
        private readonly int $minimumYears, // the consumer's own legal input
    ) {}

    public function retainedUntil(): ?DateTimeInterface
    {
        return $this->episodeClosedAt->modify("+{$this->minimumYears} years");
    }
}
```

### 2. An accounting document

Kept until the end of the fiscal year it belongs to, plus a statutory period.
A document that was never booked carries no obligation.

```php
final class AccountingDocument implements LegallyRetainable
{
    public function __construct(
        private readonly ?DateTimeImmutable $fiscalYearEnd, // null = not booked yet
        private readonly int $years,
    ) {}

    public function retainedUntil(): ?DateTimeInterface
    {
        if ($this->fiscalYearEnd === null) {
            return null; // a draft / unbooked document is free to discard
        }

        return $this->fiscalYearEnd->modify("+{$this->years} years");
    }
}
```

### 3. A minor's consent record

Evidence of consent kept while the data subject is a minor and for a window
afterwards; if consent was withdrawn and the evidence is no longer needed, the
obligation is gone.

```php
final class MinorConsentRecord implements LegallyRetainable
{
    public function __construct(
        private readonly DateTimeImmutable $dateOfBirth,
        private readonly int $ageOfMajority,
        private readonly int $evidenceYearsAfterMajority,
        private readonly bool $withdrawnAndPurgeable,
    ) {}

    public function retainedUntil(): ?DateTimeInterface
    {
        if ($this->withdrawnAndPurgeable) {
            return null;
        }

        $years = $this->ageOfMajority + $this->evidenceYearsAfterMajority;

        return $this->dateOfBirth->modify("+{$years} years");
    }
}
```

Three unrelated domains, three different anchors and rules, **one contract**.
That is the proof the contract is not shaped around any single domain.

## Where the instant comes from is your choice

The contract is silent on *how* you compute the date. Pick whatever fits:

- **Hardcode the rule** in the model (simplest, shown above).
- **Read it from your own configuration.**
- **Use a retention-presets layer** such as the `aichadigital/lara-privacy`
  Laravel package, which ships extensible jurisdictional presets
  (duration + anchor + legal source) as config — optional, and **not** a
  dependency of this core.

The core only ever reads the resulting `DateTimeInterface`.

## What does NOT belong in the contract

Keep these out of `LegallyRetainable` — they are domain or consumer concerns,
not the universal shape:

- **No durations, no anchors, no legal citations.** "6 years", "fiscal-year-end",
  "art. 30" are your jurisdiction's, expressed in your code or in a presets
  layer — never in the core.
- **No `isUnderRetention()` / `legalHold()` method.** That *derives* in
  `CheckLegalHold`. Adding it to the contract would duplicate the clock and
  invite drift.
- **No basis/reason enum.** Why a record is retained, and for how long, is
  consumer configuration — not part of the contract's type.

## larabill is one adapter, not the rail

The billing package `aichadigital/larabill` implements this interface on its
`Invoice` (fiscal invoices return fiscal-year-end of the invoice date + 6 years;
a proforma returns `null`) and on its `UserTaxProfile` (derived from its
invoices). It is simply *one* consumer that proves the rail works — exactly like
the medical and consent examples above. The contract was deliberately written
and reviewed **before** larabill implemented it, so it is not shaped around
invoices. The full worked adapter lives in the consumer guide.
