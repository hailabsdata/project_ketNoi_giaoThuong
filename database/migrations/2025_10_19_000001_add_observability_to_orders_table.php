<?php
// database/migrations/2025_10_19_000001_add_observability_to_orders_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nếu bảng orders chưa tồn tại, tạo mới
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->decimal('total_amount', 15, 2);
                $table->string('status')->default('pending'); // pending, paid, shipped, completed, cancelled
                $table->uuid('correlation_id')->nullable()->index();
                $table->uuid('request_id')->nullable();
                $table->json('trace_metadata')->nullable();
                $table->timestamps();
            });
        } else {
            // Nếu đã tồn tại, chỉ thêm columns observability
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'correlation_id')) {
                    $table->uuid('correlation_id')->nullable()->index();
                }
                if (!Schema::hasColumn('orders', 'request_id')) {
                    $table->uuid('request_id')->nullable();
                }
                if (!Schema::hasColumn('orders', 'trace_metadata')) {
                    $table->json('trace_metadata')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn(['correlation_id', 'request_id', 'trace_metadata']);
            });
        }
    }
};

// database/migrations/2025_10_19_000002_add_observability_to_payments_table.php
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->string('status'); // pending, processing, completed, failed
                $table->string('transaction_id')->nullable();
                $table->string('payment_method')->nullable();
                $table->uuid('correlation_id')->nullable()->index();
                $table->uuid('request_id')->nullable();
                $table->json('trace_metadata')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'correlation_id')) {
                    $table->uuid('correlation_id')->nullable()->index();
                }
                if (!Schema::hasColumn('payments', 'request_id')) {
                    $table->uuid('request_id')->nullable();
                }
                if (!Schema::hasColumn('payments', 'trace_metadata')) {
                    $table->json('trace_metadata')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn(['correlation_id', 'request_id', 'trace_metadata']);
            });
        }
    }
};

// database/migrations/2025_10_19_000003_add_observability_to_shipments_table.php
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->onDelete('cascade');
                $table->string('carrier');
                $table->string('tracking_number')->nullable();
                $table->string('status')->default('pending'); // pending, picked_up, in_transit, delivered
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->uuid('correlation_id')->nullable()->index();
                $table->uuid('request_id')->nullable();
                $table->json('trace_metadata')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('shipments', function (Blueprint $table) {
                if (!Schema::hasColumn('shipments', 'correlation_id')) {
                    $table->uuid('correlation_id')->nullable()->index();
                }
                if (!Schema::hasColumn('shipments', 'request_id')) {
                    $table->uuid('request_id')->nullable();
                }
                if (!Schema::hasColumn('shipments', 'trace_metadata')) {
                    $table->json('trace_metadata')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shipments')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropColumn(['correlation_id', 'request_id', 'trace_metadata']);
            });
        }
    }
};

// database/migrations/2025_10_19_000004_create_event_logs_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->uuid('request_id')->index();
            $table->uuid('correlation_id')->index();
            $table->string('event_type'); // Full class name
            $table->json('payload');
            $table->json('metadata');
            $table->timestamps();
            
            // Composite index for tracing
            $table->index(['correlation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};