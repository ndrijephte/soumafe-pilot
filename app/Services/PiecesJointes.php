<?php
namespace App\Services;

use App\Models\{AuditLog, Document};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

/**
 * Pièces jointes déposées directement depuis un formulaire (panne, maintenance…) :
 * plusieurs fichiers à la fois (photos sous différents angles, devis, facture…), chacun avec sa catégorie et sa légende.
 * Champs du formulaire : pieces[] (fichiers), pieces_categorie[], pieces_legende[].
 */
final class PiecesJointes
{
    private const DISQUE = 'local';
    public const MAX = 20;

    /** Contrôle des fichiers, à appeler avant d'enregistrer l'opération (rien n'est créé si un fichier est refusé). */
    public static function valider(Request $r, string $type): void
    {
        if (!$r->hasFile('pieces')) return;
        $categories = Document::TYPES[$type][2] ?? array_keys(Document::CATEGORIES);
        $r->validate([
            'pieces' => ['array', 'max:' . self::MAX],
            'pieces.*' => [File::types(Document::EXTENSIONS)->max(Document::TAILLE_MAX_KO)],
            'pieces_categorie' => ['nullable', 'array'],
            'pieces_categorie.*' => ['nullable', 'in:' . implode(',', $categories)],
            'pieces_legende' => ['nullable', 'array'],
            'pieces_legende.*' => ['nullable', 'string', 'max:200'],
        ], [
            'pieces.max' => 'Au plus ' . self::MAX . ' pièces jointes à la fois.',
            'pieces.*.max' => 'Une pièce jointe dépasse ' . (Document::TAILLE_MAX_KO / 1024) . ' Mo.',
            'pieces.*.mimes' => 'Format de pièce jointe non accepté (' . implode(', ', Document::EXTENSIONS) . ').',
        ]);
    }

    /** Enregistre les pièces jointes sur l'opération ; renvoie le nombre de fichiers déposés. */
    public static function deposer(Request $r, string $type, Model $objet, string $categorieDefaut = 'photo'): int
    {
        $fichiers = $r->file('pieces', []);
        if (!$fichiers) return 0;
        $categories = Document::TYPES[$type][2] ?? array_keys(Document::CATEGORIES);
        if (!in_array($categorieDefaut, $categories, true)) $categorieDefaut = $categories[0];
        $deposes = [];
        DB::transaction(function () use ($r, $type, $objet, $fichiers, $categories, $categorieDefaut, &$deposes) {
            foreach (array_values($fichiers) as $i => $f) {
                $cat = $r->input("pieces_categorie.$i");
                if (!in_array($cat, $categories, true)) $cat = str_starts_with((string) $f->getMimeType(), 'image/') ? (in_array('photo', $categories, true) ? 'photo' : $categorieDefaut) : $categorieDefaut;
                $nom = mb_substr($f->getClientOriginalName(), 0, 255);
                $ext = strtolower($f->getClientOriginalExtension() ?: $f->guessExtension() ?: 'bin');
                $legende = trim((string) $r->input("pieces_legende.$i", ''));
                $deposes[] = Document::create([
                    'documentable_type' => $type,
                    'documentable_id' => $objet->getKey(),
                    'categorie' => $cat,
                    'libelle' => $legende !== '' ? mb_substr($legende, 0, 200) : Document::CATEGORIES[$cat][0] . ' — ' . pathinfo($nom, PATHINFO_FILENAME),
                    'date_document' => today(),
                    'fichier' => $f->storeAs("parc/{$type}/" . now()->format('Y'), Str::uuid() . '.' . $ext, self::DISQUE),
                    'nom_original' => $nom,
                    'mime' => $f->getMimeType(),
                    'taille' => (int) $f->getSize(),
                    'depose_par' => $r->user()->id,
                ]);
            }
        });
        $cible = Document::libelleObjet($objet);
        foreach ($deposes as $d) AuditLog::log('creation', 'document_parc', $cible, $d->categorieLibelle() . ' : ' . $d->libelle . ' (' . $d->nom_original . ')');
        return count($deposes);
    }

    /** Complément de message : « , 3 pièces jointes ». */
    public static function mention(int $n): string
    {
        return $n ? ' (' . $n . ' pièce' . ($n > 1 ? 's' : '') . ' jointe' . ($n > 1 ? 's' : '') . ')' : '';
    }
}
