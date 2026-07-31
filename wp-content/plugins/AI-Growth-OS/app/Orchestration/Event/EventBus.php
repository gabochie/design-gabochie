<?php

declare(strict_types=1);

namespace AIGrowthOS\Orchestration\Event;

use AIGrowthOS\Domain\Event\DomainEvent;
use AIGrowthOS\Infrastructure\Logging\Logger;

class EventBus
{
    private array $listeners = [];

    public function listen(string $eventName, callable $handler): void
    {
        $this->listeners[$eventName][] = $handler;
    }

    public function dispatch(DomainEvent $event): void
    {
        Logger::debug("Dispatching event: {$event->eventName()}", $event->payload());

        $this->persistEvent($event);

        $eventName = $event->eventName();
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $handler) {
            try {
                $handler($event);
            } catch (\Throwable $e) {
                Logger::error("Event handler failed", [
                    'event'   => $eventName,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    private function persistEvent(DomainEvent $event): void
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'ai_growth_os_events',
            [
                'event_name'  => $event->eventName(),
                'payload'     => wp_json_encode($event->payload()),
                'occurred_at' => $event->occurredOn()->format('Y-m-d H:i:s'),
            ],
            ['%s', '%s', '%s']
        );
    }
}
