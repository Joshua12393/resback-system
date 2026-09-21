<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class FeedbackDateRange
{
    public function __construct(
        public ?CarbonImmutable $start,
        public ?CarbonImmutable $end,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'required_with:end_date', 'date_format:Y-m-d', 'before_or_equal:today'],
            'end_date' => ['nullable', 'required_with:start_date', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:today'],
        ], [
            'start_date.required_with' => 'Select a start date when an end date is provided.',
            'end_date.required_with' => 'Select an end date when a start date is provided.',
            'start_date.before_or_equal' => 'The start date cannot be in the future.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'end_date.before_or_equal' => 'The end date cannot be in the future.',
        ]);

        return new self(
            isset($validated['start_date'])
                ? CarbonImmutable::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay()
                : null,
            isset($validated['end_date'])
                ? CarbonImmutable::createFromFormat('Y-m-d', $validated['end_date'])->endOfDay()
                : null,
        );
    }

    public function apply(Builder $query, string $column = 'created_at'): Builder
    {
        return $query
            ->when($this->start, fn (Builder $builder) => $builder->where($column, '>=', $this->start))
            ->when($this->end, fn (Builder $builder) => $builder->where($column, '<=', $this->end));
    }

    public function isActive(): bool
    {
        return $this->start !== null && $this->end !== null;
    }

    public function label(): string
    {
        if (! $this->isActive()) {
            return 'All dates';
        }

        return $this->start->format('M j, Y').' - '.$this->end->format('M j, Y');
    }

    /** @return array{start_date?: string, end_date?: string} */
    public function queryParameters(): array
    {
        if (! $this->isActive()) {
            return [];
        }

        return [
            'start_date' => $this->start->format('Y-m-d'),
            'end_date' => $this->end->format('Y-m-d'),
        ];
    }
}
