<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\Core;

use WP_AI_Emergency_Recovery\Engine\SnapshotEngine;
use WP_AI_Emergency_Recovery\Engine\IncidentEngine;
use WP_AI_Emergency_Recovery\Engine\RecoveryEngine;
use WP_AI_Emergency_Recovery\Engine\ValidationEngine;
use WP_AI_Emergency_Recovery\Engine\RollbackEngine;
use WP_AI_Emergency_Recovery\Engine\LoggingEngine;
use WP_AI_Emergency_Recovery\Admin\EmergencyDashboard;
use WP_AI_Emergency_Recovery\REST\RecoveryController;
use WP_AI_Emergency_Recovery\CLI\RecoveryCommand;

final class Plugin {
    private static ?Plugin $instance = null;

    private SnapshotEngine $snapshotEngine;
    private IncidentEngine $incidentEngine;
    private RecoveryEngine $recoveryEngine;
    private ValidationEngine $validationEngine;
    private RollbackEngine $rollbackEngine;
    private LoggingEngine $loggingEngine;

    private function __construct() {
        $this->loggingEngine = new LoggingEngine();
        $this->snapshotEngine = new SnapshotEngine();
        $this->rollbackEngine = new RollbackEngine($this->loggingEngine);
        $this->validationEngine = new ValidationEngine();
        $this->recoveryEngine = new RecoveryEngine($this->snapshotEngine, $this->rollbackEngine, $this->loggingEngine);
        $this->incidentEngine = new IncidentEngine($this->snapshotEngine, $this->loggingEngine);
    }

    public static function getInstance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function boot(): void {
        // Register Fatal Error Handler Capture
        $this->registerEmergencyErrorHandler();

        // Register REST API Endpoints
        add_action('rest_api_init', [new RecoveryController($this->incidentEngine, $this->recoveryEngine, $this->snapshotEngine), 'registerRoutes']);

        // Register Admin Menu & Dashboard
        if (is_admin()) {
            (new EmergencyDashboard($this->incidentEngine, $this->recoveryEngine))->register();
        }

        // Register WP-CLI Command if CLI environment
        if (defined('WP_CLI') && WP_CLI) {
            RecoveryCommand::register();
        }
    }

    private function registerEmergencyErrorHandler(): void {
        register_shutdown_function(function(): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->loggingEngine->log(
                    'CRITICAL',
                    'Fatal Error Intercepted',
                    sprintf('%s in %s on line %d', $error['message'], $error['file'], $error['line'])
                );
            }
        });
    }

    public function getSnapshotEngine(): SnapshotEngine { return $this->snapshotEngine; }
    public function getIncidentEngine(): IncidentEngine { return $this->incidentEngine; }
    public function getRecoveryEngine(): RecoveryEngine { return $this->recoveryEngine; }
    public function getValidationEngine(): ValidationEngine { return $this->validationEngine; }
    public function getRollbackEngine(): RollbackEngine { return $this->rollbackEngine; }
    public function getLoggingEngine(): LoggingEngine { return $this->loggingEngine; }
}
