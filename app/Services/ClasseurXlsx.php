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
    public const TITRE = 1;      // gras 16, bleu foncé
    public const ENTETE = 2;     // bleu sur bleu clair, souligné
    public const CELLULE = 3;    // bordée, retour à la ligne
    public const MODULE = 4;     // ligne de regroupement (bleu clair)
    public const LIBELLE = 5;    // gras
    public const STORY = 6;      // ligne user story (gris clair, gras)
    public const CENTRE = 7;     // bordée, centrée
    public const SOUS_TITRE = 8; // gras 12, bleu
    public const ALERTE = 9;     // bordée, rouge (retard)
    public const OK = 10;        // bordée, vert (réalisé)
    public const FICHE_LIB = 11; // libellé de fiche (bleu, fond très clair)
    public const FICHE_VAL = 12; // valeur de fiche (gris)
    public const PROJET = 13;    // nom du projet mis en évidence
    public const CELLULE_ALT = 14; // cellule de ligne alternée

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
        // Quadrillage gris sobre ; en-tête bleu clair souligné d'un trait bleu
        $gris = fn($c = 'FFD1D5DB') => "<color rgb=\"{$c}\"/>";
        $bord = '<left style="thin">' . $gris() . '</left><right style="thin">' . $gris() . '</right><top style="thin">' . $gris() . '</top><bottom style="thin">' . $gris() . '</bottom><diagonal/>';
        $bordEntete = '<left style="thin">' . $gris() . '</left><right style="thin">' . $gris() . '</right><top style="thin">' . $gris() . '</top><bottom style="medium">' . $gris('FF2E74B5') . '</bottom><diagonal/>';
        $haut = '<alignment horizontal="left" vertical="top" wrapText="1" indent="1"/>';
        $centre = '<alignment horizontal="center" vertical="center" wrapText="1"/>';
        $milieu = '<alignment horizontal="left" vertical="center" wrapText="1" indent="1"/>';
        $police = fn(string $attr, string $taille, string $couleur) => "<font>{$attr}<sz val=\"{$taille}\"/><color rgb=\"{$couleur}\"/><name val=\"Calibri\"/></font>";
        $fond = fn(string $c) => "<fill><patternFill patternType=\"solid\"><fgColor rgb=\"{$c}\"/><bgColor indexed=\"64\"/></patternFill></fill>";
        $xf = fn(int $font, int $fill, int $border, string $align = '') => "<xf numFmtId=\"0\" fontId=\"{$font}\" fillId=\"{$fill}\" borderId=\"{$border}\" xfId=\"0\" applyFont=\"1\" applyFill=\"1\" applyBorder=\"1\" applyAlignment=\"1\">{$align}</xf>";

        $fonts = [
            $police('', '11', 'FF334155'),          // 0 texte
            $police('<b/>', '11', 'FF1F2937'),      // 1 gras
            $police('<b/>', '16', 'FF1F4E79'),      // 2 titre
            $police('<b/>', '11', 'FF2E74B5'),      // 3 en-tête
            $police('<b/>', '12', 'FF2E74B5'),      // 4 sous-titre
            $police('<b/>', '11', 'FF991B1B'),      // 5 alerte
            $police('<b/>', '11', 'FF166534'),      // 6 réalisé
            $police('<b/>', '10', 'FF2E74B5'),      // 7 libellé de fiche
            $police('', '11', 'FF64748B'),          // 8 valeur de fiche (gris)
            $police('<b/>', '12', 'FF1F4E79'),      // 9 nom du projet
        ];
        $fills = [
            '<fill><patternFill patternType="none"/></fill>', '<fill><patternFill patternType="gray125"/></fill>',
            $fond('FFBFDBFE'), // 2 en-tête
            $fond('FFEAF1FB'), // 3 regroupement
            $fond('FFF3F4F6'), // 4 ligne user story / participant
            $fond('FFF8FBFF'), // 5 libellé de fiche
            $fond('FFDBEAFE'), // 6 projet
            $fond('FFF9FAFB'), // 7 ligne alternée
        ];
        $xfs = [
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>', // NORMAL
            $xf(2, 0, 0, '<alignment vertical="center"/>'), // TITRE
            $xf(3, 2, 2, $centre),   // ENTETE
            $xf(0, 0, 1, $haut),     // CELLULE
            $xf(1, 3, 1, $milieu),   // MODULE
            $xf(1, 0, 0, '<alignment vertical="top" wrapText="1"/>'), // LIBELLE
            $xf(1, 4, 1, $haut),     // STORY
            $xf(0, 0, 1, $centre),   // CENTRE
            $xf(4, 0, 0, '<alignment vertical="center"/>'), // SOUS_TITRE
            $xf(5, 0, 1, $centre),   // ALERTE
            $xf(6, 0, 1, $centre),   // OK
            $xf(7, 5, 1, $milieu),   // FICHE_LIB
            $xf(8, 0, 1, $haut),     // FICHE_VAL
            $xf(9, 6, 1, $milieu),   // PROJET
            $xf(0, 7, 1, $haut),     // CELLULE_ALT
        ];
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="' . count($fonts) . '">' . implode('', $fonts) . '</fonts>'
            . '<fills count="' . count($fills) . '">' . implode('', $fills) . '</fills>'
            . '<borders count="3"><border><left/><right/><top/><bottom/><diagonal/></border><border>' . $bord . '</border><border>' . $bordEntete . '</border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($xfs) . '">' . implode('', $xfs) . '</cellXfs></styleSheet>';
    }
}
