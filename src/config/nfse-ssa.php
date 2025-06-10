<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Configuration
     |--------------------------------------------------------------------------
     |
     | By default, the package attempts to use the staging server.
     |
     */

    'homologacao' => env('NFSESSA_HOMOLOGACAO', true),

    'certificado_privado_path' => null,

    'certificado_publico_path' => null,

    /*
        Path to the local WSDL file.
        Leave as null to try downloading from the URL (original behavior).
    */
    'wsdl_path' => __DIR__ . '/../src/resources/wsdl/ConsultaNfse.xml',

];
