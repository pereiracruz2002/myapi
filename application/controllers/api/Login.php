<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Login extends CI_Controller
{
    public function index() 
    {
        $this->load->model('user_model','users');
        $where = array('email' => $this->input->post('email'));
        $user = $this->users->get_where($where)->row();
        if($user and $this->encrypt->decode($user->password) == $this->input->post('senha')){

            if($user->status == 'enable'){
                unset($user->password);
                $this->session->set_userdata('user', $user);
                $output = array('status' => 'success', 'token' => base64_encode($this->encrypt->encode($user->user_id)),'login' => "Login efetuado com sucesso");
            } elseif($user->status == 'pendding'){
                $output = array('status' => 'pendding');
            } else {
            $output = array('status' => 'error', 'msg' => 'Sua conta está inativa');
            }
        } else {
            $output = array('status' => 'erro', 'msg' => 'Usuário não encontrado');
        }

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output));
    }

    public function fbLogin() 
    {
        $fbid = $this->input->post('fbid');
        $resultado = $this->db->where("facebook_id", $fbid)->get("user");
        if($resultado->num_rows() > 0){
            $user = $resultado->row();
            if($user->status == 'enable'){
                $output = array('status' => 'success', 'token' => base64_encode($this->encrypt->encode($user->user_id)),'login' => "Login efetuado com sucesso");
            } elseif($user->status == 'pendding'){
                $output = array('status' => 'pendding');
            } else {
            $output = array('status' => 'error', 'msg' => 'Sua conta está inativa');
            }
        }else{
            $output = array('status' => 'error', 'msg' => 'Nenhum usuario cadastrado para o seu perfil' );
        }

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output));
    }
}
