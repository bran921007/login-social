<?php

use App\Auth\HybridSessionStorage;
use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\FilterService;
use Hybridauth\HttpClient\Curl;
use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\HttpClient\Util;
use Illuminate\Foundation\Testing\TestCase;

final class SocialLoginTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['google', 'facebook', 'twitter'] as $provider) {
            config(['services.'.$provider.'.client_id' => 'test-id', 'services.'.$provider.'.client_secret' => 'test-secret']);
        }
        // Framework HTTP tests do not populate SAPI input globals, unlike real HTTP entrypoints.
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];
        AbstractAdapter::setFilterService(new FrameworkRequestFilter());
        $this->app->instance(HttpClientInterface::class, new RecordingSocialTransport([]));
    }

    protected function tearDown(): void
    {
        AbstractAdapter::setFilterService(null);
        Util::setRedirectHandler(null);
        $_GET = [];
        parent::tearDown();
    }

    public function testHomeRetainsAllLoginLinks(): void
    {
        $this->get('/')->assertOk()->assertSee('twitterAuth')->assertSee('fbauth')->assertSee('gauth');
    }

    public function testGoogleAndFacebookRedirectWithServerSessionState(): void
    {
        foreach (['gauth' => ['google', 'accounts.google.com'], 'fbauth' => ['facebook', 'www.facebook.com']] as $path => [$provider, $host]) {
            $response = $this->get('/'.$path)->assertRedirect();
            $this->assertSame($host, parse_url($response->headers->get('Location'), PHP_URL_HOST));
            parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
            $this->assertNotEmpty($query['state']);
            $this->assertSame(session('hybridauth')[$provider.'.authorization_state'], $query['state']);
            $this->assertSame('http://localhost/'.$path.'/auth', $query['redirect_uri']);
        }
    }

    public function testGenuineGoogleCallbackValidatesStateAndDoesNotExposeTokens(): void
    {
        $transport = new RecordingSocialTransport([
            json_encode(['access_token' => 'test-access-token', 'token_type' => 'Bearer', 'expires_in' => 3600]),
            json_encode(['sub' => '123', 'name' => 'Test User', 'email' => 'test@example.invalid', 'given_name' => 'Test', 'family_name' => 'User', 'email_verified' => true]),
        ]);
        $this->app->instance(HttpClientInterface::class, $transport);
        $this->get('/gauth')->assertRedirect();
        $state = session('hybridauth')['google.authorization_state'];
        $this->get('/gauth/auth?code=test-code&state='.urlencode($state))
            ->assertOk()->assertSee('test@example.invalid')->assertDontSee('test-access-token');
        $this->assertSame('123', session('social_profile.id'));
        $this->assertArrayNotHasKey('google.authorization_state', session('hybridauth'));
        $this->assertCount(2, $transport->requests);
        $this->assertSame('https://oauth2.googleapis.com/token', $transport->requests[0]['uri']);
        $this->assertSame('test-code', $transport->requests[0]['parameters']['code']);
    }

    public function testGenuineFacebookCallbackPreservesProfileFlow(): void
    {
        $transport = new RecordingSocialTransport([
            json_encode(['access_token' => 'facebook-test-token', 'token_type' => 'Bearer', 'expires_in' => 5184000]),
            json_encode(['id' => '456', 'name' => 'Facebook Test', 'email' => 'facebook@example.invalid', 'first_name' => 'Facebook', 'last_name' => 'Test']),
        ]);
        $this->app->instance(HttpClientInterface::class, $transport);
        $this->get('/fbauth')->assertRedirect();
        $state = session('hybridauth')['facebook.authorization_state'];
        $this->get('/fbauth/auth?code=test-code&state='.urlencode($state))->assertOk()->assertSee('Facebook Test')->assertDontSee('facebook-test-token');
        $this->assertSame('456', session('social_profile.id'));
        $this->assertCount(2, $transport->requests);
        $this->assertSame('graph.facebook.com', parse_url($transport->requests[1]['uri'], PHP_URL_HOST));
    }

    public function testGenuineTwitterOAuth1RequestTokenCallbackAndEscapedProfile(): void
    {
        $transport = new RecordingSocialTransport([
            'oauth_token=request-token&oauth_token_secret=request-secret&oauth_callback_confirmed=true',
            'oauth_token=access-token&oauth_token_secret=access-secret&user_id=123&screen_name=Test',
            json_encode(['id_str' => '123', 'screen_name' => 'Test <script>', 'name' => 'Test User', 'email' => 'test@example.invalid']),
        ]);
        $this->app->instance(HttpClientInterface::class, $transport);
        $response = $this->get('/twitterAuth')->assertRedirect();
        $this->assertSame('api.twitter.com', parse_url($response->headers->get('Location'), PHP_URL_HOST));
        $this->assertStringContainsString('oauth_token=request-token', $response->headers->get('Location'));
        $_GET = ['oauth_token' => 'request-token', 'oauth_verifier' => 'test-verifier'];
        $this->get('/twitterAuth/auth?oauth_token=request-token&oauth_verifier=test-verifier')
            ->assertOk()->assertSee('Test &lt;script&gt;', false)->assertDontSee('<script>', false)->assertDontSee('access-secret');
        $this->assertSame('123', session('social_profile.id'));
        $this->assertCount(3, $transport->requests);
        $this->assertSame('https://api.twitter.com/oauth/access_token', $transport->requests[1]['uri']);
    }

    public function testInvalidStateAndDeclinedLoginMakeNoNetworkRequests(): void
    {
        $transport = new RecordingSocialTransport([]);
        $this->app->instance(HttpClientInterface::class, $transport);
        $this->get('/gauth')->assertRedirect();
        $this->get('/gauth/auth?code=test-code&state=wrong-state')->assertStatus(400);
        $this->get('/fbauth/auth?error=access_denied')->assertStatus(400);
        $this->assertCount(0, $transport->requests);
    }

    public function testMissingConfigurationFailsSafelyForEveryProvider(): void
    {
        foreach (['gauth' => 'google', 'fbauth' => 'facebook', 'twitterAuth' => 'twitter'] as $path => $provider) {
            config(['services.'.$provider.'.client_secret' => null]);
            $this->get('/'.$path)->assertStatus(503)->assertDontSee('test-secret');
        }
    }

    public function testLogoutAndScopedStorageClearOAuthSession(): void
    {
        session()->put('unrelated', 'preserve');
        $storage = new HybridSessionStorage(session()->driver());
        $storage->set('Google.access_token', 'test-token');
        $storage->set('Twitter.access_token', 'twitter-token');
        $this->assertSame('test-token', $storage->get('GOOGLE.access_token'));
        $storage->deleteMatch('Google.');
        $this->assertNull($storage->get('Google.access_token'));
        $this->assertSame('twitter-token', $storage->get('Twitter.access_token'));
        $storage->delete('Twitter.access_token');
        $this->assertNull($storage->get('Twitter.access_token'));
        $this->assertSame('preserve', session('unrelated'));
        $this->withSession(['social_profile' => ['id' => '123'], 'hybridauth' => ['google.access_token' => 'test-token']])
            ->get('/logout')->assertOk()->assertSee('Login using Twitter');
        $this->assertNull(session('social_profile'));
        $this->assertNull(session('hybridauth'));
    }
}

final class FrameworkRequestFilter extends FilterService
{
    public function filterInput($type, $var_name, $filter = FILTER_DEFAULT, $options = 0)
    {
        $value = $type === INPUT_GET ? app('request')->query($var_name) : app('request')->input($var_name);
        return $value === null ? null : filter_var($value, $filter, $options);
    }
}

final class RecordingSocialTransport extends Curl
{
    public array $requests = [];
    public function __construct(private array $responses) {}
    public function request($uri, $method = 'GET', $parameters = [], $headers = [], $multipart = false)
    {
        $this->requests[] = compact('uri', 'method', 'parameters');
        if ($this->responses === []) {
            throw new RuntimeException('Unexpected external request');
        }
        $this->responseBody = array_shift($this->responses);
        $this->responseHttpCode = 200;
        return $this->responseBody;
    }
}
