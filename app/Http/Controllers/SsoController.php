<?php

namespace Mesa\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use GuzzleHttp\Exception\GuzzleException;
use Mesa\Http\Api\Clients\EsiClientInterface;
use Mesa\Scopes;

class SsoController extends Controller
{
    /** @var EsiClientInterface $esi */
    protected EsiClientInterface $esi;

    /**
     * SsoController constructor.
     *
     * @param EsiClientInterface $esi
     */
    public function __construct(EsiClientInterface $esi)
    {
        $this->esi = $esi;
    }

    /**
     * Perform SSO login.
     *
     * @return mixed
     */
    public function login()
    {
        $scopes = Scopes::where('access', 'all')->pluck('name')->toArray();

        return $this->esi->authorize($scopes);
    }

    /**
     * perform corporate SSO login.
     *
     * @return mixed
     */
    public function corporateLogin()
    {
        session()->put('character.corporate_member', true);

        $scopes = Scopes::pluck('name')->toArray();

        return $this->esi->authorize($scopes);
    }

    /**
     * Forget SSO character tokens
     */
    public function logout()
    {
        session()->forget('character');

        return redirect()->home();
    }

    /**
     * Receive token from ESI via callback.
     *
     * @param Request $request
     * @return mixed
     * @throws GuzzleException
     */
    public function callback(Request $request)
    {
        $auth = $this->esi->callback($request);
        $expires_on = Carbon::parse(Carbon::now())->addSeconds($auth->expires_in)->toIso8601String();

        session()->put('character.access_token', $auth->access_token);
        session()->put('character.expires_on', $expires_on);
        session()->put('character.refresh_token', $auth->refresh_token);

        return $this->verify();
    }

    /**
     * Verify login and return character information.
     *
     * @return mixed
     * @throws GuzzleException
     */
    public function verify()
    {
        $character = $this->esi->verify();
        session()->put('character.id', $character->CharacterID);
        session()->put('character.name', $character->CharacterName);
        session()->put('character.scopes', explode(" ", $character->Scopes));
        session()->put('character.portrait', 'https://images.evetech.net/characters/'.
            $character->CharacterID.'/portrait?tenant=tranquility&size=128');


        return redirect(route('home'))->with('logged_in', true);
    }
}
