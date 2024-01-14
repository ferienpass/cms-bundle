<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('contao', [
        'localconfig' => [
            'licenseAccepted' => true,
            'enableSearch' => false,
            'disableCron' => true,
            'adminEmail' => '%env(ADMIN_EMAIL)%',
            'dateFormat' => 'd.m.Y',
            'datimFormat' => 'd.m.Y H:i',
            'timeZone' => 'Europe/Berlin',
        ]
    ]);
};

//contao:
//  localconfig:
//    licenseAccepted: true
//    enableSearch: false
//    doNotCollapse: true
//    folderUrl: true
//    disableCron: true
//    installPassword: '%env(INSTALL_PASSWORD)%'
//    adminEmail: '%env(ADMIN_EMAIL)%'
//    dateFormat: "d.m.Y"
//    datimFormat: "d.m.Y H:i"
//    timeZone: 'Europe/Berlin'
//    maxFileSize: 20480000
//    gdMaxImgWidth: 6000
//    gdMaxImgHeight: 6000
//
//  error_level: 8181
//
//  image:
//    sizes:
//      _defaults:
//        densities: 1x, 2x
//        resize_mode: crop
//
//      offer_details:
//        width: 650
//        height: 210
//
//      host_offer_details:
//        width: 800
//        height: 100
//
//      greeting_avatar:
//        width: 400
//        height: 400
//
//      host_details_logo:
//        width: 300
//        height: 80
//        resizeMode: 'box'
