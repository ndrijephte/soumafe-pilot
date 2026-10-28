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
        if ($this->largeurs) {
            $x .= '<cols>';
            foreach ($this->largeurs as $i => $l) $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) $l . '" customWidth="1"/>';
            $x .= '</cols>';
        }
        $x .= '<sheetData>';
        foreach ($this->lignes as $r => $cellules) {
            $num = $r + 1;
            $x .= isset($this->hauteurs[$num]) ? "<row r=\"{$num}\" ht=\"{$this->hauteurs[$num]}\" customHeight=\"1\">" : "<row r=\"{$num}\">";
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
        return $x . '</worksheet>';
    }

    private static function runs(string $html): string
    {
        $x = '';
        foreach (TexteRiche::segments($html) as [$t, $b, $i, $u]) {
            $t = htmlspecialchars(self::propre($t), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $x .= '<r><rPr>' . ($b ? '<b/>' : '') . ($i ? '<i/>' : '') . ($u ? '<u/>' : '') . '<sz val="11"/><rFont val="Calibri"/></rPr>'
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
