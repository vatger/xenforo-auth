<?php

namespace VATGER\Auth;

use VATGER\Auth\Helpers\ModerationContentType;
use VATGER\Auth\Helpers\ModerationLogType;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;

    public static string $LOG_PATH = "/var/www/board.vatsim-germany.org/xf_vatger_auth_logs";

    public function installStep1(array $stepParams = []): void
    {
        // Create log directory
        try {
            mkdir(self::$LOG_PATH, recursive: true);
        } catch (\Exception $e) {
            \XF::logError("Failed to create log dir at: " . self::$LOG_PATH, true);
        }

        $this->schemaManager()->alterTable('xf_user', function (\XF\Db\Schema\Alter $table) {
            $table->addColumn('vatsim_id', 'bigint')->nullable();
        });
    }

    public function uninstall(array $stepParams = []): void
    {
        $this->schemaManager()->alterTable('xf_user', function (\XF\Db\Schema\Alter $table) {
            $table->dropColumns('vatsim_id');
        });

        $this->schemaManager()->dropTable('xf_vatger_post_content');
        $this->schemaManager()->dropTable('xf_vatger_moderation_logs');
    }

    public function upgrade16Step1(array $stepParams = []): void
    {
        // Upgrades for version 0.1.6

        $this->schemaManager()->alterTable('xf_user', function (\XF\Db\Schema\Alter $table) {
            $table->dropColumns(['oauth_auth_token', 'oauth_remember_token']);
        });
    }

    public function upgrade136Step1() {
        // Upgrades for version 1.3.6

        $this->schemaManager()->createTable('xf_vatger_moderation_logs', function (\XF\Db\Schema\Create $table) {
            $table->addColumn('id', 'int')->primaryKey()->autoIncrement();
            $table->addColumn('user_id', 'int');
            $table->addColumn('ip_address', 'varbinary', 16)->setDefault('');
            $table->addColumn('thread_id', 'int')->nullable();
            $table->addColumn('post_id', 'int')->nullable();
            $table->addColumn('reason', 'text')->nullable();
            $table->addColumn('message', 'text')->nullable();
            $table->addColumn('change_type', 'enum')->values([
                ModerationLogType::MOVE->toString(),
                ModerationLogType::DELETE_SOFT->toString(),
                ModerationLogType::DELETE_HARD->toString(),
                ModerationLogType::UNDELETED->toString(),
            ]);
            $table->addColumn('content_type', 'enum')->values([
                ModerationContentType::POST->toString(),
                ModerationContentType::THREAD->toString(),
            ]);
            $table->addColumn('date', 'int');
        });

        // This table contains an entry referencing the moderation_log entry (by id), IFF change_type == DELETE_HARD!
        $this->schemaManager()->createTable('xf_vatger_post_content', function (Create $table) {
            $table->addColumn('id', 'int')->primaryKey()->autoIncrement();
            $table->addColumn('vatger_moderation_log_id', 'int');
            $table->addColumn('user_id', 'int');
            $table->addColumn('content', 'mediumtext');
        });
    }
}