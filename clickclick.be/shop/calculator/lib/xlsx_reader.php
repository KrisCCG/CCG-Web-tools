<?php
/**
 * Minimal .xlsx reader for Click Click's pricing sheet.
 * Uses PHP's built-in ZipArchive + SimpleXML — no external deps.
 *
 * Only reads what the pricing engine needs:
 *   - CMYK Papers (paper_type, gsm, cuts_per_mother, raw_cost)
 *   - Settings   (setting, value)
 *
 * Values are returned as associative arrays keyed by the first-row headers.
 */

declare(strict_types=1);

class PricingXlsx
{
    private array $sheets = [];
    private array $sharedStrings = [];

    public function __construct(string $filePath)
    {
        if (!is_readable($filePath)) {
            throw new RuntimeException("Cannot read xlsx: $filePath");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("Cannot open xlsx as zip: $filePath");
        }

        $this->loadSharedStrings($zip);
        $this->loadSheets($zip);

        $zip->close();
    }

    public function getSheet(string $name): ?array
    {
        return $this->sheets[$name] ?? null;
    }

    /** First row becomes the header; subsequent rows become assoc arrays keyed by header. */
    public function getSheetAssoc(string $name): array
    {
        $rows = $this->sheets[$name] ?? [];
        if (count($rows) < 2) return [];
        $headerRow = $rows[0];
        // Map A-col letter -> header name
        $headers = [];
        foreach ($headerRow as $col => $val) {
            $headers[$col] = is_string($val) ? trim($val) : $val;
        }
        $out = [];
        for ($i = 1, $n = count($rows); $i < $n; $i++) {
            $r = $rows[$i];
            $assoc = [];
            foreach ($headers as $col => $header) {
                if ($header === '' || $header === null) continue;
                $assoc[$header] = $r[$col] ?? null;
            }
            $out[] = $assoc;
        }
        return $out;
    }

    private function loadSharedStrings(ZipArchive $zip): void
    {
        $raw = $zip->getFromName('xl/sharedStrings.xml');
        if ($raw === false) return;
        $xml = new SimpleXMLElement($raw);
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $this->sharedStrings[] = (string)$si->t;
            } elseif (isset($si->r)) {
                $combined = '';
                foreach ($si->r as $r) $combined .= (string)$r->t;
                $this->sharedStrings[] = $combined;
            } else {
                $this->sharedStrings[] = '';
            }
        }
    }

    private function loadSheets(ZipArchive $zip): void
    {
        $wbRaw = $zip->getFromName('xl/workbook.xml');
        $relsRaw = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wbRaw === false || $relsRaw === false) return;

        // Rels: map rId → target path
        $rels = new SimpleXMLElement($relsRaw);
        $relMap = [];
        foreach ($rels->Relationship as $rel) {
            $relMap[(string)$rel['Id']] = (string)$rel['Target'];
        }

        // Workbook: list of sheets with name + rId
        $wb = new SimpleXMLElement($wbRaw);
        $ns = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        foreach ($wb->sheets->sheet as $sheet) {
            $name = (string)$sheet['name'];
            $rAttrs = $sheet->attributes($ns);
            $rId = (string)($rAttrs['id'] ?? '');
            $target = $relMap[$rId] ?? null;
            if (!$target) continue;

            // Target may be relative ("worksheets/sheet1.xml" → resolves to xl/worksheets/sheet1.xml)
            // or absolute within the package ("/xl/worksheets/sheet1.xml"). Handle both.
            $sheetPath = (substr($target, 0, 1) === '/')
                ? ltrim($target, '/')
                : 'xl/' . $target;
            $sheetRaw = $zip->getFromName($sheetPath);
            if ($sheetRaw === false) continue;

            $this->sheets[$name] = $this->parseSheet($sheetRaw);
        }
    }

    private function parseSheet(string $xml): array
    {
        $sheet = new SimpleXMLElement($xml);
        $out = [];
        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $ref  = (string)$c['r'];
                $type = (string)$c['t'];
                $rawV = isset($c->v) ? (string)$c->v : '';
                $rawI = isset($c->is->t) ? (string)$c->is->t : null;

                if ($type === 's') {
                    $val = $this->sharedStrings[(int)$rawV] ?? '';
                } elseif ($type === 'inlineStr' && $rawI !== null) {
                    $val = $rawI;
                } elseif ($type === 'b') {
                    $val = $rawV === '1';
                } elseif ($type === 'str' || $type === 'e') {
                    $val = $rawV;
                } else {
                    // Numeric — preserve as float/int
                    if ($rawV === '') {
                        $val = null;
                    } elseif (is_numeric($rawV)) {
                        $val = (float)$rawV;
                        if ($val == (int)$val && strpos($rawV, '.') === false) {
                            $val = (int)$val;
                        }
                    } else {
                        $val = $rawV;
                    }
                }

                // Column letter from the cell ref
                $col = preg_replace('/\d+/', '', $ref);
                $rowData[$col] = $val;
            }
            $out[] = $rowData;
        }
        return $out;
    }
}
