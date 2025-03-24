<?php

namespace VATGER\Auth\Pub\Controller;

use VATGER\Auth\Setup;
use XF\ControllerPlugin\LoginPlugin;
use XF\Entity\User;
use XF\Finder\UserFinder;
use XF\Mvc\Reply\Error;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\View;
use XF\Pub\Controller\AbstractController;

class FunctionalAccountController extends AbstractController
{
    /**
     * We don't allow anyone to use the following accounts.
     *
     * @var array|string[]
     */
    private static array $DISALLOWED_FUNC_ACC = ['Administrator', 'XF Support Admin', 'VATSIM Migration'];

    public function actionIndex(): View
    {
        /** @var UserFinder $functionalAccounts */
        $functionalAccounts = \XF::finder(UserFinder::class)
            ->where('custom_title', '=', '')
            ->order('username', 'ASC')
            ->fetch();

        $allowedAccounts = [];

        foreach ($functionalAccounts as $account) {
            if ($this->_checkAllowedToUse($account)) {
                $allowedAccounts[] = $account;
            }
        }

        return $this->view('VATGER\Auth:View', 'select_functional_account', ['functionalAccounts' => $allowedAccounts, 'accountCount' => count($allowedAccounts)]);
    }

    public function actionUse(): Redirect|Error
    {
        $requestParams = $this->request->getRequestQueryParams();
        if (!key_exists('account_id', $requestParams)) {
            return $this->redirect($this->_getHomeViewRedirect());
        }

        /** @var User|null $targetAccount */
        $targetAccount = \XF::finder(UserFinder::class)
            ->where('user_id', '=', $requestParams['account_id'])
            ->where('custom_title', '=', '')
            ->fetchOne();

        if ($targetAccount == null || !$this->_checkAllowedToUse($targetAccount)) {
            return $this->error("Internal server error");
        }

        $logFile = fopen(Setup::$LOG_PATH . "/functional.access.log", "a");
        fwrite($logFile, "[" . date('d.m.Y H:i:s T') . "] " . \XF::visitor()->username . " --> " . $targetAccount->username . "\n");
        fclose($logFile);

        $previousUserID = \XF::visitor()->user_id;
        $this->session()->logoutUser();

        /** @var LoginPlugin $loginPlugin */
        $loginPlugin = $this->plugin(LoginPlugin::class);

        $loginPlugin->completeLogin($targetAccount, false);

        $this->session()->set('using_functional_account', true);
        $this->session()->set('previous_user_id', $previousUserID);

        return $this->redirect($this->_getHomeViewRedirect());
    }

    public function actionLeave(): Redirect
    {
        if (!$this->session()->keyExists('previous_user_id')) {
            $this->session()->logoutUser();
            return $this->redirect($this->_getHomeViewRedirect());
        }

        $previousUserID = $this->session()->get('previous_user_id');

        /** @var User $userAccount */
        $userAccount = \XF::finder(UserFinder::class)
            ->where('user_id', '=', $previousUserID)
            ->fetchOne();

        if ($userAccount == null) {
            $this->session()->logoutUser();
            return $this->redirect($this->_getHomeViewRedirect());
        }

        $this->session()->remove('using_functional_account');
        $this->session()->remove('previous_user_id');

        $this->session()->logoutUser();

        /** @var LoginPlugin $loginPlugin */
        $loginPlugin = $this->plugin(LoginPlugin::class);

        $loginPlugin->completeLogin($userAccount, true);

        return $this->redirect($this->_getHomeViewRedirect());
    }

    private function _checkAllowedToUse(User $account): bool
    {
        if (array_find(self::$DISALLOWED_FUNC_ACC, fn(string $accName) => $accName === $account->username) !== null ||
            (!\XF::visitor()->is_moderator && !\XF::visitor()->is_admin && !\XF::visitor()->is_super_admin)
        ) {
            return false;
        }

        // Allow all accounts to be used if Admin or Super-Admin
        if (\XF::visitor()->is_super_admin || \XF::visitor()->is_admin) {
            return true;
        }

        $currentUserGroups = \XF::visitor()->secondary_group_ids;
        $accountUserGroups = $account->secondary_group_ids;

        return count(array_intersect($currentUserGroups, $accountUserGroups)) > 0;
    }

    private function _getHomeViewRedirect(): string
    {
        return $this->getDynamicRedirectIfNot($this->buildLink('index'));
    }
}