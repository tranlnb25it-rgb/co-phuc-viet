<?php
declare(strict_types=1);

namespace App\Support;

/** Size chart and custom-measurement rules for made-to-measure orders. */
final class Tailoring
{
    public const SIZES = ['S', 'M', 'L', 'XL'];
    public const CUSTOM = 'CUSTOM';
    public const LEAD_TIME_DAYS = '7–14';

    /** Size chart (cm / kg): size => field => [min, max]. */
    public const SIZE_CHART = [
        'S'  => ['height' => [150, 158], 'weight' => [42, 48], 'bust' => [78, 84],  'waist' => [60, 66], 'hip' => [84, 90]],
        'M'  => ['height' => [158, 165], 'weight' => [48, 55], 'bust' => [84, 90],  'waist' => [66, 72], 'hip' => [90, 96]],
        'L'  => ['height' => [165, 172], 'weight' => [55, 63], 'bust' => [90, 96],  'waist' => [72, 78], 'hip' => [96, 102]],
        'XL' => ['height' => [172, 180], 'weight' => [63, 72], 'bust' => [96, 102], 'waist' => [78, 86], 'hip' => [102, 108]],
    ];

    /** Accepted range for each custom measurement: field => [min, max, unit]. */
    public const FIELDS = [
        'height' => [120, 210, 'cm'],
        'weight' => [30, 150, 'kg'],
        'bust'   => [60, 150, 'cm'],
        'waist'  => [45, 140, 'cm'],
        'hip'    => [60, 160, 'cm'],
    ];

    /**
     * Validate custom measurements.
     * @return array{0: array<string, float|string>, 1: array<string, string>} [clean values, errors by field]
     */
    public static function validate(array $input): array
    {
        $clean = [];
        $errors = [];
        foreach (self::FIELDS as $field => [$min, $max, $unit]) {
            $raw = trim(str_replace(',', '.', (string) ($input[$field] ?? '')));
            if ($raw === '') {
                $errors[$field] = t('pdp.errors.required');
            } elseif (!is_numeric($raw) || (float) $raw < $min || (float) $raw > $max) {
                $errors[$field] = t('pdp.errors.range', ['min' => $min, 'max' => $max, 'unit' => $unit]);
            } else {
                $clean[$field] = round((float) $raw, 1);
            }
        }
        $note = mb_substr(trim((string) ($input['note'] ?? '')), 0, 300);
        if ($note !== '') $clean['note'] = $note;
        return [$clean, $errors];
    }

    /** Nearest standard size for given measurements (null if not enough data). */
    public static function suggestSize(array $m): ?string
    {
        $best = null;
        $bestScore = PHP_FLOAT_MAX;
        foreach (self::SIZE_CHART as $size => $ranges) {
            $score = 0; $used = 0;
            foreach ($ranges as $field => [$lo, $hi]) {
                if (!isset($m[$field]) || !is_numeric($m[$field])) continue;
                $v = (float) $m[$field];
                $score += $v < $lo ? $lo - $v : ($v > $hi ? $v - $hi : 0);
                $used++;
            }
            if ($used >= 2 && $score < $bestScore) { $bestScore = $score; $best = $size; }
        }
        return $best;
    }
}
