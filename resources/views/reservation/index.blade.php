@extends('layout')

@section('title')
    Réservations
@stop

@section('content')

    <div class="row">
        <div class="col-md-12">
            @if(count($timeSlots) > 0 || count($courts) > 0)
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h1 class="text-center">
                            Réserver un court
                        </h1>
                    </div>
                    <div class="ibox-content">

                        <div class="row">
                            <div class="col-md-6 text-center text-navy">
                                <h2>Courts de simple libres</h2>
                                {{ $courtSimpleAvailable }} jusqu'au 20 mars 2016
                            </div>
                            <div class="col-md-6 text-center text-info">
                                <h2>Courts de double libres</h2>
                                {{ $courtDoubleAvailable }} jusqu'au 20 mars 2016
                            </div>
                        </div>
                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped reservation">
                                <thead>
                                <tr>
                                    <th rowspan="{{ count($timeSlots) }}" class="text-center">Jour</th>
                                    <th class="text-center">Crénaux</th>
                                    @foreach($courts as $court)
                                        <th class="text-center">{{ ucfirst($court->type) }} {{ $court }}</th>
                                    @endforeach
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($allDays as $day)
                                    <tr class="text-center">
                                        <td rowspan="{{ count($timeSlots) }}" style="background: #fbfcfc;" id="{{
                                        $day->format('Y-m-d') }}" class="{{ $day->format('Y-m-d') == \Carbon\Carbon::today()->format('Y-m-d') ? 'today' : '' }}">
                                            {!! $day->format('Y-m-d') == \Carbon\Carbon::today()->format('Y-m-d') ? ucfirst($day->format('l j F Y')) . '<br>Aujourd\'hui' : ucfirst($day->format('l j F Y')) !!}
                                        </td>
                                        <td>
                                            {{ $timeSlots[0] }}
                                        </td>
                                        @foreach($courts as $court)
                                            <td>
                                                {!! $reservations[$day->format('Y-m-d')][$timeSlots[0]->id][$court->id] !!}
                                            </td>
                                        @endforeach
                                    </tr>
                                    @if(count($timeSlots) > 1)
                                        @foreach($timeSlots as $timeSlot)
                                            @if($timeSlot != $timeSlots[0])
                                                <tr class="text-center">
                                                    <td>{{ $timeSlot }}</td>
                                                    @foreach($courts as $court)
                                                        <td>
                                                            {!!  $reservations[$day->format('Y-m-d')][$timeSlot->id][$court->id] !!}
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <h1 class="text-danger text-center">
                    Pas de réservation disponible pour le moment
                </h1>
            @endif
        </div>
    </div>

@stop