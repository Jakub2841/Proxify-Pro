<?php

namespace App\View\Components;

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

    /**
     * @return array<int, array{label: string, url: string, active: bool, icon: string}>
     */
    public function navItems(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'url' => route('dashboard'),
                'active' => request()->routeIs('dashboard'),
                'icon' => 'squares-2x2',
            ],
            [
                'label' => 'Proxies',
                'url' => route('proxies.index'),
                'active' => request()->routeIs('proxies.*'),
                'icon' => 'queue-list',
            ],
            [
                'label' => 'Sources',
                'url' => route('sources.index'),
                'active' => request()->routeIs('sources.*'),
                'icon' => 'circle-stack',
            ],
            [
                'label' => 'Exports',
                'url' => route('exports.index'),
                'active' => request()->routeIs('exports.*'),
                'icon' => 'arrow-down-tray',
            ],
            [
                'label' => 'API access',
                'url' => route('api-access'),
                'active' => request()->routeIs('api-access'),
                'icon' => 'code-bracket',
            ],
            [
                'label' => 'Settings',
                'url' => route('settings'),
                'active' => request()->routeIs('settings'),
                'icon' => 'cog-6-tooth',
            ],
        ];
    }
}
