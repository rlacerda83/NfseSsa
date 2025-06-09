<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Configurações
     |--------------------------------------------------------------------------
     |
     | Por padrão o pacote tenta utilizar o servidor de homologação
     |
     */

    'homologacao' => env('NFSESSA_HOMOLOGACAO', true),

    'certificado_privado_path' => null,

    'certificado_publico_path' => null,

    /*
        Caminho para o arquivo WSDL local.
        Deixe como null para tentar baixar da URL (comportamento original).
    */
    'wsdl_path' => __DIR__ . '/../src/resources/wsdl/ConsultaNfse.xml',

];
