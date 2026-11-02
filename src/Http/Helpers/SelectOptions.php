<?php

namespace VentureDrake\LaravelCrm\Http\Helpers\SelectOptions;

use App\User;
use Carbon\Carbon;
use IntlTimeZone;
use Rinvex\Country\CountryLoader;
use Rinvex\Country\CurrencyLoader;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use VentureDrake\LaravelCrm\Models\Timezone;

function optionsFromModel($model, $null = true)
{
    $array = [];

    if ($null) {
        $array[''] = '';
    }

    if ($model) {
        foreach ($model as $item) {
            $array[$item->id] = $item->name;
        }
    }

    return $array;
}

function users($null = true)
{
    $array = [];

    if ($null) {
        $array[''] = '';
    }

    $users = [];

    if (config('laravel-crm.teams')) {
        if (auth()->user()->currentTeam) {
            $users = auth()->user()->currentTeam->allUsers();
        }
    } else {
        $users = User::all();
    }

    foreach ($users as $item) {
        $array[$item->id] = $item->name;
    }

    return $array;
}

function usersOptions($null = true)
{
    $items = [
        [
            'id' => 0,
            'name' => ucfirst(__('laravel-crm::lang.unallocated')),
        ],
    ];

    foreach (\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\users($null) as $id => $value) {
        $items[] = [
            'id' => $id,
            'name' => $value,
        ];
    }

    return $items;
}

function phoneTypes($null = true)
{
    $items = [];

    if ($null) {
        $items[] = [
            'id' => '',
            'name' => '',
        ];
    }

    $items = array_merge($items, [
        [
            'id' => 'work',
            'name' => 'Work',
        ],
        [
            'id' => 'home',
            'name' => 'Home',
        ],
        [
            'id' => 'mobile',
            'name' => 'Mobile',
        ],
        [
            'id' => 'fax',
            'name' => 'Fax',
        ],
        [
            'id' => 'other',
            'name' => 'Other',
        ],
    ]);

    return $items;
}

function emailTypes($null = true)
{
    $items = [];

    if ($null) {
        $items[] = [
            'id' => '',
            'name' => '',
        ];
    }

    $items = array_merge($items, [
        [
            'id' => 'work',
            'name' => 'Work',
        ],
        [
            'id' => 'home',
            'name' => 'Home',
        ],
        [
            'id' => 'other',
            'name' => 'Other',
        ],
    ]);

    return $items;
}

function countries()
{
    /* $countries = new Countries();
     $items = [];

     foreach ($countries->all()->pluck('name.common')->toArray() as $country) {
         $items[$country] = $country;
     }*/

    foreach (CountryLoader::countries() as $country) {
        if (! Countries::exists($country['iso_3166_1_alpha2'])) {
            continue; // user-assigned codes (XK) have no ICU name in any locale
        }

        // The id stays the stored English name; the reader sees ICU's name in their locale.
        $items[] = [
            'id' => $country['name'],
            'name' => Countries::getName($country['iso_3166_1_alpha2'], app()->getLocale()),
        ];
    }

    return $items;
}

function currencies()
{
    /*$countries = new Countries();
    $items = [];
    foreach ($countries->currencies()->sortBy('name')->toArray() as $currencyCode => $currency) {
        $items[$currencyCode] = $currency['name'].(' ('.$currencyCode.')');
    }*/

    // Allow for typo in package
    if (method_exists('Rinvex\Country\CurrencyLoader', 'curriencies')) {
        $currencies = CurrencyLoader::curriencies(true);
    } else {
        $currencies = CurrencyLoader::currencies(true);
    }

    $items = [];
    $locale = app()->getLocale();
    // CLDR keeps official Latin acronyms (CFA, BEAC) and leaves rare currencies
    // untranslated: a reader whose own currency name is not written in Latin
    // letters is spared those entries rather than shown words they cannot read.
    $latinReader = Currencies::exists('USD') && preg_match('/\p{Latin}/u', Currencies::getName('USD', $locale)) === 1;
    foreach ($currencies as $currency) {
        // The key stays the stored code; the reader sees ICU's name without it.
        $code = $currency['iso_4217_code'];
        $name = Currencies::exists($code)
            ? Currencies::getName($code, $locale)
            : $currency['iso_4217_name'];
        if (! $latinReader && preg_match('/\p{Latin}/u', $name) === 1) {
            continue;
        }
        $items[$code] = $name;
    }

    return $items;
}

function currencyOptions()
{
    $items = [];

    foreach (\VentureDrake\LaravelCrm\Http\Helpers\SelectOptions\currencies() as $id => $value) {
        $items[] = [
            'id' => $id,
            'name' => $value,
        ];
    }

    return $items;
}

function timezones()
{
    $items = [];

    $items[''] = '';

    foreach (Timezone::all() as $timezone) {
        // The key stays the stored identifier; the reader sees ICU's display name.
        $items[$timezone->name] = IntlTimeZone::createTimeZone($timezone->name)
            ->getDisplayName(false, IntlTimeZone::DISPLAY_LONG, app()->getLocale());
    }

    return $items;
}

function dateFormats()
{
    // The reader reads each format by its localized rendering, not its English one.
    $now = Carbon::now()->locale(app()->getLocale());

    return [
        'j F Y' => $now->translatedFormat('j F Y'),
        'Y-m-d' => $now->translatedFormat('Y-m-d'),
        'Y/m/d' => $now->translatedFormat('Y/m/d'),
        'm/d/Y' => $now->translatedFormat('m/d/Y'),
        'd/m/Y' => $now->translatedFormat('d/m/Y'),
        'F j, Y' => $now->translatedFormat('F j, Y'),
        'M j, Y' => $now->translatedFormat('M j, Y'),
    ];
}

function timeFormats()
{
    $now = Carbon::now()->locale(app()->getLocale());

    return [
        'g:i a' => $now->translatedFormat('g:i a'),
        'g:i A' => $now->translatedFormat('g:i A'),
        'H:i' => $now->translatedFormat('H:i'),
        'H:i:s' => $now->translatedFormat('H:i:s'),
    ];
}

function fieldModels()
{
    return [
        'VentureDrake\LaravelCrm\Models\Lead' => ucfirst(__('laravel-crm::lang.leads')),
        'VentureDrake\LaravelCrm\Models\Deal' => ucfirst(__('laravel-crm::lang.deals')),
        'VentureDrake\LaravelCrm\Models\Quote' => ucfirst(__('laravel-crm::lang.quotes')),
        'VentureDrake\LaravelCrm\Models\Order' => ucfirst(__('laravel-crm::lang.orders')),
        'VentureDrake\LaravelCrm\Models\Person' => ucfirst(__('laravel-crm::lang.people')),
        'VentureDrake\LaravelCrm\Models\Organization' => ucfirst(__('laravel-crm::lang.organizations')),
        'VentureDrake\LaravelCrm\Models\Product' => ucfirst(__('laravel-crm::lang.products')),
    ];
}
