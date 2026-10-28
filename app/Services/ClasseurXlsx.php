<?php
namespace App\Services;

/**
 * Classeur XLSX multi-onglets (sans dépendance externe) : styles, fusions,
 * largeurs de colonnes. Chaînes écrites en ligne (inlineStr).
 *
 * $c = new ClasseurXlsx();
 * $f = $c->feuille('Product Backlog')->largeurs([6, 30, 60]);
 * $f->ligne(['TITRE'], ClasseurXlsx::TITRE)->fusion('A1:C1');
 * $c->telecharger('archive.xlsx');
 */
class ClasseurXlsx
{
    // Index des styles (cellXfs)
    public const NORMAL = 0;
    public const TITRE = 1;      // gras 14, bleu foncé
    public const ENTETE = 2;     // blanc sur bleu, bordé
    public const CELLULE = 3;    // bordée, retour à la ligne
    public const MODULE = 4;     // ligne de regroupement (bleu clair)
    public const LIBELLE = 5;    // gras
    public const STORY = 6;      // ligne user story (gris clair, gras)
    public const CENTRE = 7;     // bordée, centrée
    public const SOUS_TITRE = 8; // gras 12, bleu
    public const ALERTE = 9;     // bordée, rouge (retard)
    public const OK = 10;        // bordée, vert (réalisé)

    /** @var array<int, array{nom:string, lignes:array, fusions:array, largeurs:array}> */
    private array $feuilles = [];

    public function feuille(string $nom): FeuilleXlsx
    {
        $nom = $this->nomUnique($this->nettoyerNom($nom));
        $f = new FeuilleXlsx($nom);
        $this->feuilles[] = $f;
        return $f;
    }

    public function generer(): string
    {
        if (!$this->feuilles) $this->feuille('Feuille 1');
        $chemin = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        $zip = new \ZipArchive();
        $zip->open($chemin, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $n = count($this->feuilles);
        $over = $sheets = $rels = '';
        foreach ($this->feuilles as $i => $f) {
            $k = $i + 1;
            $zip->addFromString("xl/worksheets/sheet{$k}.xml", $f->xml());
            $over .= "<Override PartName=\"/xl/worksheets/sheet{$k}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>";
            $sheets .= '<sheet name="' . htmlspecialchars($f->nom, ENT_XML1) . "\" sheetId=\"{$k}\" r:id=\"rId{$k}\"/>";
            $rels .= "<Relationship Id=\"rId{$k}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet{$k}.xml\"/>";
        }
        $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $over . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $sheets . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>');
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->close();
        return $chemin;
    }

    public function telecharger(string $nomFichier): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return response()->download($this->generer(), $nomFichier, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    private function nettoyerNom(string $nom): string
    {
        $nom = trim(preg_replace('/[\[\]\*\?\/\\\\:]/', '-', $nom));
        return mb_substr($nom !== '' ? $nom : 'Feuille', 0, 31);
    }

    private function nomUnique(string $nom): string
    {
        $pris = array_map(fn($f) => mb_strtolower($f->nom), $this->feuilles);
        $base = $nom; $i = 2;
        while (in_array(mb_strtolower($nom), $pris, true)) {
            $suffixe = " ({$i})";
            $nom = mb_substr($base, 0, 31 - mb_strlen($suffixe)) . $suffixe;
            $i++;
        }
        return $nom;
    }

    private function styles(): string
    {
        $bord = '<left style="thin"><color rgb="FF9CA3AF"/></left><right style="thin"><color rgb="FF9CA3AF"/></right><top style="thin"><color rgb="FF9CA3AF"/></top><bottom style="thin"><color rgb="FF9CA3AF"/></bottom><diagonal/>';
        $haut = '<alignment vertical="top" wrapText="1"/>';
        $centre = '<alignment horizontal="center" vertical="center" wrapText="1"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="7">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><color rgb="FF1F4E79"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF2E74B5"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="12"/><color rgb="FF2E74B5"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF991B1B"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF166534"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFBFDBFE"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDDEBF7"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF2F2F2"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border>' . $bord . '</border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="11">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1">' . $haut . '</xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">' . $centre . '</xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">' . $haut . '</xf>'
            . '<xf numFmtId="0" fontId="1" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">' . $haut . '</xf>'
            . '<xf numFmtId="0" fontId="1" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">' . $haut . '</xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">' . $centre . '</xf>'
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">' . $centre . '</xf>'
            . '<xf numFmtId="0" fontId="6" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">' . $centre . '</xf>'
            . '</cellXfs></styleSheet>';
    }
}
