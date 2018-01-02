<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Api extends CI_Controller
{
    var $data = array();
    public function __construct() {
        parent::__construct();
        $this->output->set_header('Access-Control-Allow-Origin: *');
    }

    public function teste(){
    	echo 'hello word';
    }

    public function register() {
        $this->load->model('user_model', 'user');
        
        $where_user = array('email' => $this->input->post('email'));
        $user = $this->user->get_where($where_user)->row();
        if (!$user) {
            $save_user = array(
                'nome' => $this->input->post('name'),
                'sobrenome' => $this->input->post('lastname'),
                'email' => $this->input->post('email'),
                'senha' => $this->input->post('senha'),
                'status' => 'enabled',
                'tipo_id' => $this->input->post('tipo_id'),
            );
            // if ($this->input->post('picture')) {
            //     $this->load->helper('file');
            //     $picture_name = date('YmdHis') . uniqid() . '.jpg';
            //     if (write_file(FCPATH . 'uploads/' . $picture_name, base64_decode(str_replace('data:image/jpeg;base64,', '', $this->input->post('picture'))))) {
            //         $save_user['picture'] = $picture_name;
            //     }
            // }

            $user_id = $this->user->save($save_user);

            $data = (object) array();
            $data->name = $this->input->post('name');
            $data->lastname = $this->input->post('lastname');
            $data->email = $this->input->post('email');
            $data->user_id = $user_id;
           // $this->sendConfirmarEmail($data);

            $output = array('status' => 'success', 'msg' => 'Cadastro efetuado com sucesso', 'token' => rtrim(base64_encode($this->encrypt->encode($user_id)), "="));
        } else {

            $output = array('status' => 'error', 'msg' => 'Esse email já está cadastrado.');

        }

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }
}
