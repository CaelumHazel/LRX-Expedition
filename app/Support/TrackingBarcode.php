<?php

namespace App\Support;

use InvalidArgumentException;

class TrackingBarcode
{
    private const PATTERNS = [
        '*' => '121121211',
        '0' => '111221211',
        '1' => '211211112',
        '2' => '112211112',
        '3' => '212211111',
        '4' => '111221112',
        '5' => '211221111',
        '6' => '112221111',
        '7' => '111211212',
        '8' => '211211211',
        '9' => '112211211',
        'L' => '112111122',
        'R' => '211111221',
        'X' => '121121112',
    ];

    public function render(string $trackingNumber): string
    {
        $characters = '*'.$trackingNumber.'*';

        if (! preg_match('/^LRX\d{12}$/', $trackingNumber)
            || strspn($characters, '0123456789LRX*') !== strlen($characters)) {
            throw new InvalidArgumentException('Unsupported character in tracking number.');
        }

        $x = 10;
        $bars = '';

        foreach (str_split($characters) as $character) {
            $pattern = self::PATTERNS[$character];

            foreach (str_split($pattern) as $index => $width) {
                $barWidth = $width === '1' ? 2 : 5;

                if ($index % 2 === 0) {
                    $bars .= '<rect x="'.$x.'" y="0" width="'.$barWidth.'" height="56"/>';
                }

                $x += $barWidth;
            }

            $x += 2;
        }

        $width = $x + 8;

        return '<svg xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Barcode '.$trackingNumber.'" viewBox="0 0 '.$width.' 56" width="'.$width.'" height="56" shape-rendering="crispEdges"><g fill="currentColor">'.$bars.'</g></svg>';
    }
}
