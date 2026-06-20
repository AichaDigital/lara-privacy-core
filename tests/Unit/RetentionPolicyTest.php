<?php

declare(strict_types=1);

use AichaDigital\LaraPrivacyCore\Enums\RetentionBasis;
use AichaDigital\LaraPrivacyCore\RetentionPolicy;

/*
|--------------------------------------------------------------------------
| RetentionPolicy — basis + duration → retainedUntil(anchor)
|--------------------------------------------------------------------------
|
| The core APPLIES a duration to an anchor; the domain (larabill) DECIDES the
| anchor (e.g. fiscal-year-end) and passes it in. Duration defaults to the
| basis default and may be overridden. Pure: the anchor is never mutated.
| ExpiryMode (delete/anonymize/legal_hold) is deferred to v1.1 (it is only
| acted on by the prune, which v1.0 does not ship).
|
*/

it('resolves retainedUntil as anchor plus the basis default duration', function () {
    $policy = new RetentionPolicy(RetentionBasis::COMMERCIAL); // default P6Y
    $anchor = new DateTimeImmutable('2026-06-20 12:00:00');

    expect($policy->resolveRetainedUntil($anchor))
        ->toEqual(new DateTimeImmutable('2032-06-20 12:00:00'));
});

it('resolves retainedUntil using an overridden duration', function () {
    $policy = new RetentionPolicy(RetentionBasis::COMMERCIAL, new DateInterval('P10Y'));
    $anchor = new DateTimeImmutable('2026-06-20 12:00:00');

    expect($policy->resolveRetainedUntil($anchor))
        ->toEqual(new DateTimeImmutable('2036-06-20 12:00:00'));
});

it('does not mutate a mutable anchor (no side effects)', function () {
    $policy = new RetentionPolicy(RetentionBasis::TAX); // P4Y
    $anchor = new DateTime('2026-06-20 12:00:00');

    $policy->resolveRetainedUntil($anchor);

    expect($anchor->format('Y-m-d H:i:s'))->toBe('2026-06-20 12:00:00');
});
