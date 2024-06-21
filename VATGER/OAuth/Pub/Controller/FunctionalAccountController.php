<?php

namespace VATGER\OAuth\Pub\Controller;

class FunctionalAccountController extends \XF\Pub\Controller\AbstractController
{
    public function actionIndex()
    {
        $functionalAccounts = \XF::finder("XF:User")
            ->where('custom_title', '=', '')
            ->fetch();

        $currentUserGroups = \XF::visitor()->secondary_group_ids;
        $allowedAccounts = [];

        foreach ($functionalAccounts as $account) {
            $accountGroupSet = $account->secondary_group_ids;
            if (count(array_intersect($currentUserGroups, $accountGroupSet)) > 0) {
                $allowedAccounts[] = $account;
            }
        }

        return $this->view('VATGER\OAuth:View', 'select_functional_account', ['functionalAccounts' => $allowedAccounts, 'accountCount' => count($allowedAccounts)]);
    }

    public function actionUse()
    {
        $requestParams = $this->request->getRequestQueryParams();
        if (!key_exists('account_id', $requestParams)) {
            return $this->redirect($this->_getHomeViewRedirect());
        }

        /** @var \XF\Entity\User $targetAccount */
        $targetAccount = \XF::finder("XF:User")
            ->where('user_id', '=', $requestParams['account_id'])
            ->where('custom_title', '=', '')
            ->fetchOne();

        if ($targetAccount == null || !$this->_checkAllowedToUse($targetAccount)) {
            return $this->error("Failed");
        }

        $previousUserID = \XF::visitor()->user_id;
        $this->session()->logoutUser();

        /** @var \XF\ControllerPlugin\Login $loginPlugin */
        $loginPlugin = $this->plugin('XF:Login');

        $loginPlugin->completeLogin($targetAccount, false);

        $this->session()->set('using_functional_account', true);
        $this->session()->set('previous_user_id', $previousUserID);

        return $this->redirect($this->_getHomeViewRedirect());
    }

    public function actionLeave()
    {
        if (!$this->session()->keyExists('previous_user_id')) {
            $this->session()->logoutUser();
            return $this->redirect($this->_getHomeViewRedirect());
        }

        $previousUserID = $this->session()->get('previous_user_id');

        /** @var \XF\Entity\User $userAccount */
        $userAccount = \XF::finder('XF:User')
            ->where('user_id', '=', $previousUserID)
            ->fetchOne();

        if ($userAccount == null) {
            $this->session()->logoutUser();
            return $this->redirect($this->_getHomeViewRedirect());
        }

        $this->session()->remove('using_functional_account');
        $this->session()->remove('previous_user_id');

        $this->session()->logoutUser();

        /** @var \XF\ControllerPlugin\Login $loginPlugin */
        $loginPlugin = $this->plugin('XF:Login');

        $loginPlugin->completeLogin($userAccount, true);

        return $this->redirect($this->_getHomeViewRedirect());
    }

    private function _checkAllowedToUse(\XF\Entity\User $account): bool
    {
        $currentUserGroups = \XF::visitor()->secondary_group_ids;
        $accountUserGroups = $account->secondary_group_ids;

        return count(array_intersect($currentUserGroups, $accountUserGroups)) > 0;
    }

    private function _getHomeViewRedirect()
    {
        return $this->getDynamicRedirectIfNot($this->buildLink('index'));
    }
}