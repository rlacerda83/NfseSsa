<?php

namespace Rlacerda83\NfseSsa\Services;


use Rlacerda83\NfseSsa\MySoapClient;
use Rlacerda83\NfseSsa\Request\Response;
use Rlacerda83\NfseSsa\Request\Error;
class RequestService
{

    /**
     * @var string
     */
    public $certificatePrivate;

    /**
     * @var string
     */
    private $urlBase;

    /**
     * @var array
     */
    private $soapOptions;


    public function __construct(array $config = [])
    {
        // Prioriza a configuração injetada, usando o config() global como fallback.
        $isHomologacao = $config['homologacao'] ?? config('nfse-ssa.homologacao');

        if ($isHomologacao == true) {
            $this->urlBase = 'https://notahml.salvador.ba.gov.br';
        } else {
            $this->urlBase = 'https://nfse.salvador.ba.gov.br';
        }

        // Pega as opções do SOAP diretamente da configuração injetada, se existirem.
        if (isset($config['soapOptions'])) {
            $this->soapOptions = $config['soapOptions'];
        } else {
            // Se não houver, monta o array da forma antiga para manter a compatibilidade.
            $this->certificatePrivate = config('nfse-ssa.certificado_privado_path');
            
            // Adiciona a senha, que estava faltando no construtor original
            $password = config('nfse-ssa.certificado_senha');

            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            $this->soapOptions = [
                'keep_alive' => true,
                'trace' => true,
                'local_cert' => $this->certificatePrivate,
                'passphrase' => $password, // Adicionamos a senha
                'cache_wsdl' => WSDL_CACHE_NONE,
                'stream_context' => $context
            ];
        }
    }

    /**
     * @param $wsdlSuffix
     * @param $xml
     * @param $method
     * @param $return
     * @return Response
     */
    private function consult($wsdlSuffix, $xml, $method, $return)
    {
        // $localWsdlPath = config('nfse-ssa.wsdl_path');
        $localWsdlPath = __DIR__ . '/../resources/wsdl/ConsultaNfse.xml';
        
        // Check if a local path for the WSDL is configured and if the file exists
        if ($localWsdlPath && file_exists($localWsdlPath)) {            
            $wsdl = $localWsdlPath;
        } else {
            $wsdl = $this->urlBase . $wsdlSuffix;
        }
        // dd($this->soapOptions); 

        $client = new MySoapClient($wsdl, $this->soapOptions);

        $params = new \SoapVar($xml, XSD_ANYXML);

        $result = call_user_func_array([$client, $method], [$params]);

        $responseContent = null;
        if (is_object($result) && isset($result->{$return})) {
            $responseContent = $result->{$return};
        } else {
            $rawXml = $client->__getLastResponse();
            $openingTag = "<{$return}>";
            $closingTag = "</{$return}>";
            $start = strpos($rawXml, $openingTag);
            if ($start !== false) {
                $start += strlen($openingTag);
                $end = strpos($rawXml, $closingTag, $start);
                if ($end !== false) {
                    $responseContent = substr($rawXml, $start, $end - $start);
                }
            }
        }

        $xmlObj = simplexml_load_string(html_entity_decode($responseContent));

        $response = new \Rlacerda83\NfseSsa\Request\Response();
        $response->setXmlContent($responseContent);

        if (isset($xmlObj->ListaMensagemRetorno)) {
            $response->setStatus(false);
            foreach ($xmlObj->ListaMensagemRetorno->MensagemRetorno as $mensagem) {
                $error = new \Rlacerda83\NfseSsa\Request\Error();
                $arr = get_object_vars($mensagem);
                $error->codigo = $arr['Codigo'];
                $error->mensagem = $arr['Mensagem'];
                $error->correcao = $arr['Correcao'];
                $response->addError($error);
            }
        } else {
            $response->setStatus(true);
            $json = json_encode($xmlObj);
            $data = json_decode($json, true);
            $response->setData($data);
        }

        return $response;
    }


    /**
     * @param $xml
     * @param $mainTagName
     * @return string
     */
    private function generateXmlBody($xml, $mainTagName, $subTagName)
    {
        return "
            <$mainTagName xmlns='http://tempuri.org/'>
                <$subTagName>
                  <![CDATA[$xml]]>
                </$subTagName>
            </$mainTagName>
        ";
    }


    /**
     * @param $xml
     * @return Response
     */
    public function enviarLoteRps($xml)
    {
        $wsdlSuffix = '/rps/ENVIOLOTERPS/EnvioLoteRPS.svc?wsdl';

        $finalXml = $this->generateXmlBody($xml, 'EnviarLoteRPS', 'loteXML');

        $response = $this->consult(
            $wsdlSuffix,
            $finalXml,
            'EnviarLoteRPS',
            'EnviarLoteRPSResult'
        );

        return $response;
    }

    /**
     * @param $xml
     * @return Response
     */
    public function consultarSituacaoLoteRps($xml)
    {
        $wsdlSuffix = '/rps/CONSULTASITUACAOLOTERPS/ConsultaSituacaoLoteRPS.svc?wsdl';

        $finalXml = $this->generateXmlBody($xml, 'ConsultarSituacaoLoteRPS', 'loteXML');

        $response = $this->consult(
            $wsdlSuffix,
            $finalXml,
            'ConsultarSituacaoLoteRPS',
            'ConsultarSituacaoLoteRPSResult'
        );

        return $response;
    }

    /**
     * @param $xml
     * @return Response
     */
    public function consultarLoteRps($xml)
    {
        $wsdlSuffix = '/rps/CONSULTALOTERPS/ConsultaLoteRPS.svc?wsdl';

        $finalXml = $this->generateXmlBody($xml, 'ConsultarLoteRPS', 'loteXML');

        $response = $this->consult(
            $wsdlSuffix,
            $finalXml,
            'ConsultarLoteRPS',
            'ConsultarLoteRPSResult'
        );

        return $response;
    }

    /**
     * @param $xml
     * @return Response
     */
    public function consultarNfseRps($xml)
    {
        $wsdlSuffix = '/rps/CONSULTANFSERPS/ConsultaNfseRPS.svc?wsdl';

        $finalXml = $this->generateXmlBody($xml, 'ConsultarNfseRPS', 'consultaxml');

        $response = $this->consult(
            $wsdlSuffix,
            $finalXml,
            'ConsultarNfseRPS',
            'ConsultarNfseRPSResult'
        );

        return $response;
    }

    public function consultarNfse(array $dados)
    {
        $wsdlSuffix = '/rps/CONSULTANFSE/ConsultaNfse.svc?wsdl';

        $xml = $this->generateConsultarNfseXmlFromArray($dados);

        $finalXml = $this->generateXmlBody($xml, 'ConsultarNfse', 'consultaxml');

        $response = $this->consult(
            $wsdlSuffix,
            $finalXml,
            'ConsultarNfse',
            'ConsultarNfseResult'
        );
        
        return $response;
    }

    /**
     * Gera uma tag XML a partir de uma chave/valor de um array.
     */
    private function arrayToXmlTag($array, $key)
    {
        if (isset($array[$key]) && $array[$key] !== null && $array[$key] !== '') {
            $value = $array[$key];
            $studlyKey = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));
            return "<{$studlyKey}>{$value}</{$studlyKey}>";
        }
        return '';
    }

    /**
     * Gera o XML da consulta de NFS-e a partir de um array de dados.
     */
    private function generateConsultarNfseXmlFromArray(array $dados)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<ConsultarNfseEnvio xmlns="http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd">';
        $xml .= '<Prestador>';
        $xml .= $this->arrayToXmlTag($dados['prestador'], 'cnpj');
        $xml .= $this->arrayToXmlTag($dados['prestador'], 'inscricao_municipal');
        $xml .= '</Prestador>';
        $xml .= '<PeriodoEmissao>';
        $xml .= $this->arrayToXmlTag($dados['periodo_emissao'], 'data_inicial');
        $xml .= $this->arrayToXmlTag($dados['periodo_emissao'], 'data_final');
        $xml .= '</PeriodoEmissao>';
        $xml .= '</ConsultarNfseEnvio>';
        return $xml;
    }

}
