<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FeatureRequestStatus as Status;
use App\Models\FeatureRequest;
use App\Models\Product;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The community members, in the order in which they vote. All accounts use the password "password".
     */
    private const array MEMBERS = [
        'anna' => 'Anna Becker',
        'mehmet' => 'Mehmet Yılmaz',
        'lena' => 'Lena Hoffmann',
        'jonas' => 'Jonas Richter',
        'sofia' => 'Sofia Wagner',
        'tobias' => 'Tobias Krüger',
        'demo' => 'Demo-Nutzer',
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Admin', 'email' => 'admin@featureboard.test']);

        $members = collect(self::MEMBERS)->map(fn (string $name, string $key) => User::factory()->create([
            'name' => $name,
            'email' => "{$key}@featureboard.test",
        ]));

        foreach ($this->products() as $data) {
            $product = Product::factory()->create(array_diff_key($data, ['requests' => true]));

            foreach ($data['requests'] as $index => [$title, $description, $status, $author, $votes]) {
                $featureRequest = FeatureRequest::factory()
                    ->for($product)
                    ->for($members->only($author)->firstOrFail(), 'author')
                    ->create([
                        'title' => $title,
                        'description' => $description,
                        'status' => $status,
                        'votes_count' => $votes,
                        'created_at' => now()->subDays(($index + 1) * 6),
                    ]);

                $members->except($author)->take($votes)->each(
                    fn (User $voter) => Vote::factory()->for($voter)->for($featureRequest)->create(),
                );
            }
        }
    }

    /**
     * Each request lists its title, description, status, author and number of votes.
     *
     * @return list<array{name: string, slug: string, description: string, requests: list<array{string, string, Status, string, int}>}>
     */
    private function products(): array
    {
        return [
            [
                'name' => 'Rechnungsbox',
                'slug' => 'rechnungsbox',
                'description' => 'Online-Rechnungen und Mahnwesen für Selbstständige und kleine Betriebe.',
                'requests' => [
                    ['Wiederkehrende Rechnungen automatisch versenden', 'Monatliche Rechnungen für Wartungsverträge sollen sich einmal anlegen lassen und danach automatisch verschickt werden.', Status::New, 'demo', 5],
                    ['E-Rechnung im Format ZUGFeRD erstellen', 'Geschäftskunden verlangen zunehmend elektronische Rechnungen. Der Export als ZUGFeRD würde uns viel Handarbeit ersparen.', Status::InProgress, 'anna', 6],
                    ['Mahnungen in mehreren Stufen', 'Nach Ablauf der Zahlungsfrist soll zuerst eine freundliche Erinnerung und später eine förmliche Mahnung erstellt werden.', Status::Planned, 'mehmet', 4],
                    ['Rechnungen als CSV für die Steuerberatung exportieren', 'Am Quartalsende brauche ich eine Liste aller Rechnungen, die ich an meine Steuerberaterin weitergeben kann.', Status::Done, 'lena', 5],
                    ['Kryptowährungen als Zahlungsart', 'Rechnungen sollten sich auch direkt in Bitcoin bezahlen lassen.', Status::Declined, 'jonas', 1],
                    ['Größere Logos im Rechnungskopf', 'Unser Logo wird im Kopfbereich zu klein dargestellt. Die Größe sollte frei einstellbar sein.', Status::New, 'sofia', 2],
                ],
            ],
            [
                'name' => 'Terminzeit',
                'slug' => 'terminzeit',
                'description' => 'Online-Terminbuchung für Praxen, Salons und Beratungsbüros.',
                'requests' => [
                    ['Erinnerung per SMS vor dem Termin', 'Viele Kundinnen und Kunden vergessen ihre Termine. Eine SMS am Vortag würde Ausfälle deutlich verringern.', Status::Planned, 'sofia', 6],
                    ['Warteliste für ausgebuchte Tage', 'Wenn ein Termin frei wird, sollen Personen auf der Warteliste automatisch benachrichtigt werden.', Status::New, 'tobias', 3],
                    ['Kalender mit Google und Outlook abgleichen', 'Gebuchte Termine sollen automatisch in meinem bestehenden Kalender erscheinen.', Status::Done, 'anna', 6],
                    ['Terminbestätigung mit eigenem Text', 'Die Bestätigungen sollen einen frei wählbaren Text enthalten, zum Beispiel einen Hinweis auf die Anfahrt.', Status::InProgress, 'demo', 3],
                    ['Vorauszahlung bei der Buchung', 'Bei langen Behandlungen möchten wir eine Anzahlung schon bei der Terminbuchung verlangen können.', Status::New, 'lena', 2],
                ],
            ],
            [
                'name' => 'Lagerblick',
                'slug' => 'lagerblick',
                'description' => 'Einfache Lagerverwaltung für kleine Händler und Werkstätten.',
                'requests' => [
                    ['Warnung bei niedrigem Lagerbestand', 'Wenn ein Artikel unter den Mindestbestand fällt, möchte ich rechtzeitig eine Benachrichtigung bekommen.', Status::InProgress, 'jonas', 5],
                    ['Artikel per Barcode mit dem Handy erfassen', 'Beim Wareneingang soll die Kamera des Smartphones den Barcode lesen, damit das Abtippen entfällt.', Status::Planned, 'tobias', 4],
                    ['Inventur auf mehrere Personen aufteilen', 'Bei der jährlichen Inventur sollen mehrere Personen gleichzeitig unterschiedliche Regale zählen können.', Status::New, 'mehmet', 1],
                    ['Lagerplätze mit eigenen Fächern anlegen', 'Regale sollen sich in Fächer unterteilen lassen, damit jeder Artikel schnell gefunden wird.', Status::Done, 'demo', 3],
                    ['Eigene 3D-Ansicht des Lagers', 'Das Lager sollte sich als begehbares 3D-Modell anzeigen lassen.', Status::Declined, 'sofia', 0],
                    ['Rückgaben von Kunden wieder einbuchen', 'Retouren sollen mit einem Klick zurück in den Bestand gebucht und mit einem Grund versehen werden.', Status::New, 'anna', 0],
                ],
            ],
        ];
    }
}
