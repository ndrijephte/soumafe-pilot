<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphTo};
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Document joint à une opération du parc automobile (traçabilité de la gestion du parc).
 * Les fichiers sont stockés hors du dossier public et servis par l'application, après contrôle des droits.
 */
class Document extends Model
{
    use SoftDeletes;

    protected $fillable = ['documentable_type', 'documentable_id', 'categorie', 'libelle', 'reference', 'date_document', 'date_expiration',
        'fichier', 'nom_original', 'mime', 'taille', 'remplace_id', 'notes', 'depose_par', 'alerte_envoyee', 'supprime_par', 'motif_suppression'];
    protected $casts = ['date_document' => 'date', 'date_expiration' => 'date'];

    /** Seuil d'alerte avant expiration (jours). */
    public const ALERTE_JOURS = 30;

    /** Catégories : [libellé, à date d'expiration]. */
    public const CATEGORIES = [
        'carte_grise' => ['Carte grise', false],
        'assurance' => ['Assurance', true],
        'visite_technique' => ['Visite technique', true],
        'vignette' => ['Vignette', true],
        'carte_stationnement' => ['Carte de stationnement', true],
        'patente' => ['Patente / licence de transport', true],
        'permis' => ['Permis de conduire', true],
        'piece_identite' => ["Pièce d'identité", true],
        'contrat' => ['Contrat', true],
        'devis' => ['Devis', false],
        'facture' => ['Facture', false],
        'bon_intervention' => ["Bon d'intervention", false],
        'recu' => ['Reçu', false],
        'bordereau' => ['Bordereau de versement', false],
        'pv' => ['Procès-verbal / contravention', false],
        'constat' => ['Constat amiable / rapport', false],
        'reconnaissance_dette' => ['Reconnaissance de dette', false],
        'echeancier' => ['Échéancier', false],
        'photo' => ['Photo', false],
        'autre' => ['Autre document', false],
    ];

    /** Opérations documentables : alias => [modèle, libellé, catégories proposées]. */
    public const TYPES = [
        'vehicule' => [Vehicule::class, 'Véhicule', ['carte_grise', 'assurance', 'visite_technique', 'vignette', 'carte_stationnement', 'patente', 'contrat', 'photo', 'autre']],
        'chauffeur' => [Chauffeur::class, 'Chauffeur', ['permis', 'piece_identite', 'contrat', 'photo', 'autre']],
        'panne' => [Panne::class, 'Panne', ['devis', 'facture', 'bon_intervention', 'constat', 'photo', 'autre']],
        'maintenance' => [MaintenancePreventive::class, 'Maintenance', ['facture', 'bon_intervention', 'devis', 'photo', 'autre']],
        'depense' => [Depense::class, 'Dépense', ['facture', 'recu', 'devis', 'autre']],
        'penalite' => [Penalite::class, 'Pénalité', ['pv', 'recu', 'photo', 'autre']],
        'recette' => [RecetteVehicule::class, 'Recette', ['bordereau', 'recu', 'autre']],
        'recouvrement' => [Recouvrement::class, 'Recouvrement', ['reconnaissance_dette', 'echeancier', 'recu', 'autre']],
        'emprunt' => [Emprunt::class, 'Emprunt', ['contrat', 'echeancier', 'recu', 'autre']],
        'pret' => [PretAccorde::class, 'Prêt accordé', ['contrat', 'echeancier', 'recu', 'autre']],
        'ressource' => [AutreRessource::class, 'Autre ressource', ['facture', 'recu', 'contrat', 'autre']],
    ];

    /** Formats acceptés ; seuls PDF et images s'affichent en aperçu, les autres se téléchargent. */
    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods'];
    public const TAILLE_MAX_KO = 10240;

    public function documentable(): MorphTo { return $this->morphTo(); }
    public function deposePar(): BelongsTo { return $this->belongsTo(User::class, 'depose_par'); }
    public function remplace(): BelongsTo { return $this->belongsTo(self::class, 'remplace_id'); }

    /** Documents en vigueur : non remplacés par un renouvellement. */
    public function scopeEnVigueur($q)
    {
        return $q->whereNotExists(fn($s) => $s->selectRaw(1)->from('documents as r')->whereColumn('r.remplace_id', 'documents.id')->whereNull('r.deleted_at'));
    }

    public function categorieLibelle(): string { return self::CATEGORIES[$this->categorie][0] ?? ucfirst(str_replace('_', ' ', $this->categorie)); }
    public static function aExpiration(string $categorie): bool { return self::CATEGORIES[$categorie][1] ?? false; }

    public function estApercu(): bool { return $this->estPdf() || $this->estImage(); }
    public function estPdf(): bool { return $this->mime === 'application/pdf' || str_ends_with(strtolower($this->nom_original), '.pdf'); }
    public function estImage(): bool { return str_starts_with((string) $this->mime, 'image/'); }

    public function tailleLisible(): string
    {
        $t = (int) $this->taille;
        return $t >= 1048576 ? number_format($t / 1048576, 1, ',', ' ') . ' Mo' : max(1, (int) round($t / 1024)) . ' Ko';
    }

    /**
     * État de validité (documents à expiration) : niveau du voyant et libellé.
     * @return array{niveau: string, libelle: string, jours: ?int}|null
     */
    public function validite(): ?array
    {
        if (!$this->date_expiration) return null;
        $j = (int) today()->diffInDays($this->date_expiration, false);
        if ($j < 0) return ['niveau' => 'retard', 'libelle' => 'Expiré depuis le ' . $this->date_expiration->format('d/m/Y'), 'jours' => $j];
        if ($j === 0) return ['niveau' => 'retard', 'libelle' => "Expire aujourd'hui", 'jours' => 0];
        if ($j <= 7) return ['niveau' => 'proche', 'libelle' => "Expire dans {$j} jour(s), le " . $this->date_expiration->format('d/m/Y'), 'jours' => $j];
        if ($j <= self::ALERTE_JOURS) return ['niveau' => 'semaine', 'libelle' => "À renouveler : expire le " . $this->date_expiration->format('d/m/Y'), 'jours' => $j];
        return ['niveau' => 'ok', 'libelle' => 'Valide jusqu\'au ' . $this->date_expiration->format('d/m/Y'), 'jours' => $j];
    }

    /**
     * Documents en vigueur dont l'échéance compte : pour chaque opération et catégorie, seul le document
     * qui expire le plus tard est suivi (une nouvelle assurance déposée sans « Renouveler » couvre l'ancienne).
     */
    public static function echeancesSuivies(): \Illuminate\Support\Collection
    {
        return self::enVigueur()->whereNotNull('date_expiration')->get()
            ->groupBy(fn($d) => $d->documentable_type . '|' . $d->documentable_id . '|' . $d->categorie)
            ->map(fn($g) => $g->sortByDesc('date_expiration')->first())
            ->values();
    }

    /** Documents expirés ou expirant dans les ALERTE_JOURS jours, du plus urgent au moins urgent. */
    public static function aRenouveler(): \Illuminate\Support\Collection
    {
        $docs = self::echeancesSuivies()->filter(fn($d) => $d->date_expiration->lte(today()->addDays(self::ALERTE_JOURS)))
            ->sortBy('date_expiration')->values();
        $docs->load('documentable');
        return $docs->filter(fn($d) => $d->documentable !== null)->values();
    }

    /**
     * Résumé par opération pour les trombones : nombre de documents en vigueur et voyant d'échéance le plus urgent.
     * @return array<string, array<int, array{n: int, niveau: ?string}>>
     */
    public static function resumes(): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('documents')) return [];
        $rang = ['ok' => 0, 'semaine' => 1, 'proche' => 2, 'retard' => 3];
        $res = [];
        foreach (self::enVigueur()->selectRaw('documentable_type, documentable_id, count(*) as n')->groupBy('documentable_type', 'documentable_id')->get() as $l) {
            $res[$l->documentable_type][$l->documentable_id] = ['n' => (int) $l->n, 'niveau' => null];
        }
        foreach (self::echeancesSuivies() as $d) {
            $niv = $d->validite()['niveau'];
            $c = &$res[$d->documentable_type][$d->documentable_id];
            if ($c['niveau'] === null || $rang[$niv] > $rang[$c['niveau']]) $c['niveau'] = $niv;
            unset($c);
        }
        return $res;
    }

    /** Alias de l'opération (vehicule, panne…). */
    public static function aliasDe(Model $m): ?string
    {
        foreach (self::TYPES as $alias => [$classe]) if ($m instanceof $classe) return $alias;
        return null;
    }

    /** Libellé lisible de l'opération rattachée (« Véhicule 1234 AB 01 », « Panne du 12/10/2026 »…). */
    public static function libelleObjet(?Model $m): string
    {
        if (!$m) return '—';
        $date = fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '';
        $immat = fn($v) => $v?->vehicule?->immatriculation ? ' · ' . $v->vehicule->immatriculation : '';
        return match (true) {
            $m instanceof Vehicule => 'Véhicule ' . $m->immatriculation,
            $m instanceof Chauffeur => 'Chauffeur ' . $m->nomComplet(),
            $m instanceof Panne => 'Panne du ' . $date($m->date_declaration) . $immat($m),
            $m instanceof MaintenancePreventive => 'Maintenance' . ($m->prochaine_echeance ? ' (échéance ' . $date($m->prochaine_echeance) . ')' : '') . $immat($m),
            $m instanceof Depense => 'Dépense du ' . $date($m->date_depense ?? $m->date ?? $m->created_at),
            $m instanceof Penalite => 'Pénalité du ' . $date($m->date_infraction) . $immat($m),
            $m instanceof RecetteVehicule => 'Recette du ' . $date($m->date) . $immat($m),
            $m instanceof Recouvrement => 'Recouvrement ' . ($m->chauffeur_nom ?? ''),
            $m instanceof Emprunt => 'Emprunt',
            $m instanceof PretAccorde => 'Prêt accordé',
            $m instanceof AutreRessource => 'Autre ressource',
            default => class_basename($m),
        };
    }
}
