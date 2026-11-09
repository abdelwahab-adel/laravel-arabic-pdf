<?php

namespace MostafaMoknaa\ArabicPdf\Pdf;

use MostafaMoknaa\ArabicPdf\Text\PresentationForms;

/**
 * Rewrites Identity-H /ToUnicode CMaps so Arabic presentation-form CIDs extract as
 * base letters. Glyphs on the page stay shaped; search and copy see logical letters.
 */
final class ToUnicodeFixer
{
    public function fix(string $pdf): string
    {
        if ($pdf === '' || ! str_contains($pdf, '/ToUnicode') || ! str_contains($pdf, '<0000> <FFFF> <0000>')) {
            return $pdf;
        }

        $cmap = $this->buildCMap();
        $replaced = false;

        $pdf = (string) preg_replace_callback(
            '/<<\s*\/Length\s+\d+\s*>>\r?\nstream\r?\n(\/CIDInit \/ProcSet findresource begin.*?end\r?\nend)\r?\nendstream/s',
            function (array $match) use ($cmap, &$replaced): string {
                if (! str_contains($match[1], '<0000> <FFFF> <0000>')) {
                    return $match[0];
                }

                $replaced = true;

                return '<< /Length '.strlen($cmap)." >>\nstream\n{$cmap}\nendstream";
            },
            $pdf,
        );

        return $replaced ? $this->rebuildXref($pdf) : $pdf;
    }

    private function buildCMap(): string
    {
        $chars = [];

        foreach (PresentationForms::map() as $form => $base) {
            if (! PresentationForms::isPresentationForm($form)) {
                continue;
            }

            $chars[] = sprintf('<%04X> <%s>', $form, $this->utf16Hex($base));
        }

        sort($chars);

        $bfchars = '';

        foreach (array_chunk($chars, 100) as $chunk) {
            $bfchars .= count($chunk)." beginbfchar\n".implode("\n", $chunk)."\nendbfchar\n";
        }

        return <<<CMAP
/CIDInit /ProcSet findresource begin
12 dict begin
begincmap
/CIDSystemInfo
<</Registry (Adobe)
/Ordering (UCS)
/Supplement 0
>> def
/CMapName /Adobe-Identity-UCS def
/CMapType 2 def
1 begincodespacerange
<0000> <FFFF>
endcodespacerange
2 beginbfrange
<0000> <FB4F> <0000>
<FE00> <FE6F> <0000>
endbfrange
{$bfchars}1 beginbfrange
<FF00> <FFFF> <0000>
endbfrange
endcmap
CMapName currentdict /CMap defineresource pop
end
end
CMAP;
    }

    private function utf16Hex(string $text): string
    {
        $hex = '';

        foreach (mb_str_split($text, 1, 'UTF-8') as $char) {
            $code = mb_ord($char, 'UTF-8');

            if ($code > 0xFFFF) {
                $code -= 0x10000;
                $hex .= sprintf('%04X%04X', 0xD800 + ($code >> 10), 0xDC00 + ($code & 0x3FF));
            } else {
                $hex .= sprintf('%04X', $code);
            }
        }

        return $hex;
    }

    /**
     * Dompdf emits a classic xref table. Expanding the ToUnicode stream shifts
     * every following byte, so offsets must be rebuilt from the object body.
     */
    private function rebuildXref(string $pdf): string
    {
        if (! preg_match('/\ntrailer\r?\n(<<[\s\S]*?>>)\r?\nstartxref\r?\n\d+\r?\n%%EOF\s*$/', $pdf, $trailer)) {
            return $pdf;
        }

        $xrefPos = $this->findXrefPosition($pdf);

        if ($xrefPos === null) {
            return $pdf;
        }

        $body = substr($pdf, 0, $xrefPos);

        preg_match_all('/(?m)^(\d+) (\d+) obj\r?\n/', $body, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if ($matches === []) {
            return $pdf;
        }

        $objects = [];

        foreach ($matches as $match) {
            $objects[(int) $match[1][0]] = [
                'offset' => $match[0][1],
                'generation' => (int) $match[2][0],
            ];
        }

        $size = max(array_keys($objects)) + 1;
        $xref = "xref\n0 {$size}\n";
        $xref .= "0000000000 65535 f \n";

        for ($n = 1; $n < $size; $n++) {
            if (isset($objects[$n])) {
                $xref .= sprintf("%010d %05d n \n", $objects[$n]['offset'], $objects[$n]['generation']);
            } else {
                $xref .= "0000000000 65535 f \n";
            }
        }

        $dict = $trailer[1];
        $dict = preg_match('/\/Size\s+\d+/', $dict)
            ? (preg_replace('/\/Size\s+\d+/', '/Size '.$size, $dict, 1) ?? $dict)
            : substr($dict, 0, -2).' /Size '.$size.'>>';

        return $body.$xref."trailer\n{$dict}\nstartxref\n".strlen($body)."\n%%EOF\n";
    }

    private function findXrefPosition(string $pdf): ?int
    {
        if (! preg_match('/startxref\s+(\d+)\s*%%EOF\s*$/', $pdf, $match)) {
            return null;
        }

        // After our stream rewrite the stored startxref offset is stale, but the
        // classic "xref" table still sits just before the final trailer.
        $tail = strrpos($pdf, 'trailer');

        if ($tail === false) {
            return null;
        }

        $xrefPos = strrpos(substr($pdf, 0, $tail), 'xref');

        return $xrefPos === false ? null : $xrefPos;
    }
}
