<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Minimal .xlsx writer in plain PHP (no zip extension, no Composer).
 * An .xlsx file is a ZIP of XML parts; entries are written uncompressed ("stored"), which Excel accepts.
 *
 * Column types: 'string' | 'int' | 'money' (#,##0) | 'date' (dd/mm/yyyy hh:mm).
 * Header row is bold, frozen and has an auto-filter.
 */
final class Xlsx
{
    private const STYLE = ['string' => 0, 'header' => 1, 'money' => 2, 'int' => 3, 'date' => 4, 'wrap' => 5];

    /** @var list<array{name:string, columns:list<array{0:string,1:int,2:string}>, rows:list<array>}> */
    private array $sheets = [];

    /** @param list<array{0:string,1:int,2:string}> $columns [header, width, type] */
    public function addSheet(string $name, array $columns, array $rows): self
    {
        // Excel sheet names: max 31 chars, no []:*?/\
        $name = mb_substr(preg_replace('#[\[\]:*?/\\\\]#', ' ', $name), 0, 31);
        $this->sheets[] = ['name' => $name, 'columns' => $columns, 'rows' => $rows];
        return $this;
    }

    /** Send as a download and stop. */
    public function download(string $filename): never
    {
        $bytes = $this->build();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: no-store');
        echo $bytes;
        exit;
    }

    public function build(): string
    {
        $files = [
            '[Content_Types].xml'        => $this->contentTypes(),
            '_rels/.rels'                => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml'            => $this->workbook(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRels(),
            'xl/styles.xml'              => $this->styles(),
        ];
        foreach ($this->sheets as $i => $sheet) {
            $files['xl/worksheets/sheet' . ($i + 1) . '.xml'] = $this->sheet($sheet);
        }
        return self::zip($files);
    }

    // ---------------------------------------------------------------- XML parts

    private function contentTypes(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
           . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
           . '<Default Extension="xml" ContentType="application/xml"/>'
           . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
           . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach ($this->sheets as $i => $_) {
            $x .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return $x . '</Types>';
    }

    private function workbook(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach ($this->sheets as $i => $s) {
            $x .= '<sheet name="' . self::esc($s['name']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }
        return $x . '</sheets></workbook>';
    }

    private function workbookRels(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->sheets as $i => $_) {
            $x .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        $n = count($this->sheets) + 1;
        return $x . '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    /** Style indexes match self::STYLE. */
    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
             . '<numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy hh:mm"/></numFmts>'
             . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
             . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
             . '<fill><patternFill patternType="solid"><fgColor rgb="FF8B1E1E"/><bgColor indexed="64"/></patternFill></fill></fills>'
             . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
             . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
             . '<cellXfs count="6">'
             . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
             . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
             . '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
             . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
             . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
             . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment wrapText="1" vertical="top"/></xf>'
             . '</cellXfs></styleSheet>';
    }

    private function sheet(array $s): string
    {
        $cols = $s['columns'];
        $lastCol = self::col(count($cols) - 1);
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
           . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
           . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
           . '<cols>';
        foreach ($cols as $i => [, $width]) {
            $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $width . '" customWidth="1"/>';
        }
        $x .= '</cols><sheetData>';

        // header
        $x .= '<row r="1">';
        foreach ($cols as $i => [$title]) $x .= self::strCell(self::col($i) . '1', $title, self::STYLE['header']);
        $x .= '</row>';

        foreach (array_values($s['rows']) as $r => $row) {
            $rn = $r + 2;
            $x .= '<row r="' . $rn . '">';
            foreach ($cols as $i => [, , $type]) {
                $ref = self::col($i) . $rn;
                $v = $row[$i] ?? null;
                if ($v === null || $v === '') continue;
                $x .= match ($type) {
                    'money', 'int' => '<c r="' . $ref . '" s="' . self::STYLE[$type] . '"><v>' . (0 + $v) . '</v></c>',
                    'date'         => '<c r="' . $ref . '" s="4"><v>' . self::excelDate((string) $v) . '</v></c>',
                    'wrap'         => self::strCell($ref, (string) $v, self::STYLE['wrap']),
                    default        => self::strCell($ref, (string) $v, 0),
                };
            }
            $x .= '</row>';
        }
        $lastRow = count($s['rows']) + 1;
        return $x . '</sheetData><autoFilter ref="A1:' . $lastCol . $lastRow . '"/></worksheet>';
    }

    // ---------------------------------------------------------------- helpers

    private static function strCell(string $ref, string $text, int $style): string
    {
        return '<c r="' . $ref . '" t="inlineStr"' . ($style ? ' s="' . $style . '"' : '') . '><is><t xml:space="preserve">'
             . self::esc($text) . '</t></is></c>';
    }

    private static function esc(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);   // characters XML can't hold
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** 0 → A, 25 → Z, 26 → AA */
    private static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s;
        return $s;
    }

    /** 'Y-m-d H:i:s' (wall-clock) → Excel serial date. */
    private static function excelDate(string $dt): string
    {
        $ts = strtotime($dt . ' UTC');
        return $ts === false ? '0' : (string) round($ts / 86400 + 25569, 6);
    }

    /** ZIP archive with stored (uncompressed) entries. */
    private static function zip(array $files): string
    {
        $data = ''; $central = ''; $offset = 0;
        [$dosTime, $dosDate] = self::dosTime(time());
        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $len = strlen($content);
            $nameLen = strlen($name);
            $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $dosTime, $dosDate, $crc, $len, $len, $nameLen, 0) . $name;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, $dosTime, $dosDate, $crc, $len, $len,
                             $nameLen, 0, 0, 0, 0, 0, $offset) . $name;
            $data .= $local . $content;
            $offset += strlen($local) + $len;
        }
        $count = count($files);
        return $data . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), $offset, 0);
    }

    private static function dosTime(int $ts): array
    {
        $d = getdate($ts);
        return [
            ($d['hours'] << 11) | ($d['minutes'] << 5) | intdiv($d['seconds'], 2),
            (($d['year'] - 1980) << 9) | ($d['mon'] << 5) | $d['mday'],
        ];
    }
}
