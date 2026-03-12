<?php

declare(strict_types=1);

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private readonly Config $config;
    private readonly DatabaseManager $db;

    public function __construct()
    {
        $app = app();
        $this->config = $app->make(Config::class);
        $this->db = $app->make(DatabaseManager::class);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_users', static function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('password');
            $table->rememberToken();

            $table->boolean('activated')->default(false);
            $table->boolean('forbidden')->default(false);
            $table->string('language', 2)->default('en');

            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['email', 'deleted_at']);
        });

        $connection = $this->config->get('database.default');
        $driver = $this->config->get(sprintf('database.connections.%s.driver', $connection));
        if ($driver === 'pgsql') {
            //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
            Schema::table('admin_users', function (Blueprint $table): void {
                $this->db->statement(
                    //phpcs:ignore: SlevomatCodingStandard.Files.LineLength.LineTooLong
                    'CREATE UNIQUE INDEX admin_users_email_null_deleted_at ON admin_users (email) WHERE deleted_at IS NULL;',
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
