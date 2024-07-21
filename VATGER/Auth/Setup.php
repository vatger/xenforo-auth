<?php

namespace VATGER\Auth;

use XF\AddOn\AbstractSetup;

class Setup extends AbstractSetup
{
    public static string $LOG_PATH = "/var/www/board.vatsim-germany.org/xf_vatger_auth_logs";

    public function install(array $stepParams = []): void
    {
        // Create log directory
        mkdir(self::$LOG_PATH, recursive: true);

        $this->schemaManager()->alterTable('xf_user', function (\XF\Db\Schema\Alter $table) {
            $table->addColumn('vatsim_id', 'bigint')->nullable();
        });
    }

    public function uninstall(array $stepParams = []): void
    {
        $this->schemaManager()->alterTable('xf_user', function (\XF\Db\Schema\Alter $table) {
            $table->dropColumns('vatsim_id');
        });
    }

    public function upgrade(array $stepParams = []): void
    {
        if ($this->addOn->version_id < 16) // v.1.0.6
        {
            $this->schemaManager()->alterTable('xf_user', function (\XF\Db\Schema\Alter $table) {
                $table->dropColumns(['oauth_auth_token', 'oauth_remember_token']);
            });
        }
    }
}