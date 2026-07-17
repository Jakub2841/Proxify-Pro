<?php

namespace App\View\Components;

use App\Models\Setting;
use Illuminate\View\Component;
use Illuminate\View\View;

class Sidebar extends Component
{
    public function render(): View
    {
        return view('components.sidebar', [
            'navItems' => $this->navItems(),
        ]);
    }

    public function navItems(): array
    {
        $items = [
            [
                'label' => __('Dashboard'),
                'url' => route('dashboard'),
                'active' => request()->routeIs('dashboard'),
                'icon' => 'squares-2x2',
            ],
            [
                'label' => __('Sources'),
                'url' => route('sources.index'),
                'active' => request()->routeIs('sources.*'),
                'icon' => 'circle-stack',
            ],
        ];

        if (Setting::get('api_access', true)) {
            $items[] = [
                'label' => __('API access'),
                'url' => route('api-access'),
                'active' => request()->routeIs('api-access'),
                'icon' => 'code-bracket',
            ];
        }

        $items[] = [
            'label' => __('Settings'),
            'url' => route('settings'),
            'active' => request()->routeIs('settings'),
            'icon' => 'cog-6-tooth',
        ];

        return $items;
    }
}
