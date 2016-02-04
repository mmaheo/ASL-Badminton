<?php

namespace App\Http\Controllers;

use App\ChampionshipPool;
use App\Helpers;
use App\Score;
use App\Team;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ChampionshipResultController extends Controller
{

    public function __construct()
    {
        parent::__constructor();
    }

    public static function routes($router)
    {
        //patterns
        $router->pattern('pool_id', '[0-9]+');

        //admin reservation create day
        $router->get('show/{pool_id}', [
            'uses' => 'ChampionshipResultController@show',
            'as'   => 'championshipResult.show',
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param $pool_id
     * @return \Illuminate\Http\Response
     */
    public function show($pool_id)
    {
        $pool = ChampionshipPool::findOrFail($pool_id);

        $type = $pool->type;

        if($pool->type == 'simple' || $pool->type == 'simple_man' || $pool->type == 'simple_woman')
        {
            $type = 'simple';
        }
        elseif($pool->type == 'double' || $pool->type == 'double_man' || $pool->type == 'double_woman')
        {
            $type = 'double';
        }

        $results = $this->getResults($pool_id, $type);

        return view('championshipResult.show', compact('pool', 'results', 'type'));
    }

    private function getResults($pool_id, $type)
    {

        $scores = [];

        if($type == 'simple')
        {
            $scores = Score::select(
                'userFirstTeam.name as userFirstTeamName', 'userFirstTeam.forname as userFirstTeamForname',
                'userFirstTeam.id as userFirstTeamId', 'userSecondTeam.name as userSecondTeamName',
                'userSecondTeam.forname as userSecondTeamForname', 'userSecondTeam.id as userSecondTeamId',
                'scores.first_set_first_team', 'scores.first_set_second_team', 'scores.second_set_first_team',
                'scores.second_set_second_team', 'scores.third_set_first_team', 'scores.third_set_second_team',
                'scores.my_wo', 'scores.his_wo', 'scores.unplayed', 'scores.id as scoreId')
                ->join('teams as firstTeam', 'firstTeam.id', '=', 'scores.first_team_id')
                ->join('players as playerFirstTeam', 'playerFirstTeam.id', '=', 'firstTeam.player_one')
                ->join('users as userFirstTeam', 'userFirstTeam.id', '=', 'playerFirstTeam.user_id')
                ->join('teams as secondTeam', 'secondTeam.id', '=', 'scores.second_team_id')
                ->join('players as playerSecondTeam', 'playerSecondTeam.id', '=', 'secondTeam.player_one')
                ->join('users as userSecondTeam', 'userSecondTeam.id', '=', 'playerSecondTeam.user_id')
                ->join('championship_rankings as rankingFirstTeam', 'rankingFirstTeam.team_id', '=', 'firstTeam.id')
                ->join('championship_rankings as rankingSecondTeam', 'rankingSecondTeam.team_id', '=', 'secondTeam.id')
                ->where('rankingFirstTeam.championship_pool_id', $pool_id)
                ->where('rankingSecondTeam.championship_pool_id', $pool_id)
                ->get();
        }
        else
        {
            $scores = Score::select(
                'userOneFirstTeam.name as userOneFirstTeamName', 'userOneFirstTeam.forname as userOneFirstTeamForname',
                'userOneFirstTeam.id as userOneFirstTeamId', 'userTwoFirstTeam.name as userTwoFirstTeamName',
                'userTwoFirstTeam.forname as userTwoFirstTeamForname', 'userTwoFirstTeam.id as userTwoFirstTeamId',
                'userOneSecondTeam.name as userOneSecondTeamName', 'userOneSecondTeam.forname as userOneSecondTeamForname',
                'userOneSecondTeam.id as userOneSecondTeamId', 'userTwoSecondTeam.name as userTwoSecondTeamName',
                'userTwoSecondTeam.forname as userTwoSecondTeamForname', 'userTwoSecondTeam.id as userTwoSecondTeamId',
                'scores.first_set_first_team', 'scores.first_set_second_team', 'scores.second_set_first_team',
                'scores.second_set_second_team', 'scores.third_set_first_team', 'scores.third_set_second_team',
                'scores.my_wo', 'scores.his_wo', 'scores.unplayed', 'scores.id as scoreId')
                ->join('teams as firstTeam', 'firstTeam.id', '=', 'scores.first_team_id')
                ->join('players as playerOneFirstTeam', 'playerOneFirstTeam.id', '=', 'firstTeam.player_one')
                ->join('users as userOneFirstTeam', 'userOneFirstTeam.id', '=', 'playerOneFirstTeam.user_id')
                ->join('players as playerTwoFirstTeam', 'playerTwoFirstTeam.id', '=', 'firstTeam.player_two')
                ->join('users as userTwoFirstTeam', 'userTwoFirstTeam.id', '=', 'playerTwoFirstTeam.user_id')
                ->join('teams as secondTeam', 'secondTeam.id', '=', 'scores.second_team_id')
                ->join('players as playerOneSecondTeam', 'playerOneSecondTeam.id', '=', 'secondTeam.player_one')
                ->join('users as userOneSecondTeam', 'userOneSecondTeam.id', '=', 'playerOneSecondTeam.user_id')
                ->join('players as playerTwoSecondTeam', 'playerTwoSecondTeam.id', '=', 'secondTeam.player_two')
                ->join('users as userTwoSecondTeam', 'userTwoSecondTeam.id', '=', 'playerTwoSecondTeam.user_id')
                ->join('championship_rankings as rankingFirstTeam', 'rankingFirstTeam.team_id', '=', 'firstTeam.id')
                ->join('championship_rankings as rankingSecondTeam', 'rankingSecondTeam.team_id', '=', 'secondTeam.id')
                ->where('rankingFirstTeam.championship_pool_id', $pool_id)
                ->where('rankingSecondTeam.championship_pool_id', $pool_id)
                ->get();
        }

        $results = [];

        foreach ($scores as $index => $score)
        {
            if($type == 'simple')
            {
                $results[$index]['firstTeam'] = Helpers::getInstance()->getTeamName($score->userFirstTeamForname,
                    $score->userFirstTeamName);
                $results[$index]['secondTeam'] = Helpers::getInstance()->getTeamName($score->userSecondTeamForname,
                    $score->userSecondTeamName);
                $results[$index]['owner'] = $this->user->id == $score->userFirstTeamId || $this->user->id ==
                    $score->userSecondTeamId;
            }
            else
            {
                $results[$index]['firstTeam'] = Helpers::getInstance()->getTeamName($score->userOneFirstTeamForname,
                    $score->userOneFirstTeamName, $score->userTwoFirstTeamForname, $score->userTwoFirstTeamName);

                $results[$index]['secondTeam'] = Helpers::getInstance()->getTeamName($score->userOneSecondTeamForname,
                    $score->userOneSecondTeamName, $score->userTwoSecondTeamForname, $score->userTwoSecondTeamName);

                $results[$index]['owner'] = $this->user->id == $score->userOneFirstTeamId || $this->user->id ==
                    $score->userTwoFirstTeamId || $this->user->id == $score->userOneSecondTeamId || $this->user->id ==
                    $score->userTwoSecondTeamId;
            }

            $results[$index]['first_set_first_team'] = $score->first_set_first_team;
            $results[$index]['first_set_second_team'] = $score->first_set_second_team;
            $results[$index]['second_set_first_team'] = $score->second_set_first_team;
            $results[$index]['second_set_second_team'] = $score->second_set_second_team;
            $results[$index]['third_set_first_team'] = $score->third_set_first_team;
            $results[$index]['third_set_second_team'] = $score->third_set_second_team;
            $results[$index]['my_wo'] = $score->my_wo;
            $results[$index]['his_wo'] = $score->his_wo;
            $results[$index]['unplayed'] = $score->unplayed;
            $results[$index]['scoreId'] = $score->scoreId;
        }

        return $results;
    }
}