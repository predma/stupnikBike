<?php

namespace Database\Seeders;

use App\Models\Bike;
use App\Models\BikePrice;
use App\Models\Issue;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\ReservationSetting;
use App\Models\Setting;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('bike_reservation_setting')->delete();
        Issue::query()->delete();
        Notification::query()->delete();
        Reservation::query()->delete();
        ReservationSetting::query()->delete();
        Setting::query()->delete();
        BikePrice::query()->delete();
        Bike::query()->delete();
        Station::query()->delete();
        User::query()->delete();
        Schema::enableForeignKeyConstraints();

        $admin = User::query()->create([
            'name' => 'Admin Stupnik',
            'email' => 'admin@sibinjbike.local',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+385 91 000 0000',
            'is_active' => true,
            'api_token' => null,
        ]);

        $testUser = User::query()->create([
            'name' => 'Test User',
            'email' => 'testuser+codex@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'phone' => '+385 91 111 1111',
            'is_active' => true,
            'api_token' => null,
        ]);

        Setting::query()->create([
            'key' => 'app',
            'value' => [
                'contact_email' => 'rent@stupnik.bike',
                'terms_of_use' => "Korištenjem aplikacije Stupnik Bike korisnik potvrđuje da će bicikl koristiti odgovorno, u skladu s prometnim propisima i u dogovorenom terminu rezervacije.\n\nKorisnik je dužan vratiti bicikl u stanju u kojem ga je preuzeo te odmah prijaviti svaki kvar ili oštećenje kroz aplikaciju.\n\nOpćina Stupnik i administrator sustava mogu odbiti ili otkazati rezervaciju u slučaju zlouporabe, netočnih podataka ili nedostupnosti bicikla.",
            ],
        ]);

        $settingsBikes = collect([
            [
                'code' => 'P272A18261-L',
                'name' => 'BICIKL POLAR MIRAGE SPORT 27,5 Men anthracite 26',
                'size' => 'L',
            ],
            [
                'code' => 'P272A18261-M',
                'name' => 'BICIKL POLAR MIRAGE SPORT 27,5 Men anthracite 26',
                'size' => 'M',
            ],
        ])->map(function (array $bike): Bike {
            return Bike::query()->create([
                'station_id' => null,
                'code' => $bike['code'],
                'name' => $bike['name'],
                'size' => $bike['size'],
                'stock_quantity' => 20,
                'gear_count' => 21,
                'type' => 'standard',
                'status' => 'available',
                'battery_level' => null,
                'price_per_hour' => 0,
                'description' => 'Lager artikl za rezervacije.',
                'equipment' => 'Kaciga, lokot, prednje i zadnje svjetlo.',
                'technical_details' => 'Kotači 27,5; muški okvir; model Polar Mirage Sport; boja anthracite.',
                'image_url' => null,
                'last_service_at' => null,
            ]);
        });

        $dailySetting = ReservationSetting::query()->create([
            'name' => 'Dnevne rezervacije',
            'mode' => 'daily',
            'effective_from' => now()->toDateString(),
            'max_days_per_reservation' => 7,
            'slots' => null,
            'is_active' => true,
            'notes' => 'Dnevni model rezervacije za oba veličinska modela.',
        ]);
        $dailySetting->bikes()->sync($settingsBikes->pluck('id')->all());

        $hourlySetting = ReservationSetting::query()->create([
            'name' => 'Satne rezervacije',
            'mode' => 'hourly',
            'effective_from' => now()->addDay()->toDateString(),
            'max_days_per_reservation' => 1,
            'slots' => [
                ['start' => '08:00', 'end' => '10:00'],
                ['start' => '10:00', 'end' => '12:00'],
                ['start' => '12:00', 'end' => '14:00'],
                ['start' => '14:00', 'end' => '16:00'],
                ['start' => '16:00', 'end' => '18:00'],
            ],
            'is_active' => true,
            'notes' => 'Satni model kreće od sutra.',
        ]);
        $hourlySetting->bikes()->sync($settingsBikes->pluck('id')->all());

        $settingsBikes->each(function (Bike $bike): void {
            BikePrice::query()->create([
                'bike_id' => $bike->id,
                'effective_from' => now()->toDateString(),
                'price' => 0,
                'billing_type' => 'daily',
                'is_active' => true,
                'notes' => 'Početna cijena. Promijeni u Cjeniku prije produkcije.',
            ]);
        });

        // Keep one short demo station list out of the way for now.
        collect([
            [
                'name' => 'Općina Stupnik - Centar',
                'slug' => 'stupnik-centar',
                'address' => 'Trg hrvatskih branitelja 1',
                'city' => 'Stupnik',
                'description' => 'Glavna stanica kod općine.',
            ],
        ])->each(fn (array $station) => Station::query()->create([
            ...$station,
            'is_active' => true,
        ]));

        // Leave clean users for auth testing and admin login.
        if ($testUser && $admin) {
            return;
        }
    }
}
