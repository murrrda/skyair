<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryField;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Izgubljen prtljag' => [
                [
                    'field_name' => 'baggage_number',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Broj prtljaga',
                    'prompt' => 'Molimo unesite broj prtljaga sa prtljažne oznake (npr. BA123456).',
                    'validation_rule' => '/^[A-Z]{2}\d{6}$/i',
                ],
                [
                    'field_name' => 'flight_number',
                    'field_type' => 'reference',
                    'required' => true,
                    'display_label' => 'Broj leta',
                    'prompt' => 'Na koji let je prtljag trebao biti utovaren?',
                    'reference_table' => 'flights',
                    'reference_column' => 'id',
                ],
                [
                    'field_name' => 'checkin_time',
                    'field_type' => 'datetime',
                    'required' => true,
                    'display_label' => 'Vreme čekiranja',
                    'prompt' => 'Kada ste izvršili čekiranje za let (datum i vreme)?',
                ],
                [
                    'field_name' => 'baggage_description',
                    'field_type' => 'text',
                    'required' => false,
                    'display_label' => 'Opis prtljaga',
                    'prompt' => 'Opišite izgled prtljaga (boja, veličina, marka).',
                ],
            ],

            'Oštećen prtljag' => [
                [
                    'field_name' => 'baggage_number',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Broj prtljaga',
                    'prompt' => 'Molimo unesite broj prtljaga sa prtljažne oznake (npr. BA123456).',
                    'validation_rule' => '/^[A-Z]{2}\d{6}$/i',
                ],
                [
                    'field_name' => 'flight_number',
                    'field_type' => 'reference',
                    'required' => true,
                    'display_label' => 'Broj leta',
                    'prompt' => 'Na kom letu je prtljag oštećen?',
                    'reference_table' => 'flights',
                    'reference_column' => 'id',
                ],
                [
                    'field_name' => 'damage_description',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Opis oštećenja',
                    'prompt' => 'Opišite oštećenje na prtljagu.',
                ],
                [
                    'field_name' => 'damage_discovered_at',
                    'field_type' => 'datetime',
                    'required' => true,
                    'display_label' => 'Kada je oštećenje primećeno',
                    'prompt' => 'Kada ste primetili oštećenje (datum i vreme)?',
                ],
            ],

            'Kašnjenje leta' => [
                [
                    'field_name' => 'flight_number',
                    'field_type' => 'reference',
                    'required' => true,
                    'display_label' => 'Broj leta',
                    'prompt' => 'Koji let je kasnio?',
                    'reference_table' => 'flights',
                    'reference_column' => 'id',
                ],
                [
                    'field_name' => 'expected_departure',
                    'field_type' => 'datetime',
                    'required' => true,
                    'display_label' => 'Očekivano vreme polaska',
                    'prompt' => 'Koje je bilo planirano vreme polaska?',
                ],
                [
                    'field_name' => 'actual_departure',
                    'field_type' => 'datetime',
                    'required' => false,
                    'display_label' => 'Stvarno vreme polaska',
                    'prompt' => 'Kada je let stvarno poleteo (ako je poleteo)?',
                ],
                [
                    'field_name' => 'delay_impact',
                    'field_type' => 'text',
                    'required' => false,
                    'display_label' => 'Uticaj kašnjenja',
                    'prompt' => 'Da li je kašnjenje uticalo na vaše planove (npr. propušten povezujući let)?',
                ],
            ],

            'Otkazan let' => [
                [
                    'field_name' => 'flight_number',
                    'field_type' => 'reference',
                    'required' => true,
                    'display_label' => 'Broj leta',
                    'prompt' => 'Koji let je otkazan?',
                    'reference_table' => 'flights',
                    'reference_column' => 'id',
                ],
                [
                    'field_name' => 'booking_reference',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Broj rezervacije',
                    'prompt' => 'Koji je vaš broj rezervacije?',
                ],
                [
                    'field_name' => 'notification_received',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Da li ste obavešteni',
                    'prompt' => 'Da li ste bili obavešteni o otkazivanju leta i kada?',
                ],
                [
                    'field_name' => 'alternative_offered',
                    'field_type' => 'text',
                    'required' => false,
                    'display_label' => 'Ponuđena alternativa',
                    'prompt' => 'Da li vam je ponuđen alternativni let ili neko drugo rešenje?',
                ],
            ],

            'Refundacija' => [
                [
                    'field_name' => 'flight_number',
                    'field_type' => 'reference',
                    'required' => true,
                    'display_label' => 'Broj leta',
                    'prompt' => 'Za koji let tražite refundaciju?',
                    'reference_table' => 'flights',
                    'reference_column' => 'id',
                ],
                [
                    'field_name' => 'booking_reference',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Broj rezervacije',
                    'prompt' => 'Koji je vaš broj rezervacije?',
                ],
                [
                    'field_name' => 'refund_reason',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Razlog refundacije',
                    'prompt' => 'Koji je razlog za traženje refundacije?',
                ],
                [
                    'field_name' => 'amount',
                    'field_type' => 'number',
                    'required' => false,
                    'display_label' => 'Traženi iznos',
                    'prompt' => 'Koji iznos refundacije tražite (u EUR)?',
                    'validation_rule' => '/^\d+(\.\d{1,2})?$/',
                ],
            ],

            'Sedišta' => [
                [
                    'field_name' => 'flight_number',
                    'field_type' => 'reference',
                    'required' => true,
                    'display_label' => 'Broj leta',
                    'prompt' => 'Na koji let se odnosi problem sa sedištem?',
                    'reference_table' => 'flights',
                    'reference_column' => 'id',
                ],
                [
                    'field_name' => 'seat_number',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Broj sedišta',
                    'prompt' => 'Koji je broj vašeg sedišta (npr. 12A)?',
                    'validation_rule' => '/^\d{1,3}[A-Z]$/i',
                ],
                [
                    'field_name' => 'seat_issue',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Opis problema',
                    'prompt' => 'Opišite problem sa sedištem.',
                ],
            ],

            'Hrana / obrok' => [
                [
                    'field_name' => 'flight_number',
                    'field_type' => 'reference',
                    'required' => true,
                    'display_label' => 'Broj leta',
                    'prompt' => 'Na koji let se odnosi problem sa hranom?',
                    'reference_table' => 'flights',
                    'reference_column' => 'id',
                ],
                [
                    'field_name' => 'meal_type',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Tip obroka',
                    'prompt' => 'Koji tip obroka je bio u pitanju (npr. vegetarijanski, standardni)?',
                ],
                [
                    'field_name' => 'meal_issue',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Opis problema',
                    'prompt' => 'Opišite problem sa obrokom.',
                ],
            ],

            'Loyalty program' => [
                [
                    'field_name' => 'loyalty_card_number',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Broj loyalty kartice',
                    'prompt' => 'Koji je vaš broj loyalty kartice ili korisničkog naloga?',
                ],
                [
                    'field_name' => 'loyalty_issue',
                    'field_type' => 'text',
                    'required' => true,
                    'display_label' => 'Opis problema',
                    'prompt' => 'Opišite problem vezan za loyalty program (npr. nedostajući poeni, pogrešan status).',
                ],
                [
                    'field_name' => 'flight_number',
                    'field_type' => 'reference',
                    'required' => false,
                    'display_label' => 'Broj leta',
                    'prompt' => 'Ako se problem odnosi na konkretan let, koji je to let?',
                    'reference_table' => 'flights',
                    'reference_column' => 'id',
                ],
            ],

            'Ostalo' => [],
        ];

        foreach ($categories as $name => $fields) {
            $category = Category::firstOrCreate(['name' => $name]);

            foreach ($fields as $field) {
                CategoryField::firstOrCreate(
                    [
                        'category_id' => $category->id,
                        'field_name' => $field['field_name'],
                    ],
                    [
                        'field_type' => $field['field_type'],
                        'required' => $field['required'],
                        'display_label' => $field['display_label'],
                        'prompt' => $field['prompt'],
                        'validation_rule' => $field['validation_rule'] ?? null,
                        'reference_table' => $field['reference_table'] ?? null,
                        'reference_column' => $field['reference_column'] ?? null,
                    ],
                );
            }
        }
    }
}
