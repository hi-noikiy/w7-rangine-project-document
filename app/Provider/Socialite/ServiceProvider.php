<?php

namespace W7\App\Provider\Socialite;

use Overtrue\Socialite\SocialiteManager;
use Symfony\Component\Finder\Finder;
use W7\Core\Provider\ProviderAbstract;

class ServiceProvider extends ProviderAbstract
{
    public function register()
    {
        $this->registerSocialiteManager();
        $this->registerThirdPartyLogin();
    }

    private function registerSocialiteManager()
    {
        iloader()->set(SocialiteManager::class, function() {
            return new SocialiteManager([]);
        });
    }

    private function registerThirdPartyLogin()
    {
        $thirdPartyLogins = [];
		foreach ((new Finder)->in(__DIR__ . '/ThirdPartyLogin/')->files() as $file) {
			if ($file->getExtension() !== 'php') {
				continue;
			}

			if (strrchr($file->getFilename(), 'Trait') === false) {
				$fileName = substr($file->getBasename(), 0, -4);
				$thirdPartyLogins[] = "\\W7\\App\\Provider\\Socialite\\ThirdPartyLogin\\" . $fileName;
			}
		}

        /**
         * @var SocialiteManager $socialite
         */
        $socialite = iloader()->get(SocialiteManager::class);
		foreach ($thirdPartyLogins as $name => $thirdPartyLogin) {
            $obj = new $thirdPartyLogin();
            // if (!($obj instanceof ThirdPartyLoginAbstract)) {
            //     throw new \RuntimeException('class ' . $thirdPartyLogin . ' must instanceof ' . ThirdPartyLoginAbstract::class);
            // }
            $appName = $obj->getAppName();
			$socialite->extend($obj->getAppName(), function ($config) use ($socialite, $thirdPartyLogin, $appName) {
                return new $thirdPartyLogin(
                    $socialite->getRequest(), 
                    $config['client_id'],
                    $config['client_secret'],
                    ienv('API_HOST') . '/common/auth/third-party-login/' . $appName
                );
            });
		}
    }
}