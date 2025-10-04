<?php

namespace SrinathReddyDudi\FailedJobs\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ViewJobAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'view';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('View'))
            ->icon(Heroicon::Eye)
            ->modalHeading(__('Failed job details'))
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (Action $action) => $action->label(__('Close')))
            ->modalContent(fn (array $record): HtmlString => new HtmlString($this->renderContent($record)));
    }

    protected function renderContent(array $record): string
    {
        $payload = $record['payload'];
        $formattedPayload = null;

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            $formattedPayload = $decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $payload;
        }

        $fields = [
            __('Project') => $record['project_name'] ?? null,
            __('Connection') => $record['connection'] ?? null,
            __('Queue') => $record['queue'] ?? null,
            __('Job') => $record['payload_display_name'] ?? null,
            __('UUID') => $record['uuid'] ?? null,
            __('Failed at') => optional($record['failed_at'])->toDateTimeString(),
        ];

        $items = collect($fields)
            ->filter()
            ->map(fn ($value, $label) => sprintf('<dt class="font-medium text-sm text-gray-500">%s</dt><dd class="mb-4 text-sm text-gray-900 dark:text-gray-100">%s</dd>', e($label), e($value)))
            ->implode('');

        if ($formattedPayload) {
            $items .= sprintf(
                '<dt class="font-medium text-sm text-gray-500">%s</dt><dd class="mb-4"><pre class="text-xs bg-gray-100 dark:bg-gray-800 rounded p-3 overflow-x-auto">%s</pre></dd>',
                __('Payload'),
                e($formattedPayload),
            );
        }

        if (! empty($record['exception'])) {
            $items .= sprintf(
                '<dt class="font-medium text-sm text-gray-500">%s</dt><dd class="mb-0"><pre class="text-xs bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-200 rounded p-3 overflow-x-auto">%s</pre></dd>',
                __('Exception'),
                e(Str::of($record['exception'])->trim()),
            );
        }

        return sprintf('<dl class="divide-y divide-gray-200 dark:divide-gray-700">%s</dl>', $items);
    }
}
