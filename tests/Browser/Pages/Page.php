<?php

namespace Tests\Browser\Pages;


abstract class Page extends BasePage
{

    public static function siteElements(): array
    {
        return [
            '@element' => '#selector',
        ];
    }
}
