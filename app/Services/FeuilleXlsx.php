<?php
namespace App\Services;

/** Un onglet du classeur. */
class FeuilleXlsx
{
    private array $lignes = [];
    private array $fusions = [];
    private array $largeurs = [];
    private array $hauteurs = [];

    public function __construct(public string $nom) {}

    /** Prochain numéro de ligne (1-based). */
    public function numero(): int { return count($this->lignes) + 1; }

    /**
     * Ajoute une ligne. $style : style commun, ou tableau [colonne => style] ;
     * une cellule peut aussi être ['v' => valeur, 's' => style].
     */
    public function ligne(array $valeurs, int|array $style = ClasseurXlsx::NORMAL): static
    {
        $cellules = [];
        foreach (array_values($valeurs) as $i => $v) {
            $s = is_array($style) ? ($style[$i] ?? ClasseurXlsx::NORMAL) : $style;
            if (is_array($v)) { $s = $v['s'] ?? $s; $v = $v['v'] ?? null; }
            $cellules[] = [$v, $s];
        }
        $this->lignes[] = $cellules;
        return $this;
    }

    public function vide(int $n = 1): static
    {
        for ($i = 0; $i < $n; $i++) $this->lignes[] = [];
        return $this;
    }

    /** Fusionne une plage (« A1:E1 ») ; sans argument de ligne, vise la dernière ligne ajoutée. */
    public function fusion(string $plage): static
    {
        $this->fusions[] = $plage;
        return $this;
    }

    /** Fusionne les colonnes $de → $a (index 0) de la dernière ligne. */
    public function fusionner(int $de, int $a): static
    {
        $r = count($this->lignes);
        return $this->fusion(self::col($de) . $r . ':' . self::col($a) . $r);
    }

    /** Hauteur (points) de la dernière ligne ajoutée - utile pour les cellules fusionnées longues. */
    public function hauteur(float $pt): static
    {
        $this->hauteurs[count($this->lignes)] = $pt;
        return $this;
    }

    /** Largeurs en caractères, par colonne (index 0). */
    public function largeurs(array $largeurs): static
    {
        $this->largeurs = array_values($largeurs);
        return $this;
    }

    public function xml(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        // Impression ajustée à la largeur de la page ; quadrillage d'Excel masqué (seules les bordures des tableaux restent)
        $x .= '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews><sheetFormatPr defaultRowHeight="16"/>';
        if ($this->largeurs) {
            $x .= '<cols>';
            foreach ($this->largeurs as $i => $l) $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) $l . '" customWidth="1"/>';
            $x .= '</cols>';
        }
        $x .= '<sheetData>';
        foreach ($this->lignes as $r => $cellules) {
            $num = $r + 1;
            $ht = $this->hauteurs[$num] ?? $this->hauteurAuto($num, $cellules);
            $x .= $ht ? "<row r=\"{$num}\" ht=\"{$ht}\" customHeight=\"1\">" : "<row r=\"{$num}\">";
            foreach ($cellules as $c => [$v, $s]) {
                $ref = self::col($c) . $num;
                $st = $s ? " s=\"{$s}\"" : '';
                if ($v === null || $v === '') {
                    $x .= "<c r=\"{$ref}\"{$st}/>";
                } elseif (is_int($v) || is_float($v)) {
                    $x .= "<c r=\"{$ref}\"{$st}><v>{$v}</v></c>";
                } elseif (TexteRiche::estHtml((string) $v)) {
                    // Cadre de rédaction : texte enrichi (gras, italique, souligné, puces)
                    $x .= "<c r=\"{$ref}\"{$st} t=\"inlineStr\"><is>" . self::runs((string) $v) . '</is></c>';
                } else {
                    $t = htmlspecialchars(self::propre((string) $v), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $x .= "<c r=\"{$ref}\"{$st} t=\"inlineStr\"><is><t xml:space=\"preserve\">{$t}</t></is></c>";
                }
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if ($this->fusions) {
            $x .= '<mergeCells count="' . count($this->fusions) . '">';
            foreach ($this->fusions as $f) $x .= "<mergeCell ref=\"{$f}\"/>";
            $x .= '</mergeCells>';
        }
        $x .= '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>';
        $x .= '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>';
        return $x . '</worksheet>';
    }

    /** Hauteur d'une ligne selon son style et la longueur de ses textes (Segoe UI 11 : 16,8 pt par ligne). */
    private function hauteurAuto(int $num, array $cellules): ?float
    {
        if (!$cellules) return null;
        $style = $cellules[0][1];
        if ($style === ClasseurXlsx::TITRE) return 30;
        if ($style === ClasseurXlsx::SOUS_TITRE) return 19.2;
        if (in_array($style, [ClasseurXlsx::NORMAL, ClasseurXlsx::LIBELLE], true)) return null;
        if ($style === ClasseurXlsx::ENTETE) return 33.6;
        // Largeur de chaque cellule, fusions de la ligne comprises
        $largeur = fn(int $c) => (float) ($this->largeurs[$c] ?? 8.43);
        $etendue = [];
        foreach ($this->fusions as $f) {
            if (preg_match('/^([A-Z]+)(\d+):([A-Z]+)\d+$/', $f, $m) && (int) $m[2] === $num) $etendue[self::index($m[1])] = self::index($m[3]);
        }
        $lignes = 1;
        foreach ($cellules as $c => [$v, $s]) {
            if ($v === null || $v === '' || is_int($v) || is_float($v)) continue;
            $l = 0;
            for ($i = $c, $fin = $etendue[$c] ?? $c; $i <= $fin; $i++) $l += $largeur($i);
            $texte = TexteRiche::estHtml((string) $v) ? TexteRiche::texte((string) $v) : (string) $v;
            $n = 0;
            foreach (explode("
", $texte) as $p) $n += max(1, (int) ceil(mb_strlen($p) * 1.15 / max(4, $l - 2)));
            $lignes = max($lignes, $n);
        }
        return $lignes > 1 ? round(min(409, $lignes * 16.8), 1) : null;
    }

    private static function index(string $col): int
    {
        $n = 0;
        foreach (str_split($col) as $ch) $n = $n * 26 + (ord($ch) - 64);
        return $n - 1;
    }

    private static function runs(string $html): string
    {
        $x = '';
        foreach (TexteRiche::segments($html) as [$t, $b, $i, $u]) {
            $t = htmlspecialchars(self::propre($t), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $x .= '<r><rPr>' . ($b ? '<b/>' : '') . ($i ? '<i/>' : '') . ($u ? '<u/>' : '') . '<sz val="11"/><color rgb="FF1F2937"/><rFont val="Segoe UI"/></rPr>'
                . "<t xml:space=\"preserve\">{$t}</t></r>";
        }
        return $x ?: '<t></t>';
    }

    /** Retire les caractères de contrôle interdits en XML (sauf tabulation et sauts de ligne). */
    private static function propre(string $s): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s) ?? '';
    }

    public static function col(int $index): string
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
