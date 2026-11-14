<?php

namespace App\Http\Controllers;

use App\Administration\AuditLog;
use App\Administration\Typography;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class FontController
{
    public function __construct(private Typography $typography, private AuditLog $audit) {}

    public function index(Request $request): View
    {
        abort_unless($this->typography->ready(), 503);
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'script' => 'nullable|string|max:30', 'language' => 'nullable|integer|exists:languages,id']);
        $catalog = config('typography.families');
        $families = DB::table('font_families')->orderBy('name')->get();
        $languages = DB::table('languages')->orderBy('sort_order')->orderBy('id')->get();
        $language = isset($filters['language']) ? $languages->firstWhere('id', $filters['language']) : $languages->first();
        abort_if($language === null, 503);
        $script = $this->typography->script($language->code);
        $fonts = $this->typography->resolve($language->id, $language->code);

        return view('workspace.fonts', [
            'filters' => $filters, 'catalog' => $catalog, 'languages' => $languages,
            'language' => $language, 'script' => $script, 'fonts' => $fonts,
            'inherited' => $this->typography->inherited($script),
            'defaultsReady' => $this->typography->defaultsReady(),
            'stylesReady' => $this->typography->stylesReady(),
            'italicFamilies' => array_keys(array_filter($catalog, fn (array $scripts, string $family): bool => in_array('italic', $this->typography->styles($family), true), ARRAY_FILTER_USE_BOTH)),
            'stylesheet' => $this->typography->stylesheet($fonts),
            'assignments' => DB::table('font_assignments')->where('language_id', $language->id)->get()->keyBy('role'),
            'compatible' => $families->filter(fn ($family): bool => $family->is_active && in_array($script, $catalog[$family->name] ?? [], true)),
            'families' => $families->filter(fn ($family): bool => str_contains(strtolower($family->name), strtolower($filters['q'] ?? ''))
                && (! isset($filters['script']) || in_array($filters['script'], $catalog[$family->name] ?? [], true))),
            'scripts' => collect($catalog)->flatten()->unique()->sort(),
        ]);
    }

    public function assign(Request $request, int $language, string $role): RedirectResponse
    {
        abort_unless($this->typography->ready(), 503);
        abort_unless(in_array($role, Typography::ROLES, true), 404);
        $data = $request->validate([
            'font_family_id' => 'nullable|integer',
            'weight' => 'required|integer|in:400,600',
            'style' => 'sometimes|required|in:normal,italic',
            'fallback' => ['required', Rule::in(array_keys(Typography::FALLBACKS))],
        ]);

        DB::transaction(function () use ($request, $language, $role, $data): void {
            $familyId = $data['font_family_id'] ?? null;
            $family = $familyId === null ? null : DB::table('font_families')->lockForUpdate()->find($familyId);
            $locale = DB::table('languages')->lockForUpdate()->find($language);
            abort_if($locale === null, 404);
            if ($familyId !== null && (! $family?->is_active || ! in_array($this->typography->script($locale->code), config('typography.families')[$family->name] ?? [], true))) {
                throw ValidationException::withMessages(['font_family_id' => 'Select an active font that supports this writing system.']);
            }
            $style = $data['style'] ?? 'normal';
            if (! in_array($style, $this->typography->styles($family->name ?? null), true) || ($style !== 'normal' && ! $this->typography->stylesReady())) {
                throw ValidationException::withMessages(['style' => 'Select a style supported by this family and database schema.']);
            }

            DB::table('font_assignments')->updateOrInsert(['language_id' => $language, 'role' => $role], [
                ...($this->typography->stylesReady() ? ['style' => $style] : []),
                'font_family_id' => $familyId, 'weight' => $data['weight'], 'fallback' => $data['fallback'],
            ]);
            $this->audit->record($request->user(), 'font.assigned', 'language', $language, ['role' => $role]);
        }, 3);

        return to_route('admin.fonts.index', ['language' => $language])->with('status', 'Font assignment saved.');
    }

    public function saveDefault(Request $request, int $language): RedirectResponse
    {
        abort_unless($this->typography->ready() && $this->typography->defaultsReady(), 503);
        $data = $request->validate(['default_family_id' => 'required|integer']);
        DB::transaction(function () use ($request, $language, $data): void {
            $family = DB::table('font_families')->lockForUpdate()->find($data['default_family_id']);
            $locale = DB::table('languages')->lockForUpdate()->find($language);
            abort_if($locale === null, 404);
            $script = $this->typography->script($locale->code);
            if (! $family?->is_active || ! in_array($script, config('typography.families')[$family->name] ?? [], true)) {
                throw ValidationException::withMessages(['default_family_id' => 'Select a compatible active font.']);
            }
            DB::table('font_script_defaults')->upsert([
                ['script' => $script, 'font_family_id' => $family->id],
            ], ['script'], ['font_family_id']);
            $this->audit->record($request->user(), 'font.default_changed', 'language', $language, ['script' => $script]);
        }, 3);

        return to_route('admin.fonts.index', ['language' => $language])->with('status', 'Writing system default saved.');
    }

    public function reset(Request $request, int $language, string $role): RedirectResponse
    {
        abort_unless($this->typography->ready(), 503);
        abort_unless(in_array($role, Typography::ROLES, true), 404);
        DB::transaction(function () use ($request, $language, $role): void {
            abort_if(DB::table('languages')->lockForUpdate()->find($language) === null, 404);
            DB::table('font_assignments')->where('language_id', $language)->where('role', $role)->delete();
            $this->audit->record($request->user(), 'font.reset', 'language', $language, ['role' => $role]);
        }, 3);

        return to_route('admin.fonts.index', ['language' => $language])->with('status', 'Inherited typography restored.');
    }

    public function visibility(Request $request, int $family): RedirectResponse
    {
        abort_unless($this->typography->ready(), 503);
        $active = (bool) $request->validate(['is_active' => 'required|boolean'])['is_active'];
        DB::transaction(function () use ($request, $family, $active): void {
            abort_if(DB::table('font_families')->lockForUpdate()->find($family) === null, 404);
            if (! $active && DB::table('font_assignments')->where('font_family_id', $family)->exists()) {
                throw ValidationException::withMessages(['is_active' => 'Reset or replace this family’s assignments before deactivating it.']);
            }
            if (! $active && $this->typography->defaultsReady()
                && DB::table('font_script_defaults')->where('font_family_id', $family)->exists()) {
                throw ValidationException::withMessages(['is_active' => 'Replace this writing system default before deactivating it.']);
            }
            DB::table('font_families')->where('id', $family)->update(['is_active' => $active]);
            $this->audit->record($request->user(), $active ? 'font.activated' : 'font.deactivated', 'font', $family);
        }, 3);

        return back()->with('status', 'Font visibility updated.');
    }
}
