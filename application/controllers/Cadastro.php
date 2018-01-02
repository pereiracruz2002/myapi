<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Cadastro extends CI_Controller {

    var $data = array();

    public function __contruct() {
        $this->load->help(array('form', 'url'));
        $this->load->library('form_validation');
        $this->load->library('session');
    }

    public function index() {
        $this->data['page_title'] = 'Cadastro de chef';
        $this->data['view'] = 'login';
        //$this->session->userdata("admin")->user_id
        $this->session->set_userdata('action', rand(10, 1000));
        $token = base64_encode($this->encrypt->encode($this->session->userdata('action')));
        $this->data['token'] = $token;
        $validation = array(
            array(
                'field' => 'field_name',
                'label' => 'Nome',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_lastname',
                'label' => 'Sobrenome',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_sex',
                'label' => 'Sexo',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_nascimento',
                'label' => 'Data de nascimento',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_phone',
                'label' => 'Telefone / Celular',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_cep',
                'label' => 'CEP',
                'rules' => 'required|min_length[8]'
            ),
            array(
                'field' => 'field_city',
                'label' => 'Cidade',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_state',
                'label' => 'Estado',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_address',
                'label' => 'Endereço',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_number',
                'label' => 'Número',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_email',
                'label' => 'E-mail',
                'rules' => 'trim|required|is_unique[user.email]',
                'errors' => array(
                    'required' => "O campo %s é obrigatório",
                    'is_unique' => "O E-mail informado já foi cadastrado"
                )
            ),
            array(
                'field' => 'field_confirm_email',
                'label' => 'Confirmar e-mail',
                'rules' => 'trim|required|matches[field_email]',
                'errors' => array(
                    'required' => "O campo %s é obrigatório"
                )
            ),
            array(
                'field' => 'field_password',
                'label' => 'Senha',
                'rules' => 'trim|required|min_length[6]'
            ),
            array(
                'field' => 'field_confirm',
                'label' => 'Confirmar senha',
                'rules' => 'trim|required|matches[field_password]|min_length[6]'
            ),
            array(
                'field' => 'field_message',
                'label' => 'Mensagem',
                'rules' => 'trim|required|min_length[10]'
            ),
            array(
                'field' => 'field_profession',
                'label' => 'Profissão',
                'rules' => 'trim'
            ),
            array(
                'field' => 'field_formation',
                'label' => 'Formação',
                'rules' => 'trim'
            ),
            array(
                'field' => 'field_license',
                'label' => 'Termos de adesão',
                'rules' => 'required'
            )
        );
        $this->form_validation->set_rules($validation);
        if ($this->form_validation->run() == FALSE) {
            $this->load->view('site/cadastro', $this->data);
        } else {
            $this->load->model('user_model', 'user');
            $this->load->model('user_info_model', 'user_info');

            $user = array(
                'name' => $this->input->post('field_name'),
                'lastname' => $this->input->post('field_lastname'),
                'username' => $this->genUsername($this->input->post('field_name') . ' ' . $this->input->post('field_lastname')),
                'email' => $this->input->post('field_email'),
                'password' => $this->encrypt->encode($this->input->post('field_password')),
                'status' => 'confirm_email',
                'user_type_id' => 2
            );
            $user_id = $this->user->save($user);

            $field_nascimento = explode("/", $this->input->post('field_nascimento'));
            $user_info = array(
                'cep' => $this->input->post('field_cep'),
                'endereco' => $this->input->post('field_address'),
                'cidade' => $this->input->post('field_city'),
                'estado' => $this->input->post('field_state'),
                'numero' => $this->input->post('field_number'),
                'complemento' => $this->input->post('field_complement'),
                'telefone' => $this->input->post('field_phone'),
                'sexo' => $this->input->post('field_sex'),
                'codigo' => $this->input->post('field_code'),
                'sobrevoce' => $this->input->post('field_about_you'),
                'nascimento' => "{$field_nascimento[2]}-{$field_nascimento[1]}-{$field_nascimento[0]}",
                'profissao' => $this->input->post('field_profession'),
                'formacao' => $this->input->post('field_formation'),
                'mensagem' => $this->input->post('field_message'),
                'profissao' => $this->input->post('field_profession'),
                'curriculo' => $this->input->post('field_curriculo'),
                'requestChef' => "admin"
            );
            foreach ($user_info as $key => $item) {
                $save_info = array(
                    'info_key' => $key,
                    'info_value' => $item,
                    'user_id' => $user_id
                );
                $this->user_info->save($save_info);
            }
            $user = array(
                'user_id' => $user_id,
                'name' => $this->input->post('field_name'),
                'lastname' => $this->input->post('field_lastname'),
                'email' => $this->input->post("field_email")
            );
            $data['user'] = (object) $user;
            $data = (object) $data;
            $this->sendConfirmarEmail($user);
            $this->load->view('chef/confirm_email', $data);
        }
    }
    
    public function EnviarConfirmarEmail($email)
    {
        $email = urldecode($email);
        $this->load->model('user_model', 'user');
        $where = array('email' => urldecode($email));
        $user = $this->user->getUserInfo($where);
        $this->sendConfirmarEmail($user);
        $this->load->view("emails/templates/reenviar_email", $user);
    }

    protected function sendConfirmarEmail($data) 
    {
        if (is_array($data)) {
            $data = (object) $data;
        }
        $data->code = urlencode($this->encrypt->encode($data->user_id));
        unset($data->user_id);
        
        $this->load->library('email');
        $this->email->clear(TRUE);
        $this->email->from(EMAIL_FROM, 'Dinner for Friends');
        $this->email->to($data->email);
        $this->email->subject('Confirmação de email');
        $this->email->message($this->load->view("emails/templates/confirmar_email", $data, TRUE));
        $this->email->send();
    }
    
    public function ConfirmarEmail()
    {
        $this->load->model('user_model', 'user');
        $code = $this->encrypt->decode($this->input->get("code"));
        $email = urldecode($this->input->get("email"));
        
        $where = array("user_id" => $code, "email" => $email);
        $data = $this->user->get_where($where)->row();
        if ($data->status == "confirm_email") {
            $this->user->update(array('status' => 'pendding'), $where);
            if ($this->db->affected_rows() > 0) {
                $this->load->view("emails/templates/email_confirmado", $data);
            } else {
                $this->load->view("emails/templates/erro_confirmacao", $data);
            }
        } else {
            $this->load->view("emails/templates/email_ativo", $data);
        }
    }

    /*
    public function user() {
        $this->load->model('User_model', 'user');
        if ($this->user->validar())
            ;
        $this->data['page_title'] = "Cadastro de usuários";

        $validation = array(
            array(
                'field' => 'field_name',
                'label' => 'Nome',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_lastname',
                'label' => 'Sobrenome',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_cep',
                'label' => 'CEP',
                'rules' => 'required|min_length[8]'
            ),
            array(
                'field' => 'field_address',
                'label' => 'Endereço',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_phone',
                'label' => 'Telefone',
                'rules' => 'required'
            ),
            array(
                'field' => 'field_email',
                'label' => 'Email',
                'rules' => 'required|is_unique[user.email]',
                'errors' => array(
                    'required' => "O campo %s é obrigatório",
                    'is_unique' => "O E-mail informado já foi cadastrado"
                )
            ),
            array(
                'field' => 'field_password',
                'label' => 'Senha',
                'rules' => 'trim|required|min_length[8]'
            ),
            array(
                'field' => 'field_confirm',
                'label' => 'Confirmar senha',
                'rules' => 'trim|required|matches[field_password]|min_length[8]'
            )
        );

        $this->form_validation->set_rules($validation);
        if ($this->form_validation->run() == FALSE) {
            $this->load->view('site/cadastro_user', $this->data);
        } else {
            $this->load->model('user_model', 'user');
            $this->load->model('user_info_model', 'user_info');
            $this->load->library('email');

            $user = array(
                'name' => $this->input->post('field_name'),
                'lastname' => $this->input->post('field_lastname'),
                'username' => $this->genUsername($this->input->post('field_name') . ' ' . $this->input->post('field_lastname')),
                'email' => $this->input->post('field_email'),
                'password' => $this->encrypt->encode($this->input->post('field_password')),
                'user_type_id' => 3
            );

            $user_id = $this->user->save($user);

            $user_info = array(
                'cep' => $this->input->post('field_cep'),
                'endereco' => explode("-", trim($this->input->post('field_address')))[0],
                'bairro' => trim(explode(",", trim(explode("-", trim($this->input->post('field_address')))[1]))[0]),
                'cidade' => trim(explode(",", trim(explode("-", trim($this->input->post('field_address')))[1]))[1]),
                'estado' => trim(explode(",", trim(explode("-", trim($this->input->post('field_address')))[1]))[2]),
                'numero' => $this->input->post('field_number'),
                'telefone' => $this->input->post('field_phone'),
                'sexo' => $this->input->post('field_sex')
            );

            foreach ($user_info as $key => $item) {
                $save_info = array(
                    'info_key' => $key,
                    'info_value' => $item,
                    'user_id' => $user_id
                );
                $this->user_info->save($save_info);
            }

            $msg = $this->load->view('emails/cadastro', $user, true);
            $this->email->from(EMAIL_FROM, 'Dinner 4 Friends');
            $this->email->to($this->input->post('field_email'));
            $this->email->subject('Bem vindo ao Dinner 4 Friends');
            $this->email->message($msg);
            $this->email->send();

            $this->load->view('site/cadastro_success');
        }
    }
    */
    public function genUsername($name, $i = null) {
        $user = false;
        do {
            $username = url_title($name, '-', true);
            $i = rand(0, 99999);
            $where = array('username' => $username . $i);
            $user = $this->user->get_where($where)->row();
        } while ($user);

        return $username . $i;
    }

}
