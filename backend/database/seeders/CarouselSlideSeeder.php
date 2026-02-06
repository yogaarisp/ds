<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarouselSlide;

class CarouselSlideSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            [
                'title' => 'Diskon Januari',
                'subtitle' => 'Hemat hingga 50%',
                'period_text' => 'Periode Promo: 1-31 Januari 2026',
                'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'badge_label' => 'Pengguna Baru',
                'badge_value' => 'DISKON 15.000',
                'badge_label_2' => 'Semua Pengguna',
                'badge_value_2' => 'DISKON 5.000',
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Menu Baru',
                'subtitle' => 'Coba sekarang!',
                'period_text' => 'Tersedia mulai Februari 2026',
                'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'badge_label' => 'New',
                'badge_value' => 'MENU TERBARU',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Spesial Imlek',
                'subtitle' => 'Gong Xi Fa Cai!',
                'period_text' => 'Promo Imlek: 10-25 Feb 2026',
                'image' => 'https://images.unsplash.com/photo-1496116218417-7a17478ee177?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
                'badge_label' => 'Lucky Draw',
                'badge_value' => 'HADIAH EMAS',
                'badge_label_2' => 'Min. Order 100rb',
                'badge_value_2' => 'GRATIS ANGPAO',
                'start_date' => '2026-02-10',
                'end_date' => '2026-02-25',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($slides as $slide) {
            CarouselSlide::create($slide);
        }
    }
}
