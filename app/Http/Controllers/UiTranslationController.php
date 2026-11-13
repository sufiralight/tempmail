<?php

namespace App\Http\Controllers;

use App\Administration\AuditLog;
use App\Administration\Typography;
use App\Administration\UiTranslations;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class UiTranslationController
{
    public function __construct(private UiTranslations $translations, private AuditLog $audit) {}

    public function index(Request $request): View|Response
    {
        if (! $this->translations->ready()) {
            return response()->view('workspace.ui-translations-unavailable', [], 503);
        }

        $filters = $request->validate([
            'language' => 'nullable|integer|exists:languages,id', 'q' => 'nullable|string|max:100',
            'group' => 'nullable|alpha_dash:ascii|max:50', 'status' => 'nullable|in:missing,draft,published,review',
            'edit' => 'nullable|integer|min:1',
        ]);
        $languages = DB::table('languages')->orderBy('sort_order')->orderBy('id')->get();
        $language = isset($filters['language']) ? $languages->firstWhere('id', $filters['language']) : $languages->first();
        abort_if($language === null, 503);
        $query = DB::table('ui_translation_keys as catalog')
            ->leftJoin('ui_translation_values as translation', fn ($join) => $join->on('catalog.id', '=', 'translation.key_id')->where('translation.language_id', $language->id))
            ->select('catalog.*', 'translation.value', 'translation.status', 'translation.updated_at');
        $entry = isset($filters['edit']) ? (clone $query)->where('catalog.id', $filters['edit'])->first() : null;
        abort_if(isset($filters['edit']) && $entry === null, 404);
        if (isset($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(fn ($search) => $search->whereLike('catalog.identifier', $term)->orWhereLike('catalog.source', $term));
        }
        if (isset($filters['group'])) {
            $query->whereLike('catalog.identifier', $filters['group'].'.%');
        }
        if (isset($filters['status'])) {
            $filters['status'] === 'missing' ? $query->whereNull('translation.status') : $query->where('translation.status', $filters['status']);
        }
        $typography = app(Typography::class);
        $fonts = $entry && $typography->ready() ? $typography->resolve($language->id, $language->code) : [];

        return view('workspace.ui-translations', [
            'languages' => $languages, 'language' => $language, 'filters' => $filters, 'entry' => $entry,
            'statuses' => UiTranslations::STATUSES, 'fonts' => $fonts, 'stylesheet' => $typography->stylesheet($fonts),
            'groups' => DB::table('ui_translation_keys')->pluck('identifier')->map(fn (string $key): string => explode('.', $key, 2)[0])->unique()->sort(),
            'entries' => $query->orderBy('catalog.identifier')->paginate(15)->withQueryString(),
        ]);
    }

    public function update(Request $request, int $language, int $key): RedirectResponse
    {
        abort_unless($this->translations->ready(), 503);
        $data = $request->validate([
            'action' => 'required|in:save,reset',
            'value' => 'exclude_unless:action,save|required|string|max:4000',
            'status' => ['exclude_unless:action,save', 'required', Rule::in(array_keys(UiTranslations::STATUSES))],
        ]);
        DB::transaction(function () use ($request, $language, $key, $data): void {
            abort_if(DB::table('languages')->lockForUpdate()->find($language) === null, 404);
            $entry = DB::table('ui_translation_keys')->find($key);
            abort_if($entry === null, 404);
            $target = ['language_id' => $language, 'key_id' => $key];
            if ($data['action'] === 'reset') {
                DB::table('ui_translation_values')->where($target)->delete();
            } else {
                if ($this->translations->placeholders($data['value']) !== $this->translations->placeholders($entry->source)) {
                    throw ValidationException::withMessages(['value' => 'Keep the same named placeholders as the English source.']);
                }
                $now = now();
                DB::table('ui_translation_values')->upsert([[...$target, 'value' => $data['value'], 'status' => $data['status'], 'created_at' => $now, 'updated_at' => $now]], ['key_id', 'language_id'], ['value', 'status', 'updated_at']);
            }
            $this->audit->record($request->user(), 'ui_translation.'.$data['action'], 'ui_translation_key', $key, ['language_id' => (string) $language]);
        }, 3);

        return to_route('admin.ui-translations.index', ['language' => $language, 'edit' => $key])
            ->with('status', $data['action'] === 'reset' ? 'English source restored.' : 'Translation saved.');
    }
}
