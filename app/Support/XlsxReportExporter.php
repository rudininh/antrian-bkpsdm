<?php

namespace App\Support;

use InvalidArgumentException;
use ZipArchive;

class XlsxReportExporter
{
    /**
     * @param  array<int, array{title:string, rows:array<int, array<int, scalar|null>>}>  $sheets
     */
    public function export(array $sheets, string $path): void
    {
        if ($sheets === []) {
            throw new InvalidArgumentException('Minimal satu sheet diperlukan untuk export XLSX.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new InvalidArgumentException('Tidak dapat membuat file XLSX sementara.');
        }

        $sheetCount = count($sheets);
        $sheetRefs = [];

        foreach ($sheets as $index => $sheet) {
            $sheetRefs[] = [
                'id' => $index + 1,
                'name' => $this->sanitizeSheetTitle($sheet['title']),
            ];
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml($sheetCount));
        $zip->addFromString('_rels/.rels', $this->relsXml());
        $zip->addFromString('docProps/app.xml', $this->appXml($sheetRefs));
        $zip->addFromString('docProps/core.xml', $this->coreXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetRefs));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml($sheetCount));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        foreach ($sheets as $index => $sheet) {
            $zip->addFromString(
                sprintf('xl/worksheets/sheet%d.xml', $index + 1),
                $this->worksheetXml($sheet['rows'])
            );
        }

        $zip->close();
    }

    /**
     * @param  array<int, array{title:string}>  $sheetRefs
     */
    protected function appXml(array $sheetRefs): string
    {
        $titles = '';

        foreach ($sheetRefs as $sheetRef) {
            $titles .= '<vt:lpstr>'.$this->escape($sheetRef['name']).'</vt:lpstr>';
        }

        $template = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Laravel</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <HeadingPairs>
    <vt:vector size="2" baseType="variant">
      <vt:variant>
        <vt:lpstr>Worksheets</vt:lpstr>
      </vt:variant>
      <vt:variant>
        <vt:i4>__COUNT__</vt:i4>
      </vt:variant>
    </vt:vector>
  </HeadingPairs>
  <TitlesOfParts>
    <vt:vector size="__COUNT__" baseType="lpstr">__TITLES__</vt:vector>
  </TitlesOfParts>
</Properties>
XML;

        return strtr($template, [
            '__COUNT__' => (string) count($sheetRefs),
            '__TITLES__' => $titles,
        ]);
    }

    protected function coreXml(): string
    {
        $created = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:creator>BKPSDM System</dc:creator>
  <cp:lastModifiedBy>BKPSDM System</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{$created}</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">{$created}</dcterms:modified>
</cp:coreProperties>
XML;
    }

    /**
     * @param  array<int, array{title:string, id:int}>  $sheetRefs
     */
    protected function workbookXml(array $sheetRefs): string
    {
        $sheets = '';

        foreach ($sheetRefs as $sheetRef) {
            $sheets .= sprintf(
                '<sheet name="%s" sheetId="%d" r:id="rId%d"/>',
                $this->escape($sheetRef['name']),
                $sheetRef['id'],
                $sheetRef['id']
            );
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    {$sheets}
  </sheets>
</workbook>
XML;
    }

    protected function workbookRelsXml(int $sheetCount): string
    {
        $rels = '';
        $stylesId = $sheetCount + 1;

        for ($index = 1; $index <= $sheetCount; $index++) {
            $rels .= sprintf(
                '<Relationship Id="rId%d" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet%d.xml"/>',
                $index,
                $index
            );
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  {$rels}
  <Relationship Id="rId{$stylesId}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    protected function relsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;
    }

    protected function contentTypesXml(int $sheetCount): string
    {
        $sheetOverrides = '';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $sheetOverrides .= sprintf(
                '<Override PartName="/xl/worksheets/sheet%d.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>',
                $index
            );
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  {$sheetOverrides}
</Types>
XML;
    }

    protected function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font>
      <sz val="11"/>
      <name val="Calibri"/>
    </font>
    <font>
      <b/>
      <sz val="11"/>
      <name val="Calibri"/>
    </font>
  </fonts>
  <fills count="2">
    <fill>
      <patternFill patternType="none"/>
    </fill>
    <fill>
      <patternFill patternType="solid">
        <fgColor rgb="FF0F766E"/>
        <bgColor indexed="64"/>
      </patternFill>
    </fill>
  </fills>
  <borders count="1">
    <border>
      <left/>
      <right/>
      <top/>
      <bottom/>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
</styleSheet>
XML;
    }

    /**
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    protected function worksheetXml(array $rows): string
    {
        $dimension = $this->dimension($rows);
        $xmlRows = '';

        foreach ($rows as $rowIndex => $row) {
            $cells = '';

            foreach ($row as $columnIndex => $value) {
                if ($value === null) {
                    continue;
                }

                $cells .= $this->cellXml($rowIndex + 1, $columnIndex + 1, $value, $rowIndex === 0);
            }

            $xmlRows .= sprintf('<row r="%d">%s</row>', $rowIndex + 1, $cells);
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <dimension ref="{$dimension}"/>
  <sheetViews>
    <sheetView workbookViewId="0"/>
  </sheetViews>
  <sheetFormatPr defaultRowHeight="15"/>
  <sheetData>
    {$xmlRows}
  </sheetData>
</worksheet>
XML;
    }

    protected function cellXml(int $row, int $column, mixed $value, bool $header = false): string
    {
        $ref = $this->columnName($column).$row;
        $style = $header ? ' s="1"' : '';

        if (is_bool($value)) {
            $value = $value ? 'Ya' : 'Tidak';
        }

        if (is_int($value) || is_float($value)) {
            return sprintf('<c r="%s"%s><v>%s</v></c>', $ref, $style, $value);
        }

        return sprintf(
            '<c r="%s"%s t="inlineStr"><is><t xml:space="preserve">%s</t></is></c>',
            $ref,
            $style,
            $this->escape((string) $value)
        );
    }

    /**
     * @param  array<int, array<int, scalar|null>>  $rows
     */
    protected function dimension(array $rows): string
    {
        if ($rows === []) {
            return 'A1';
        }

        $lastRow = count($rows);
        $lastColumn = 1;

        foreach ($rows as $row) {
            $lastColumn = max($lastColumn, count($row));
        }

        return 'A1:'.$this->columnName($lastColumn).$lastRow;
    }

    protected function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    protected function sanitizeSheetTitle(string $title): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $title) ?? $title;

        return trim(mb_substr($title, 0, 31));
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
