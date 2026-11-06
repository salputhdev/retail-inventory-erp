<?php

declare(strict_types=1);

use App\Support\Console\InteractsWithLocation;
use App\Support\Contracts\LocationAware;
use App\Support\Exceptions\LocationNotAllowed;
use App\Support\Exceptions\MissingLocationContext;
use App\Support\Jobs\RunsInLocation;
use App\Support\LocationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Identity\LocationFixtures;
use Tests\Fixtures\Models\SampleLocatedLine;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

/** Job contoh: mencatat nama baris yang terlihat dalam konteksnya. */
final class CountLinesJob implements LocationAware, ShouldQueue
{
    use Dispatchable, Queueable;

    /** @var list<array{location: int, names: list<string>}> */
    public static array $seen = [];

    public function __construct(private readonly int $locationId) {}

    public function locationId(): int
    {
        return $this->locationId;
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [new RunsInLocation];
    }

    public function handle(): void
    {
        self::$seen[] = ['location' => $this->locationId, 'names' => SampleLocatedLine::query()->pluck('name')->all()];
    }
}

/** Job tanpa middleware: harus gagal tertutup, bukan melihat semua lokasi. */
final class UnscopedJob
{
    use Dispatchable;

    public function handle(): void
    {
        SampleLocatedLine::query()->count();
    }
}

// ---- Kriteria 3: job queue dan command dengan konteks lokasi eksplisit, tanpa kebocoran
it('job berjalan hanya dalam lokasinya', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    CountLinesJob::dispatchSync($d['a']->id);
    CountLinesJob::dispatchSync($d['b']->id);

    expect(CountLinesJob::$seen)->toBe([
        ['location' => $d['a']->id, 'names' => ['A-1']],
        ['location' => $d['b']->id, 'names' => ['B-1']],
    ]);
});

it('konteks job tidak bocor ke eksekusi berikutnya', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    CountLinesJob::dispatchSync($d['a']->id);

    // Setelah job selesai tidak ada konteks tersisa: query tanpa konteks gagal tertutup.
    expect(fn () => SampleLocatedLine::query()->count())->toThrow(MissingLocationContext::class);
});

it('job tanpa konteks lokasi gagal tertutup (tidak melihat semua lokasi)', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    expect(fn () => UnscopedJob::dispatchSync())->toThrow(MissingLocationContext::class);
});

it('konteks scoped di-reset antar eksekusi (seperti worker queue per job)', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    $context = app(LocationContext::class);
    $context->run($d['a']->id, function () {
        // Simulasi worker: Laravel melupakan instance scoped sebelum job berikutnya.
        app()->forgetScopedInstances();
        expect(fn () => SampleLocatedLine::query()->count())->toThrow(MissingLocationContext::class);
    });
});

it('job di worker yang sama untuk dua lokasi berurutan tidak saling melihat', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    foreach ([$d['b']->id, $d['a']->id, $d['b']->id] as $id) {
        CountLinesJob::dispatchSync($id);
        app()->forgetScopedInstances();
    }

    expect(array_column(CountLinesJob::$seen, 'names'))->toBe([['B-1'], ['A-1'], ['B-1']]);
});

it('job yang dipasang middleware menolak lokasi di luar akses user yang sedang login', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    actingAs($d['userA']);
    expect(fn () => CountLinesJob::dispatchSync($d['b']->id))->toThrow(LocationNotAllowed::class);
});

it('command wajib menyebut lokasi dan hanya memproses lokasi itu', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    Artisan::registerCommand(new class extends Command
    {
        use InteractsWithLocation;

        protected $signature = 'test:count-lines {--location= : Kode lokasi}';

        public function handle(): int
        {
            $names = $this->runInLocation(fn () => SampleLocatedLine::query()->pluck('name')->implode(','));
            if ($names === null) {
                return self::FAILURE;
            }
            $this->line("lines={$names}");

            return self::SUCCESS;
        }
    });

    artisan('test:count-lines', ['--location' => 'LOCA'])->expectsOutput('lines=A-1')->assertExitCode(0);
    artisan('test:count-lines', ['--location' => 'LOCB'])->expectsOutput('lines=B-1')->assertExitCode(0);
    artisan('test:count-lines')->expectsOutput('Wajib menyebut lokasi: --location=KODE.')->assertExitCode(1);
    artisan('test:count-lines', ['--location' => 'TIDAKADA'])->expectsOutputToContain('tidak ditemukan')->assertExitCode(1);
});
