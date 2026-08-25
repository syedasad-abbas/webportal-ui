<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissionNames = [
        'contacts.view',
        'contacts.create',
        'contacts.edit',
        'contacts.delete',
        'contacts.comment',
        'contacts.labels',
    ];

    public function up(): void
    {
        // Merge user-owned duplicates before making phone numbers globally unique.
        DB::table('dialer_contacts')
            ->select('phone_normalized')
            ->groupBy('phone_normalized')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone_normalized')
            ->each(function (string $phone): void {
                $contacts = DB::table('dialer_contacts')
                    ->where('phone_normalized', $phone)
                    ->orderBy('id')
                    ->get();
                $keeper = $contacts->first();

                foreach ($contacts->slice(1) as $duplicate) {
                    DB::table('dialer_contact_comments')
                        ->where('dialer_contact_id', $duplicate->id)
                        ->update(['dialer_contact_id' => $keeper->id]);
                    DB::table('dialer_contacts')->where('id', $duplicate->id)->delete();
                }
            });

        DB::statement('ALTER TABLE dialer_contacts DROP CONSTRAINT IF EXISTS dialer_contacts_user_id_phone_normalized_unique');
        DB::statement('DROP INDEX IF EXISTS dialer_contacts_user_id_name_index');
        DB::statement('ALTER TABLE dialer_contacts DROP CONSTRAINT IF EXISTS dialer_contacts_user_id_foreign');
        DB::statement('ALTER TABLE dialer_contacts RENAME COLUMN user_id TO created_by');
        DB::statement('ALTER TABLE dialer_contacts ALTER COLUMN created_by DROP NOT NULL');
        DB::statement('ALTER TABLE dialer_contacts ADD CONSTRAINT dialer_contacts_created_by_foreign FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL');

        Schema::table('dialer_contacts', function (Blueprint $table): void {
            $table->unique('phone_normalized');
            $table->index('name');
        });

        Schema::create('dialer_contact_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dialer_contact_id')->constrained('dialer_contacts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->text('description');
            $table->json('changes')->nullable();
            $table->timestamps();
            $table->index(['dialer_contact_id', 'created_at']);
        });

        $now = now();
        foreach ($this->permissionNames as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['group_name' => 'Contacts', 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $roleIds = DB::table('roles')->whereIn('name', ['Admin', 'Superadmin'])->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('name', $this->permissionNames)->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app('cache')->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        Schema::dropIfExists('dialer_contact_activities');

        Schema::table('dialer_contacts', function (Blueprint $table): void {
            $table->dropUnique(['phone_normalized']);
            $table->dropIndex(['name']);
        });
        DB::statement('ALTER TABLE dialer_contacts DROP CONSTRAINT IF EXISTS dialer_contacts_created_by_foreign');
        DB::statement('ALTER TABLE dialer_contacts RENAME COLUMN created_by TO user_id');
        DB::statement('ALTER TABLE dialer_contacts ALTER COLUMN user_id SET NOT NULL');
        DB::statement('ALTER TABLE dialer_contacts ADD CONSTRAINT dialer_contacts_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');

        Schema::table('dialer_contacts', function (Blueprint $table): void {
            $table->unique(['user_id', 'phone_normalized']);
            $table->index(['user_id', 'name']);
        });

        $permissionIds = DB::table('permissions')->whereIn('name', $this->permissionNames)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        app('cache')->forget(config('permission.cache.key'));
    }
};
