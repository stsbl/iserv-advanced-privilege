<?php

declare(strict_types=1);

use IServ\Bundle\AdminIntegration\Config\MenuConfigurator;
use IServ\Bundle\AdminIntegration\Config\MenuIcon;
use IServ\Bundle\AdminIntegration\Menu\Domain\AdminPage;

return static function (MenuConfigurator $config): void {
    $config
        ->get(AdminPage::MODULES->id())
        ->add(
            key: 'advanced-privilege',
            label: _('Advanced privilege assignment'),
            url: '/admin/advanced-privilege',
            icon: new MenuIcon('img/advanced-privilege.svg', 'app/public/static/manifest.json'),
            moduleId: 'stsbl/advanced-privilege',
        )
    ;
};
