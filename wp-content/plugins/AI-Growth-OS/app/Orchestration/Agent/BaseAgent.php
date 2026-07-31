<?php

declare(strict_types=1);

namespace AIGrowthOS\Orchestration\Agent;

use AIGrowthOS\Infrastructure\AI\Client\AIOrchestrator;
use AIGrowthOS\Infrastructure\Logging\Logger;

abstract class BaseAgent
{
    protected string $name;
    protected string $role;
    protected string $mission;
    protected AIOrchestrator $ai;
    protected array $memory = [];

    public function __construct(string $name, string $role, string $mission, AIOrchestrator $ai)
    {
        $this->name = $name;
        $this->role = $role;
        $this->mission = $mission;
        $this->ai = $ai;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getMission(): string
    {
        return $this->mission;
    }

    protected function think(string $prompt, array $context = []): string
    {
        Logger::info("Agent {$this->name} thinking", ['prompt' => substr($prompt, 0, 100)]);

        $systemMessage = $this->buildSystemMessage();
        $userMessage = $this->buildUserMessage($prompt, $context);

        $response = $this->ai->complete([
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $userMessage],
        ], [
            'temperature' => 0.3,
        ]);

        $this->remember($prompt, $response->getContent());

        return $response->getContent();
    }

    protected function buildSystemMessage(): string
    {
        return "You are {$this->name}, acting as {$this->role}. Your mission: {$this->mission}. " .
               "You are part of the AI Growth OS autonomous system. " .
               "Provide clear, actionable, data-driven responses. " .
               "Always output in the requested format. " .
               "Be concise and precise.";
    }

    protected function buildUserMessage(string $prompt, array $context): string
    {
        $msg = $prompt;
        if (!empty($context)) {
            $msg .= "\n\nContext:\n" . wp_json_encode($context, JSON_PRETTY_PRINT);
        }
        if (!empty($this->memory)) {
            $recent = array_slice($this->memory, -5);
            $msg .= "\n\nRecent memory:\n" . wp_json_encode($recent, JSON_PRETTY_PRINT);
        }
        return $msg;
    }

    protected function remember(string $input, string $output): void
    {
        $this->memory[] = [
            'input'    => substr($input, 0, 500),
            'output'   => substr($output, 0, 500),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if (count($this->memory) > 100) {
            array_shift($this->memory);
        }
    }
}
