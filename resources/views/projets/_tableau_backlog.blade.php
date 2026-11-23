{{-- Tableau de bord du Product Backlog : exécution du produit, répartition par statut, progression sprint après sprint. Paramètres : $bilan, $tableau --}}
@php
    $statutsBord = [
        'acceptee' => ['Acceptées', '#166534'],
        'terminee' => ['Terminées', '#16a34a'],
        'en_cours' => ['En cours', '#2e74b5'],
        'en_revue' => ['En revue', '#ca8a04'],
        'bloquee' => ['Bloquées', '#7c3aed'],
        'a_faire' => ['À faire', '#94a3b8'],
    ];
@endphp
@once
@push('styles')
<style>
.pb-bord{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:0;background:#fff;border:1px solid var(--bord);border-radius:12px;margin-bottom:14px}
.pb-bord>div{padding:14px 18px;min-width:0}
.pb-bord>div+div{border-left:1px solid var(--bord)}
.pb-bord h4{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin:0 0 8px}
.pb-chiffre{display:flex;align-items:baseline;gap:8px;margin-bottom:10px}
.pb-chiffre b{font-size:30px;line-height:1;font-weight:700;color:#1f4e79}
.pb-chiffre span{font-size:12.5px;color:#475569}
.pb-seg{display:flex;gap:2px;height:10px;margin-bottom:8px}
.pb-seg button{border:0;padding:0;min-width:4px;cursor:pointer}
.pb-seg button:first-child{border-radius:4px 0 0 4px}.pb-seg button:last-child{border-radius:0 4px 4px 0}.pb-seg button:only-child{border-radius:4px}
.pb-seg button:hover,.pb-seg button:focus-visible{outline:2px solid #1f4e79;outline-offset:1px}
.pb-leg{display:flex;flex-wrap:wrap;gap:4px 12px;margin-bottom:8px}
.pb-leg button{display:inline-flex;align-items:center;gap:5px;background:none;border:0;padding:0;font:inherit;font-size:12px;color:#475569;cursor:pointer}
.pb-leg button:hover{color:#1f4e79}
.pb-leg i{width:9px;height:9px;border-radius:2px}
.pb-leg b{color:#1f2937}
.pb-pied{font-size:11.5px;color:#64748b}
.pb-sp{display:grid;grid-template-columns:128px minmax(60px,1fr) 56px 56px;gap:10px;align-items:center;padding:5px 0;font-size:12px;color:#475569}
.pb-sp.entete{padding:0 0 4px;font-size:10.5px;font-weight:600;letter-spacing:.3px;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #eef2f7;margin-bottom:2px}
.pb-sp .en-cours{font-size:10px;font-weight:600;color:#2e74b5;margin-left:5px;white-space:nowrap}
.pb-sp>span:first-child{white-space:nowrap}
.pb-sp .nb{text-align:right;white-space:nowrap}.pb-sp .nb b{color:#1f2937}
.pb-sp .cumul{text-align:right;color:#64748b;white-space:nowrap}
.pb-sp a{font-weight:700;color:#1f4e79;text-decoration:none}.pb-sp a:hover{text-decoration:underline}
.pb-sp .jauge{height:6px;background:#e5eaf1;border-radius:6px;overflow:hidden}.pb-sp .jauge i{display:block;height:100%;background:#16a34a;border-radius:6px}.pb-sp .jauge.ouvert i{background:#2e74b5}

.pb-vide{font-size:12.5px;color:var(--gris);padding:10px 0}
@media(max-width:800px){.pb-bord{grid-template-columns:1fr}.pb-bord>div+div{border-left:0;border-top:1px solid var(--bord)}}
</style>
@endpush
@endonce
<div class="pb-bord">
    <div>
        <h4>Exécution du produit</h4>
        <div class="pb-chiffre"><b>{{ $bilan['pct'] }} %</b><span>{{ $bilan['stories_terminees'] }}/{{ $bilan['stories'] }} user stories terminées @if($bilan['points'])· {{ $bilan['points_faits'] }}/{{ $bilan['points'] }} pts @endif</span></div>
        <div class="pb-seg" role="group" aria-label="Répartition par statut">
            @foreach($statutsBord as $cle => [$lib, $couleur])
            @if($tableau['repartition'][$cle])
            <button type="button" style="flex:{{ $tableau['repartition'][$cle] }};background:{{ $couleur }}" onclick="filtrerStatut('{{ $cle }}')" title="{{ $lib }} : {{ $tableau['repartition'][$cle] }} - afficher" aria-label="{{ $lib }} : {{ $tableau['repartition'][$cle] }}"></button>
            @endif
            @endforeach
        </div>
        <div class="pb-leg">
            @foreach($statutsBord as $cle => [$lib, $couleur])
            <button type="button" onclick="filtrerStatut('{{ $cle }}')"><i style="background:{{ $couleur }}"></i>{{ $lib }} <b>{{ $tableau['repartition'][$cle] }}</b></button>
            @endforeach
        </div>
        @if($tableau['a_reprendre'] || $tableau['issues_review'])
        <div class="pb-pied">
            @if($tableau['a_reprendre']){{ $tableau['a_reprendre'] }} entamée(s), à reprendre @endif
            @if($tableau['a_reprendre'] && $tableau['issues_review']) · @endif
            @if($tableau['issues_review']){{ $tableau['issues_review'] }} issue(s) des Reviews @endif
        </div>
        @endif
    </div>
    <div>
        <h4>Progression par sprint</h4>
        @if($tableau['sprints'])
        <div class="pb-sp entete" aria-hidden="true"><span>Sprint</span><span>Stories livrées dans le sprint</span><span></span><span style="text-align:right">Produit</span></div>
        <div data-collapse="4">
        @foreach($tableau['sprints'] as $l)
        @php $taux = $l['engagees'] ? round($l['terminees'] / $l['engagees'] * 100) : 0; @endphp
        <div class="pb-sp" data-collapse-item title="Sprint {{ $l['numero'] }} : {{ $l['terminees'] }} user story(s) livrée(s) sur {{ $l['engagees'] }} engagée(s) ({{ $taux }} %). À l'issue de ce sprint, {{ $l['cumul'] }} % des user stories du produit sont terminées.">
            <span><a href="{{ route('sprints.show', $l['id']) }}">Sprint {{ $l['numero'] }}</a>@if($l['statut'] === 'en_cours')<span class="en-cours">en cours</span>@endif</span>
            <span class="jauge {{ $l['statut'] !== 'termine' ? 'ouvert' : '' }}" role="img" aria-label="{{ $taux }} % des stories du sprint livrées"><i style="width:{{ $taux }}%"></i></span>
            <span class="nb"><b>{{ $l['terminees'] }}</b>/{{ $l['engagees'] }}</span>
            <span class="cumul">{{ $l['cumul'] }} %</span>
        </div>
        @endforeach
        </div>
        <div class="pb-pied" style="margin-top:4px">Barre : stories livrées dans le sprint sur celles engagées. Produit : part des user stories du produit terminées à l'issue du sprint.</div>
        @else
        <div class="pb-vide">Aucune user story engagée dans un sprint pour l'instant.</div>
        @endif
    </div>
</div>
