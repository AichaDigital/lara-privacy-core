# lara-privacy-core

The dependency-free, framework-free core of
[`lara-privacy`](https://github.com/AichaDigital/lara-privacy): a universal
contract for legal data-retention obligations and a pure decision over it.

No Eloquent, no side effects, no jurisdiction, no domain assumptions. A domain
package (billing, medical, HR…) implements the contract; the privacy layer reads
it to decide whether a record may be touched.

> **Governing rule:** lara-privacy defines privacy contracts. Domain packages
> implement them. No domain package — and no jurisdiction — defines the shape of
> the core.

## Install

```bash
composer require aichadigital/lara-privacy-core
```

Requires PHP 8.3+. That is the only requirement — there are no runtime
dependencies.

## What it ships

- **`Contracts\LegallyRetainable`** — one method, `retainedUntil(): ?DateTimeInterface`:
  the instant until which an object must be kept, or `null` when it carries no
  retention obligation.
- **`CheckLegalHold`** — a pure, clock-injected decision: is a subject under an
  active retention hold at a given `$now`? (`retainedUntil > now`).

## Quick example

```php
use AichaDigital\LaraPrivacyCore\Contracts\LegallyRetainable;
use AichaDigital\LaraPrivacyCore\CheckLegalHold;
use DateTimeImmutable;
use DateTimeInterface;

final class AccountingDocument implements LegallyRetainable
{
    public function __construct(
        private readonly ?DateTimeImmutable $fiscalYearEnd, // null = not booked
        private readonly int $years,
    ) {}

    public function retainedUntil(): ?DateTimeInterface
    {
        return $this->fiscalYearEnd?->modify("+{$this->years} years");
    }
}

$held = (new CheckLegalHold)->isUnderRetention($document, new DateTimeImmutable);
```

## Documentation

- [Implementing `LegallyRetainable`](docs/implementing-legally-retainable.md) —
  the contract, three neutral domain examples, and what does *not* belong in it.

The Laravel integration (the read-only hold gate, jurisdictional retention
presets, and worked adapters) lives in the
[`aichadigital/lara-privacy`](https://github.com/AichaDigital/lara-privacy)
package.

## License

MIT. See [LICENSE](LICENSE).
