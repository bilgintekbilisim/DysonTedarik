<?php

namespace App\View\Composers;

use App\Enums\MenuLocation;
use App\Services\NavigationService;
use App\Services\SettingsService;
use App\Support\Seo;
use App\Support\WhatsApp;
use Illuminate\View\View;

/** Layout'un her sayfada ihtiyaç duyduğu ayar ve menüler (önbellekten okunur). */
class SiteComposer
{
    public function __construct(
        private SettingsService $settings,
        private NavigationService $navigation,
    ) {}

    public function compose(View $view): void
    {
        $view->with([
            'site' => $this->settings,
            'seo' => $view->getData()['seo'] ?? new Seo,
            'headerMenu' => $this->navigation->menu(MenuLocation::Header),
            'mobileMenu' => $this->navigation->menu(MenuLocation::Mobile),
            'footerMenus' => [
                'corporate' => $this->navigation->menu(MenuLocation::FooterCorporate),
                'quick' => $this->navigation->menu(MenuLocation::FooterQuick),
                'legal' => $this->navigation->menu(MenuLocation::FooterLegal),
            ],
            'menuCategories' => $this->navigation->categories(),
            'whatsappUrl' => WhatsApp::link(),
        ]);
    }
}
