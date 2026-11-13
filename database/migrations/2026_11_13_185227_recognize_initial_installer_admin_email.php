<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $installation = DB::table('workspace_installation')->where('id', 1)->first();
        if ($installation === null) {
            return;
        }

        DB::table('users')->where('id', $installation->initial_admin_id)
            ->where('is_super_admin', true)
            ->whereNull('email_verified_at')
            ->whereNotExists(fn ($query) => $query->select('id')->from('administrative_audits')
                ->whereColumn('target_id', 'users.id')->where('target_type', 'user')->where('action', 'account.email_changed'))
            ->update(['email_verified_at' => $installation->completed_at]);
    }

    public function down(): void
    {
        // Verification may have been superseded by an account action.
    }
};
