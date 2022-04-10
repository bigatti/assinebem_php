<?php

require 'vendor/autoload.php';
use GuzzleHttp\Client;

class AuthAD {
	# Classe para autenticacao Assine Bem.
    public $ASSINE_BEM_SECRET = '';
    public $ASSINE_BEM_TOKEN = '';
    public $ASSINE_BEM_URL = 'https://www.assinebem.com.br/api';

    public function monta_security_hash($endpoint='', $query_string='')
    {
        $url_request = $this->ASSINE_BEM_URL.$endpoint;
        $token_acesso = hash('sha256', $url_request.$this->ASSINE_BEM_SECRET.$query_string);
        $security_hash = base64_encode($this->ASSINE_BEM_TOKEN.':'.$token_acesso);
        return $security_hash;
    }

    public function send_request($tipo_requisicao,$security_hash, $endpoint, $params)
    {
        $client = new Client();
        $url_endpoint = $this->ASSINE_BEM_URL.$endpoint;
        $headers = [ 'SECURITY-HASH' => $security_hash];

        # --- caso seja POST precisa enviar form_params
        if ($tipo_requisicao == 'POST') {
            $response = $client->request($tipo_requisicao, $url_endpoint, [
                'headers' => $headers,
                'verify'  => false,
                'form_params' => ($params ? $params : null)
            ]);
        # --- caso seja GET precisa enviar a query junto apenas.
        }else{
            $response = $client->request($tipo_requisicao, $url_endpoint, [
                'headers' => $headers,
                'query' => $params
            ]);
        }

        $json = json_decode($response->getBody(), true);

        return $json;
    }

    public function get_identifier()
    {
        # -- endpoint de solicitar identificacao do documento vazio na assine bem ---
        $url = '/documento/get_identifier_to_upload';
        $security_hash_fmt = $this->monta_security_hash($url, '');
        $result = $this->send_request('GET',$security_hash_fmt, $url, '');
        return $result['identifier'];
    }

    public function inserir_parte()
    {

        $params  = [
            'id_validacao_bloco' => '2',
            'id_tipo_telefone' => '2',
            'identificacao_parte' => 'Parte 1',
            'id_referencia' => 1,
            'nome' => 'Pessoa de teste',
            'rg' => '000000000',
            'cpf' => '',
            'email' => 'teste@teste.com.br',
            'ddd' => '11',
            'telefone' => '111111111'
        ];

        # -- endpoint de solicitar criacao da parte ---
        $url = '/parte';
        $security_hash_fmt = $this->monta_security_hash($url, '');
        $result = $this->send_request('POST', $security_hash_fmt, $url, $params);

        return $result;
    }

    public function consultar_status_parte($id_externo)
    {
        $params  = ['id_externo' => $id_externo];
        $url = '/parte/status';
        $security_hash_fmt = $this->monta_security_hash($url, 'id_externo='.$id_externo);
        $result = $this->send_request('GET', $security_hash_fmt, $url, $params);

        if ($result) {
            return $result['descricao'];
        }
        return Null;
    }

    public function download_documento($id_externo)
    {
        $params = [
            'id_externo' => $id_externo,
            'assinado'=> '1'
        ];

        $url = '/documento/download';
        $security_hash_fmt = $this->monta_security_hash($url, http_build_query($params));
        $result = $this->send_request('GET', $security_hash_fmt, $url, $params);
        return $result;
    }

    public function invalidar_documento($id_externo)
    {
        $params  = ['id_externo' => $id_externo];
        $url = '/documento/invalidar';
        $security_hash_fmt = $this->monta_security_hash($url, '');

        $result = $this->send_request('POST', $security_hash_fmt, $url, $params);

        if ($result) {
            return $result['mensagem'];
        }
        return Null;
    }

    public function usar_parte_existente($id_externo)
    {
        $result = [
            'id_validacao_bloco' => 2,
            'id_externo'=> $id_externo,
            'identificacao_parte'=> 'parte 1',
            'id_referencia'=> 'aaa111', 
            'ordem_assinatura' => 0
        ];

        return $result;
    }

    public function upload_documento($id_externo)
    {
    	# -- dados documento --
    	$documento = [
            'identificacao_arquivo' => $id_externo,
            'sufixo_arquivo'=> 'pdf',
            'url_documento'=> 'http://www.gbigatti.com/assinebem/teste.pdf',
            # 'file_path'=> '/assinebem/teste.pdf',
        ];


        # -- solicita identifier na assine bem
        $identifier = $this->get_identifier();

        $params = [
            'id_identifier' => $identifier,
            'identificacao_arquivo' => $documento['identificacao_arquivo'],
            'sufixo_arquivo' =>  $documento['sufixo_arquivo'],
            'lista_partes' => json_encode($this->usar_parte_existente($id_externo))
        ];

        if($documento['url_documento'] != ''){
            $params['url_arquivo'] = $documento['url_documento'];
        }else{
            # -- precisa pegar o arquivo em si, converter em md5 para enviar na request
            $file_base64 = base64_encode($documento['file_path']);
            $params['arquivo'] = $file_base64;
        }
        # -- endpoint de solicitar upload do documento ---
        $url = '/documento';
        $security_hash_fmt = $this->monta_security_hash($url, '');

        $result = $this->send_request('POST', $security_hash_fmt, $url, $params);
        return $result;
    }

    public function upload_documento_exemplo($id_externo)
    {
        $url_documento = 'http://www.gbigatti.com/assinebem/teste.pdf';

        # -- solicita identifier
        $identifier = $this->get_identifier();

        $params = [
            'id_identifier' => $identifier,
            'url_arquivo' => $url_documento,
            'identificacao_arquivo' => 'teste_'.date('d_m_Y_H_i'),
            'sufixo_arquivo' => 'pdf',
            'lista_partes' => json_encode($this->usar_parte_existente($id_externo))
        ];

        # -- endpoint de solicitar upload do documento ---
        $url = '/documento';
        $security_hash_fmt = $this->monta_security_hash($url, '');

        $result = $this->send_request('POST', $security_hash_fmt, $url, $params);
        return $result;
    }
}

$assine = new AuthAD;
$assine->get_identifier()

?>