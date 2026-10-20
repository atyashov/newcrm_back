<?php

namespace Database\Seeders;

use App\Modules\Admin\Subdivisions\Models\Subdivision;
use Illuminate\Database\Seeder;

class SubdivisionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subdivisions = [
            [
                'title' => 'Впрокат',
                'description' => '
<div style="line-height: 14px;font-weight:900;display: table-cell;font-size: 12px;padding: 10px 10px 10px 10px;">
                        Адрес: ул. 1-ая Ельцовка, 1, корпус Р<br>
                        Маршрут: <a href="https://go.2gis.com/6vs6p" target="_blank">2ГИС</a><br>
                        Телефон: <a href="tel:+73832841009">+7 (383) 284 10 09</a> <br>
                        Whatsapp: <a href="whatsapp://send?phone=79833071662">+7 983 307 16 62 </a><br>
</div>
<div style="line-height: 14px;font-weight:900;display: table-cell;font-size: 12px;padding: 10px 10px 10px 10px;">
                        Сайт: <a href="http://wprokat.ru" target="_blank">www.wprokat.ru</a><br>
                        Email: <a href="mailto:info@wprokat.ru">info@wprokat.ru</a><br>
                        Вконтакте: <a href="http://vk.com/wprokat" target="_blank">www.vk.com/wprokat</a><br>
                        Instagram: <a href="https://instagram.com/wprokat" target="_blank">https://instagram.com/wprokat</a>
</div>',
                'amo_ids' => '1190866, 3273166',
                'sms_sender_name' => 'Впрокат',
                'site_link' => 'https://wprokat.ru',

            ],
            [
                'title' => 'Flowersgift',
                'description' => '<div style="vertical-align: middle;">
<strong>
                        Адрес: Красный проспект, 159 (пл. Калинина)<br>
                        Маршрут: <a href="https://go.2gis.com/irpu7f" target="_blank">2ГИС</a><br>
                        Телефон: +7 (383) 285 99 39<br>
                        Сайт: <a href="https://flowersgift.ru" target="_blank">www.flowersgift.ru</a><br>
                        Email: <a href="mailto:info@flowersgift.ru">info@flowersgift.ru</a><br>
                        Вконтакте: <a href="http://vk.com/flowersgift" target="_blank">www.vk.com/flowersgift</a><br>
                        Instagram: <a href="https://instagram.com/flowersgift.ru" target="_blank">https://instagram.com/flowersgift.ru</a>
</strong>
</div>',
                'amo_ids' => '1076179',
                'sms_sender_name' => 'Flowersgift',
                'site_link' => 'https://flowersgift.ru',
            ],
            [
                'title' => 'Слова',
                'amo_ids' => '645631, 3863020',
                'site_link' => 'https://words54.ru, https://opt.words54.ru',
            ],
            [
                'title' => 'Tentmobile',
                'amo_ids' => '1000471',
                'description' => '
<strong>
                       ООО "Группа компаний Впрокат"<br>
                        Адрес: ул. 1-ая Ельцовка, 1, корпус Р<br>
                        Маршрут: <a href="https://go.2gis.com/6vs6p" target="_blank">2ГИС</a><br>
                        Телефон: +7 (383) 285 60 30 <br>
                        Whatsapp:  <a href="https://api.whatsapp.com/send?phone=79529416773" target="_blank"> +79529416773 </a><br>
                        Сайт: <a href="http://tentmobile.ru" target="_blank">www.tentmobile.ru</a><br>
                        Email: <a href="mailto:info@tentmobile.ru">info@tentmobile.ru</a><br>
</strong>',
                'site_link' => 'https://tentmobile.ru',
            ],
        ];
        foreach ($subdivisions as $subdivision) {
            $exists = Subdivision::query()->whereRaw('LOWER(title) = LOWER(?)', [$subdivision['title']])->exists();
            if (! $exists) {
                Subdivision::query()->create($subdivision);
            }
        }
    }
}
