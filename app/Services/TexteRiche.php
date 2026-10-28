<?php
namespace App\Services;

/**
 * Texte des cadres de rédaction : gras, italique, souligné, listes à puces / numérotées.
 * Stocké en HTML restreint ; les anciens textes bruts (« • » en début de ligne) restent lisibles.
 */
class TexteRiche
{
    private const BALISES = '<b><strong><i><em><u><ul><ol><li><br><p><div>';

    /** Contenu du cadre de rédaction (balises de mise en forme ou entités HTML comme &#039; &amp;). */
    public static function estHtml(?string $v): bool
    {
        return $v !== null && (bool) preg_match('/<(b|strong|i|em|u|ul|ol|li|br|p|div)\b[^>]*>|&(#\d+|#x[0-9a-f]+|[a-z][a-z0-9]{1,7});/i', $v);
    }

    /** Échappement minimal : seuls &, < et > (les apostrophes et guillemets restent lisibles). */
    private static function esc(string $t): string
    {
        return htmlspecialchars($t, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * HTML saisi dans un cadre de rédaction → HTML restreint sans attributs ; null si vide.
     * Un texte brut multiligne (import Excel, ancien champ) est converti en HTML.
     */
    public static function nettoyer(?string $html): ?string
    {
        if ($html === null || trim($html) === '') return null;
        if (!self::estHtml($html) && str_contains($html, "\n")) return self::depuisTexte($html);
        if (!preg_match('/<[a-z\/]/i', $html)) {
            // Une ligne sans balise : on normalise l'encodage (« s&#039;est » → « s'est »)
            $t = self::esc(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            return trim($t) === '' ? null : trim($t);
        }
        $html = preg_replace('#<(script|style|head)\b.*?</\1>#is', '', $html);
        $html = strip_tags($html, self::BALISES);
        $html = preg_replace('/<(\/?)(b|strong|i|em|u|ul|ol|li|br|p|div)\b[^>]*>/i', '<$1$2>', $html);
        $html = str_ireplace(['<strong>', '</strong>', '<em>', '</em>'], ['<b>', '</b>', '<i>', '</i>'], $html);
        // Blocs vides laissés par l'éditeur
        $html = preg_replace('/<(b|i|u|li|p|div)>(\s|&nbsp;|<br>)*<\/\1>/i', '', $html);
        $html = preg_replace('/(<br>\s*)+$/i', '', trim($html));
        return trim(self::texte($html)) === '' ? null : $html;
    }

    /** Texte brut → HTML : lignes « • », « - », « * » regroupées en liste à puces. */
    public static function depuisTexte(?string $texte): string
    {
        $out = '';
        $liste = false;
        foreach (preg_split('/\R/u', trim((string) $texte)) as $ligne) {
            if (preg_match('/^\s*(?:[•\-\*·▪◦]|\d+[.)])\s+(.*)$/u', $ligne, $m)) {
                if (!$liste) { $out .= '<ul>'; $liste = true; }
                $out .= '<li>' . self::esc(trim($m[1])) . '</li>';
                continue;
            }
            if ($liste) { $out .= '</ul>'; $liste = false; }
            elseif ($out !== '') $out .= '<br>';
            $out .= self::esc($ligne);
        }
        return $out . ($liste ? '</ul>' : '');
    }

    /** Liste à puces HTML à partir de lignes. */
    public static function liste(iterable $lignes): ?string
    {
        $li = '';
        foreach ($lignes as $l) if (trim((string) $l) !== '') $li .= '<li>' . self::esc(trim($l)) . '</li>';
        return $li === '' ? null : "<ul>{$li}</ul>";
    }

    /** HTML sûr pour l'affichage (ancien texte brut converti). */
    public static function html(?string $v): string
    {
        if ($v === null || trim($v) === '') return '';
        return self::estHtml($v) ? (self::nettoyer($v) ?? '') : self::depuisTexte($v);
    }

    /** Texte brut (recherche, résumés, exports simples). */
    public static function texte(?string $v): string
    {
        if ($v === null) return '';
        if (!self::estHtml($v)) return trim($v);
        $v = preg_replace('#<(script|style)\b.*?</\1>#is', '', $v);
        $v = preg_replace('/<li>/i', "\n• ", $v);
        $v = preg_replace('/<br\s*\/?>|<\/(p|div|ul|ol)>/i', "\n", $v);
        $v = html_entity_decode(strip_tags($v), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace("/\n{2,}/", "\n", str_replace("\u{a0}", ' ', $v)));
    }

    /**
     * Segments mis en forme pour Excel : [[texte, gras, italique, souligné], …].
     * Les puces et sauts de ligne sont rendus en texte.
     */
    public static function segments(string $html): array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8"><div>' . self::html($html) . '</div>', LIBXML_NOERROR);
        $racine = $doc->getElementsByTagName('div')->item(0);
        $segs = [];
        $parcourir = function (\DOMNode $n, array $fmt) use (&$parcourir, &$segs) {
            foreach ($n->childNodes as $c) {
                if ($c instanceof \DOMText) { if ($c->data !== '') $segs[] = [$c->data, ...$fmt]; continue; }
                if (!$c instanceof \DOMElement) continue;
                $tag = strtolower($c->tagName);
                $f = $fmt;
                if ($tag === 'b') $f[0] = true;
                if ($tag === 'i') $f[1] = true;
                if ($tag === 'u') $f[2] = true;
                if ($tag === 'br') { $segs[] = ["\n", false, false, false]; continue; }
                if ($tag === 'li') {
                    $num = '• ';
                    if (strtolower($c->parentNode->nodeName) === 'ol') {
                        $rang = 0;
                        for ($p = $c; $p; $p = $p->previousSibling) if ($p instanceof \DOMElement) $rang++;
                        $num = $rang . '. ';
                    }
                    $segs[] = ["\n" . $num, false, false, false];
                }
                $bloc = in_array($tag, ['p', 'div', 'ul', 'ol'], true);
                if ($bloc) $segs[] = ["\n", false, false, false];
                $parcourir($c, $f);
                if ($bloc) $segs[] = ["\n", false, false, false];
            }
        };
        if ($racine) $parcourir($racine, [false, false, false]);

        // Fusion des segments identiques, nettoyage des sauts de ligne en trop
        $res = [];
        foreach ($segs as $s) {
            $s[0] = str_replace("\u{a0}", ' ', $s[0]);
            $d = count($res) - 1;
            if ($d >= 0 && array_slice($res[$d], 1) === array_slice($s, 1)) $res[$d][0] .= $s[0];
            else $res[] = $s;
        }
        $out = [];
        $precedentSaut = true; // pas de saut de ligne en tête ni en double
        foreach ($res as $s) {
            $t = $s[0];
            if ($precedentSaut) $t = ltrim($t, "\n");
            $t = preg_replace("/\n{2,}/", "\n", $t);
            if ($t === '') continue;
            $precedentSaut = str_ends_with($t, "\n");
            $out[] = [$t, $s[1], $s[2], $s[3]];
        }
        if ($out) $out[count($out) - 1][0] = rtrim($out[count($out) - 1][0], "\n");
        return array_values(array_filter($out, fn($s) => $s[0] !== ''));
    }
}
