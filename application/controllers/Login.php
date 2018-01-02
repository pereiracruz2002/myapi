<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Login extends CI_Controller {

    var $data = array();

    public function index() {
        $this->data['page_title'] = 'Área do Chef';
        $this->data['view'] = 'login';
        if ($this->session->userdata('user')) {
            redirect('chef/painel');
        }

        if ($this->input->posts()) {
            $this->load->model('user_model', 'user');

            $where = array('email' => $this->input->post('email'));
            $user = $this->user->get_where($where)->row();

            if (!$user) {
                $this->data['msg'] = 'Usuário não encontrado';
            } else {
                if ($user->user_type_id == 2) {
                    if ($user->status === "not_activated") {
                        $this->data['msg'] = 'Sua solicitalção à chef foi recusada.<br/>Entre em contato no e-mail <a href="mailto:app@dinnerforfriends.com.br?Subject=Solicitação%20não%20aceita-%20' . $this->input->post('email') . '" target="_top">app@dinnerforfriends.com.br</a> caso queira saber o motivo.';
                    } elseif ($user->status === "confirm_email") {
                        $this->data['msg'] = 'Seu email ainda não foi confirmado. Acesse a sua conta de email e clique no link para confirmar.<br/>'
                                . 'Caso não tenha recebido o email, <a href="' . SITE_URL . 'cadastro/EnviarConfirmarEmail/' . urlencode($this->input->post('email')) . '">clique aqui</a> para reenviar.';
                    } elseif ($user->status === "pendding") {
                        $user_id_encoded = rtrim(base64_encode($this->encrypt->encode($user->user_id)), '=');
                        redirect('login/pendding/' . $user_id_encoded);
                    } elseif ($this->input->post('password') == $this->encrypt->decode($user->password)) {
                        unset($user->password);
                        $this->session->set_userdata('user', $user);
                        redirect('chef/painel');
                    } else {
                        $this->data['msg'] = 'Usuário ou senha inválido';
                    }
                } else if ($user->user_type_id == 3) {
                    unset($user->password);
                    $this->session->set_userdata('user_upgrade', $user);
                    redirect('chef/conta/upgrade');
                }
            }
            /* if (!$user) {
              $this->data['msg'] = 'Usuário não encontrado';
              } elseif ($user->status === "not_activated") {
              $this->data['msg'] = 'Sua solicitalção à chef foi recusada.<br/>Entre em contato no e-mail <a href="mailto:app@dinner4friends.com.br?Subject=Solicitação%20não%20aceita-%20'.$this->input->post('email').'" target="_top">app@dinner4friends.com.br</a> caso queira saber o motivo.';
              } elseif ($user->status === "confirm_email") {
              $this->data['msg'] = 'Seu email ainda não foi confirmado. Acesse a sua conta de email e clique no link para confirmar.<br/>'
              . 'Caso não tenha recebido o email, <a href="'.SITE_URL.'cadastro/EnviarConfirmarEmail/'.urlencode($this->input->post('email')).'">clique aqui</a> para reenviar.';
              } elseif ($user->status === "pendding") {
              $user_id_encoded = rtrim(base64_encode($this->encrypt->encode($user->user_id)), '=');
              redirect('login/pendding/'.$user_id_encoded);
              } elseif ($this->input->post('password') == $this->encrypt->decode($user->password)) {
              unset($user->password);
              $this->session->set_userdata('user', $user);
              redirect('chef/painel');
              } else {
              $this->data['msg'] = 'Usuário ou senha inválido';
              } */
        }
        $this->load->view('site/login', $this->data);
    }

    public function pendding($user_id_encoded) {
        $this->load->model('user_model', 'user');
        $user_id = $this->encrypt->decode(base64_decode($user_id_encoded));
        $this->data['user'] = $this->user->get($user_id)->row();
        $this->data['view'] = 'login';

        $this->load->view('chef/pendding', $this->data);
    }

    public function first() {
        $this->data['user'] = $this->session->userdata('user');
        $this->load->view('chef/primeiro_login', $this->data);
    }

}
