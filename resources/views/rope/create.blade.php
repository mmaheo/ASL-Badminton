@extends('layout')

@section('title')
    Ajouter des cordages
@stop

@section('content')

    <div class="row">
        <div class="col-md-12">
            <h1 class="text-center">
                @if($rest > 0)
                    Il reste <span class="text-navy">{{ $rest }}</span> cordage{{ $rest > 1 ? 's' : '' }}
                @else
                    <span class="text-danger">
                        Il n'y a plus de cordage
                    </span>
                @endif
                <hr>
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h1 class="text-center">Ajouter une bobine</h1>
                </div>
                <div class="ibox-content">
                    {!! Form::open(['route' => 'rope.adding', 'class' => 'form-horizontal']) !!}

                    <p class="text-right"><i class="text-navy">* Champs obligatoires</i></p>

                    <div class="form-group">
                        <div class="col-md-3">
                            {!! Form::label('rest', 'Nb cordage', ['class' => 'control-label']) !!}
                            <i class="text-navy">*</i>
                        </div>

                        <div class="col-md-9">
                            {!! Form::number('rest', old('rest'), ['class' => 'form-control', 'required']) !!}
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-3">
                            {!! Form::label('comment', 'Commentaire', ['class' => 'control-label']) !!}
                        </div>

                        <div class="col-md-9">
                            {!! Form::text('comment', old('comment'), ['class' => 'form-control']) !!}
                        </div>
                    </div>

                    <div class="form-group text-center">
                        {!! Form::submit('Ajouter', ['class' => 'btn btn-primary']) !!}
                    </div>

                    {!! Form::close() !!}
                </div>
            </div>

        </div>

        <div class="col-md-8">
            @if(count($lastRopes) > 0)
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h1 class="text-center">Consommation sur 6 mois (> à 1 cordage)</h1>
                    </div>
                    <div class="ibox-content">
                        <table class="table table-striped table-hover display" id="lastropesList">
                            <thead>
                            <tr>
                                <th class="text-center">Nom</th>
                                <th class="text-center">Consommation</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($lastRopes as $rope)
                                @if ($rope->rest > 1)
                                <tr class="text-center">
                                    <td>
                                        {{ $rope->forname }} {{ $rope->name }}
                                    </td>
                                    <td>
                                        {{ $rope->rest }}
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <h2 class="text-danger text-center">Personne n'a pris de cordage</h2>
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @if(count($ropes) > 0)
                <div class="ibox float-e-margins">
                    <div class="ibox-content">
                        <table class="table table-striped table-hover display" id="ropesList">
                            <thead>
                            <tr>
                                <th class="text-center">Nom</th>
                                <th class="text-center">Date</th>
                                <th class="text-center">Tension</th>
                                <th class="text-center">Comment</th>
                                <th class="text-center">Action</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($ropes as $rope)
                                <tr class="text-center">
                                    <td>
                                        {{ $rope->forname }} {{ $rope->name }}
                                    </td>
                                    <td>
                                        <span class="text-navy">
                                            {{ $rope->created_at }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $rope->tension }} kg
                                    </td>
                                    <td>
                                        {{ $rope->comment }}
                                    </td>
                                    <td>
                                        @if($rope->hasFill(true))
                                            <span class="text-navy">
                                                Approvisionnement de {{ $rope->rest }}
                                            </span>
                                        @else
                                            <span class="text-danger">
                                                Retrait de {{ $rope->rest }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <h2 class="text-danger text-center">Personne n'a pris de cordage</h2>
            @endif
        </div>
    </div>
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#ropesList').DataTable( {
                "pageLength": 100,
                "order": [[ 1, "desc" ]],
                language: {
                    processing:     "Traitement en cours...",
                    search:         "Rechercher&nbsp;:",
                    lengthMenu:    "Afficher _MENU_ &eacute;l&eacute;ments",
                    info:           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
                    infoEmpty:      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
                    infoFiltered:   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                    infoPostFix:    "",
                    loadingRecords: "Chargement en cours...",
                    zeroRecords:    "Aucun &eacute;l&eacute;ment &agrave; afficher",
                    emptyTable:     "Aucune donnée disponible dans le tableau",
                    paginate: {
                        first:      "Premier",
                        previous:   "Pr&eacute;c&eacute;dent",
                        next:       "Suivant",
                        last:       "Dernier"
                    },
                    aria: {
                        sortAscending:  ": activer pour trier la colonne par ordre croissant",
                        sortDescending: ": activer pour trier la colonne par ordre décroissant"
                    }
                }
            } );
        } );
    </script>
@stop