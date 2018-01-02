<?php

class Lib_splitpagseguro {

    var $token = "";
    private $app_id = 'dinner-for-friends';
    private $app_key = 'ED2625DB9494703EE47EDF9BAF1FE286';

    private $publicKey = 'PUBB54D521013344112A998681266A86F9C';
    /*private $app_id = 'app3481647419';
    private $app_key = '23EA6CDA8585F4B5545FBF81A250C632';
    private $publicKey = 'PUBAA20731AD137401E9CEF4030ACAFBEAA';*/
    
    var $authCode = false;
    var $carrinho = array();
    var $url_pagamento = "https://pagseguro.uol.com.br/v2/checkout/payment.html?code=";
    var $ws_pagseguro = 'https://ws.pagseguro.uol.com.br';
    var $url_pagseguro = 'https://pagseguro.uol.com.br';
    var $ipn_data = array();
    var $erro = false;
    var $CI;
    var $logger = true;
    var $code = 0;

    private $permissoes = 'CREATE_CHECKOUTS,RECEIVE_TRANSACTION_NOTIFICATIONS,SEARCH_TRANSACTIONS,DIRECT_PAYMENT';
    public $redirectURL = "http://localhost.d4f.com.br/pagseguro/autorizacao/";

    public function __construct($config = array()) 
    {
        $this->CI =& get_instance();
        if($config){
            $this->app_key = $config['appKey'];
            $this->publicKey = $config['publicKey'];
        }
        if(ENVIRONMENT != 'production'){
            $this->app_id = 'app7407552515';
            $this->app_key = 'B0381A89E0E06CA774E3CFBD5274BC9B';
            //$this->app_id = 'facileme-social-commerce';
            //$this->app_key = '8480534A4F4F7D8FF4436FB7F65368B9';
            $this->publicKey = 'PUBA29A2F7DA71043DFAEABB5B957C543EF';
            /*$this->app_id = 'app3481647419';
            $this->app_key = '23EA6CDA8585F4B5545FBF81A250C632';
            $this->publicKey = 'PUBAA20731AD137401E9CEF4030ACAFBEAA';*/
            $this->ws_pagseguro = 'https://ws.sandbox.pagseguro.uol.com.br';
            $this->url_pagseguro = 'https://sandbox.pagseguro.uol.com.br';
        }
    }

    public function novaCompra($itens = array(), $dados_cliente = array()) {
        $this->carrinho = $itens;
        $this->carrinho['appId'] = $this->app_id;
        $this->carrinho['appKey'] = $this->app_key;
        //$this->carrinho['authorizationCode'] = $this->authCode;
        $this->carrinho['payment.mode'] = 'default';
        $this->carrinho['notificationURL'] = 'http://www.dinner4friends.com.br/api/pagamento/notificacoes';
        $this->carrinho['currency'] = "BRL";
        
        $this->carrinho['receiver[1].publicKey'] = $this->publicKey;
        $this->carrinho['receiver[1].split.rate'] = 0.00;
        $this->carrinho['receiver[1].split.fee'] = 0.00;

        if ($dados_cliente['cli_email'])
            $this->carrinho['sender.email'] = $this->parseEmail($dados_cliente['cli_email']);

        if ($dados_cliente['cli_nome'] and strstr($dados_cliente['cli_nome'], ' '))
            $this->carrinho['sender.name'] = utf8_decode($dados_cliente['cli_nome']);
        
        if ($dados_cliente['cli_cpf'])
            $this->carrinho['sender.CPF'] = preg_replace('/[^\d\s]/', '', $dados_cliente['cli_cpf']);
        
        if ($dados_cliente['cli_telefone']){
            $this->carrinho['sender.areaCode'] = substr($dados_cliente['cli_telefone'], 1,2);
            $this->carrinho['sender.phone'] = trim(str_replace('-', '',substr($dados_cliente['cli_telefone'], 4)));
        }

        if ($dados_cliente['cli_cep']){
            $this->carrinho['shipping.address.postalCode'] = $dados_cliente['cli_cep'];
            $this->carrinho['billingAddress.postalCode'] = $dados_cliente['cli_cep'];
        }

        if ($dados_cliente['cli_logradouro']){
            $this->carrinho['shipping.address.street'] = utf8_decode($dados_cliente['cli_logradouro']);
            $this->carrinho['billingAddress.street'] = utf8_decode($dados_cliente['cli_logradouro']);
        }

        if ($dados_cliente['cli_numero']){
            $this->carrinho['shipping.address.number'] = $dados_cliente['cli_numero'];
            $this->carrinho['billingAddress.number'] = $dados_cliente['cli_numero'];
        }

        if ($dados_cliente['cli_complemento']){
            $this->carrinho['shipping.address.complement'] = utf8_decode($dados_cliente['cli_complemento']);
            $this->carrinho['billingAddress.complement'] = utf8_decode($dados_cliente['cli_complemento']);
        }

        if ($dados_cliente['cli_cidade']){
            $this->carrinho['shipping.address.city'] = utf8_decode($dados_cliente['cli_cidade']);
            $this->carrinho['billingAddress.city'] = utf8_decode($dados_cliente['cli_cidade']);
        }

        if ($dados_cliente['cli_estado']){
            $this->carrinho['shipping.address.state'] = utf8_decode($dados_cliente['cli_estado']);
            $this->carrinho['billingAddress.state'] = utf8_decode($dados_cliente['cli_estado']);
        }

        if ($dados_cliente['cli_bairro']){
            $this->carrinho['shipping.address.district'] = utf8_decode($dados_cliente['cli_bairro']);
            $this->carrinho['billingAddress.district'] = utf8_decode($dados_cliente['cli_bairro']);
        }

        $this->carrinho['shipping.address.country'] = "BRA";
        $this->carrinho['billingAddress.country'] = "BRA";
        return $this->httprequest();
    }

    private function httprequest() 
    {
        $data = http_build_query($this->carrinho);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->ws_pagseguro."/transactions/");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 6);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
        //curl_setopt($curl, CURLOPT_HEADER, true);
        $result = curl_exec($curl);
        //$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (in_array($result, array("Unauthorized", "Forbidden"))) {
            $this->erro = "<p>Não Configurada corretamente, verifique sua integração</p>";
        } elseif($result == "Internal Server Error") {
            $this->erro = "<p>Erro no servidor do PagSeguro</p>";
        } else {
            $objXml = json_decode(json_encode(simplexml_load_string($result)));
            if (isset($objXml->error)) {
                $this->erro = '';
                if(is_array($objXml->error)){
                    foreach ($objXml->error as $item) {
                        $this->erro .= '<p>'.$item->message.'</p>';
                    }
                } else {
                    $this->erro .= '<p>'.$objXml->error->message.'</p>';
                }
            }
            return $objXml;
        }
    }

    public function getPublicKey($authorizationCode) 
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->ws_pagseguro."/v2/authorizations/{$authorizationCode}?appId={$this->app_id}&appKey={$this->app_key}");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 6);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
        $result = curl_exec($curl);
        curl_close($curl);
        $objXml = json_decode(json_encode(simplexml_load_string($result)));
        return $objXml->account->publicKey;
    }

    public function notificationPost() {
        $ci = & $this->CI;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->ws_pagseguro."/v3/transactions/notifications/{$ci->input->post('notificationCode')}?appId={$this->app_id}&appKey={$this->app_key}");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
        $result = trim(curl_exec($curl));
        curl_close($curl);

        $this->ipn_data = json_decode(json_encode(simplexml_load_string($result)));

        if (!isset($this->ipn_data->erro))
            return true;
        else
            return false;
    }
    public function getDirectSession() 
    {
        $dados['appId'] = $this->app_id;
        $dados['appKey'] = $this->app_key;
        //$dados['authorizationCode'] = $this->authCode;
        $curl = curl_init($this->ws_pagseguro.'/sessions/');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($dados));
        $result = curl_exec($curl);
        if($result == "Unauthorized" or $result == "Forbidden"){
            curl_close($curl);
            return false;
        } else {
            $json = json_decode(json_encode(simplexml_load_string($result)));
            curl_close($curl);
            return $json->id;
        }
    }

    public function authorizationRequest($reference) 
    {
        $post['appId'] = $this->app_id;
        $post['appKey'] = $this->app_key;
        $post['reference'] = $reference;
        $post['permissions'] = $this->permissoes;
        $post['redirectURL'] = 'https://www.dinnerforfriends.com.br/chef/pagseguro';
        $data = http_build_query($post);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->ws_pagseguro."/v2/authorizations/request");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
        $result = curl_exec($curl);
        curl_close($curl);
        $objJson = json_decode(json_encode(simplexml_load_string($result)));
        $objJson->redirectURL = $this->url_pagseguro."/v2/authorization/request.jhtml?code=".$objJson->code;
        return $objJson;
    }

    public function applicationAuthorization($notificationCode) 
    {
        $post['appId'] = $this->app_id;
        $post['appKey'] = $this->app_key;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->ws_pagseguro."/v2/authorizations/notifications/".$notificationCode."?".http_build_query($post));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
        $result = curl_exec($curl);
        curl_close($curl);
        $objXml = @simplexml_load_string($result);
        $ipn_data = false;
        if($objXml){
            $ipn_data = json_decode(json_encode($objXml));
        }
        return $ipn_data;
    }

    public function notificationAuthorization($authorizationCode)
    {
        //$authorizationCode = 'F7F936E6DDDD320774EF2F8FCC283F8B';
        $post['appId'] = $this->app_id;
        $post['appKey'] = $this->app_key;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://ws.pagseguro.uol.com.br/v2/authorizations/notifications/".$authorizationCode."?".http_build_query($post));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
        $result = curl_exec($curl);
        curl_close($curl);
        $objXml = @simplexml_load_string($result);
        return json_decode(json_encode($objXml));
    }

    public function authorization($authorizationCode)
    {
        //$authorizationCode = 'F7F936E6DDDD320774EF2F8FCC283F8B';
        $post['appId'] = $this->app_id;
        $post['appKey'] = $this->app_key;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://ws.pagseguro.uol.com.br/v2/authorizations/".$authorizationCode."?".http_build_query($post));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
        $result = curl_exec($curl);
        curl_close($curl);
        $objXml = @simplexml_load_string($result);
        return json_decode(json_encode($objXml));
    }

    private function logger($data) {
        $ci = & $this->CI;
        $ci->load->helper('file');
        try{
          $dados = serialize($data);
          $fp = fopen('application/logs/log_pagseguro.php', 'a+');
          fwrite($fp, "###########################################################################################\n");
          fwrite($fp, $dados."\n\n");
          fclose($fp);
        } catch(Exception $e) {}
    }
    
    private function parseEmail($email) 
    {
        if (ENVIRONMENT != "production")
            return explode("@", $email)[0] . "@sandbox.pagseguro.com.br";
        return $email;
    }

}

