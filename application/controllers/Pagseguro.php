<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Pagseguro extends CI_Controller
{
    public function autorizacao() 
    {
        if($this->input->server('QUERY_STRING') and strpos($this->input->server('QUERY_STRING'), 'notificationCode=') === 0){
            parse_str($this->input->server('QUERY_STRING'));
            $this->load->model('user_model','usuario');
            $this->load->library('lib_splitpagseguro');
            $authorization = $this->lib_splitpagseguro->applicationAuthorization($notificationCode);
            if($authorization){
                $setCode['pagseguroAppCode'] = $authorization->code;
                $setCode['publicKey'] = $authorization->account->publicKey;
                $whereParceiro['user_id'] = $this->session->userdata('user')->user_id;
                $this->usuario->update($setCode, $whereParceiro);
            }
        }
        //redirect('/#!/configuracoes');
    }
    public function getUrlIntegracao() 
    {
        $this->load->library('lib_splitpagseguro');
        $pagseguro = $this->lib_splitpagseguro->authorizationRequest('d4f'.$this->session->userdata('user')->user_id);
        $this->output->set_output($pagseguro->redirectURL);
    }

}
