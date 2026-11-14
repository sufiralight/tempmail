<?php

namespace App\Http\Controllers;

use App\Administration\WorkspaceAdministration;
use App\Auth\WorkspaceRole;
use App\Http\Requests\UpdateWorkspaceUser;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class WorkspaceAdminController extends Controller
{
    public function overview(): View
    {
        return view('workspace.overview', [
            'accounts' => User::count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'workspaceName' => DB::table('workspace_settings')->where('id', 1)->value('display_name') ?? config('app.name'),
        ]);
    }

    public function users(Request $request, WorkspaceAdministration $administration): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'role' => 'nullable|in:user,admin,super_admin', 'status' => 'nullable|in:active,inactive']);
        $query = User::query()->select('id', 'name', 'email', 'role', 'is_super_admin', 'is_active', 'email_verified_at', 'avatar_media_id');
        if (! empty($filters['q'])) {
            $pattern = '%'.$filters['q'].'%';
            $query->where(fn ($query) => $query->whereLike('name', $pattern)->orWhereLike('email', $pattern));
        }
        if (! empty($filters['role'])) {
            $query->where('is_super_admin', $filters['role'] === 'super_admin');
            if ($filters['role'] !== 'super_admin') {
                $query->where('role', $filters['role']);
            }
        }
        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return view('workspace.users', ['users' => $query->orderBy('id')->paginate(20)->appends($filters), 'filters' => $filters, 'founderId' => $administration->founderId()]);
    }

    public function user(User $user, WorkspaceAdministration $administration): View
    {
        return view('workspace.user', ['account' => $user, 'founderId' => $administration->founderId()]);
    }

    public function updateUser(UpdateWorkspaceUser $request, User $user, WorkspaceAdministration $admin): RedirectResponse
    {
        $admin->updateUser($request->user(), $user, $request->enum('role', WorkspaceRole::class), $request->boolean('active'));

        return redirect()->route('admin.users.show', $user)->with('status', 'Account updated.');
    }

    public function roles(): View
    {
        $counts = User::query()->selectRaw('role, is_super_admin, COUNT(*) as total')->groupBy('role', 'is_super_admin')->get()
            ->groupBy(fn (User $user): string => $user->workspaceRole()->value)->map->sum('total');

        return view('workspace.roles', compact('counts'));
    }

    public function settings(): View
    {
        return view('workspace.settings', ['name' => DB::table('workspace_settings')->where('id', 1)->value('display_name') ?? config('app.name')]);
    }

    public function updateSettings(Request $request, WorkspaceAdministration $admin): RedirectResponse
    {
        $validated = $request->validate(['display_name' => 'required|string|max:80']);
        $admin->updateName($request->user(), $validated['display_name']);

        return redirect()->route('admin.settings')->with('status', 'Settings updated.');
    }

    public function audits(Request $request): View
    {
        $action = $request->validate(['action' => ['nullable', Rule::in(WorkspaceAdministration::ACTIONS)]])['action'] ?? null;
        $query = DB::table('administrative_audits')->orderByDesc('id');
        if ($action !== null) {
            $query->where('action', $action);
        }

        return view('workspace.audits', ['entries' => $query->paginate(20)->appends(['action' => $action]), 'action' => $action]);
    }
}
