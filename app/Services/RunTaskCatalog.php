<?php

namespace App\Services;

/**
 * Read access to the Phase-2 objective parameters in config('game.run.tasks')
 * (GDD §15 Aufgabenpool) — the single place that knows the config shape.
 *
 * Also builds the player-facing objective label from the same values, so the
 * number shown in the UI is the number the objective is measured against.
 */
class RunTaskCatalog
{
    public const TYPE_COUNTER = 'counter';

    public const TYPE_STREAK = 'streak';

    /**
     * Full parameter set of one task, or [] for an unknown key.
     *
     * @return array<string, mixed>
     */
    public function params(string $taskKey): array
    {
        $params = config("game.run.tasks.{$taskKey}");

        return is_array($params) ? $params : [];
    }

    /**
     * One parameter of a task (e.g. 'threshold', 'min_rank').
     */
    public function param(string $taskKey, string $name, mixed $default = null): mixed
    {
        return $this->params($taskKey)[$name] ?? $default;
    }

    /**
     * Completion target (count or streak length). Unknown tasks get 1.
     */
    public function target(string $taskKey): int
    {
        return (int) $this->param($taskKey, 'target', 1);
    }

    public function category(string $taskKey): ?string
    {
        $category = $this->param($taskKey, 'category');

        return is_string($category) ? $category : null;
    }

    public function isStreak(string $taskKey): bool
    {
        return $this->param($taskKey, 'type') === self::TYPE_STREAK;
    }

    /**
     * Task keys that may be drawn: config('game.run.task_pool'), falling back to
     * every configured task.
     *
     * @return list<string>
     */
    public function pool(): array
    {
        $pool = config('game.run.task_pool');

        if (! is_array($pool)) {
            $pool = array_keys((array) config('game.run.tasks', []));
        }

        return array_values(array_map('strval', $pool));
    }

    /**
     * Difficulty levels that count for task_expedition_coverage: min_difficulty
     * and every harder level (order from config('game.missions.difficulty.order')).
     *
     * @return list<string>
     */
    public function countedDifficulties(): array
    {
        $order = array_values((array) config('game.missions.difficulty.order', ['easy', 'normal', 'hard']));
        $min = (string) $this->param('task_expedition_coverage', 'min_difficulty', 'normal');
        $index = array_search($min, $order, true);

        return $index === false ? [$min] : array_slice($order, (int) $index);
    }

    /**
     * Player-facing label with the configured values filled in.
     *
     * @param  int|null  $target  the objective's persisted target_value; defaults to config
     */
    public function label(string $taskKey, ?int $target = null): string
    {
        $replace = ['target' => $this->formatNumber($target ?? $this->target($taskKey))];

        foreach ($this->params($taskKey) as $name => $value) {
            if (in_array($name, ['category', 'type', 'target'], true)) {
                continue;
            }
            if (is_int($value)) {
                $replace[$name] = $this->formatNumber($value);
            }
        }

        if ($taskKey === 'task_expedition_coverage') {
            $replace['min_difficulty'] = __('missions.difficulty_'.$this->param($taskKey, 'min_difficulty', 'normal'));
        }

        return __('run.'.$taskKey, $replace);
    }

    private function formatNumber(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}
