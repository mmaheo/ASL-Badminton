<?php

namespace App\Http\Controllers;

use App\Helpers;
use App\Http\Requests\PlayerListRequest;
use App\Http\Requests\PlayerStoreRequest;
use App\Http\Requests\PlayerUpdateRequest;
use App\Http\Utilities\SendMail;
use App\Player;
use App\Season;
use App\Setting;
use App\Team;
use App\User;

/**
 * Manage players
 *
 * Class PlayerController
 * @package App\Http\Controllers
 */
class PlayerController extends Controller
{

    /**
     * PlayerController constructor.
     */
    public function __construct()
    {
        parent::__constructor();
    }

    /**
     * @param $router
     */
    public static function routes($router)
    {
        $router->pattern('player_id', '[0-9]+');

        //player list
        $router->get('/index', [
            'middleware' => ['admin', 'notCE'],
            'uses'       => 'PlayerController@index',
            'as'         => 'player.index',
        ]);

        //player list with season
        $router->post('/index', [
            'middleware' => ['admin', 'notCE'],
            'uses'       => 'PlayerController@index',
            'as'         => 'player.index',
        ]);

        //player delete
        $router->get('/delete/{player_id}', [
            'middleware' => ['admin', 'notCE'],
            'uses'       => 'PlayerController@delete',
            'as'         => 'player.delete',
        ]);

        //player edit
        $router->get('/edit/{player_id}', [
            'middleware' => ['playerOwner', 'settingExists', 'notCE'],
            'uses'       => 'PlayerController@edit',
            'as'         => 'player.edit',
        ]);

        //player update
        $router->post('/edit/{player_id}', [
            'middleware' => ['playerOwner', 'buyTshirtClose', 'settingExists', 'notCE'],
            'uses'       => 'PlayerController@update',
            'as'         => 'player.update',
        ]);

        //player create
        $router->get('/create', [
            'middleware' => ['enrollOpen', 'settingExists', 'notCE'],
            'uses'       => 'PlayerController@create',
            'as'         => 'player.create',
        ]);

        //player store
        $router->post('/create', [
            'middleware' => ['enrollOpen', 'buyTshirtClose', 'settingExists', 'notCE'],
            'uses'       => 'PlayerController@store',
            'as'         => 'player.store',
        ]);

        //player change ce_state to contribution_paid
        $router->get('/ce_state/contribution_paid/{player_id}', [
            'middleware' => ['notUser'],
            'uses'       => 'PlayerController@changeCeStateToContributionPaid',
            'as'         => 'player.ce_stateTocontribution_paid',
        ]);

        //player change gbc_state to valid
        $router->get('/gbc_state/valid/{player_id}', [
            'middleware' => ['admin', 'notCE'],
            'uses'       => 'PlayerController@changeGbcStateToValid',
            'as'         => 'player.gbc_stateTocontribution_paid',
        ]);
    }

    /**
     * View all players on current season or specific season
     *
     * @param PlayerListRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(PlayerListRequest $request)
    {
        $season = null;

        if ($request->exists('season_id')) {
            $season = Season::findOrFail($request->season_id);
        } else {
            $season = Season::active()->first();
            if ($season == null) {
                // situation anormale, il doit toujours exiter une saison active
                abort(404);
            }
        }

        $players = Player::select('players.*')
            ->with('user')
            ->join('users', 'users.id', '=', 'players.user_id')
            ->orderByForname()
            ->withSeason($season->id)
            ->get();

        $seasons = Season::orderBy('created_at', 'desc')->lists('name', 'id');

        return view('player.index', compact('players', 'seasons', 'season'));
    }

    /**
     * Form to create one player
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {

        $alreadySubscribe = false;

        //on s'inscrit dans la saison active
        $activeSeason = Season::active()->first();

        //si il y a pas encore de saison
        if ($activeSeason === null) {
            return redirect()->route('home.index')->with('error', "Les inscriptions ne sont pas ouvertes !");
        }

        //compte le nombre d'inscription dans lesquels on est inscrit
        $numberOfPlayerForUserInSelectedSeason = Player::select('players.id')
            ->withSeason($activeSeason->id)
            ->where('user_id', $this->user->id)
            ->count();

        //si on a plus de 1 inscription
        if ($numberOfPlayerForUserInSelectedSeason >= 1) {
            $alreadySubscribe = true;
        }

        $player = new Player();
        $setting = Helpers::getInstance()->setting();

        $gender = $this->user->gender;

        $listPartnerAvailable['double'] = Player::listPartnerAvailable('double', $gender, $this->user->id);
        $listPartnerAvailable['mixte'] = Player::listPartnerAvailable('mixte', $gender, $this->user->id);

        return view('player.create', compact('player', 'setting', 'listPartnerAvailable', 'alreadySubscribe'));
    }

    /**
     * Store the player created
     *
     * @param PlayerStoreRequest $request
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(PlayerStoreRequest $request)
    {
        //on s'inscrit dans la saison active
        $activeSeason = Season::active()->first();

        //si il y a pas encore de saison
        if ($activeSeason === null) {
            return redirect()->route('home.index')->with('error', "Les inscriptions ne sont pas ouvertes !");
        }

        //compte le nombre d'inscription dans lesquels on est inscrit
        $numberOfPlayerForUserInSelectedSeason = Player::select('players.id')
            ->withSeason($activeSeason->id)
            ->where('user_id', $this->user->id)
            ->count();

        //si on a plus de 1 inscription
        if ($numberOfPlayerForUserInSelectedSeason >= 1) {
            return redirect()->back()->with('error',
                "Vous êtes est déjà inscrit !")->withInput($request->input());
        }

        $player = Player::create([
            'formula'       => $request->formula,
            // tshirt inclus dans les formules competition et corpo, pas dans les autres
            't_shirt'       => $request->formula === 'leisure' || $request->formula === 'tournament' || $request->formula === 'fun' || $request->formula === 'performance' ? $request->t_shirt : true,
            'simple'        => $request->formula !== 'leisure' ? $request->simple : false,
            'double'        => $request->formula !== 'leisure' ? $request->double : false,
            'mixte'         => $request->formula !== 'leisure' ? $request->mixte : false,
            'corpo_man'     => ($request->formula === 'corpo' || $request->formula === 'competition') && $this->user->hasGender('man') ? $request->corpo_man : false,
            'corpo_woman'   => ($request->formula === 'corpo' || $request->formula === 'competition') && $this->user->hasGender('woman') ? $request->corpo_woman : false,
            'corpo_mixte'   => $request->formula === 'corpo' || $request->formula === 'competition' ? $request->corpo_mixte : false,
            'user_id'       => $this->user->id,
            'ce_state'      => $this->user->hasRole('admin') ? $request->ce_state : 'contribution_payable',
            'gbc_state'     => $this->onPlayerCreateChoseGbc_state($request),
            'season_id'     => $activeSeason->id,
            'search_double' => $request->double && $request->double_partner === 'search' ? true : false,
            'search_mixte'  => $request->mixte && $request->mixte_partner === 'search' ? true : false,
        ]);

        $this->createSimpleTeams($player, $activeSeason);
        $this->createDoubleOrMixteTeams($player, $activeSeason, $request->double_partner, 'double');
        $this->createDoubleOrMixteTeams($player, $activeSeason, $request->mixte_partner, 'mixte');

        if ($player->hasFormula('competition')) {
            SendMail::send($this->user, 'subscribeCompetitionFormula', $this->user->attributesToArray(), 'Inscription
             formule compétition AS Lectra Badminton');
        } elseif ($player->hasFormula('corpo')) {
            SendMail::send($this->user, 'subscribeCorpoFormula', $this->user->attributesToArray(), 'Inscription
             formule corpo AS Lectra Badminton');
        } else {
            SendMail::send($this->user, 'subscribeFormula', $this->user->attributesToArray(), 'Inscription à la section Badminton');
        }


        $admin = User::where('email', 'c.maheo@lectra.com')->first();

        if ($admin != null) {
            $data['newValues'] = $player->attributesToArray();
            $data['userName'] = $player->user->forname . " " . $player->user->name;
            $data['adminUserName'] = $admin->forname . " " . $admin->name;
            SendMail::send($admin, 'newSubscribe', $data, 'Nouvelle inscription AS Lectra Badminton');
        }

        return redirect()->route('home.index')->with('success', "Vous êtes bien inscrit !");
    }

    /**
     * Form to edit player
     *
     * @param $player_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($player_id)
    {
        $player = Player::findOrFail($player_id);
        $setting = Helpers::getInstance()->setting();

        if ($player != null) {
            $gender = $player->user->gender;

            $listPartnerAvailable['double'] = Player::listPartnerAvailable('double', $gender, $this->user->id, $player_id);

            $listPartnerAvailable['mixte'] = Player::listPartnerAvailable('mixte', $gender, $this->user->id, $player_id);

            return view('player.edit', compact('player', 'setting', 'listPartnerAvailable'));
        }

        abort(404);
        return null;
    }

    /**
     * Update the player updated
     *
     * @param PlayerUpdateRequest $request
     * @param $player_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(PlayerUpdateRequest $request, $player_id)
    {
        $player = Player::findOrFail($player_id);
        $activeSeason = Season::active()->first();

        //si on est admin on peut mettre à jour les 2 champs
        if ($this->user->hasRole('admin')) {
            $player->update([
                'ce_state'  => $request->ce_state,
                'gbc_state' => $request->gbc_state,
            ]);
        }

        $data['oldValues'] = $player->attributesToArray();
        $data['oldValues']['player-double'] = "";
        $data['oldValues']['player-mixte'] = "";

        $player->update([
            'formula'       => $request->formula,
            // tshirt inclus dans les formules competition et corpo, pas dans les autres
            't_shirt'       => $request->formula === 'leisure' || $request->formula === 'tournament' || $request->formula === 'fun' || $request->formula === 'performance' ? $request->t_shirt : true,
            'simple'        => $request->formula !== 'leisure' ? $request->simple : false,
            'double'        => $request->formula !== 'leisure' ? $request->double : false,
            'mixte'         => $request->formula !== 'leisure' ? $request->mixte : false,
            'corpo_man'     => ($request->formula === 'corpo' || $request->formula === 'competition') && $player->user->hasGender('man') ? $request->corpo_man : false,
            'corpo_woman'   => ($request->formula === 'corpo' || $request->formula === 'competition') && $player->user->hasGender('woman') ? $request->corpo_woman : false,
            'corpo_mixte'   => $request->formula === 'corpo' || $request->formula === 'competition' ? $request->corpo_mixte : false,
            'search_double' => $request->double && $request->double_partner === 'search' ? true : false,
            'search_mixte'  => $request->mixte && $request->mixte_partner === 'search' ? true : false,
        ]);

        $this->createSimpleTeams($player, $activeSeason);
        $this->createDoubleOrMixteTeams($player, $activeSeason, $request->double_partner, 'double');
        $this->createDoubleOrMixteTeams($player, $activeSeason, $request->mixte_partner, 'mixte');


        if ($player->hasFormula('competition') && $data['oldValues']['formula'] != 'competition') {
            SendMail::send($this->user, 'subscribeCompetitionFormula', $this->user->attributesToArray(), 'Inscription
             formule compétition AS Lectra Badminton');
        } elseif ($player->hasFormula('corpo') && $data['oldValues']['formula'] != 'corpo') {
            SendMail::send($this->user, 'subscribeCorpoFormula', $this->user->attributesToArray(), 'Inscription
             formule corpo AS Lectra Badminton');
        } elseif ($player->formula != $data['oldValues']['formula']){
            SendMail::send($this->user, 'subscribeFormula', $this->user->attributesToArray(), 'Inscription à la section Badminton');
        }

        $admin = User::where('email', 'c.maheo@lectra.com')->first();
        $data['newValues'] = $player->attributesToArray();
        $data['newValues']['player-double'] = "";
        $data['newValues']['player-mixte'] = "";
        if ($request->double_partner != 'search') 
            {
                $doublePartner =  Player::findOrFail($request->double_partner);
                $data['newValues']['player-double'] = Helpers::getInstance()->getTeamName($doublePartner->user->forname, $doublePartner->user->name);
            }

        if ($request->mixte_partner != 'search') 
            {
                $mixtePartner =  Player::findOrFail($request->mixte_partner);
                $data['newValues']['player-mixte'] = Helpers::getInstance()->getTeamName($mixtePartner->user->forname, $mixtePartner->user->name);
            }

        $data['userName'] = $player->user->forname . " " . $player->user->name;
        $data['adminUserName'] = $admin->forname . " " . $admin->name;

        if (Helpers::nbDifference($data,'oldValues', 'newValues') > 1) SendMail::send($admin, 'updateSubscribe', $data, 'Modification d\'une inscription AS Lectra Badminton');

        return redirect()->route('home.index')->with('success', "Les modifications sont bien prises en compte !");
    }

    /**
     * Delete the player
     *
     * @param $player_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($player_id)
    {
        $player = Player::findOrFail($player_id);
        $player->delete();

        return redirect()->route('player.index')->with('success', "Le joueur $player a été supprimé !");
    }

    /**
     * Helpfull to determined the gbc state with his formula
     *
     * @param $request
     * @return string
     */
    private function onPlayerCreateChoseGbc_state($request)
    {
        //on est l'admin, on peut choisir le champ
        if ($this->user->hasRole('admin')) {
            return $request->gbc_state;
        } else {
            //si on a choisit la formule loisir, fun, ou performance, on ne peut pas etre a GBC
            if ($request->formula === 'leisure' || $request->formula === 'tournament' || $request->formula === 'fun' || $request->formula === 'performance') {
                return 'non_applicable';
            } //si on esy en corpo ou competition on doit remettre notre dossier
            elseif ($request->formula === 'corpo' || $request->formula === 'competition') {
                return 'entry_must';
            }
        }

        return 'non_applicable';
    }

    /**
     * Used by CE or admin to change the ce state to paid
     *
     * @param $player_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeCeStateToContributionPaid($player_id)
    {
        $player = Player::findOrFail($player_id);

        if ($player->hasCeState('contribution_payable')) {
            $player->update([
                'ce_state' => 'contribution_paid',
            ]);

            return redirect()->back()->with('success', "Le joueur $player a payé sa cotisation !");
        }

        return redirect()->back()->with('error', "Le joueur $player a déjà payé sa cotisation !");
    }

    /**
     * Used by admin to change the gbc state to valid
     * @param $player_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeGbcStateToValid($player_id)
    {
        $player = Player::findOrFail($player_id);

        if ($player->hasGbcState('entry_must')) {
            $player->update([
                'gbc_state' => 'valid',
            ]);

            return redirect()->back()->with('success', "Le joueur $player a son dossier GBC valide !");
        }

        return redirect()->back()->with('error',
            "Le joueur $player est non applicable ou il a déjà validé son dossier!");
    }

    /**
     * Helpfull to create simple teams for one player
     * This also update teams when he updated his profile
     *
     * @param $player
     * @param $activeSeason
     */
    private function createSimpleTeams($player, $activeSeason)
    {
        //Toutes les équipes de simple, active ou pas. On met first parce qu'il ne peut y en avoir qu'une seule
        $allMySimpleTeams = Team::allMySimpleTeams($this->user->hasGender('man') ? 'man' : 'woman',
            $player->id, $activeSeason->id)
            ->first();

        //si j'ai une équipe de simple, on met à jour le statut enable
        if ($allMySimpleTeams !== null) {
            $allMySimpleTeams->update([
                'enable' => $player->simple,
            ]);
        }

        if ($player->hasSimple(true) && $allMySimpleTeams === null) {
            //si on a pas encore d'équipe, il faut en créer une
            Team::create([
                'player_one'   => $player->id,
                'player_two'   => null,
                'season_id'    => $activeSeason->id,
                'simple_man'   => $this->user->hasGender('man') ? true : false,
                'simple_woman' => $this->user->hasGender('man') ? false : true,
                'double_man'   => false,
                'double_woman' => false,
                'mixte'        => false,
                'enable'       => true,
            ]);
        }
    }

    /**
     * Helpfull to create mixte and double teams of one player
     * This also update teams when he updated his profile
     *
     * @param $player
     * @param $activeSeason
     * @param $partner_id
     * @param $type
     */
    private function createDoubleOrMixteTeams($player, $activeSeason, $partner_id, $type)
    {
        /*
         * Dans tous les cas on cherche si il y a une equipe enable avec ce joueur.
         *      Si il y a en une on la passe à disable et le deuxième joueur de cette l'équipe passe en recherche
         *
         * Si partenaire = 'search', l equipe n'est pa complete donc on ne cree pas l'équipe => on ne fait rien
         *
         *  Si partenaire n'est pas 'search', l'équipe est complète. Il faut l'activer ou la créer.
         *      On s'assure que le champ 'search' est à faux pour le partenaire
         *      On cherche l'équipe avec le joueur et son partenaire.
         *          Si l'équipe existe on la passe à enable
         *          Si l'équipe n'existe pas on crée l'équipe. Note dans une équipe le joueur 1 est le premier par ordre alphabétique du prénom. En mixte le premier joueur et toujours la femme.
         */

        $gender = $this->user->hasGender('man') ? 'man' : 'woman';

        //toutes mes équipe de double, active
        $allMyDoubleOrMixteTeams = Team::allMyDoubleOrMixteActiveTeams($type, $gender, $player->id, $activeSeason->id)
            ->first();

        //on désactive toutes les équipes
        if ($allMyDoubleOrMixteTeams !== null) {
            $allMyDoubleOrMixteTeams->update([
                'enable' => false,
            ]);

            $partner = Player::findOrFail($allMyDoubleOrMixteTeams->player_one === $player->id ? $allMyDoubleOrMixteTeams->player_two : $allMyDoubleOrMixteTeams->player_one);

            $partner->update([
                'search_double' => $type === 'double' ? true : $partner->search_double,
                'search_mixte'  => $type === 'mixte' ? true : $partner->search_mixte,
            ]);
        }

        $type === 'double' ? $mixteOrDouble = $player->double : $mixteOrDouble = $player->mixte;

        if ($mixteOrDouble && $partner_id !== 'search') {
            $partner = Player::findOrFail($partner_id);

            $partner->update([
                'search_double' => $type === 'double' ? false : $partner->search_double,
                'search_mixte'  => $type === 'mixte' ? false : $partner->search_mixte,
            ]);

            $myTeam = Team::myDoubleOrMixteTeamsWithPartner($type, $gender, $player->id, $partner_id,
                $activeSeason->id)->first();
            if ($myTeam !== null) {
                $myTeam->update([
                    'enable' => true,
                ]);
            } else {
                $partner = Player::findOrFail($partner_id);

                $userOne = $player->user->__toString();
                $userTwo = $partner->user->__toString();

                if ($type === 'double') {
                    Team::create([
                        'player_one'   => $userOne < $userTwo ? $player->id : $partner_id,
                        'player_two'   => $userOne < $userTwo ? $partner_id : $player->id,
                        'season_id'    => $activeSeason->id,
                        'simple_man'   => false,
                        'simple_woman' => false,
                        'double_man'   => $gender === 'man' ? true : false,
                        'double_woman' => $gender === 'woman' ? true : false,
                        'mixte'        => false,
                        'enable'       => true,
                    ]);
                } else {
                    Team::create([
                        'player_one'   => $gender === 'woman' ? $player->id : $partner_id,
                        'player_two'   => $gender === 'woman' ? $partner_id : $player->id,
                        'season_id'    => $activeSeason->id,
                        'simple_man'   => false,
                        'simple_woman' => false,
                        'double_man'   => false,
                        'double_woman' => false,
                        'mixte'        => true,
                        'enable'       => true,
                    ]);
                }
            }
        }
    }
}
