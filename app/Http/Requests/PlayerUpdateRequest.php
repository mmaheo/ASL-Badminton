<?php

namespace App\Http\Requests;

use App\Player;

class PlayerUpdateRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $player_id = $this->route()->getParameter('player_id');
        $player = Player::findOrFail($player_id);
        $user_id = $player->user->id;

        $user = $this->user();

        if ($user->hasOwner($user_id) || $user->hasRole('admin'))
        {
            return true;
        }

        abort(401, 'Unauthorized action.');

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'formula'        => 'required|in:leisure,tournament,fun,performance,corpo,competition',
            't_shirt'        => 'required_if:formula,leisure,tournament,fun,performance',
            'simple'         => 'required_if:formula,tournament,fun,performance,corpo,competition|boolean',
            'double'         => 'required_if:formula,tournament,fun,performance,corpo,competition|boolean',
            'mixte'          => 'required_if:formula,tournament,fun,performance,corpo,competition|boolean',
            'corpo_man'      => 'required_if:formula,corpo,competition|boolean',
            'corpo_woman'    => 'required_if:formula,corpo,competition|boolean',
            'corpo_mixte'    => 'required_if:formula,corpo,competition|boolean',
            'double_partner' => 'required_if:double,1',
            'mixte_partner'  => 'required_if:mixte,1',
        ];
    }
}
