  <?php

  use Illuminate\Database\Migrations\Migration;
  use Illuminate\Database\Schema\Blueprint;
  use Illuminate\Support\Facades\Schema;

  return new class extends Migration
  {
      public function up(): void
      {
          Schema::table('emails', function (Blueprint $table) {
              // Agregar provider_message_id si no existe
              if (!Schema::hasColumn('emails', 'provider_message_id')) {
                  $table->string('provider_message_id')->nullable()->after('message_id');
              }

              // Agregar email_account_id si no existe como foreign key
              if (!Schema::hasColumn('emails', 'email_account_id')) {
                  $table->foreignId('email_account_id')->nullable()->after('user_id')->constrained('email_accounts')->onDelete('cascade');
              }

              // Crear índice único compuesto para evitar duplicados
              // Esto garantiza que no haya dos emails con el mismo provider_message_id y email_account_id
              $table->unique(['email_account_id', 'provider_message_id'], 'unique_email_per_account');
          });
      }

      public function down(): void
      {
          Schema::table('emails', function (Blueprint $table) {
              $table->dropUnique('unique_email_per_account');
          });
      }
  };;
