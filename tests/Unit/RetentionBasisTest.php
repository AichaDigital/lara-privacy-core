<?php

declare(strict_types=1);

use AichaDigital\LaraPrivacyCore\Enums\RetentionBasis;

/*
|--------------------------------------------------------------------------
| RetentionBasis — why a thing is retained, and the default duration
|--------------------------------------------------------------------------
|
| The enum names the legal reason; defaultDuration() ships the sane Spanish
| default per ADR-001 (configurable later in the lara-privacy mechanism, never
| legal truth). The computation anchor (e.g. fiscal-year-end) is NOT here yet:
| it is owned by the domain (larabill) and enters when a test needs it.
|
*/

it('declares the commercial retention default as 6 years (Código de Comercio art. 30)', function () {
    expect(RetentionBasis::COMMERCIAL->defaultDuration())->toEqual(new DateInterval('P6Y'));
});

it('declares the tax retention default as 4 years (LGT art. 66)', function () {
    expect(RetentionBasis::TAX->defaultDuration())->toEqual(new DateInterval('P4Y'));
});

it('declares the consent retention default as 3 years', function () {
    expect(RetentionBasis::CONSENT->defaultDuration())->toEqual(new DateInterval('P3Y'));
});
