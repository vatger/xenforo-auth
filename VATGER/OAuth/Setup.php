<?php

namespace VATGER\OAuth;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    public static string $OAUTH_DB_AUTH_COLUMN = "oauth_auth_token";
    public static string $OAUTH_DB_REFRESH_COLUMN = "oauth_remember_token";

    public function install(array $stepParams = []): void
    {
        $this->schemaManager()->alterTable('xf_user', function (\XF\Db\Schema\Alter $table) {
            $table->addColumn(self::$OAUTH_DB_AUTH_COLUMN, 'text', 255)->nullable();
            $table->addColumn(self::$OAUTH_DB_REFRESH_COLUMN, 'text', 255)->nullable();
            $table->addColumn('vatsim_id', 'bigint')->nullable();
        });
    }

    public function uninstall(array $stepParams = []): void
    {
        $this->schemaManager()->alterTable('xf_user', function (\XF\Db\Schema\Alter $table) {
            $table->dropColumns(self::$OAUTH_DB_AUTH_COLUMN);
            $table->dropColumns(self::$OAUTH_DB_REFRESH_COLUMN);
            $table->dropColumns('vatsim_id');
        });
    }

    public function upgrade(array $stepParams = []): void
    {
        // Currently, nothing to upgrade!
    }
}