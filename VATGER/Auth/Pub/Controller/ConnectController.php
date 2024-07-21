<?php

namespace VATGER\Auth\Pub\Controller;

use VATGER\Auth\Service\Vatsim\Connect;
use VATGER\Auth\Setup;
use XF\Mvc\Reply\Redirect;
use XF\PrintableException;
use XF\Pub\Controller\AbstractController;
use XF\Repository\User;

class ConnectController extends AbstractController
{
    protected static string $GENERIC_ERROR_MESSAGE = "An internal error occurred. Please try again later or contact an administrator.";

    public function actionIndex(): Redirect
    {
        if (!$this->_allowLogin()) {
            return $this->redirect($this->_getHomeViewRedirect());
        }

        /** @var Connect $connectService */
        $connectService = $this->service('VATGER\Auth:Vatsim\Connect', $this->options());

        return $this->redirect($connectService->getRedirectURI());
    }

    /**
     * @throws PrintableException
     */
    public function actionCallback()
    {
        if (!$this->_allowLogin()) {
            return $this->redirect($this->_getHomeViewRedirect());
        }

        /** @var Connect $connectService */
        $connectService = $this->service('VATGER\Auth:Vatsim\Connect', $this->options());

        $requestParams = $this->request->getRequestQueryParams();

        if (!key_exists('code', $requestParams)) {
            return $this->error("Code not found in request. Please try again later or contact an administrator.", 400);
        }

        $tokens = $connectService->getAuthToken($requestParams['code']);
        if ($tokens == null) {
            return $this->error(self::$GENERIC_ERROR_MESSAGE, 400);
        }

        $apiUser = $connectService->getUserDetails($tokens['access_token']);
        $cid = $this->_getValueFromJsonPath($apiUser, $this->options()["cid_mapping"]);
        $email = $this->_getValueFromJsonPath($apiUser, $this->options()["email_mapping"]);
        $fullName = $this->_getValueFromJsonPath($apiUser, $this->options()["full_name_mapping"]);

        if (!isset($apiUser, $cid, $email, $fullName)) {
            return $this->error(self::$GENERIC_ERROR_MESSAGE, 400);
        }

        /** @var \XF\Entity\User $databaseUser */
        $databaseUser = \XF::finder('XF:User')->where('vatsim_id', $cid)->fetchOne();

        if (!$databaseUser) {
            // Find the first available username
            $count = 1;
            $fullDBName = $fullName;
            while (\XF::finder('XF:User')->where('username', $fullDBName)->fetch()->count() > 0) {
                $fullDBName = $fullName . ' ' . $count;
                $count++;
            }

            /** @var User $userRepository */
            $userRepository = $this->repository('XF:User');
            $baseUser = $userRepository->setupBaseUser();

            if ($baseUser == null) {
                $baseUser->delete();
                return $this->error(self::$GENERIC_ERROR_MESSAGE, 400);
            }

            $baseUser["vatsim_id"] = $cid;
            $baseUser["email"] = $email;
            $baseUser["username"] = $fullDBName;

            $baseUser->custom_title = strval($cid);
            $baseUser->Auth->setNoPassword();
            $baseUser->Profile->password_date = \XF::$time;

            $baseUser->save();
            $databaseUser = $baseUser;

            // Inform the homepage of this new user.
            try {
                $response = \XF::app()->http()->client()->post($this->options()['homepage_callback'], [
                    'json' => [
                        'vatsim_id' => $cid,
                        'forum_id' => $baseUser['user_id']
                    ],
                    'headers' => [
                        'Authorization' => 'Token ' . $this->options()['homepage_token']
                    ]
                ]);

                if ($response->getStatusCode() != 200) {
                    throw new \Exception("Non 200 status code. ConnectController#L107");
                }
            } catch (\Exception $e) {
                \XF::logError($e->getMessage(), true);
                $baseUser->delete();
                return $this->error("Failed to assign a homepage account.", 400);
            }
        }

        // Update the user whilst we're here...
        $databaseUser->email = $email;
        $databaseUser->save();

        /** @var \XF\ControllerPlugin\Login $loginPlugin */
        $loginPlugin = $this->plugin('XF:Login');

        $loginPlugin->completeLogin($databaseUser, true);

        return $this->redirect($this->_getHomeViewRedirect());
    }

    private function _getHomeViewRedirect()
    {
        return $this->getDynamicRedirectIfNot($this->buildLink('index'));
    }

    private function _allowLogin(): bool
    {
        $this->assertIpNotBanned();

        // Potentially add some more cases in the future :)
        if (\XF::visitor()->user_id != null || \XF::visitor()->is_banned)
        {
            return false;
        }

        return true;
    }

    private function _getValueFromJsonPath(mixed $obj, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $obj;

        foreach ($keys as $key)
        {
            if (isset($value[$key]))
            {
                $value = $value[$key];
            } else
            {
                return null;
            }
        }

        return $value;
    }
}