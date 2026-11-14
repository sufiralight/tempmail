<?php

namespace App\Http\Controllers;

use App\Administration\AuditLog;
use App\Administration\LanguageRegistry;
use App\Administration\UiTranslations;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LanguageController
{
    public function __construct(private LanguageRegistry $registry, private AuditLog $audit) {}

    public function index(Request $request): View
    {
        abort_unless($this->registry->ready(), 503);
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:active,inactive', 'direction' => 'nullable|in:ltr,rtl']);
        $query = $this->registry->query();
        $total = $query->count();
        $active = (clone $query)->where('is_active', true)->count();
        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(fn ($search) => $search->whereLike('name', $term)->orWhereLike('native_name', $term)->orWhereLike('code', $term));
        }
        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        if (isset($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        $languages = $query->orderBy('sort_order')->orderBy('id')->paginate(15)->withQueryString();
        $progress = app(UiTranslations::class)->ready()
            ? DB::table('ui_translation_values as translation')
                ->join('ui_translation_keys as catalog', 'catalog.id', '=', 'translation.key_id')
                ->whereIn('catalog.identifier', array_keys(config('ui_translations')))
                ->whereIn('translation.language_id', $languages->getCollection()->pluck('id'))
                ->where('translation.status', 'published')->where('translation.value', '<>', '')
                ->groupBy('translation.language_id')->selectRaw('translation.language_id, COUNT(*) as total')
                ->pluck('total', 'language_id')
            : null;

        return view('workspace.languages', [
            'languages' => $languages, 'progress' => $progress, 'homepageTotal' => count(config('ui_translations')),
            'defaultId' => (int) $this->registry->defaults()->value('language_id'),
            'total' => $total, 'active' => $active, 'filters' => $filters,
        ]);
    }

    public function show(int $language): View
    {
        abort_unless($this->registry->ready(), 503);
        $record = $this->registry->query()->find($language);
        abort_if($record === null, 404);

        return view('workspace.language', ['language' => $record, 'defaultId' => (int) $this->registry->defaults()->value('language_id')]);
    }

    public function update(Request $request, int $language): RedirectResponse
    {
        abort_unless($this->registry->ready(), 503);
        $data = $request->validate([
            'name' => 'required|string|max:100', 'native_name' => 'required|string|max:100',
            'direction' => 'required|in:ltr,rtl', 'sort_order' => 'required|integer|min:0|max:1000000',
            'is_active' => 'required|boolean', 'code' => 'missing',
        ]);
        $this->registry->change(function (int $default) use ($request, $language, $data): void {
            abort_unless($this->registry->query()->where('id', $language)->exists(), 404);
            $this->protect($default, [$language], (bool) $data['is_active'], 'is_active');
            $this->registry->query()->where('id', $language)->update([...$data, 'updated_at' => now()]);
            $this->audit->record($request->user(), 'language.updated', 'language', $language);
        });

        return to_route('admin.languages.show', $language)->with('status', 'Language saved.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        abort_unless($this->registry->ready(), 503);
        $data = $request->validate(['action' => 'required|in:activate,deactivate', 'languages' => 'required|array|list|max:30', 'languages.*' => 'required|integer|distinct|exists:languages,id']);
        $ids = array_map(intval(...), $data['languages']);
        $active = $data['action'] === 'activate';
        $this->registry->change(function (int $default) use ($request, $ids, $active): void {
            $this->protect($default, $ids, $active, 'languages');
            $this->registry->query()->whereIn('id', $ids)->update(['is_active' => $active, 'updated_at' => now()]);
            foreach ($ids as $id) {
                $this->audit->record($request->user(), $active ? 'language.activated' : 'language.deactivated', 'language', $id);
            }
        });

        return back()->with('status', 'Language visibility updated.');
    }

    public function makeDefault(Request $request, int $language): RedirectResponse
    {
        abort_unless($this->registry->ready(), 503);
        $this->registry->change(function () use ($request, $language): void {
            $record = $this->registry->query()->find($language);
            abort_if($record === null, 404);
            if (! $record->is_active) {
                throw ValidationException::withMessages(['is_active' => 'Activate this language before making it the default.']);
            }
            $this->registry->defaults()->update(['language_id' => $language]);
            $this->audit->record($request->user(), 'language.default_changed', 'language', $language);
        });

        return to_route('admin.languages.show', $language)->with('status', 'Default language updated.');
    }

    /** @param list<int> $ids */
    private function protect(int $default, array $ids, bool $active, string $field): void
    {
        if (! $active && in_array($default, $ids, true)) {
            throw ValidationException::withMessages([$field => 'The default language must stay active.']);
        }
    }
}
