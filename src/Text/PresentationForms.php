<?php

namespace MostafaMoknaa\ArabicPdf\Text;

/**
 * Maps Arabic / Persian presentation forms back to their base letters (NFKC).
 * Used so PDF search and copy-paste see logical characters, not glyph variants.
 */
final class PresentationForms
{
    /**
     * Presentation-form code point => base letter string (may be 1 or 2 chars for ligatures).
     *
     * @var array<int, string>|null
     */
    private static ?array $toBase = null;

    public static function toBase(string $text): string
    {
        if ($text === '' || ! preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text)) {
            return $text;
        }

        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_KC);

            if (is_string($normalized) && $normalized !== '') {
                return $normalized;
            }
        }

        $map = self::map();
        $out = '';

        foreach (mb_str_split($text, 1, 'UTF-8') as $char) {
            $code = mb_ord($char, 'UTF-8');
            $out .= $map[$code] ?? $char;
        }

        return $out;
    }

    public static function isPresentationForm(int $code): bool
    {
        return ($code >= 0xFB50 && $code <= 0xFDFF) || ($code >= 0xFE70 && $code <= 0xFEFF);
    }

    /**
     * @return array<int, string>
     */
    public static function map(): array
    {
        if (self::$toBase !== null) {
            return self::$toBase;
        }

        $map = [];

        foreach (self::letterForms() as $base => $forms) {
            $baseChar = mb_chr($base, 'UTF-8');

            foreach ($forms as $form) {
                if ($form !== null) {
                    $map[$form] = $baseChar;
                }
            }
        }

        foreach (self::lamAlef() as $alef => $forms) {
            $base = mb_chr(0x0644, 'UTF-8').mb_chr($alef, 'UTF-8');

            foreach ($forms as $form) {
                $map[$form] = $base;
            }
        }

        // Common presentation-form digits and punctuation that may appear in fonts.
        foreach ([
            0x0660 => '0', 0x0661 => '1', 0x0662 => '2', 0x0663 => '3', 0x0664 => '4',
            0x0665 => '5', 0x0666 => '6', 0x0667 => '7', 0x0668 => '8', 0x0669 => '9',
        ] as $code => $digit) {
            $map[$code] = $digit;
        }

        return self::$toBase = $map;
    }

    /**
     * Same tables as Shaper: [isolated, final, initial, medial].
     *
     * @return array<int, array{0: int, 1: ?int, 2: ?int, 3: ?int}>
     */
    private static function letterForms(): array
    {
        return [
            0x0621 => [0xFE80, null, null, null],
            0x0622 => [0xFE81, 0xFE82, null, null],
            0x0623 => [0xFE83, 0xFE84, null, null],
            0x0624 => [0xFE85, 0xFE86, null, null],
            0x0625 => [0xFE87, 0xFE88, null, null],
            0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C],
            0x0627 => [0xFE8D, 0xFE8E, null, null],
            0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92],
            0x0629 => [0xFE93, 0xFE94, null, null],
            0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98],
            0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C],
            0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0],
            0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4],
            0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8],
            0x062F => [0xFEA9, 0xFEAA, null, null],
            0x0630 => [0xFEAB, 0xFEAC, null, null],
            0x0631 => [0xFEAD, 0xFEAE, null, null],
            0x0632 => [0xFEAF, 0xFEB0, null, null],
            0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4],
            0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8],
            0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC],
            0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0],
            0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4],
            0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8],
            0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC],
            0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0],
            0x0640 => [0x0640, 0x0640, 0x0640, 0x0640],
            0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4],
            0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8],
            0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC],
            0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0],
            0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4],
            0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8],
            0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC],
            0x0648 => [0xFEED, 0xFEEE, null, null],
            0x0649 => [0xFEEF, 0xFEF0, 0xFBE8, 0xFBE9],
            0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4],
            0x0671 => [0xFB50, 0xFB51, null, null],
            0x067E => [0xFB56, 0xFB57, 0xFB58, 0xFB59],
            0x0686 => [0xFB7A, 0xFB7B, 0xFB7C, 0xFB7D],
            0x0698 => [0xFB8A, 0xFB8B, null, null],
            0x06A4 => [0xFB6A, 0xFB6B, 0xFB6C, 0xFB6D],
            0x06A9 => [0xFB8E, 0xFB8F, 0xFB90, 0xFB91],
            0x06AF => [0xFB92, 0xFB93, 0xFB94, 0xFB95],
            0x06CC => [0xFBFC, 0xFBFD, 0xFBFE, 0xFBFF],
        ];
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    private static function lamAlef(): array
    {
        return [
            0x0622 => [0xFEF5, 0xFEF6],
            0x0623 => [0xFEF7, 0xFEF8],
            0x0625 => [0xFEF9, 0xFEFA],
            0x0627 => [0xFEFB, 0xFEFC],
        ];
    }
}
