<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('audit.table', 'audit_logs');

        Schema::create($table, function (Blueprint $table) {
            // ULID primary key — sortable by time, collision-free, no sequential leak
            $table->ulid('id')->primary();

            // What happened
            $table->string('event', 32); // created | updated | deleted | restored | custom

            // Which model was affected (polymorphic)
            $table->string('auditable_type');
            $table->string('auditable_id');
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'audit_auditable_idx');

            // Who did it (polymorphic — supports any actor model)
            $table->string('actor_type')->nullable();
            $table->string('actor_id')->nullable();
            $table->index(['actor_type', 'actor_id', 'created_at'], 'audit_actor_idx');

            // Network context
            $table->string('actor_ip', 45)->nullable(); // supports IPv6

            // Multi-tenancy
            $table->string('tenant_id')->nullable();
            $table->index(['tenant_id', 'created_at'], 'audit_tenant_idx');

            // The diff — only changed fields stored
            $table->json('before')->nullable();
            $table->json('after')->nullable();

            // Request metadata
            $table->string('url', 2048)->nullable();
            $table->json('metadata')->nullable(); // tags, user_agent, custom fields

            // Timestamp only — no updated_at (audit logs are immutable)
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('audit.table', 'audit_logs'));
    }
};
