<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, Document, Vehicule};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

/**
 * Documents du parc automobile : dépôt, consultation (aperçu PDF / image), téléchargement, renouvellement
 * et archivage motivé des pièces de chaque opération. Les fichiers sont privés (disque local) et servis après contrôle.
 */
class DocumentParcController extends Controller
{
    private const DISQUE = 'local';

    /** Consultation : gestionnaires du parc et direction. */
    private function autoriserLecture(Request $r): void
    {
        abort_unless($r->user()->peutGererParc(), 403);
    }

    /** Dépôt et renouvellement : gestionnaires du parc (la direction consulte). */
    private function peutDeposer(Request $r): bool
    {
        return $r->user()->peutGererParc() && !$r->user()->aRole('direction');
    }

    private function objet(string $type, int $id): Model
    {
        abort_unless(isset(Document::TYPES[$type]), 404);
        return Document::TYPES[$type][0]::findOrFail($id);
    }

    /** Dossier documentaire d'une opération (fragment HTML du panneau « Documents »). */
    public function liste(Request $r, string $type, int $id)
    {
        $this->autoriserLecture($r);
        $objet = $this->objet($type, $id);
        return view('parc._documents', $this->donneesDossier($r, $type, $objet));
    }

    /** Dépôt d'un ou plusieurs fichiers ; avec remplace_id, renouvellement d'un document (l'ancien reste en historique). */
    public function store(Request $r, string $type, int $id)
    {
        $this->autoriserLecture($r);
        abort_unless($this->peutDeposer($r), 403, 'La direction consulte les documents sans en déposer.');
        $objet = $this->objet($type, $id);

        $ancien = null;
        if ($r->filled('remplace_id')) {
            $ancien = Document::where('documentable_type', $type)->where('documentable_id', $objet->getKey())->findOrFail((int) $r->remplace_id);
            $r->merge(['categorie' => $ancien->categorie]);
        }

        $data = $r->validate([
            'categorie' => ['required', 'in:' . implode(',', array_keys(Document::CATEGORIES))],
            'libelle' => ['nullable', 'string', 'max:200'],
            'reference' => ['nullable', 'string', 'max:100'],
            'date_document' => ['nullable', 'date'],
            'date_expiration' => ['nullable', 'date', 'after_or_equal:date_document'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'fichiers' => ['required', 'array', 'min:1', 'max:20'],
            'legendes' => ['nullable', 'array'],
            'legendes.*' => ['nullable', 'string', 'max:200'],
            'fichiers.*' => ['required', File::types(Document::EXTENSIONS)->max(Document::TAILLE_MAX_KO)],
        ], [
            'fichiers.required' => 'Choisissez au moins un fichier.',
            'fichiers.*.max' => 'Un fichier dépasse la taille maximale de ' . (Document::TAILLE_MAX_KO / 1024) . ' Mo.',
            'fichiers.*.mimes' => 'Format non accepté. Formats acceptés : ' . implode(', ', Document::EXTENSIONS) . '.',
            'date_expiration.after_or_equal' => "La date d'expiration précède la date du document.",
        ]);
        if (!Document::aExpiration($data['categorie'])) $data['date_expiration'] = null;
        if ($ancien && count($data['fichiers']) > 1) {
            return $this->reponse($r, $type, $objet, null, 'Un renouvellement porte sur un seul fichier.', 422);
        }

        $deposes = [];
        DB::transaction(function () use ($r, $type, $objet, $data, $ancien, &$deposes) {
            foreach ($data['fichiers'] as $i => $f) {
                $legende = trim((string) ($data['legendes'][$i] ?? ''));
                $ext = strtolower($f->getClientOriginalExtension() ?: $f->guessExtension() ?: 'bin');
                $chemin = $f->storeAs("parc/{$type}/" . now()->format('Y'), Str::uuid() . '.' . $ext, self::DISQUE);
                $nom = mb_substr($f->getClientOriginalName(), 0, 255);
                $deposes[] = Document::create([
                    'documentable_type' => $type,
                    'documentable_id' => $objet->getKey(),
                    'categorie' => $data['categorie'],
                    // Légende propre au fichier, sinon libellé commun, sinon la catégorie (et le nom du fichier s'il y en a plusieurs)
                    'libelle' => $legende !== '' ? $legende : (trim($data['libelle'] ?? '') !== '' ? trim($data['libelle']) . (count($data['fichiers']) > 1 ? ' — ' . pathinfo($nom, PATHINFO_FILENAME) : '')
                        : Document::CATEGORIES[$data['categorie']][0] . (count($data['fichiers']) > 1 ? ' — ' . pathinfo($nom, PATHINFO_FILENAME) : '')),
                    'reference' => $data['reference'] ?? null,
                    'date_document' => $data['date_document'] ?? null,
                    'date_expiration' => $data['date_expiration'] ?? null,
                    'fichier' => $chemin,
                    'nom_original' => $nom,
                    'mime' => $f->getMimeType(),
                    'taille' => (int) $f->getSize(),
                    'remplace_id' => $ancien?->id,
                    'notes' => $data['notes'] ?? null,
                    'depose_par' => $r->user()->id,
                ]);
            }
        });

        $cible = Document::libelleObjet($objet);
        foreach ($deposes as $d) {
            AuditLog::log($ancien ? 'renouvellement' : 'creation', 'document_parc', $cible,
                $d->categorieLibelle() . ' : ' . $d->libelle . ' (' . $d->nom_original . ')' . ($d->date_expiration ? ', expire le ' . $d->date_expiration->format('d/m/Y') : '')
                . ($ancien ? ' — remplace « ' . $ancien->libelle . ' »' : ''));
        }
        $msg = $ancien ? 'Document renouvelé ; la version précédente reste dans l\'historique.' : (count($deposes) > 1 ? count($deposes) . ' documents déposés.' : 'Document déposé.');
        return $this->reponse($r, $type, $objet, $msg);
    }

    /** Aperçu dans le navigateur (PDF, images) ; les autres formats sont téléchargés. */
    public function apercu(Request $r, Document $document)
    {
        $this->autoriserLecture($r);
        if (!$document->estApercu()) return $this->telecharger($r, $document);
        abort_unless(Storage::disk(self::DISQUE)->exists($document->fichier), 404, 'Fichier introuvable.');
        return response()->file(Storage::disk(self::DISQUE)->path($document->fichier), [
            'Content-Type' => $document->estPdf() ? 'application/pdf' : $document->mime,
            'Content-Disposition' => 'inline; filename="' . addcslashes(Str::ascii($document->nom_original), '"\\') . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    public function telecharger(Request $r, Document $document)
    {
        $this->autoriserLecture($r);
        abort_unless(Storage::disk(self::DISQUE)->exists($document->fichier), 404, 'Fichier introuvable.');
        return Storage::disk(self::DISQUE)->download($document->fichier, $document->nom_original);
    }

    /** Archivage motivé : le document disparaît du dossier, le fichier est conservé pour l'audit. */
    public function destroy(Request $r, Document $document)
    {
        $this->autoriserLecture($r);
        abort_unless($r->user()->estAdmin() || ($this->peutDeposer($r) && $document->depose_par === $r->user()->id), 403,
            'Seul un administrateur ou l\'auteur du dépôt peut retirer un document.');
        $r->validate(['motif_suppression' => ['required', 'string', 'min:5', 'max:500']], [
            'motif_suppression.required' => 'Le motif est obligatoire.', 'motif_suppression.min' => 'Le motif doit compter au moins 5 caractères.',
        ]);
        $objet = $document->documentable;
        $document->update(['supprime_par' => $r->user()->id, 'motif_suppression' => trim($r->motif_suppression)]);
        $document->delete();
        AuditLog::log('suppression', 'document_parc', Document::libelleObjet($objet),
            $document->categorieLibelle() . ' : ' . $document->libelle . ' (' . $document->nom_original . ') — Motif : ' . trim($r->motif_suppression));

        $type = $r->input('dossier_type', $document->documentable_type);
        $dossier = isset(Document::TYPES[$type]) ? Document::TYPES[$type][0]::find((int) $r->input('dossier_id')) : null;
        return $this->reponse($r, $dossier ? $type : $document->documentable_type, $dossier ?? $objet, 'Document retiré du dossier.');
    }

    /** Réponse : fragment rafraîchi pour le panneau (requête AJAX), sinon retour à la page. */
    private function reponse(Request $r, string $type, ?Model $objet, ?string $message, ?string $erreur = null, int $code = 200)
    {
        if ($r->expectsJson() || $r->ajax()) {
            return response()->json([
                'ok' => !$erreur,
                'message' => $erreur ?? $message,
                'html' => $objet ? view('parc._documents', $this->donneesDossier($r, $type, $objet))->render() : null,
                'compteur' => $objet ? $this->compteur($type, $objet->getKey()) : null,
            ], $erreur ? $code : 200);
        }
        return back()->with($erreur ? 'error' : 'success', $erreur ?? $message);
    }

    /** Nombre de documents en vigueur et niveau du voyant le plus urgent, pour le bouton trombone de la ligne. */
    private function compteur(string $type, int $id): array
    {
        return Document::resumes()[$type][$id] ?? ['n' => 0, 'niveau' => null];
    }

    private function donneesDossier(Request $r, string $type, Model $objet): array
    {
        $avec = ['deposePar:id,name', 'remplace:id,libelle'];
        $tous = Document::with($avec)->where('documentable_type', $type)->where('documentable_id', $objet->getKey())
            ->orderByDesc('date_expiration')->orderByDesc('created_at')->get();
        $remplaces = $tous->pluck('remplace_id')->filter()->all();
        $enVigueur = $tous->reject(fn($d) => in_array($d->id, $remplaces, true));
        $historique = $tous->filter(fn($d) => in_array($d->id, $remplaces, true));

        // Dossier d'un véhicule : on y retrouve aussi les pièces de ses opérations (pannes, maintenances, dépenses, pénalités…)
        $lies = collect();
        if ($objet instanceof Vehicule) {
            foreach (['panne', 'maintenance', 'depense', 'penalite', 'recette', 'recouvrement', 'emprunt', 'ressource'] as $t) {
                $classe = Document::TYPES[$t][0];
                $ids = $classe::where('vehicule_id', $objet->id)->pluck('id');
                if ($ids->isEmpty()) continue;
                $lies = $lies->merge(Document::with(array_merge($avec, ['documentable']))->enVigueur()->where('documentable_type', $t)->whereIn('documentable_id', $ids)->get());
            }
            $lies = $lies->sortByDesc(fn($d) => $d->date_document ?? $d->created_at)->values();
        }

        return [
            'type' => $type,
            'objet' => $objet,
            'titre' => Document::libelleObjet($objet),
            'enVigueur' => $enVigueur->values(),
            'historique' => $historique->values(),
            'lies' => $lies,
            'categories' => Document::TYPES[$type][2],
            'peutDeposer' => $this->peutDeposer($r),
            'peutRetirer' => fn(Document $d) => $r->user()->estAdmin() || ($this->peutDeposer($r) && $d->depose_par === $r->user()->id),
        ];
    }
}
