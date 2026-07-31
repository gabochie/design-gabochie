<?php

declare(strict_types=1);

namespace WP_AI_Emergency_Recovery\REST;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_AI_Emergency_Recovery\Engine\IncidentEngine;
use WP_AI_Emergency_Recovery\Engine\RecoveryEngine;
use WP_AI_Emergency_Recovery\Engine\SnapshotEngine;

final class RecoveryController extends WP_REST_Controller {
    protected $namespace = 'ai-recovery/v1';

    private IncidentEngine $incidentEngine;
    private RecoveryEngine $recoveryEngine;
    private SnapshotEngine $snapshotEngine;

    public function __construct(IncidentEngine $incidentEngine, RecoveryEngine $recoveryEngine, SnapshotEngine $snapshotEngine) {
        $this->incidentEngine = $incidentEngine;
        $this->recoveryEngine = $recoveryEngine;
        $this->snapshotEngine = $snapshotEngine;
    }

    public function registerRoutes(): void {
        register_rest_route($this->namespace, '/snapshot', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'getSnapshot'],
            'permission_callback' => [$this, 'checkPermission']
        ]);

        register_rest_route($this->namespace, '/repair', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'executeRepair'],
            'permission_callback' => [$this, 'checkPermission']
        ]);
    }

    public function checkPermission(): bool {
        return current_user_can('manage_options');
    }

    public function getSnapshot(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response($this->snapshotEngine->createSnapshot(), 200);
    }

    public function executeRepair(WP_REST_Request $request): WP_REST_Response {
        $params = $request->get_json_params();
        $res = $this->recoveryEngine->executeRepair($params['actionType'] ?? '', $params['target'] ?? '');
        return new WP_REST_Response($res, 200);
    }
}
