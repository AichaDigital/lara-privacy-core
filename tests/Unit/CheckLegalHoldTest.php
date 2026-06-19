<?php

declare(strict_types=1);

use AichaDigital\LaraPrivacyCore\CheckLegalHold;
use AichaDigital\LaraPrivacyCore\Contracts\LegallyRetainable;

/*
|--------------------------------------------------------------------------
| CheckLegalHold — pure retention decision
|--------------------------------------------------------------------------
|
|   subject.retainedUntil()        decision
|   ------------------------        ----------------
|   null                       ->   not under retention   (nothing to hold)
|   > now                      ->   UNDER retention        (block / restrict)
|   == now                     ->   not under retention    (hold has lapsed)
|   < now                      ->   not under retention    (hold has lapsed)
|
| `now` is injected, never read from the system clock: the decision is pure
| and the test is deterministic.
|
*/

function fixedNow(): DateTimeImmutable
{
    return new DateTimeImmutable('2026-06-19 12:00:00');
}

function retainableUntil(?DateTimeInterface $until): LegallyRetainable
{
    return new class($until) implements LegallyRetainable
    {
        public function __construct(private ?DateTimeInterface $until) {}

        public function retainedUntil(): ?DateTimeInterface
        {
            return $this->until;
        }
    };
}

it('is not under retention when retainedUntil is null', function () {
    $subject = retainableUntil(null);

    expect((new CheckLegalHold)->isUnderRetention($subject, fixedNow()))->toBeFalse();
});

it('is under retention when retainedUntil is in the future', function () {
    $subject = retainableUntil(fixedNow()->modify('+1 day'));

    expect((new CheckLegalHold)->isUnderRetention($subject, fixedNow()))->toBeTrue();
});

it('is not under retention when retainedUntil is in the past', function () {
    $subject = retainableUntil(fixedNow()->modify('-1 second'));

    expect((new CheckLegalHold)->isUnderRetention($subject, fixedNow()))->toBeFalse();
});

it('is not under retention when retainedUntil is exactly now (boundary)', function () {
    $subject = retainableUntil(fixedNow());

    expect((new CheckLegalHold)->isUnderRetention($subject, fixedNow()))->toBeFalse();
});
