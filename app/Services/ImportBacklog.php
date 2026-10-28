<?php
namespace App\Services;

use App\Models\{Projet, UserStory};
use Illuminate\Support\Str;

/**
 * Import d'un Product Backlog depuis un fichier Excel (structure LEVRY ou export SOUMAFE).
 * Colonnes reconnues par leur en-tête : N°, Module / Fonctionnalité, User Story,
 * Exigences fonctionnelles, Tâches, Priorité, Statut, Effort.
 */
class ImportBacklog
{
    private const COLONNES = [
        'module' => ['module', 'fonctionnalite', 'epic'],
        'intitule' => ['user story', 'user stories', 'intitule', 'besoin'],
        'exigences' => ['exigence', 'critere'],
        'taches' => ['tache'],
        'priorite' => ['priorite'],
        'statut' => ['statut', 'etat'],
        'effort' => ['effort', 'point', 'estimation'],
    ];

    public array $rapport = ['crees' => 0, 'maj' => 0, 'ignores' => 0, 'onglet' => null, 'remarques' => []];

    public function __construct(private Projet $projet) {}

    /** @param string $mode « maj » : met à jour les numéros existants ; « ajout » : ne crée que les nouveaux. */
    public function importer(string $chemin, string $mode = 'maj'): array
    {
        $lecteur = new LecteurXlsx($chemin);
        $onglet = $this->choisirOnglet($lecteur->feuilles());
        $this->rapport['onglet'] = $onglet;
        $lignes = $lecteur->lignesFeuille($onglet);

        [$entete, $cols] = $this->trouverEntete($lignes);
        if ($entete === null) {
            throw new \RuntimeException("Colonne « User Story » introuvable dans l'onglet « {$onglet} ». Utilisez le modèle d'import.");
        }

        $stories = $this->extraire($lignes, $entete, $cols);
        if (!$stories) throw new \RuntimeException('Aucune user story trouvée sous la ligne d\'en-tête.');

        $existantes = $this->projet->userStories()->get()->keyBy(fn($us) => $this->cle($us->numero));
        $prochain = $this->compteurNumero(array_merge($existantes->keys()->all(), array_map(fn($s) => $this->cle($s['numero']), array_filter($stories, fn($s) => $s['numero'] !== null))));
        $vus = [];

        foreach ($stories as $s) {
            if ($s['numero'] === null) $s['numero'] = $prochain();
            $cle = $this->cle($s['numero']);
            if (isset($vus[$cle])) {
                $this->remarque("Ligne {$s['ligne']} : numéro {$s['numero']} en double dans le fichier, ignorée.");
                $this->rapport['ignores']++;
                continue;
            }
            $vus[$cle] = true;

            $donnees = array_filter([
                'numero' => $s['numero'],
                'module' => $s['module'],
                'intitule' => $s['intitule'],
                'exigences' => $s['exigences'] ? TexteRiche::depuisTexte($s['exigences']) : null,
                'taches_techniques' => $s['taches'] ? TexteRiche::depuisTexte($s['taches']) : null,
                // Priorité / statut ne sont pas repris : le statut est piloté par les sprints
                'effort_estime' => $s['effort'],
            ], fn($v) => $v !== null && $v !== '');

            if ($us = $existantes->get($cle)) {
                if ($mode !== 'maj') { $this->rapport['ignores']++; continue; }
                $us->update($donnees);
                $this->rapport['maj']++;
            } else {
                $this->projet->userStories()->create($donnees + ['priorite' => 'moyenne', 'statut' => 'a_faire', 'origine' => 'import']);
                $this->rapport['crees']++;
            }
        }
        return $this->rapport;
    }

    /* ─── Lecture ─── */

    private function choisirOnglet(array $noms): string
    {
        foreach ($noms as $n) if (str_contains($this->norm($n), 'product backlog')) return $n;
        foreach ($noms as $n) if (str_contains($this->norm($n), 'backlog') && !str_contains($this->norm($n), 'sprint')) return $n;
        return $noms[0] ?? throw new \RuntimeException('Le classeur ne contient aucun onglet.');
    }

    /** Ligne d'en-tête = première ligne (parmi les 30 premières) contenant une colonne « User Story ». */
    private function trouverEntete(array $lignes): array
    {
        foreach (array_slice($lignes, 0, 30, true) as $num => $cellules) {
            $cols = [];
            foreach ($cellules as $i => $v) {
                $h = $this->norm($v);
                if ($h === '' || mb_strlen($h) > 60) continue;
                if (!isset($cols['numero']) && preg_match('/^(n|no|n°|num|numero|id|ref|reference)\b/u', str_replace(['°', 'º'], '°', $h))) { $cols['numero'] = $i; continue; }
                foreach (self::COLONNES as $champ => $mots) {
                    if (isset($cols[$champ])) continue;
                    foreach ($mots as $mot) {
                        if (str_contains($h, $mot)) { $cols[$champ] = $i; continue 3; }
                    }
                }
            }
            if (isset($cols['intitule'])) return [$num, $cols];
        }
        return [null, []];
    }

    private function extraire(array $lignes, int $entete, array $cols): array
    {
        $brut = [];
        $moduleCourant = null;
        foreach ($lignes as $num => $c) {
            if ($num <= $entete) continue;
            $get = fn($champ) => isset($cols[$champ]) ? trim((string) ($c[$cols[$champ]] ?? '')) : '';
            $texte = $get('intitule');
            if ($texte === '') {
                // Ligne de séparation « MODULE 2 : … »
                $seul = trim(implode(' ', $c));
                if (preg_match('/^module\s*\d*\s*[:\-–—]\s*(.+)$/iu', $seul, $m)) $moduleCourant = trim($m[1]);
                continue;
            }
            $numero = null;
            if (preg_match('/^\s*(US[\s\-_]*[A-Z]?\d+(?:[.\-]\d+)*)\s*[—–\-:]*\s*(.*)$/su', $texte, $m)) {
                $numero = preg_replace('/^US[\s\-_]*/i', 'US ', strtoupper(trim($m[1])));
                $texte = trim($m[2]) !== '' ? trim($m[2]) : $texte;
            }
            $brut[] = [
                'ligne' => $num,
                'numero' => $numero,
                'numero_col' => $get('numero'),
                'module' => $get('module') ?: null,
                'module_courant' => $moduleCourant,
                'intitule' => $this->texte($texte, 2000),
                'exigences' => $this->texte($get('exigences'), 5000),
                'taches' => $this->texte($get('taches'), 5000),
                'priorite' => $this->priorite($get('priorite')),
                'statut' => $this->statut($get('statut')),
                'effort' => UserStory::normaliserPoints($get('effort')),
            ];
        }

        // N° de colonne : retenu comme numéro de story seulement s'il est propre à la ligne
        $frequences = array_count_values(array_filter(array_column($brut, 'numero_col'), fn($v) => $v !== ''));
        foreach ($brut as &$s) {
            if ($s['numero'] === null && $s['numero_col'] !== '' && ($frequences[$s['numero_col']] ?? 0) === 1 && !ctype_digit($s['numero_col'])) {
                $s['numero'] = $s['numero_col'];
            }
            // Clé de regroupement du module : « US 3.2 » → 3, sinon le N° (cellule fusionnée)
            $s['groupe'] = $s['numero'] && preg_match('/(\d+)[.\-]\d+/', $s['numero'], $m) ? 'n' . $m[1] : ($s['numero_col'] !== '' ? 'c' . $s['numero_col'] : null);
        }
        unset($s);

        // Module : la cellule de la ligne, sinon celle d'une autre story du même groupe, sinon le dernier module vu
        $modulesGroupe = [];
        foreach ($brut as $s) if ($s['module'] && $s['groupe']) $modulesGroupe[$s['groupe']] ??= $s['module'];
        $dernier = null;
        foreach ($brut as &$s) {
            $s['module'] = $s['module'] ?: ($s['groupe'] ? ($modulesGroupe[$s['groupe']] ?? null) : null) ?: $s['module_courant'] ?: $dernier;
            $dernier = $s['module'] ?: $dernier;
            $s['module'] = $s['module'] ? Str::limit($s['module'], 147) : null;
            if ($s['numero'] && mb_strlen($s['numero']) > 20) {
                $this->remarque("Ligne {$s['ligne']} : numéro « {$s['numero']} » tronqué à 20 caractères.");
                $s['numero'] = mb_substr($s['numero'], 0, 20);
            }
        }
        unset($s);
        return $brut;
    }

    /* ─── Normalisation ─── */

    private function norm(?string $v): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower(Str::ascii((string) $v))));
    }

    private function cle(string $numero): string
    {
        return preg_replace('/[\s\-_]+/', '', mb_strtolower($numero));
    }

    /** Une puce par ligne (« • a ; • b » → deux lignes). */
    private function texte(string $v, int $max): ?string
    {
        $v = trim(str_replace("\r", '', $v));
        if ($v === '') return null;
        if (substr_count($v, '•') >= 2) {
            $v = trim(preg_replace('/\s*;?\s*•\s*/u', "\n• ", $v));
        }
        return mb_substr($v, 0, $max);
    }

    private function priorite(string $v): ?string
    {
        $n = $this->norm($v);
        if ($n === '') return null;
        return match (true) {
            str_contains($n, 'haut') || str_contains($n, 'elev') || str_contains($n, 'high') || str_contains($n, 'must') || $n === '1' => 'haute',
            str_contains($n, 'bas') || str_contains($n, 'faible') || str_contains($n, 'low') || str_contains($n, 'could') || $n === '3' => 'basse',
            default => 'moyenne',
        };
    }

    private function statut(string $v): ?string
    {
        $n = $this->norm($v);
        if ($n === '') return null;
        return match (true) {
            str_contains($n, 'termin') || str_contains($n, 'realis') || str_contains($n, 'done') || $n === 't' || $n === 'tt' => 'terminee',
            str_contains($n, 'cours') || $n === 'e' || $n === 'd' => 'en_cours',
            str_contains($n, 'planif') => 'planifiee',
            default => 'a_faire',
        };
    }

    /** Générateur de numéros « US x.y » à la suite des numéros existants. */
    private function compteurNumero(array $cles): \Closure
    {
        $max = [0, 0];
        foreach ($cles as $c) {
            if (preg_match('/^us(\d+)\.(\d+)$/', $c, $m) && [(int) $m[1], (int) $m[2]] > $max) $max = [(int) $m[1], (int) $m[2]];
        }
        $prefixe = $max[0] ?: 1;
        $n = $max[1];
        $prises = array_flip($cles);
        return function () use (&$n, $prefixe, $prises) {
            do { $n++; } while (isset($prises["us{$prefixe}.{$n}"]));
            return "US {$prefixe}.{$n}";
        };
    }

    private function remarque(string $m): void
    {
        if (count($this->rapport['remarques']) < 15) $this->rapport['remarques'][] = $m;
    }
}
