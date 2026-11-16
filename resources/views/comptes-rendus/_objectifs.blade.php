{{-- Sprint Goal : liste quand il regroupe plusieurs objectifs (lignes ou « ; »), texte sinon. Paramètre : $texte --}}
@php $objs = \App\Models\Sprint::decouperObjectif($texte ?? ''); @endphp
@if(!$objs)—
@elseif(count($objs) === 1){{ $objs[0] }}
@else<ul style="margin:0;padding-left:18px;list-style:disc">@foreach($objs as $o)<li style="margin:1px 0">{{ $o }}</li>@endforeach</ul>@endif
