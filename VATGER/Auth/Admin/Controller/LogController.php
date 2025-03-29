<?php

namespace VATGER\Auth\Admin\Controller;

use VATGER\Auth\Entity\VatgerModerationLog;
use VATGER\Auth\Repository\VatgerModerationLogRepository;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\Reply\View;

class LogController extends AbstractController {
    /**
     * @throws Exception
     */
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('viewLogs');
    }

    /**
     * @throws Exception
     */
    public function actionIndex(ParameterBag $params): Redirect|View
    {
        if ($params->log_id) {
            $entry = $this->assertRecordExists(VatgerModerationLog::class, $params->log_id, null, 'requested_log_entry_not_found');

            $viewParams = [
                'entry' => $entry
            ];

            return $this->view('VATGER\Auth:AdminLog', 'vatger_log_moderator_view', $viewParams);
        } else {

            $page = $this->filterPage();
            $perPage = 20;

            /** @var VatgerModerationLogRepository $vatgerModerationLogRepo */
            $vatgerModerationLogRepo = $this->repository(VatgerModerationLogRepository::class);

            $logFinder = $vatgerModerationLogRepo->findLogsForList()->limitByPage($page, $perPage);

            $linkFilters = [];
            if ($userId = $this->filter('user_id', 'uint')) {
                $linkFilters['user_id'] = $userId;
                $logFinder->where('user_id', $userId);
            }

            if ($changeType = $this->filter('change_type', 'string')) {
                $linkFilters['change_type'] = $changeType;
                $logFinder->where('change_type', $changeType);
            }

            if ($contentType = $this->filter('content_type', 'string')) {
                $linkFilters['content_type'] = $contentType;
                $logFinder->where('content_type', $contentType);
            }

            if ($this->isPost()) {
                return $this->redirect($this->buildLink('vatger/logs/moderator', null, $linkFilters));
            }

            $viewParams = [
                'entries' => $logFinder->fetch(),
                'logUsers' => $vatgerModerationLogRepo->getUsersInLog(),

                'userId' => $userId,
                'changeType' => $changeType,
                'contentType' => $contentType,

                'page' => $page,
                'perPage' => $perPage,
                'total' => $logFinder->total(),
                'linkFilters' => $linkFilters,
            ];
            return $this->view('VATGER\Auth:AdminLog', 'vatger_log_moderator_list', $viewParams);
        }
    }
}