<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** Reporting period from the dashboard filter (today / 7d / 30d / month / custom). */
final readonly class DateRange
{
    public const PRESETS = [
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'month' => 'This month',
        'custom' => 'Custom range',
    ];

    public function __construct(public CarbonImmutable $from, public CarbonImmutable $to, public string $preset) {}

    public static function fromRequest(Request $request, string $default = '30d'): self
    {
        $preset = array_key_exists($request->query('range'), self::PRESETS) ? $request->query('range') : $default;
        $now = CarbonImmutable::now();

        if ($preset === 'custom') {
            try {
                $from = CarbonImmutable::parse((string) $request->query('from'))->startOfDay();
                $to = CarbonImmutable::parse((string) $request->query('to'))->endOfDay();
                if ($from->gt($to)) {
                    [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
                }
                if ($from->diffInDays($to) > 366) {
                    $from = $to->subDays(366)->startOfDay();
                }

                return new self($from, min($to, $now->endOfDay()), 'custom');
            } catch (\Throwable) {
                $preset = $default;
            }
        }

        return match ($preset) {
            'today' => new self($now->startOfDay(), $now->endOfDay(), $preset),
            '7d' => new self($now->subDays(6)->startOfDay(), $now->endOfDay(), $preset),
            'month' => new self($now->startOfMonth(), $now->endOfDay(), $preset),
            default => new self($now->subDays(29)->startOfDay(), $now->endOfDay(), '30d'),
        };
    }

    /** The period of equal length immediately before this one. */
    public function previous(): self
    {
        $days = $this->days();

        return new self($this->from->subDays($days), $this->from->subSecond(), $this->preset);
    }

    public function days(): int
    {
        return (int) $this->from->startOfDay()->diffInDays($this->to->startOfDay()) + 1;
    }

    public function label(): string
    {
        return $this->preset === 'today'
            ? $this->from->format('M j, Y')
            : $this->from->format('M j').' – '.$this->to->format('M j, Y');
    }

    public function query(): array
    {
        return $this->preset === 'custom'
            ? ['range' => 'custom', 'from' => $this->from->toDateString(), 'to' => $this->to->toDateString()]
            : ['range' => $this->preset];
    }
}
