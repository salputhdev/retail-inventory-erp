<?php

declare(strict_types=1);

use App\Support\Console\InteractsWithLocation;
use App\Support\Contracts\LocationAware;
use App\Support\Exceptions\LocationNotAllowed;
use App\Support\Exceptions\MissingLocationContext;
use App\Support\Jobs\CapturesLocationActor;
use App\Support\Jobs\RunsInLocation;
use App\Support\LocationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Tests\Feature\Identity\LocationFixtures;
use Tests\Fixtures\Models\SampleLocatedLine;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

/** Job contoh: mencatat nama baris yang terlihat dalam konteksnya. */
final class CountLinesJob implements LocationAware, ShouldQueue
{
    use CapturesLocationActor, Dispatchable, Queueable;

    /** @var list<array{location: int, names: list<string>}> */
    public static array $seen = [];

    public function __construct(int $locationId)
    {
        $this->bindLocation($locationId); // diperiksa saat DISPATCH, bukan hanya saat dijalankan (F2)
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [new RunsInLocation];
    }

    public function handle(): void
    {
        self::$seen[] = ['location' => $this->locationId(), 'names' => SampleLocatedLine::query()->pluck('name')->all()];
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

it('F2: user tidak bisa men-dispatch job untuk lokasi di luar aksesnya (dicek saat dispatch, bukan saat dijalankan)', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    actingAs($d['userA']);

    expect(fn () => new CountLinesJob($d['b']->id))->toThrow(LocationNotAllowed::class);
    expect(fn () => CountLinesJob::dispatch($d['b']->id))->toThrow(LocationNotAllowed::class);
});

it('F2: job async (diserialisasi, tanpa Auth di worker) memeriksa ulang akses pemicunya', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];

    // 1. Request user A men-dispatch job untuk lokasinya sendiri.
    actingAs($d['userA']);
    $payload = serialize(new CountLinesJob($d['a']->id));

    // 2. Worker: tidak ada user login, job di-unserialize lalu dijalankan.
    Auth::forgetGuards();
    app()->forgetScopedInstances();
    $job = unserialize($payload);
    expect($job->triggeredByUserId())->toBe($d['userA']->id);
    app(Dispatcher::class)->dispatchSync($job);
    expect(CountLinesJob::$seen)->toBe([['location' => $d['a']->id, 'names' => ['A-1']]]);
});

it('F2: akses yang dicabut antara dispatch dan eksekusi membuat job ditolak', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    actingAs($d['userA']);
    $payload = serialize(new CountLinesJob($d['a']->id));

    $d['userA']->locations()->detach($d['a']->id); // akses dicabut setelah dispatch
    Auth::forgetGuards();
    app()->forgetScopedInstances();

    expect(fn () => app(Dispatcher::class)->dispatchSync(unserialize($payload)))
        ->toThrow(LocationNotAllowed::class);
    expect(CountLinesJob::$seen)->toBe([]);
});

it('F2: pemicu yang sudah dihapus membuat job ditolak', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    actingAs($d['userA']);
    $payload = serialize(new CountLinesJob($d['a']->id));

    $d['userA']->locations()->detach();
    $d['userA']->delete();
    Auth::forgetGuards();
    app()->forgetScopedInstances();

    expect(fn () => app(Dispatcher::class)->dispatchSync(unserialize($payload)))
        ->toThrow(LocationNotAllowed::class);
});

it('F2: job terjadwal/console tanpa pemicu (sistem) tetap jalan di lokasinya', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    $job = new CountLinesJob($d['b']->id); // tanpa login: console
    expect($job->triggeredByUserId())->toBeNull();
    app(Dispatcher::class)->dispatchSync($job);
    expect(CountLinesJob::$seen)->toBe([['location' => $d['b']->id, 'names' => ['B-1']]]);
});

it('F2: dispatch dari request HTTP tanpa login ditolak', function () {
    $d = LocationFixtures::boot();
    CountLinesJob::$seen = [];
    expect(fn () => LocationFixtures::asHttpRequest(fn () => new CountLinesJob($d['a']->id)))
        ->toThrow(LocationNotAllowed::class);
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
