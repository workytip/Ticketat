<?php

use App\Support\CaseConverter;

it('converts top-level keys to snake_case', function () {
    expect(CaseConverter::toSnake(['deviceName' => 'iPhone', 'isActive' => true]))
        ->toBe(['device_name' => 'iPhone', 'is_active' => true]);
});

it('converts keys inside nested arrays and lists', function () {
    $input = [
        'priceTiers' => [
            ['tierName' => 'VIP', 'unitPrice' => 500],
            ['tierName' => 'Regular', 'unitPrice' => 200],
        ],
        'venueInfo' => ['seatMap' => ['rowCount' => 10]],
    ];

    expect(CaseConverter::toSnake($input))->toBe([
        'price_tiers' => [
            ['tier_name' => 'VIP', 'unit_price' => 500],
            ['tier_name' => 'Regular', 'unit_price' => 200],
        ],
        'venue_info' => ['seat_map' => ['row_count' => 10]],
    ]);
});

it('keeps list indexes and values untouched', function () {
    expect(CaseConverter::toSnake(['seatIds' => [3 => 'A1', 7 => 'someValue']]))
        ->toBe(['seat_ids' => [3 => 'A1', 7 => 'someValue']]);
});
