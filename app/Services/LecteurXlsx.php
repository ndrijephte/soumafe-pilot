<?php
namespace App\Services;

/**
 * Lecteur XLSX natif (sans dépendance externe).
 * Utilise ZipArchive + SimpleXML pour lire les feuilles d'un fichier .xlsx.
 */
class LecteurXlsx
{
    private array $sharedStrings = [];
    private \ZipArchive $zip;

    public function __construct(private string $chemin)
    {
        $this->zip = new \ZipArchive();
        if ($this->zip->open($chemin) !== true) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier XLSX : {$chemin}");
        }
        $this->chargerChainesPartagees();
    }

    public function __destruct()
    {
        $this->zip->close();
    }

    /** Liste des noms de feuilles. */
    public function feuilles(): array
    {
        $xml = simplexml_load_string($this->zip->getFromName('xl/workbook.xml'));
        $noms = [];
        foreach ($xml->sheets->sheet as $sheet) {
            $noms[] = (string) $sheet['name'];
        }
        return $noms;
    }

    /**
     * Lit une feuille et retourne un tableau de lignes.
     * @param int $index Index de la feuille (1-based)
     * @param int $ligneEntete Numéro de la ligne d'en-tête (1-based). 0 = pas d'en-tête.
     */
    public function lire(int $index = 1, int $ligneEntete = 0): array
    {
        $contenu = $this->zip->getFromName("xl/worksheets/sheet{$index}.xml");
        if ($contenu === false) {
            throw new \RuntimeException("Feuille {$index} introuvable.");
        }
        $xml = simplexml_load_string($contenu);
        $lignes = [];

        foreach ($xml->sheetData->row as $row) {
            $numRow = (int) $row['r'];
            $cols = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $colIndex = $this->colToIndex($ref);
                $val = $this->valeurCellule($cell);
                $cols[$colIndex] = $val;
            }
            // Remplir les trous
            if (!empty($cols)) {
                $max = max(array_keys($cols));
                for ($i = 0; $i <= $max; $i++) {
                    if (!isset($cols[$i])) $cols[$i] = null;
                }
                ksort($cols);
            }
            $lignes[$numRow] = array_values($cols);
        }

        // Si en-tête demandé, transformer en tableau associatif
        if ($ligneEntete > 0 && isset($lignes[$ligneEntete])) {
            $entetes = array_map(fn ($v) => trim(strtolower(str_replace([' ', "\n"], '_', $v ?? ''))), $lignes[$ligneEntete]);
            $resultat = [];
            foreach ($lignes as $num => $vals) {
                if ($num <= $ligneEntete) continue;
                if (empty(array_filter($vals))) continue;
                $row = [];
                foreach ($entetes as $i => $key) {
                    if ($key === '') continue;
                    $row[$key] = $vals[$i] ?? null;
                }
                $resultat[] = $row;
            }
            return $resultat;
        }

        return array_values($lignes);
    }

    private function chargerChainesPartagees(): void
    {
        $contenu = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($contenu === false) return;
        $xml = simplexml_load_string($contenu);
        foreach ($xml->si as $si) {
            // Gérer les chaînes simples et les chaînes riches (t vs r/t)
            if (isset($si->t)) {
                $this->sharedStrings[] = (string) $si->t;
            } else {
                $texte = '';
                foreach ($si->r as $run) {
                    $texte .= (string) $run->t;
                }
                $this->sharedStrings[] = $texte;
            }
        }
    }

    private function valeurCellule(\SimpleXMLElement $cell): ?string
    {
        $type = (string) $cell['t'];
        $val = (string) $cell->v;
        if ($val === '') return null;

        if ($type === 's') {
            return $this->sharedStrings[(int) $val] ?? $val;
        }
        return $val;
    }

    private function colToIndex(string $ref): int
    {
        preg_match('/^([A-Z]+)/', $ref, $m);
        $lettres = $m[1];
        $index = 0;
        for ($i = 0; $i < strlen($lettres); $i++) {
            $index = $index * 26 + (ord($lettres[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }
}
