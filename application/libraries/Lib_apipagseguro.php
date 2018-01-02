<?php

class Lib_apipagseguro{
  var $token = "3050054619E1421C9A9EC120FE4EFA26";
  var $email = "renato.frazao@uol.com.br";

  var $carrinho = array();
  var $return_api = array();
  var $ws_url= "https://ws.pagseguro.uol.com.br";
  var $url_pagamento = 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=';
  var $url_assinatura = "https://pagseguro.uol.com.br/v2/pre-approvals/request.html?code=";
  var $redirectURL = "";
  var $ipn_data = array();
  var $erro = false;
  var $code = false;
  var $CI;
  var $logger = true;

  public function __construct($config)
  {
      $this->CI =& get_instance();

      if($config){
          $this->email = $config['pagseguroEmail'];
          $this->token = $config['pagseguroToken'];
      }

      if(ENVIRONMENT != 'production'){
          $this->ws_url = "https://ws.sandbox.pagseguro.uol.com.br";
          $this->url_pagamento = 'https://sandbox.pagseguro.uol.com.br/v2/checkout/payment.html?code=';
          $this->url_assinatura = "https://sandbox.pagseguro.uol.com.br/v2/pre-approvals/request.html?code=";
          $this->token = 'B348949A90E54BBE980913F7F0C494A6';
          $this->email = 'de.akao@gmail.com';
      }
  }

  public function novaAssinatura($plano, $dados_cliente) {
    $this->carrinho['token'] = $this->token;
    $this->carrinho['email'] = $this->email;
    $this->carrinho['redirectURL'] = $this->redirectURL;
    $this->carrinho['reviewURL'] = 'https://app.facilemecursos.com.br/admin/conta';
    $this->carrinho['currency'] = "BRL";

    $this->carrinho['senderEmail'] = $dados_cliente['email'];
    $this->carrinho['shippingType'] = 3;
    $this->carrinho['itemId1'] = $plano['id_plano'];
    $this->carrinho['itemQuantity1'] = 1;
    $this->carrinho['itemWeight1'] = 0;
    if(isset($plano['extraAmount'])){
      $this->carrinho['itemAmount1'] = number_format($plano['extraAmount'] + $plano['itemAmount1'], 2, '.','');
    }else{
      $this->carrinho['itemAmount1'] = $plano['itemAmount1'];
    }
    $this->carrinho['itemDescription1'] = $plano['itemDescription1'];

    $this->carrinho['reference'] = $plano['reference'];
    $this->carrinho['preApprovalName'] = $plano['itemDescription1'];
    $this->carrinho['preApprovalAmountPerPayment'] = $this->carrinho['itemAmount1'];
    $this->carrinho['preApprovalDetails'] = utf8_decode('A cada '.$plano['meses'].' '.($plano['meses'] > 1 ? 'meses' : 'mês').' todo dia '.date('d').' será cobrado o valor de R$ '.formata_valor($this->carrinho['preApprovalAmountPerPayment']).' referente a assinatura do Facileme');
    $this->carrinho['preApprovalPeriod'] = $plano['periodo'];
    $this->carrinho['preApprovalDayOfMonth'] = (date('d') > 28 ? 28 : date('d'));
    $this->carrinho['preApprovalMaxAmountPerPeriod'] = $this->carrinho['itemAmount1'];
    $this->carrinho['preApprovalInitialDate'] = date('Y-m-d').'T00:00:00-03:00';
    $this->carrinho['preApprovalFinalDate'] = date('Y-m-d', strtotime('+2 years')).'T00:00:00-03:00';
    $this->carrinho['preApprovalMaxTotalAmount'] = number_format($this->carrinho['itemAmount1'] * ((12 / $plano['meses']) * 2), 2, '.','');
    $this->carrinho['notificationURL'] = $plano['notificationURL'];
    $this->httprequest();
  }

  public function novaCompra($itens=array(), $dados_cliente=array()){
    $this->carrinho = $itens;

    $this->carrinho['email'] = $this->email;
    $this->carrinho['token'] = $this->token;
    if($this->redirectURL)
      $this->carrinho['redirectURL'] = $this->redirectURL;
    $this->carrinho['currency'] = "BRL";


    $this->carrinho['senderEmail'] = $dados_cliente['cli_email'];
    if(isset($dados_cliente['cli_nome']) and $dados_cliente['cli_nome'] and strstr($dados_cliente['cli_nome'], ' '))
      $this->carrinho['senderName'] = abreviaString($dados_cliente['cli_nome'], 50, '');

    if(isset($dados_cliente['cli_cidade']) and $dados_cliente['cli_cidade'])
      $this->carrinho['shippingAddressCity'] = abreviaString($dados_cliente['cli_cidade'], 80, '');

    if(isset($dados_cliente['cli_estado']) and $dados_cliente['cli_estado'])
      $this->carrinho['shippingAddressState'] = strtoupper(abreviaString($dados_cliente['cli_estado'], 2, ''));

    if(isset($dados_cliente['cli_endereco']) and $dados_cliente['cli_endereco'])
      $this->carrinho['shippingAddressStreet'] = abreviaString($dados_cliente['cli_endereco'], 80, '');

    if(isset($dados_cliente['cli_bairro']) and $dados_cliente['cli_bairro'])
      $this->carrinho['shippingAddressDistrict'] = abreviaString($dados_cliente['cli_bairro'], 80, '');


    if(isset($dados_cliente['cli_numero']) and $dados_cliente['cli_numero']){
      $dados_cliente['cli_numero'] = (int) $dados_cliente['cli_numero'];
      $this->carrinho['shippingAddressNumber'] = abreviaString($dados_cliente['cli_numero'], 20, '');
    }

    if(isset($dados_cliente['cli_complemento']) and $dados_cliente['cli_complemento'])
      $this->carrinho['shippingAddressComplement'] = abreviaString($dados_cliente['cli_complemento'], 40, '');

    if(isset($dados_cliente['cli_cep'])){
      $dados_cliente['cli_cep'] = str_replace('-', '', $dados_cliente['cli_cep']);
      if($dados_cliente['cli_cep'] and strlen($dados_cliente['cli_cep']) == 8)
        $this->carrinho['shippingAddressPostalCode'] = $dados_cliente['cli_cep'];
     }


    $this->carrinho['shippingAddressCountry'] = "BRA";
    return $this->httprequest();
  }

  private function httprequest(){
      $data = http_build_query($this->carrinho);
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $this->ws_url."/v2/checkout/");
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_TIMEOUT, 60);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
      $result = curl_exec($curl);
      curl_close($curl);
      if($result == "Forbidden"){
          $this->erro = "Não Configurada corretamente";
      }elseif($result == "Internal Server Error"){
          $this->erro = "Não Configurada corretamente";
      }elseif($result == "Unauthorized"){
          $this->erro = "Não Configurada corretamente, verifique seu email pagseguro e Token";
      }else{
          $objXml = simplexml_load_string($result);
          if(isset($objXml->error)){
              $this->erro = (string) $objXml->error->message;
          }
          $this->code = (string) $objXml->code;
          $this->url_pagamento .= $this->code;
      }
      if($this->erro){
          return array('erro' => $this->erro);
      } else {
          return array('paymentLink' => $this->url_pagamento, 'code' => $this->code);
      }

  }

  public function notificationPost(){
    $ci =& $this->CI;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $this->ws_url."/v2/transactions/notifications/{$ci->input->post('notificationCode')}?email={$this->email}&token={$this->token}");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
    $result = trim(curl_exec($curl));
    curl_close($curl);
    $objXml = simplexml_load_string($result);
    if($this->logger)
      $this->logger($ci->input->posts());

    $erro = (bool) $objXml->error->code;
    if(!$erro){
      $this->ipn_data = json_decode(json_encode($objXml));
      return true;
    }
    else
      return false;
  }

  public function getTransaction($transactionId)
  {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $this->ws_url."/v2/transactions/{$transactionId}?email={$this->email}&token={$this->token}");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
    $xml = json_decode(json_encode(simplexml_load_string(curl_exec($curl))));
    curl_close($curl);

    if(!isset($xml->erro)){
      $this->transaction_data['status'] = $xml->status;
      return true;
    }
    else
      return false;

  }

  public function notificationAssinatura($post)
  {
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $this->ws_url."/v2/pre-approvals/notifications/{$post['notificationCode']}?email={$this->email}&token={$this->token}");
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_TIMEOUT, 60);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
      $result = trim(curl_exec($curl));
      curl_close($curl);
      $objXml = json_decode(json_encode(simplexml_load_string($result)));
      return $objXml;
  }
  public function cobranca($data)
  {

    $dados['email'] = $this->email;
    $dados['token'] = $this->token;
    $dados['itemId1'] = $data->id_plano;
    $dados['itemAmount1'] = $data->preApprovalAmountPerPayment;
    $dados['itemDescription1'] = 'Facileme - '.$data->plano;
    $dados['itemQuantity1'] = '1';
    $dados['reference'] = $data->id_fatura;
    $dados['preApprovalCode'] = $data->preApprovalCode;

    $dados = http_build_query($dados);

    $curl = curl_init($this->ws_url.'/v2/pre-approvals/payment');
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $dados);
    $xml = json_decode(json_encode(simplexml_load_string(curl_exec($curl))));
    curl_close($curl);
    if($xml == 'Unauthorized'){
      $erro = array('error' => array('message' => 'Problema com a API'));
      $xml = json_decode(json_encode($erro));
    }
    return $xml;
  }


  public function consulta($initialDate, $finalDate)
  {
    $curl = curl_init($this->ws_url.'/v2/pre-approvals?email='.$this->email.'&token='.$this->token.'&initialDate='.$initialDate.'&finalDate='.$finalDate);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $json = json_decode(json_encode(simplexml_load_string(curl_exec($curl))));
    curl_close($curl);
    $this->return_api[$initialDate] = $json;
    return $json;
  }

  private function logger($data){
    $ci =& $this->CI;
    $ci->load->helper('file');
    $dados = serialize($data);
    if(write_file('application/logs/log_pagseguro.php', $dados))
      print "ok";
  }

  public function getDirectSession() 
  {

    $dados['email'] = $this->email;
    $dados['token'] = $this->token;
    $curl = curl_init($this->ws_url.'/v2/sessions');
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($dados));
    $result = curl_exec($curl);
    if($result == "Unauthorized" or $result == "Forbidden"){
      curl_close($curl);
      return false;
    } else {
      $json = json_decode(json_encode(simplexml_load_string(curl_exec($curl))));
      curl_close($curl);
      return $json->id;
    }
  }

  public function directBoleto($itens=array(), $dados_cliente=array())
  {
    $this->carrinho = $itens;
    $this->carrinho['paymentMethod'] = 'boleto';
    $boleto = $this->directRequest($dados_cliente);
    $url = parse_url($boleto->paymentLink);
    $boleto->downloadLink = 'https://pagseguro.uol.com.br/checkout/payment/booklet/download_pdf.jhtml?'.$url['query'];
    return $boleto;
  }

  public function directEft($itens=array(), $dados_cliente=array()) 
  {
      $this->carrinho = $itens;
      $this->carrinho['paymentMethod'] = 'eft';
      return $this->directRequest($dados_cliente);
  }

  public function directCreditCard($itens=array(), $dados_cliente=array()) 
  {
      $this->carrinho = $itens;
      $this->carrinho['paymentMethod'] = 'creditCard';
      $this->carrinho['installmentQuantity'] = $this->carrinho['installment.quantity'];
      $this->carrinho['installmentValue'] = $this->carrinho['installment.value'];
      $this->carrinho['creditCardHolderBirthDate'] = $this->carrinho['creditCard.holder.birthDate'];
      $this->carrinho['creditCardHolderCPF'] = $this->carrinho['creditCard.holder.CPF'];
      $this->carrinho['creditCardHolderName'] = utf8_decode(abreviaString($this->carrinho['creditCard.holder.name'], 50, ''));
      $this->carrinho['creditCardHolderAreaCode'] = preg_replace('/[^\d\s]/', '', $this->carrinho['creditCard.holder.areaCode']);
      $this->carrinho['creditCardHolderPhone'] = preg_replace('/[^\d\s]/', '', $this->carrinho['creditCard.holder.phone']);
      $this->carrinho['creditCardToken'] = $this->carrinho['creditCard.token'];

      $this->carrinho['billingAddressPostalCode'] = preg_replace('/[^\d\s]/', '', $dados_cliente['cli_cep']);
      $this->carrinho['billingAddressStreet'] = utf8_decode($dados_cliente['cli_logradouro']);
      $dados_cliente['numero'] = (int) $dados_cliente['cli_numero'];
      $this->carrinho['billingAddressNumber'] = $dados_cliente['cli_numero'];
      if(isset($dados_cliente['cli_complemento']) and $dados_cliente['cli_complemento'])
          $this->carrinho['billingAddressComplement'] = utf8_decode($dados_cliente['cli_complemento']);

      $this->carrinho['billingAddressDistrict'] = utf8_decode(abreviaString($dados_cliente['cli_bairro'], 60, ''));
      $this->carrinho['billingAddressCity'] = utf8_decode(abreviaString($dados_cliente['cli_cidade'], 60, ''));
      $this->carrinho['billingAddressState'] = utf8_decode(abreviaString($dados_cliente['cli_estado'], 2, ''));
      $this->carrinho['billingAddressCountry'] = 'BRA';

      $dados_cliente['cpf'] = $this->carrinho['creditCard.holder.CPF'];
      $dados_cliente['nome'] = $this->carrinho['creditCard.holder.name'];
      $dados_cliente['telefone'] = '('.$this->carrinho['creditCard.holder.areaCode'].') '.$this->carrinho['creditCard.holder.phone'];


      return $this->directRequest($dados_cliente);
  }


  private function directRequest($dados_cliente) 
  {

      $this->carrinho['email'] = $this->email;
      $this->carrinho['token'] = $this->token;
      if($this->redirectURL)
          $this->carrinho['redirectURL'] = $this->redirectURL;

      if(ENVIRONMENT == 'development'){
          $dados_cliente['cli_email'] = 'c96576168997972118179@sandbox.pagseguro.com.br';
      }

      $this->carrinho['currency'] = "BRL";

      $this->carrinho['paymentMode'] = 'default';
      $this->carrinho['shippingAddressCountry'] = "BRA";

      $this->carrinho['senderEmail'] = abreviaString($dados_cliente['cli_email'], 60, '');
      $this->carrinho['senderName'] = utf8_decode(abreviaString($dados_cliente['cli_nome'], 50, ''));
      $this->carrinho['senderCPF'] = preg_replace('/[^\d\s]/', '', $dados_cliente['cli_cpf']);
      $this->carrinho['senderAreaCode'] = substr($dados_cliente['cli_telefone'], 1, 2);
      $this->carrinho['senderPhone'] = preg_replace('/[^\d\s]/', '', substr($dados_cliente['cli_telefone'], 5));
      $this->carrinho['shippingAddressCountry'] = 'BRA';
      $this->carrinho['shippingAddressState'] = abreviaString($dados_cliente['cli_estado'], 2, '');
      $this->carrinho['shippingAddressCity'] = utf8_decode(abreviaString($dados_cliente['cli_cidade'], 60, ''));
      $this->carrinho['shippingAddressPostalCode'] = preg_replace('/[^\d\s]/', '', $dados_cliente['cli_cep']);
      $this->carrinho['shippingAddressDistrict'] = utf8_decode(abreviaString($dados_cliente['cli_bairro'], 60, ''));
      $this->carrinho['shippingAddressStreet'] = utf8_decode(abreviaString($dados_cliente['cli_logradouro'], 80, ''));
      $dados_cliente['cli_numero'] = (int) $dados_cliente['cli_numero'];
      $this->carrinho['shippingAddressNumber'] = abreviaString($dados_cliente['cli_numero'], 20, '');
      if(isset($dados_cliente['cli_complemento']) and $dados_cliente['cli_complemento'])
          $this->carrinho['shippingAddressComplement'] = utf8_decode(abreviaString($dados_cliente['cli_complemento'], 40, ''));

      $dados = http_build_query($this->carrinho);
      $curl = curl_init($this->ws_url.'/v2/transactions');
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $dados);
      $result = curl_exec($curl);
      curl_close($curl);
      if($result == 'Forbidden'){
          $this->erro = 'Sua conta não está autorizada a fazer cobrança direta.';
          return false;
      }
      $json = json_decode(json_encode(simplexml_load_string($result)));
      if(isset($json->error)){
          if(is_array($json->error))
              foreach ($json->error as $item) 
                  $this->erro .= '<p>'.$item->message.'</p>';
          else
              $this->erro = $json->error->message;
          return false;
      }
      return $json;
  }


}
