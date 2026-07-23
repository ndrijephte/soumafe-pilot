<?php
namespace App\Services;

/**
 * Générateur XLSX natif (sans dépendance externe).
 * Crée un fichier .xlsx minimal avec ZipArchive + XML.
 */
class GenerateurXlsx
{
    private array $lignes = [];
    private array $entetes = [];
    private string $nomFeuille;

    public function __construct(string $nomFeuille = 'Export')
    {
        $this->nomFeuille = $nomFeuille;
    }

    public function entetes(array $entetes): static
    {
        $this->entetes = $entetes;
        return $this;
    }

    public function ligne(array $valeurs): static
    {
        $this->lignes[] = $valeurs;
        return $this;
    }

    public function lignes(array $tableau): static
    {
        foreach ($tableau as $row) {
            $this->lignes[] = $row;
        }
        return $this;
    }

    /** Génère le fichier et retourne le chemin temporaire. */
    public function generer(): string
    {
        $chemin = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        $zip = new \ZipArchive();
        $zip->open($chemin, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());
        $zip->addFromString('xl/sharedStrings.xml', $this->sharedStrings());

        $zip->close();
        return $chemin;
    }

    /** Génère et retourne une Response de téléchargement. */
    public function telecharger(string $nomFichier): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $chemin = $this->generer();
        return response()->download($chemin, $nomFichier, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    // --- XML builders ---

    private function toutesLesLignes(): array
    {
        $all = [];
        if (!empty($this->entetes)) $all[] = $this->entetes;
        return array_merge($all, $this->lignes);
    }

    private function collecterChaines(): array
    {
        $chaines = [];
        foreach ($this->toutesLesLignes() as $row) {
            foreach ($row as $val) {
                if (is_string($val) && $val !== '' && !is_numeric($val)) {
                    if (!in_array($val, $chaines, true)) {
                        $chaines[] = $val;
                    }
                }
            }
        }
        return $chaines;
    }

    private function sheet(): string
    {
        $chaines = $this->collecterChaines();
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetData>';

        foreach ($this->toutesLesLignes() as $r => $row) {
            $rowNum = $r + 1;
            $xml .= "<row r=\"{$rowNum}\">";
            foreach ($row as $c => $val) {
                $ref = $this->indexToCol($c) . $rowNum;
                if ($val === null || $val === '') {
                    $xml .= "<c r=\"{$ref}\"/>";
                } elseif (is_numeric($val)) {
                    $xml .= "<c r=\"{$ref}\"><v>{$val}</v></c>";
                } else {
                    $idx = array_search($val, $chaines, true);
                    // Style 1 = en-tête (gras) pour la première ligne si entêtes définies
                    $style = ($r === 0 && !empty($this->entetes)) ? ' s="1"' : '';
                    $xml .= "<c r=\"{$ref}\" t=\"s\"{$style}><v>{$idx}</v></c>";
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private function sharedStrings(): string
    {
        $chaines = $this->collecterChaines();
        $count = count($chaines);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= "<sst xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\" count=\"{$count}\" uniqueCount=\"{$count}\">";
        foreach ($chaines as $s) {
            $xml .= '<si><t>' . htmlspecialchars($s, ENT_XML1) . '</t></si>';
        }
        $xml .= '</sst>';
        return $xml;
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>
<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>
</styleSheet>';
    }

    private function workbook(): string
    {
        $nom = htmlspecialchars($this->nomFeuille, ENT_XML1);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="' . $nom . '" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
    }

    private function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>';
    }

    private function indexToCol(int $index): string
    {
        $col = '';
        $index++;
        while ($index > 0) {
            $index--;
            $col = chr(65 + ($index % 26)) . $col;
            $index = intdiv($index, 26);
        }
        return $col;
    }
}
