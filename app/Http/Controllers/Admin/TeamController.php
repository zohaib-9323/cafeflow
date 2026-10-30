<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Back-office accounts (super admin only). */
class TeamController extends Controller
{
    private const ROLES = [RoleEnum::SuperAdmin, RoleEnum::Manager, RoleEnum::Staff];

    public function index(): View
    {
        return view('admin.team.index', [
            'members' => User::query()->withRole(...self::ROLES)->withCount('assignedOrders')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.team.form', ['member' => new User(['is_active' => true]), 'roles' => self::ROLES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $member = User::query()->create($data);

        return redirect()->route('admin.team.index')->with('success', "{$member->name} can now sign in as {$member->roleEnum()->label()}.");
    }

    public function edit(User $user): View
    {
        abort_unless($user->isBackOffice(), 404);

        return view('admin.team.form', ['member' => $user, 'roles' => self::ROLES]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isBackOffice(), 404);
        $this->authorize('manage', $user);
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && ($data['role_id'] !== $user->role_id || ! $data['is_active'])) {
            return back()->withErrors(['role' => "You can't change your own role or deactivate yourself."]);
        }
        if (! filled($data['password'] ?? null)) {
            unset($data['password']);
        }
        $user->update($data);
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return redirect()->route('admin.team.index')->with('success', 'Team member saved.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isBackOffice(), 404);
        $this->authorize('delete', $user);
        $user->tokens()->delete();
        $user->delete(); // soft delete: order history keeps the name

        return redirect()->route('admin.team.index')->with('success', "{$user->name} removed.");
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(array_map(fn ($r) => $r->value, self::ROLES))],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ]);

        return [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role_id' => Role::idFor(RoleEnum::from($data['role'])),
            'password' => $data['password'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
