<?php

namespace Database\Seeders;

use App\Enums\CatalogOrigin;
use App\Enums\MoistureLevel;
use App\Enums\PlantCategory;
use App\Enums\PlantLifecycle;
use App\Enums\SoilType;
use App\Enums\SunExposure;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlantCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $demoUser = $this->resolveDemoUser();

        $this->seedSystemSpecies();
        $this->seedCommunitySpecies($demoUser);
    }

    private function resolveDemoUser(): User
    {
        $existing = User::where('email', '!=', '')->first();

        if ($existing !== null) {
            return $existing;
        }

        return User::factory()->create([
            'name' => 'Demo-Gärtnerin',
            'username' => 'demo_gaertnerin',
            'email' => 'demo@mygarden.local',
            'password' => Hash::make('password'),
        ]);
    }

    private function seedSystemSpecies(): void
    {
        $specs = $this->systemSpeciesData();

        foreach ($specs as $spec) {
            $varieties = $spec['varieties'] ?? [];
            unset($spec['varieties']);

            $archived = isset($spec['archived_at']);
            unset($spec['archived_at']);

            $key = isset($spec['botanical_name'])
                ? ['botanical_name' => $spec['botanical_name']]
                : ['common_name' => $spec['common_name'], 'origin' => CatalogOrigin::System->value];

            $species = PlantSpecies::updateOrCreate($key, array_merge($spec, [
                'origin' => CatalogOrigin::System->value,
                'created_by' => null,
            ]));

            if ($archived && $species->archived_at === null) {
                $species->forceFill(['archived_at' => now()])->save();
            }

            $this->seedVarieties($species, $varieties);
        }
    }

    private function seedCommunitySpecies(User $user): void
    {
        foreach ($this->communitySpeciesData() as $spec) {
            $varieties = $spec['varieties'] ?? [];
            unset($spec['varieties']);

            $key = isset($spec['botanical_name'])
                ? ['botanical_name' => $spec['botanical_name']]
                : ['common_name' => $spec['common_name'], 'origin' => CatalogOrigin::User->value];

            $species = PlantSpecies::updateOrCreate($key, array_merge($spec, [
                'origin' => CatalogOrigin::User->value,
                'created_by' => $user->id,
            ]));

            $this->seedVarieties($species, $varieties);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $varieties
     */
    private function seedVarieties(PlantSpecies $species, array $varieties): void
    {
        foreach ($varieties as $vSpec) {
            $archived = isset($vSpec['archived_at']);
            unset($vSpec['archived_at']);

            $createdBy = $vSpec['created_by'] ?? null;
            unset($vSpec['created_by']);

            $variety = PlantVariety::updateOrCreate(
                ['plant_species_id' => $species->id, 'name' => $vSpec['name']],
                array_merge($vSpec, [
                    'plant_species_id' => $species->id,
                    'origin' => $createdBy !== null ? CatalogOrigin::User->value : CatalogOrigin::System->value,
                    'created_by' => $createdBy,
                ]),
            );

            if ($archived && $variety->archived_at === null) {
                $variety->forceFill(['archived_at' => now()])->save();
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function systemSpeciesData(): array
    {
        return [
            [
                'common_name' => 'Echter Lavendel',
                'botanical_name' => 'Lavandula angustifolia',
                'alternate_name' => 'Schmalblättriger Lavendel',
                'category' => PlantCategory::Flower->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Mediterranes Halbstrauch-Gewächs mit intensiv aromatischen Blüten. Zieht Bienen und Schmetterlinge an.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Sandy->value, SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Dry->value, MoistureLevel::Normal->value],
                'height_min_cm' => 30,
                'height_max_cm' => 60,
                'hardiness_note' => 'Winterhart bis etwa −15 °C',
                'varieties' => [
                    ['name' => 'Hidcote', 'year_introduced' => 1950, 'description' => 'Kompakt, tiefviolett, sehr aromatisch. Ideal für Kräutergärten und Bordüren.'],
                    ['name' => 'Munstead', 'year_introduced' => 1916, 'description' => 'Früh blühend, kompakt, hellviolett. Besonders robust.'],
                    ['name' => 'Rosea', 'description' => 'Rosa Blütenfarbe – seltenere Variante mit zartem Duft.'],
                ],
            ],
            [
                'common_name' => 'Hortensie',
                'botanical_name' => 'Hydrangea macrophylla',
                'category' => PlantCategory::Shrub->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Großblättrige Hortensie mit üppigen Blütenbällen. Blütenfarbe variiert je nach Boden-pH.',
                'sun_exposures' => [SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Moist->value],
                'height_min_cm' => 100,
                'height_max_cm' => 200,
                'hardiness_note' => 'Junge Triebe bei Spätfrost schützen',
                'varieties' => [
                    ['name' => 'Annabelle', 'description' => 'Große weiße Blütenbälle, sehr robust und pflegeleicht.'],
                    ['name' => 'Endless Summer', 'year_introduced' => 2004, 'description' => 'Wiederblühend, blau oder rosa je nach Boden-pH.', 'archived_at' => true],
                ],
            ],
            [
                'common_name' => 'Tomate',
                'botanical_name' => 'Solanum lycopersicum',
                'category' => PlantCategory::Vegetable->value,
                'lifecycle' => PlantLifecycle::Annual->value,
                'description' => 'Wärmeliebende Gemüsepflanze mit aromatischen Früchten. Gehört zu den meistangebauten Nutzpflanzen.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Normal->value, MoistureLevel::Moist->value],
                'height_min_cm' => 50,
                'height_max_cm' => 200,
                'varieties' => [
                    ['name' => 'Harzfeuer', 'description' => 'Klassische deutsche Fleischtomate, mittelgroß und sehr aromatisch.'],
                    ['name' => 'Sungold', 'description' => 'Kleine gelblich-orange Cocktailtomate mit süßem Aroma.'],
                    ['name' => 'Black Cherry', 'description' => 'Dunkelrote Cherrytomate mit intensivem Geschmack.'],
                ],
            ],
            [
                'common_name' => 'Apfelbaum',
                'botanical_name' => 'Malus domestica',
                'category' => PlantCategory::Fruit->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Kulturapfel als einer der wichtigsten Obstbäume in gemäßigten Breiten.',
                'sun_exposures' => [SunExposure::FullSun->value, SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 300,
                'height_max_cm' => 600,
                'varieties' => [
                    ['name' => 'Elstar', 'year_introduced' => 1972, 'description' => 'Aromatisch-süß mit leichter Säure. Gut für Frischverzehr und Saft.'],
                    ['name' => 'Jonagold', 'year_introduced' => 1968, 'description' => 'Groß, süß-säuerlich, saftig. Sehr beliebte Sorte.'],
                ],
            ],
            [
                'common_name' => 'Rasen',
                'botanical_name' => null,
                'category' => PlantCategory::Lawn->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Allgemeiner Sammeleintrag für Rasengräser und Rasenmischungen.',
                'sun_exposures' => [SunExposure::FullSun->value, SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Sandy->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 2,
                'height_max_cm' => 15,
            ],
            [
                'common_name' => 'Rosmarin',
                'botanical_name' => 'Salvia rosmarinus',
                'alternate_name' => 'Rosmarinus officinalis',
                'category' => PlantCategory::Herb->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Aromatisches Küchenkraut mit nadelförmigen Blättern und blauen Blüten.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Sandy->value, SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Dry->value, MoistureLevel::Normal->value],
                'height_min_cm' => 50,
                'height_max_cm' => 150,
                'hardiness_note' => 'In rauhen Lagen Winterschutz empfohlen',
            ],
            [
                'common_name' => 'Pfefferminze',
                'botanical_name' => 'Mentha × piperita',
                'category' => PlantCategory::Herb->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Intensiv aromatische Minzesorte. Bildet Ausläufer – am besten in Töpfen oder Rhizomsperre.',
                'sun_exposures' => [SunExposure::FullSun->value, SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Normal->value, MoistureLevel::Moist->value],
                'height_min_cm' => 30,
                'height_max_cm' => 60,
            ],
            [
                'common_name' => 'Sonnenblume',
                'botanical_name' => 'Helianthus annuus',
                'category' => PlantCategory::Flower->value,
                'lifecycle' => PlantLifecycle::Annual->value,
                'description' => 'Großblumige Sommerpflanze mit auffälligen gelben Blütenköpfen. Samenstände sind für Vögel wertvoll.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Sandy->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 100,
                'height_max_cm' => 300,
            ],
            [
                'common_name' => 'Buchsbaum',
                'botanical_name' => 'Buxus sempervirens',
                'category' => PlantCategory::Hedge->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Immergrüner Strauch, traditionell für Formschnitte und Hecken genutzt.',
                'sun_exposures' => [SunExposure::FullSun->value, SunExposure::PartialShade->value, SunExposure::Shade->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Mixed->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 50,
                'height_max_cm' => 200,
            ],
            [
                'common_name' => 'Efeu',
                'botanical_name' => 'Hedera helix',
                'category' => PlantCategory::Climber->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Immergrüner Kletterer. Bedeckt Wände, wächst als Bodendecker und bietet Vögeln Schutz.',
                'sun_exposures' => [SunExposure::PartialShade->value, SunExposure::Shade->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 200,
                'height_max_cm' => 1000,
                'hardiness_note' => 'Sehr winterhart',
            ],
            [
                'common_name' => 'Seerose',
                'botanical_name' => 'Nymphaea spec.',
                'category' => PlantCategory::Aquatic->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Schwimmende Wasserpflanze mit großen Blüten. Beschattet das Wasser und hemmt Algenwachstum.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => null,
                'moisture_levels' => [MoistureLevel::Wet->value],
                'height_min_cm' => 10,
                'height_max_cm' => 30,
                'hardiness_note' => 'Frost auf Wasseroberfläche schützen oder ins Haus holen',
            ],
            [
                'common_name' => 'Zimmerpalme',
                'botanical_name' => 'Chamaedorea elegans',
                'alternate_name' => 'Bergpalme',
                'category' => PlantCategory::Houseplant->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Schlanke, anspruchslose Zimmerpflanze für schattige Standorte.',
                'sun_exposures' => [SunExposure::PartialShade->value, SunExposure::Shade->value],
                'soil_types' => [SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 100,
                'height_max_cm' => 200,
                'hardiness_note' => 'Nicht winterhart – ausschließlich als Zimmerpflanze',
            ],
            [
                'common_name' => 'Rose',
                'botanical_name' => 'Rosa spec.',
                'category' => PlantCategory::Flower->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Klassischer Garten-Zierstrauch mit duftenden Blüten in vielfältigen Farben.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 50,
                'height_max_cm' => 200,
            ],
            [
                'common_name' => 'Kartoffel',
                'botanical_name' => 'Solanum tuberosum',
                'category' => PlantCategory::Vegetable->value,
                'lifecycle' => PlantLifecycle::Annual->value,
                'description' => 'Wichtige Nutzpflanze. Die Stärkeknollen reifen unterirdisch.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Sandy->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 40,
                'height_max_cm' => 80,
            ],
            [
                'common_name' => 'Basilikum',
                'botanical_name' => 'Ocimum basilicum',
                'category' => PlantCategory::Herb->value,
                'lifecycle' => PlantLifecycle::Annual->value,
                'description' => 'Wärmeliebendes Küchenkraut. Klassische Begleitung zur Tomate.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Normal->value, MoistureLevel::Moist->value],
                'height_min_cm' => 20,
                'height_max_cm' => 60,
                'hardiness_note' => 'Nicht winterhart – empfindlich gegenüber Kälte und Zugluft',
            ],
            [
                'common_name' => 'Silberbirke',
                'botanical_name' => 'Betula pendula',
                'category' => PlantCategory::Tree->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Heimischer Laubbaum mit charakteristischer weißer Rinde. Schnell wachsend.',
                'sun_exposures' => [SunExposure::FullSun->value, SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Sandy->value, SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Normal->value, MoistureLevel::Dry->value],
                'height_min_cm' => 1000,
                'height_max_cm' => 2500,
                'hardiness_note' => 'Sehr winterhart',
            ],
            [
                'common_name' => 'Thymian',
                'botanical_name' => 'Thymus vulgaris',
                'category' => PlantCategory::Herb->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Würziges Küchenkraut und Heilpflanze. Sehr genügsam und trockenheitsresistent.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Sandy->value, SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Dry->value, MoistureLevel::Normal->value],
                'height_min_cm' => 10,
                'height_max_cm' => 40,
            ],
            [
                'common_name' => 'Erdbeere',
                'botanical_name' => 'Fragaria × ananassa',
                'alternate_name' => 'Gartenerdbeere',
                'category' => PlantCategory::Fruit->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Beliebtestes Sommerobst im Hausgarten. Bildet Ausläufer zur Vermehrung.',
                'sun_exposures' => [SunExposure::FullSun->value, SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Normal->value, MoistureLevel::Moist->value],
                'height_min_cm' => 15,
                'height_max_cm' => 30,
                'varieties' => [
                    ['name' => 'Elsanta', 'year_introduced' => 1981, 'description' => 'Großfrüchtig, fest, gut transportierbar. Dominante Handelssorte.'],
                    ['name' => 'Mieze Schindler', 'year_introduced' => 1925, 'description' => 'Alte Sorte mit intensivem Duft. Nur als Bestäubersorte verwendbar.'],
                ],
            ],
            [
                'common_name' => 'Kirschlorbeer',
                'botanical_name' => 'Prunus laurocerasus',
                'category' => PlantCategory::Hedge->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Schnell wachsender, immergrüner Heckenstrauch. Sichtschutz ganzjährig.',
                'sun_exposures' => [SunExposure::PartialShade->value, SunExposure::Shade->value],
                'soil_types' => [SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 100,
                'height_max_cm' => 500,
            ],
            [
                'common_name' => 'Fuchsie',
                'botanical_name' => 'Fuchsia spec.',
                'category' => PlantCategory::Flower->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Elegant hängende Blüten in Rosa, Violett und Weiß. Gut für Ampeln und Töpfe.',
                'sun_exposures' => [SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Humus->value, SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Normal->value, MoistureLevel::Moist->value],
                'height_min_cm' => 30,
                'height_max_cm' => 100,
                'hardiness_note' => 'Nur bedingt winterhart – Überwinterung bei +5 °C empfohlen',
            ],
            [
                'common_name' => 'Dahlie',
                'botanical_name' => 'Dahlia spec.',
                'category' => PlantCategory::Flower->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Üppige Sommerblüher mit sehr vielfältigen Blütenformen und -farben.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 30,
                'height_max_cm' => 120,
                'hardiness_note' => 'Knollen vor dem ersten Frost ausgraben und frostfrei einlagern',
            ],
            [
                'common_name' => 'Speisezwiebel',
                'botanical_name' => 'Allium cepa',
                'category' => PlantCategory::Vegetable->value,
                'lifecycle' => PlantLifecycle::Biennial->value,
                'description' => 'Grundlegendes Küchen- und Nutzgemüse. Wird meist einjährig kultiviert.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Sandy->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 30,
                'height_max_cm' => 60,
            ],
            [
                'common_name' => 'Heidelbeere',
                'botanical_name' => 'Vaccinium corymbosum',
                'alternate_name' => 'Kulturheidelbeere',
                'category' => PlantCategory::Fruit->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Beerenstrauch mit süß-herb schmeckenden Früchten. Braucht sauren Boden (pH 4–5).',
                'sun_exposures' => [SunExposure::FullSun->value, SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Humus->value, SoilType::Sandy->value],
                'moisture_levels' => [MoistureLevel::Normal->value, MoistureLevel::Moist->value],
                'height_min_cm' => 100,
                'height_max_cm' => 200,
            ],
            [
                'common_name' => 'Blauregen',
                'botanical_name' => 'Wisteria sinensis',
                'category' => PlantCategory::Climber->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Beeindruckender Kletterer mit langen duftenden Blütentrauben. Wird sehr alt und groß.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 500,
                'height_max_cm' => 2000,
                'hardiness_note' => 'Winterhart bis −20 °C',
            ],
            // Archivierte Species – hat keine Varieties, damit die fachliche Regel greift
            [
                'common_name' => 'Spargel',
                'botanical_name' => 'Asparagus officinalis',
                'category' => PlantCategory::Vegetable->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Mehrjähriges Stangengemüse. Ab dem dritten Jahr erntbar.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Sandy->value, SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 100,
                'height_max_cm' => 150,
                'archived_at' => true,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function communitySpeciesData(): array
    {
        return [
            [
                'common_name' => 'Tomatillo',
                'botanical_name' => 'Physalis philadelphica',
                'category' => PlantCategory::Vegetable->value,
                'lifecycle' => PlantLifecycle::Annual->value,
                'description' => 'Mexikanische Schale-Tomate. Zutaten für grüne Salsa. Benötigt zwei Pflanzen zur Befruchtung.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 80,
                'height_max_cm' => 150,
            ],
            [
                'common_name' => 'Japanischer Ahorn',
                'botanical_name' => 'Acer palmatum',
                'category' => PlantCategory::Shrub->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Eleganter Zierstrauch mit feingezackten Blättern und prachtvollem Herbstlaub.',
                'sun_exposures' => [SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Normal->value, MoistureLevel::Moist->value],
                'height_min_cm' => 100,
                'height_max_cm' => 500,
                'hardiness_note' => 'Empfindlich gegenüber Spätfrost und Staunässe',
            ],
            [
                'common_name' => 'Kapuzinerkresse',
                'botanical_name' => null,
                'category' => PlantCategory::Flower->value,
                'lifecycle' => PlantLifecycle::Annual->value,
                'description' => 'Rankende oder buschige Sommerblume. Blüten und Blätter sind essbar – scharf-würziger Geschmack.',
                'sun_exposures' => [SunExposure::FullSun->value, SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Sandy->value, SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Dry->value, MoistureLevel::Normal->value],
                'height_min_cm' => 30,
                'height_max_cm' => 300,
            ],
            [
                'common_name' => 'Winterharte Banane',
                'botanical_name' => 'Musa basjoo',
                'category' => PlantCategory::Other->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Tropisch wirkende Staude mit riesigen Blättern. Markanter Blickfang im Garten.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Moist->value, MoistureLevel::Wet->value],
                'height_min_cm' => 200,
                'height_max_cm' => 400,
                'hardiness_note' => 'Scheinbar winterhart mit Schutz bis −10 °C; stirbt oberirdisch zurück',
            ],
            [
                'common_name' => 'Zitronenmelisse',
                'botanical_name' => 'Melissa officinalis',
                'category' => PlantCategory::Herb->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Zitronig duftendes Küchenkraut und Heilpflanze. Ideal für Tee und Salate.',
                'sun_exposures' => [SunExposure::FullSun->value, SunExposure::PartialShade->value],
                'soil_types' => [SoilType::Loamy->value, SoilType::Humus->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 30,
                'height_max_cm' => 80,
            ],
            [
                'common_name' => 'Agapanthus',
                'botanical_name' => 'Agapanthus africanus',
                'alternate_name' => 'Schmucklilie',
                'category' => PlantCategory::Flower->value,
                'lifecycle' => PlantLifecycle::Perennial->value,
                'description' => 'Mediterran wirkende Staude mit blauen Kugelblüten. Gut für Töpfe und Kübel.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Normal->value],
                'height_min_cm' => 50,
                'height_max_cm' => 100,
                'hardiness_note' => 'Bedingt winterhart – bei starkem Frost einpacken oder einwintern',
            ],
            [
                'common_name' => 'Lauch',
                'botanical_name' => 'Allium ampeloprasum var. porrum',
                'category' => PlantCategory::Vegetable->value,
                'lifecycle' => PlantLifecycle::Biennial->value,
                'description' => 'Stangengemüse mit mildem Zwiebelaroma. Wichtiges Wintergemüse.',
                'sun_exposures' => [SunExposure::FullSun->value],
                'soil_types' => [SoilType::Loamy->value],
                'moisture_levels' => [MoistureLevel::Normal->value, MoistureLevel::Moist->value],
                'height_min_cm' => 40,
                'height_max_cm' => 80,
            ],
        ];
    }
}
