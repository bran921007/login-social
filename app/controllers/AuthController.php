<?php

use App\Auth\HybridSessionStorage;
use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\HttpClient\Util;
use Hybridauth\Hybridauth;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function getTwitterLogin(Request $request, ?string $auth = null)
    {
        return $this->signIn($request, 'Twitter', 'twitterAuth');
    }

    public function getFacebookLogin(Request $request, ?string $auth = null)
    {
        return $this->signIn($request, 'Facebook', 'facebookAuth');
    }

    public function getGoogleLogin(Request $request, ?string $auth = null)
    {
        return $this->signIn($request, 'Google', 'googleAuth');
    }

    private function signIn(Request $request, string $provider, string $route)
    {
        $name = strtolower($provider);
        $id = config("services.$name.client_id");
        $secret = config("services.$name.client_secret");
        abort_unless($id && $secret, 503, 'Social login is not configured.');
        abort_if($request->has('error') || $request->has('denied'), 400, 'Social login was declined.');
        $storage = new HybridSessionStorage($request->session());
        $hybrid = new Hybridauth([
            'callback' => config("services.$name.redirect") ?: route($route, ['auth' => 'auth']),
            'providers' => [$provider => ['enabled' => true, 'keys' => ['id' => $id, 'key' => $id, 'secret' => $secret]]],
        ], app(HttpClientInterface::class), $storage);

        // Return redirects through Laravel so session state is saved before the response is sent.
        $redirect = null;
        Util::setRedirectHandler(function ($url) use (&$redirect): void {
            $redirect = redirect()->away($url);
        });
        try {
            $adapter = $hybrid->authenticate($provider);
            if ($redirect !== null) {
                return $redirect;
            }
            $profile = $adapter->getUserProfile();
        } catch (InvalidAuthorizationStateException $exception) {
            abort(400, 'The social login session has expired. Please try again.');
        } catch (Throwable $exception) {
            abort(502, 'The social login provider could not complete the request.');
        } finally {
            Util::setRedirectHandler(null);
        }

        $storage->delete($provider.'.authorization_state');
        $request->session()->regenerate();
        $request->session()->put('social_profile', [
            'provider' => $name, 'id' => $profile->identifier,
            'name' => $profile->displayName, 'email' => $profile->email,
        ]);
        return response(e($name === 'google' ? $profile->email : $profile->displayName).'<a href="'.e(route('logout')).'">Log Out</a>');
    }

    public function getLoggedOut(Request $request)
    {
        (new HybridSessionStorage($request->session()))->clear();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return view('login');
    }
}
