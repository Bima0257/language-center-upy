<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel sesi (slot) per hari
        Schema::create('exam_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_schedule_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('late_tolerance_minutes')->default(15);
            $table->integer('max_participants')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['exam_schedule_id', 'date']);
        });

        // 2. exam_schedules menjadi periode: rentang tanggal
        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });

        // 3. exam_sessions menunjuk ke sesi
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->foreignId('exam_schedule_slot_id')->nullable()->after('exam_schedule_id');
        });

        // 4. Backfill: jadwal lama → periode + 1 slot default, sessions → slot tersebut
        DB::transaction(function () {
            DB::table('exam_schedules')->orderBy('id')->chunkById(100, function ($schedules) {
                foreach ($schedules as $schedule) {
                    $startDate = substr((string) $schedule->scheduled_start, 0, 10);
                    $endDate = substr((string) $schedule->scheduled_end, 0, 10);

                    DB::table('exam_schedules')
                        ->where('id', $schedule->id)
                        ->update([
                            'start_date' => $startDate ?: null,
                            'end_date' => $endDate ?: null,
                        ]);

                    $slotId = DB::table('exam_schedule_slots')->insertGetId([
                        'exam_schedule_id' => $schedule->id,
                        'date' => $startDate,
                        'start_time' => substr((string) $schedule->scheduled_start, 11, 5),
                        'end_time' => substr((string) $schedule->scheduled_end, 11, 5),
                        'late_tolerance_minutes' => $schedule->late_tolerance_minutes ?? 15,
                        'max_participants' => $schedule->max_participants ?? 30,
                        'is_active' => $schedule->is_active ?? true,
                        'created_at' => $schedule->created_at,
                        'updated_at' => $schedule->updated_at,
                    ]);

                    DB::table('exam_sessions')
                        ->where('exam_schedule_id', $schedule->id)
                        ->whereNull('exam_schedule_slot_id')
                        ->update(['exam_schedule_slot_id' => $slotId]);
                }
            });
        });

        // 5. Perketat kolom
        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->dropColumn(['scheduled_start', 'scheduled_end', 'late_tolerance_minutes', 'max_participants']);
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->foreignId('exam_schedule_slot_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // 1. Kembalikan kolom waktu di exam_schedules
        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->dateTime('scheduled_start')->nullable();
            $table->dateTime('scheduled_end')->nullable();
            $table->integer('late_tolerance_minutes')->default(15);
            $table->integer('max_participants')->default(30);
        });

        // 2. Backfill dari slot pertama tiap periode
        DB::transaction(function () {
            DB::table('exam_schedule_slots')->orderBy('id')->chunkById(100, function ($slots) {
                foreach ($slots as $slot) {
                    DB::table('exam_schedules')
                        ->where('id', $slot->exam_schedule_id)
                        ->update([
                            'scheduled_start' => $slot->date.' '.substr((string) $slot->start_time, 0, 5).':00',
                            'scheduled_end' => $slot->date.' '.substr((string) $slot->end_time, 0, 5).':00',
                            'late_tolerance_minutes' => $slot->late_tolerance_minutes,
                            'max_participants' => $slot->max_participants,
                        ]);
                }
            });
        });

        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $fkRows = DB::select('select constraint_name from information_schema.key_column_usage where table_schema = database() and table_name = "exam_sessions" and column_name = "exam_schedule_slot_id"');
            foreach ($fkRows as $row) {
                DB::statement('alter table exam_sessions drop foreign key `'.$row->constraint_name.'`');
            }

            $table->dropColumn('exam_schedule_slot_id');
        });

        Schema::dropIfExists('exam_schedule_slots');
    }
};
