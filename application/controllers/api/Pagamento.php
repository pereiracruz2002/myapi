<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Pagamento extends CI_Controller
{
    public function __construct() 
    {
        parent::__construct();
        $this->output->set_header('Access-Control-Allow-Origin: *');
    }

    public function getDirectSession($event_id) 
    {
        $this->load->model('events_model', 'events');
        $this->db->select('user.pagseguroLib, 
                       user.pagseguroAppCode, 
                       user.publicKey, 
                       user.pagseguroEmail, 
                       pagseguroToken')
             ->join('user', 'user.user_id=events.user_id');
        $chef = $this->events->get_where(array('events.event_id' => $event_id))->row();
        if($chef->pagseguroLib == 'lib_splitpagseguro'){
            $pagseguro_config = array(
                'appKey' => $chef->pagseguroAppCode, 
                'publicKey' => $chef->publicKey
            );
        } else {
             $pagseguro_config = array(
                'pagseguroEmail' => $chef->pagseguroEmail, 
                'pagseguroToken' => $chef->pagseguroToken
            );
        }
        $this->load->library($chef->pagseguroLib, $pagseguro_config, 'pagseguro');
        $session_id = $this->pagseguro->getDirectSession();
        $this->output->set_output($session_id);
    } 

    public function cupom() 
    {
        $this->load->model('cupom_model','cupom'); 
        $where = array('code' => $this->input->post('cupom'), 
                       'event_id' => $this->input->post('event_id'), 
                       'valid >=' => date('Y-m-d')
                      );
        $cupom = $this->cupom->get_where($where)->row();
        if(!$cupom){
            $output = array('status' => 'error', 'msg' => 'Cupom não encontrado');
        } else {
            $cupom->value = (float) $cupom->value;
            $output = array('status' => 'success', 'cupom' => $cupom);
        }

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output));
    }

    public function pagseguro() 
    {
        header('Access-Control-Allow-Origin: *');
        $this->load->library('lib_apipagseguro');
        $this->load->model('events_model','events');
        $this->load->model('payments_model','payments');
        $this->load->model('user_model','user');
        $this->load->model('event_guests_model','event_guests');
        $this->load->model('payments_guests_model','payments_guests');

        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $where = array('event_guests.user_id' => $user_id, 'event_guests.event_id' => $this->input->post('event_id'));
        $this->db->join('event_guests', 'event_guests.event_id=events.event_id');
        $event = $this->events->get_where($where)->row();

        $user = $this->user->get($user_id)->row();
        $dados_cliente = $this->input->posts();
        $dados_cliente['nome'] = $user->name.' '.$user->lastname;
        $dados_cliente['email'] = $user->email;

        $save_payment = array(
            'user_id' => $user_id,
            'price' => ($this->input->post('qty') ? (($this->input->post('qty') +1) * $this->input->post('price')) : $this->input->post('price')),
            'status' => 'Aguardando Pagto.',
            'method' => 'PagSeguro',
            'user_data' => json_encode($dados_cliente),
            'qty_friends' => $this->input->post('qty')
        );
        $save_payment['feeAmountSite'] = ($save_payment['price'] * 0.05);

        $payment_id = $this->payments->save($save_payment);
        if($this->input->post('acompanhantes')){
            $acompanhantes = $this->input->post('acompanhantes');
            foreach ($acompanhantes as $key => $item) {
                $where_friend = array('email' => $acompanhantes[$key]['email']);
                $friend = $this->user->get_where($where_friend)->row();
                $save_payment_guests = array('payment_id' => $payment_id);
                if($friend){
                    $where_event_guest = array('event_id' => $event->event_id, 'user_id' => $friend->user_id);
                    $event_guest = $this->event_guests->get_where($where_event_guest)->row();
                    if($event_guest){
                        $save_payment_guests['event_guest_id'] = $event_guest->event_guest_id;
                    } else {
                        $save_payment_guests['event_guest_id'] = $this->event_guests->save($where_event_guest);
                    }
                } else {
                    $where_friend['name'] = $acompanhantes[$key]['name'];
                    $where_friend['lastname'] = $acompanhantes[$key]['lastname'];
                    $where_friend['user_type_id'] = 4;
                    $save_event_guest['user_id'] = $this->user->save($where_friend);
                    $save_event_guest['event_id'] = $event->event_id;
                    $save_payment_guests['event_guest_id'] = $this->event_guests->save($save_event_guest);
                    $this->user->beFriends($user_id, $save_event_guest['user_id']);
                }
                $this->payments_guests->save($save_payment_guests);
            }
            
        }

        $carrinho = array(
           "reference" => $payment_id,
           "shippingType" => 3, 
           "itemId1" => $event->event_id,
           "itemDescription1" => utf8_decode(abreviaString($event->name)),
           "itemAmount1" => $save_payment['price'],
           "itemQuantity1" => $this->input->post('qty') + 1,
           'notificationURL' => SITE_URL.'api/pagamento/notificacao'
        );
        $pagseguro = $this->lib_apipagseguro->novaCompra($carrinho, $dados_cliente);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($pagseguro));
    }

    public function pagar_free()
    {
        $this->load->model('events_model','events');
        $this->load->model('payments_model','payments');
        $this->load->model('user_model','user');
        $this->load->model('payments_guests_model','payments_guests');
        $this->load->model('event_guests_model','event_guests');
        
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $where = array('event_guests.user_id' => $user_id, 'event_guests.event_id' => $this->input->post('event_id'));
        $this->db->select('events.*')
                 ->join('event_guests', 'event_guests.event_id=events.event_id');
        $event = $this->events->get_where($where)->row();

        $user = $this->user->get($user_id)->row();
        $dados_cliente = $this->input->posts();
        $dados_cliente['nome'] = $user->name.' '.$user->lastname;
        $dados_cliente['email'] = $user->email;

        $save_payment = array(
            'user_id' => $user_id,
            'event_id' => $this->input->post('event_id'),
            'price' => 0,
            'status' => 'Pago',
            'method' => 'free',
            'user_data' => json_encode($dados_cliente),
            'qty_friends' => $this->input->post('qty'),
            'feeAmountSite' => 0,
            'feePagSeguro' =>  0,
            'amountReal' => 0
        );

        $payment_id = $this->payments->save($save_payment);
        $this->db->set(array('status' => 'confirmed'))->where(array('event_id' => $this->input->post('event_id'), 'user_id' => $user_id))->update('event_guests');
        if($this->input->post('acompanhantes')) {
            $acompanhantes = $this->input->post('acompanhantes');
            foreach ($acompanhantes as $key => $item) {
                $where_friend = array('email' => $acompanhantes[$key]['email']);
                $friend = $this->user->get_where($where_friend)->row();
                $save_payment_guests = array('payment_id' => $payment_id);
                if($friend) {
                    $where_event_guest = array('event_id' => $event->event_id, 'user_id' => $friend->user_id);
                    $event_guest = $this->event_guests->get_where($where_event_guest)->row();
                    if($event_guest) {
                        $save_payment_guests['event_guest_id'] = $event_guest->event_guest_id;
                    } else {
                        $save_payment_guests['event_guest_id'] = $this->event_guests->save($where_event_guest);
                    }
                } else {
                    $where_friend['name'] = $acompanhantes[$key]['name'];
                    $where_friend['lastname'] = $acompanhantes[$key]['lastname'];
                    $where_friend['user_type_id'] = 4;
                    $save_event_guest['user_id'] = $this->user->save($where_friend);
                    $save_event_guest['event_id'] = $event->event_id;
                    $save_event_guest['status'] = 'confirmed';
                    $save_payment_guests['event_guest_id'] = $this->event_guests->save($save_event_guest);
                    //$this->user->beFriends($user_id, $save_event_guest['user_id']);
                }
                $this->payments_guests->save($save_payment_guests);
            }
        }



        $output['payment']['status'] = "Confirmado";
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output));  
    }

    public function pagar() 
    {
        header('Access-Control-Allow-Origin: *');
        $this->load->model('events_model','events');
        $this->load->model('payments_model','payments');
        $this->load->model('user_model','user');
        $this->load->model('payments_guests_model','payments_guests');
        $this->load->model('event_guests_model','event_guests');
        
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $where = array('event_guests.user_id' => $user_id, 'event_guests.event_id' => $this->input->post('event_id'));
        $this->db->select('events.*')
                 ->join('event_guests', 'event_guests.event_id=events.event_id');
        $event = $this->events->get_where($where)->row();

        $user = $this->user->get($user_id)->row();
        $dados_cliente = $this->input->posts();
        $dados_cliente['nome'] = $user->name.' '.$user->lastname;
        $dados_cliente['email'] = $user->email;
        
        $chef = $this->user->get($event->user_id)->row();
        if($chef->pagseguroLib == 'lib_splitpagseguro'){
            $this->load->library("lib_splitpagseguro", array('appKey' => $chef->pagseguroAppCode, 'publicKey' => $chef->publicKey), 'pagseguro');
        } else {
            $this->load->library("lib_apipagseguro", array('pagseguroEmail' => $chef->pagseguroEmail, 'pagseguroToken' => $chef->pagseguroToken), 'pagseguro');
        }

        
        $taxa_pagseguro = number_format((($this->input->post('price') * 3.99)/100) + 0.40, 2, '.', '');
        $taxa_site = number_format((($this->input->post('price') * $event->feeAmountSite) / 100), 2, '.','');
        $valor_liquido = number_format(($this->input->post('price') - $taxa_pagseguro - $taxa_site), 2, '.','');

        $save_payment = array(
            'user_id' => $user_id,
            'event_id' => $this->input->post('event_id'),
            'price' => $this->input->post('price') / ($this->input->post('qty') +1),
            'status' => 'Aguardando Pagto.',
            'method' => $this->input->post('pagamento'),
            'user_data' => json_encode($dados_cliente),
            'qty_friends' => $this->input->post('qty'),
            'feeAmountSite' => $taxa_site,
            'feePagSeguro' =>  $taxa_pagseguro,
            'amountReal' => $valor_liquido
        );

        $payment_id = $this->payments->save($save_payment);
        if($this->input->post('acompanhantes')) {
            $acompanhantes = $this->input->post('acompanhantes');
            foreach ($acompanhantes as $key => $item) {
                $where_friend = array('email' => $acompanhantes[$key]['email']);
                $friend = $this->user->get_where($where_friend)->row();
                $save_payment_guests = array('payment_id' => $payment_id);
                if($friend) {
                    $where_event_guest = array('event_id' => $event->event_id, 'user_id' => $friend->user_id);
                    $event_guest = $this->event_guests->get_where($where_event_guest)->row();
                    if($event_guest) {
                        $save_payment_guests['event_guest_id'] = $event_guest->event_guest_id;
                    } else {
                        $save_payment_guests['event_guest_id'] = $this->event_guests->save($where_event_guest);
                    }
                } else {
                    $where_friend['name'] = $acompanhantes[$key]['name'];
                    $where_friend['lastname'] = $acompanhantes[$key]['lastname'];
                    $where_friend['user_type_id'] = 4;
                    $save_event_guest['user_id'] = $this->user->save($where_friend);
                    $save_event_guest['event_id'] = $event->event_id;
                    $save_payment_guests['event_guest_id'] = $this->event_guests->save($save_event_guest);
                    //$this->user->beFriends($user_id, $save_event_guest['user_id']);
                }
                $this->payments_guests->save($save_payment_guests);
            }
        }

        $carrinho = array(
            "reference" => (ENVIRONMENT != 'production' ? 'teste_' : '').$payment_id,
            "sender.hash" => $this->input->post('pagseguroHash'),
            "shipping.type" => 3,
            "item[1].id" => $event->event_id,
            "item[1].description" => utf8_decode(abreviaString($event->name)),
            "item[1].amount" => number_format($this->input->post('price') / ($this->input->post('qty') +1), 2, '.', ''),
            "item[1].quantity" => (1 + $this->input->post('qty')),
            //SEM APP PAGSEGURO
            "senderHash" => $this->input->post('pagseguroHash'),
            "itemId1" => $event->event_id,
            "shippingType" => 3, 
            "itemDescription1" => utf8_decode(abreviaString($event->name)),
            "itemAmount1" => number_format($this->input->post('price') / ($this->input->post('qty') +1), 2, '.', ''),
            "itemQuantity1" => $this->input->post('qty') + 1,
            'notificationURL' => SITE_URL.'api/pagamento/notificacoes/'.$event->event_id
        );
        

        $carrinho['primaryReceiver.publicKey'] = $chef->publicKey;
        $carrinho['receiver[1].split.amount'] = $taxa_site;

        $dados_chef['cli_email'] = $user->email;
        $dados_chef['cli_nome'] = $user->name.' '.$user->lastname;
        $dados_chef['cli_cpf'] = $this->input->post('cpf', $this->input->post('creditCardHolderCPF'));
        $dados_chef['cli_telefone'] = $this->input->post('telefone');
        $dados_chef['cli_cep'] = $this->input->post('cep');
        $dados_chef['cli_logradouro'] = $this->input->post('endereco');
        $dados_chef['cli_numero'] = $this->input->post('numero');
        $dados_chef['cli_complemento'] = $this->input->post('complemento');
        $dados_chef['cli_cidade'] = $this->input->post('cidade');
        $dados_chef['cli_estado'] = $this->input->post('estado');
        $dados_chef['cli_bairro'] = $this->input->post('bairro');

        $output = array(
            'pagamento' => $this->input->post('pagamento'), 
            'payment_id' => $payment_id, 
            'status' => $save_payment['status']
        );
        switch($this->input->post('pagamento')){
            case "boleto":
                $carrinho['payment.method'] = 'boleto';
                if($chef->pagseguroLib == 'lib_apipagseguro'){
                    $pagamento_pagseguro = $this->pagseguro->directBoleto($carrinho, $dados_chef);
                }
            break;
            case "creditCard":
                $carrinho['payment.method'] = 'credit_card';
                $carrinho['installment.quantity'] = $this->input->post('installmentQuantity');
                $carrinho['installment.value'] = number_format($this->input->post('installmentValue'), 2, '.','');
                $carrinho['creditCard.holder.CPF'] = preg_replace('/[^\d\s]/', '', $this->input->post('creditCardHolderCPF'));
                $carrinho['creditCard.holder.areaCode'] = trim($this->input->post('creditCardHolderAreaCode'));
                $carrinho['creditCard.holder.phone'] = trim(str_replace('-', '',$this->input->post('creditCardHolderPhone')));
                $carrinho['creditCard.holder.birthDate'] = substr($this->input->post('creditCardHolderBirthDate'),0, 10);
                $carrinho['creditCard.holder.name'] = $this->input->post('creditCardHolderName');
                $carrinho['creditCard.token'] = $this->input->post('creditCardToken');
                $carrinho['billingAddressCity'] = $this->input->post('cidade');
                if($chef->pagseguroLib == 'lib_apipagseguro'){
                    $pagamento_pagseguro = $this->pagseguro->directCreditCard($carrinho, $dados_chef);
                }
            break;
        }
        if($chef->pagseguroLib == 'lib_splitpagseguro'){
            $pagamento_pagseguro = $this->pagseguro->novaCompra($carrinho, $dados_chef);
        }
        
        //$output['dados_chef'] = $this->input->posts();
        if($this->pagseguro->erro){
            $output['erro'] = $this->pagseguro->erro;
        }

        if(isset($pagamento_pagseguro->paymentLink)){
            $url = parse_url($pagamento_pagseguro->paymentLink);

            $output['payment']['boleto_link'] = $pagamento_pagseguro->paymentLink;
            $output['payment']['boleto_download'] = 'https://pagseguro.uol.com.br/checkout/payment/booklet/download_pdf.jhtml?'.$url['query'];
            $this->payments->save(array('redirect' => $pagamento_pagseguro->paymentLink, 'payment_id' => $payment_id));
        }
        if(isset($pagamento_pagseguro->status)){
            $output['payment']['status'] = pagseguro_status($pagamento_pagseguro->status);
        }
        $output['return'] = $pagamento_pagseguro;
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output));
    }

    public function notificacoes($event_id) 
    {
        $this->load->model('payments_model','payments');
        $this->load->model('events_model', 'events');
        $this->db->select('user.pagseguroLib, 
                       user.pagseguroAppCode, 
                       user.publicKey, 
                       user.pagseguroEmail, 
                       pagseguroToken')
             ->join('user', 'user.user_id=events.user_id');
        $chef = $this->events->get_where(array('events.event_id' => $event_id))->row();
        if($chef->pagseguroLib == 'lib_splitpagseguro'){
            $pagseguro_config = array(
                'appKey' => $chef->pagseguroAppCode, 
                'publicKey' => $chef->publicKey
            );
        } else {
             $pagseguro_config = array(
                'pagseguroEmail' => $chef->pagseguroEmail, 
                'pagseguroToken' => $chef->pagseguroToken
            );
        }


        $this->load->library($chef->pagseguroLib, $pagseguro_config, 'pagseguro');

        if($this->input->post('notificationCode') and $this->pagseguro->notificationPost()){
            $save['payment_id'] = str_replace('teste_', '', $this->pagseguro->ipn_data->reference);
            $save['status'] = pagseguro_status($this->pagseguro->ipn_data->status);
            if($save['status'] == 'Pago'){
                $this->db->select('user.email as user_email, 
                                   user.name as user_name, 
                                   events.*')
                         ->join('events', 'events.event_id=payments.event_id')
                         ->join('user', 'user.user_id=payments.user_id');
                $this->data['event']= $this->payments->get_where(array('payments.payment_id' => $save['payment_id']))->row();

                $guests = $this->db->get_where('payments', array('payments.payment_id' => $save['payment_id']))->result();
                foreach ($guests as $item) {
                    $this->db->set(array('status' => 'confirmed'))->where(array('event_id' => $item->event_id, 'user_id' => $item->user_id))->update('event_guests');
                }

                $msg = $this->load->view('emails/confirmacao_pagamento', $this->data, true);
                $this->load->library("email");
                $config['mailtype'] = 'html';
                $config['charset'] = 'iso-8859-1';
                $this->email->initialize($config);
                $this->email->to($this->data['event']->user_email);
                $this->email->from(EMAIL_FROM, utf8_decode('Dinner for Friends'));
                $this->email->subject(utf8_decode('Confirmação de pagamento'));
                $this->email->message(utf8_decode($msg));
                $this->email->send();
            }
	    $this->payments->save($save);

            $save['notificationCode'] = $this->input->post('notificationCode');
            $save['transactionId'] = $this->pagseguro->ipn_data->code;
            $this->db->insert('payments_log', $save);
        }
    }

}
