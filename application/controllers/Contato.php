<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Contato extends CI_Controller
{
    var $data = array();

    public function index() 
    {
        $this->data['page_title'] = 'Fale Conosco';
        $this->data['view'] = 'contato';
        if($this->input->posts()){
            $this->load->library('email');
            $this->email->subject('FAQ');
            $this->email->to(EMAIL_TO);
            $this->email->from(EMAIL_FROM, 'Dinner for Friends - FAQ');
            $msg = '
                <p><strong>Data:</strong> '.date('d/m/Y H:i:s').'</p>
                <p><strong>Nome:</strong> '.$this->input->post('nome').'</p>
                <p><strong>Email:</strong> '.$this->input->post('email').'</p>
                <p><strong>Assunto:</strong> '.$this->input->post('assunto').'</p>
                <p><strong>Mensagem:</strong> '.$this->input->post('msg').'</p>
                ';
            $this->email->message(($msg));
            if($this->email->send()){
                $this->data['msg'] = box_success('Mensagem enviada com sucesso!');
            } else {
                $this->data['msg'] = $this->email->print_debugger();
                //$this->data['msg'] = box_alert('Desculpe, não foi possível enviar sua mensagem, tente novamente mais tarde.');
            }
        }
        $this->load->view('site/contato', $this->data);
    }
}
