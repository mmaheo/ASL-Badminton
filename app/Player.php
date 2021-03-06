<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $table = 'players';

    protected $fillable = [
        'formula',
        'ce_state',
        'gbc_state',
        'simple',
        'double',
        'mixte',
        'search_double',
        'search_mixte',
        'corpo_man',
        'corpo_woman',
        'corpo_mixte',
        't_shirt',
        'user_id',
        'season_id',
        'certificate',
        'corpo_team',
        'corpo_team_mixte',
        'corpo_comment',
        'polo_delivered',
    ];

    protected $casts = [
        'simple' => 'boolean',
        'double' => 'boolean',
        'mixte' => 'boolean',
        'search_double' => 'boolean',
        'search_mixte' => 'boolean',
        'corpo_man' => 'boolean',
        'corpo_woman' => 'boolean',
        'corpo_mixte' => 'boolean',
        't_shirt' => 'boolean',
    ];

    protected $dates = ['created_at', 'updated_at'];

    public function __toString()
    {
        return $this->user->__toString();
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function season()
    {
        return $this->belongsTo('App\Season');
    }

    public function teamsPlayerOne()
    {
        return $this->hasMany('App\Team', 'player_one');
    }

    public function teamsPlayerTwo()
    {
        return $this->hasMany('App\Team', 'player_two');
    }

    /******************/
    /*      Has       */
    /******************/

    public function hasSimple($simple)
    {
        return $this->simple === $simple;
    }

    public function hasDouble($double)
    {
        return $this->double === $double;
    }

    public function hasMixte($mixte)
    {
        return $this->mixte === $mixte;
    }

    public function hasCeState($ce_state)
    {
        return $this->ce_state === $ce_state;
    }

    public function hasGbcState($gbc_state)
    {
        return $this->gbc_state === $gbc_state;
    }

    public function hasCorpoMan($corpo_man)
    {
        return $this->corpo_man === $corpo_man;
    }

    public function hasCorpoWoman($corpo_woman)
    {
        return $this->corpo_woman === $corpo_woman;
    }

    public function hasCorpoMixte($corpo_mixte)
    {
        return $this->corpo_mixte === $corpo_mixte;
    }

    public function hasTShirt($t_shirt)
    {
        return $this->t_shirt === $t_shirt;
    }

    public function hasFormula($formula)
    {
        return $this->formula === $formula;
    }

    public function hasCertificate($certificate)
    {
        return $this->certificate === $certificate;
    }

    /******************/
    /*      Scope     */
    /******************/

    public function scopeWithSeason($query, $season_id)
    {
        $query->where('players.season_id', $season_id);
    }

    public function scopeWithUser($query, $user_id)
    {
        $query->where('players.user_id', $user_id);
    }

    public function scopeOrderByForname($query)
    {
        $query->orderBy('users.forname', 'asc');
    }

    public function scopePlayerResearchByType($query, $type)
    {
        $query->where('players.search_' . $type, true);
    }

    public function scopeMyPlayerInActiveSeason($query, $user_id)
    {
        $query->join('seasons', 'seasons.id', '=', 'players.season_id')
            ->where('user_id', $user_id)
            ->where('seasons.active', true);
    }

    /******************/
    /*    Functions   */
    /******************/

    public function getTotalPrice($setting)
    {
        $totalPrice = $this->totalPrice($setting);

        return $totalPrice['formula'] + $totalPrice['t_shirt'];
    }

    public function totalPrice(Setting $setting)
    {
        $price['t_shirt'] = 0;
        $price['formula'] = 0;

        //calculate t-shirt but not corpo and competition player
        if ($this->hasTShirt(true) && !$this->hasFormula('corpo') && !$this->hasFormula('competition')) {
            $price['t_shirt'] += $setting->t_shirt_price;
        }

        //calculate leisure formula
        if ($this->hasFormula('leisure') || $this->hasFormula('tournament')) {
            if ($this->user->hasLectraRelation('external') || $this->user->hasLectraRelation('subcontractor')) { 
                $price['formula'] += $setting->leisure_external_price;
            } elseif (!$this->user->hasLectraRelation('partnership')) {
                $price['formula'] +=$setting->leisure_price;
            }
        } //calculate fun formula
        elseif ($this->hasFormula('fun')) {
            if ($this->user->hasLectraRelation('external') || $this->user->hasLectraRelation('subcontractor')) { 
                $price['formula'] += $setting->fun_external_price;
            } elseif (!$this->user->hasLectraRelation('partnership')) {
                $price['formula'] +=$setting->fun_price;
            }
        } //calculate performance formula
        elseif ($this->hasFormula('performance')) {
            if ($this->user->hasLectraRelation('external') || $this->user->hasLectraRelation('subcontractor')) { 
                $price['formula'] += $setting->performance_external_price;
            } elseif (!$this->user->hasLectraRelation('partnership')) {
                $price['formula'] +=$setting->performance_price;
            }
        } //calculate corpo formula
        elseif ($this->hasFormula('corpo')) {
            if ($this->user->hasLectraRelation('external') || $this->user->hasLectraRelation('subcontractor')) { 
                $price['formula'] += $setting->corpo_external_price;
            } elseif (!$this->user->hasLectraRelation('partnership')) {
                $price['formula'] +=$setting->corpo_price;
            }
        } //calculate competition formula
        elseif ($this->hasFormula('competition')) {
            if ($this->user->hasLectraRelation('external') || $this->user->hasLectraRelation('subcontractor')) { 
                $price['formula'] += $setting->competition_external_price;
            } elseif (!$this->user->hasLectraRelation('partnership')) {
                $price['formula'] +=$setting->competition_price;
            }
        }

        return $price;
    }

    public static function listPartnerAvailable($type, $gender, $user_id, $player_id = null)
    {
        $activeSeason = Season::active()->first();

        if ($player_id !== null) {

            $myTeam = Team::allMyDoubleOrMixteActiveTeams($type, $gender, $player_id, $activeSeason->id)
                ->first();

            if ($myTeam !== null) {
                $partnerId = $myTeam->player_one == $player_id ? $myTeam->player_two : $myTeam->player_one;

                $partner = Player::findOrFail($partnerId);

                $listPartnerAvailable[$partnerId] = $partner->user->__toString();
            }
        }

        $playersResarch = Player::select('players.id', 'users.name', 'users.forname')
            ->with('user')
            ->join('users', 'users.id', '=', 'players.user_id')
            ->where('users.id', '<>', $user_id)
            ->where('users.gender', '=', $type === 'mixte' ? $gender === 'man' ? 'woman' : 'man' : $gender)
            ->where('players.season_id', $activeSeason->id)
            ->playerResearchByType($type)
            ->orderByForname()
            ->get();

        $listPartnerAvailable['search'] = 'En recherche';

        foreach ($playersResarch as $player) {
            $listPartnerAvailable[$player->id] = $player->forname . ' ' . $player->name;
        }

        return $listPartnerAvailable;
    }

}
