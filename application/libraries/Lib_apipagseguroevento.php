<?php
class Lib_apipagseguroevento{
	var $token = "3050054619E1421C9A9EC120FE4EFA26";
	var $email = "renato.frazao@uol.com.br";
	var $carrinho = array();
	var $return_api = array();
	var $ws_url= "https://ws.pagseguro.uol.com.br";
	var $url_pagamento = 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=';
	var $redirectURL = "";
	var $ipn_data = array();
	var $erro = false;
	var $code = false;
	var $CI;
	var $logger = true;

	
	 public function __construct()
  	{
      $this->CI =& get_instance();

  		if(ENVIRONMENT != 'production'){
  		  $this->ws_url = "https://ws.sandbox.pagseguro.uol.com.br";
  		  $this->url_pagamento = 'https://sandbox.pagseguro.uol.com.br/v2/checkout/payment.html?code=';

        $this->token = '1E5EDCAA16554C1E904722F5FC67290A';
        $this->email = 'pereiracruz2002@gmail.com';
        //$dados_cliente['senderEmail'] = "c32597606053695794376@sandbox.pagseguro.com.br";
  		}
  	}

    public function novaCompra($dados_cliente) {


	    $this->carrinho['token'] = $this->token;
	    $this->carrinho['email'] = $this->email;
	    $this->carrinho['redirectURL'] = $this->redirectURL;
	    $this->carrinho['currency'] = "BRL";
      //$this->carrinho['senderName'] = abreviaString($dados_cliente['senderName'], 50, '');
	    $this->carrinho['senderEmail'] =$dados_cliente['senderEmail'];
	    //$this->carrinho['shippingType'] = 3;
	    $this->carrinho['itemId1'] = $dados_cliente['itemId1'];
	    $this->carrinho['itemQuantity1'] = $dados_cliente['itemQuantity1'];
	    $this->carrinho['itemWeight1'] = $dados_cliente['itemWeight1'];
	    $this->carrinho['shippingAddressRequired'] = "false";
	    $this->carrinho['itemAmount1'] = $dados_cliente['itemAmount1'];
	    $this->carrinho['itemDescription1'] = $dados_cliente['itemDescription1'];
	    $this->carrinho['reference'] = $dados_cliente['payment_id'];
	    $this->carrinho['notificationURL'] = $dados_cliente['notificationURL'];

	    $this->httprequest();
  	}

  	private function httprequest() {
    $data = http_build_query($this->carrinho);


    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL,$this->ws_url."/v2/checkout/");
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'charset=UTF-8'));
    $result = trim(curl_exec($curl));


    curl_close($curl);

    if ($result == "Unauthorized") {
      $this->erro = "Não Configurada corretamente, verifique seu email pagseguro e Token";
    } else {

      $objXml = simplexml_load_string($result);

      if (isset($objXml->error)) {
       $this->erro = (string) $objXml->error->message;
	
      }

      $code = (string) $objXml->code;
      $this->url_pagamento .= $code;
      $this->code = $code;
      $this->url_pagamento .= $code;
      $this->code = $code;

      echo $this->code;



      // $url_desistente = $this->redirectURL;
      // $url_desistente_array = explode("/",$this->redirectURL);


      // $url_desistente = SITE_URL;

      // echo "<script type='text/javascript' src='https://stc.sandbox.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.lightbox.js'></script>";
      // echo "<script>isOpenLightbox = PagSeguroLightbox({code:'".$code."'}, {success : function(code) {alert('sucess'+ code)},abort : function() { window.location = '".$url_desistente."';}});</script>";     
      // echo "<script>if(!isOpenLightbox){location.href='https://pagseguro.uol.com.br/v2/checkout/payment.html?code=".$code."'}</script>";
      
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

	private function logger($data){
	$ci =& $this->CI;
	$ci->load->helper('file');
	$dados = serialize($data);
	if(write_file('application/logs/log_pagseguro.php', $dados))
	  print "ok";
	}

}

?>