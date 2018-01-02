<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');



class Usuario extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->output->set_header('Access-Control-Allow-Origin: *');
    }



    public function info() {
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $this->load->model('user_model', 'user');
        $this->load->model('categories_model', 'categories');
        $this->db->select("user.user_id,
            user.username,
            user.email,
            user.name,
            user.lastname,
            CONCAT('" . SITE_URL . "uploads/', user.picture) as picture,
            user.facebook_id,
            user.user_type_id,
            user_info.info_key,
            user_info.info_value
            ")->join('user_info', 'user_info.user_id=user.user_id', 'left');
        $usuario = $this->user->get($user_id)->result_array();

        $output = array();
        foreach ($usuario as $item) {
            if (!$output) {
                if (strstr($item['picture'], 'user_default.png') and $item['facebook_id']) {
                    $item['picture'] = 'https://graph.facebook.com/' . $item['facebook_id'] . '/picture?type=square';
                }
                $output = $item;
            }
            if (!isset($output['extra'])) {
                $output['extra'] = array();
            }
            if ($item['info_key']) {
                if ($item['info_key'] == 'category_id') {
                    $this->db->select('name');
                    $category = $this->categories->get($item['info_value'])->row();
                    $output['extra']['especialidades'][] = $category->name;
                } else if ($item['info_key'] == 'picture' or $item['info_key'] == 'cover') {
                    $output['extra'][$item['info_key']] = (strstr($item['info_value'], 'http') ? '' : SITE_URL . 'uploads/') . $item['info_value'];
                }else {
                    $output['extra'][$item['info_key']] = $item['info_value'];
                }
            }
            unset($output['info_key'], $output['info_value']);
        }
        
        if (isset($output['extra']['endereco']))
            $output['extra']['formated_address'] = $output['extra']['endereco'];
        if (isset($output['extra']['bairro']))
            $output['extra']['formated_address'] .= " - {$output['extra']['bairro']}";
        if (isset($output['cidade']))
            $output['extra']['formated_address'] .= ", {$output['cidade']}";
        if (isset($output['estado']))
            $output['extra']['formated_address'] .= " - {$output['estado']}";

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }



    public function lembrarSenha() {
        $email = $this->input->post("email");
        $buscaUser = $this->db->where("email", $email)->get("user");
        if ($buscaUser->num_rows() > 0) {
            $usuario = $buscaUser->row();
            $senhaCript = $usuario->password;
            $senha = $this->encrypt->decode($usuario->password);
            $para = $usuario->email;
            $msg = "<h1>Recuperação de senha Chef Amigo</h1>";
            $msg .= "<p><strong>Senha:</strong>" . $senha . "</p>";

            $this->load->library('email');
            $this->email->from(EMAIL_FROM, 'Recuperação de senha Chef Amigo');
            $this->email->to($para);
            $this->email->subject('Recuperação de senha Chef Amigo');
            $this->email->message($msg);

            if ($this->email->send()) {
                $output["status"] = "success";
                $output["msg"] = "Sua senha foi enviada para o e-mail cadastrado";
            } else {
                //echo $this->email->print_debugger();
                //exit();

                $output["status"] = "error";
                $output["msg"] = "Não foi possivel enviar e-mail no momento";
                $output["debug"] = $this->email->print_debugger();
            }
        } else {
            $output["status"] = "error";
            $output["msg"] = "E-mail não encontrado no sistema";
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }


    public function procurar() {

        $dataAtual = date('Ymd');

        $user_id = $this->encrypt->decode(base64_decode($this->input->post("token")));
        $this->load->model('user_model', 'users');
        $this->db->select("user.user_type_id,
                           CONCAT(user.name,' ',user.lastname) as name,
                           CONCAT('" . SITE_URL . "uploads/', user.picture) as picture,
                           user.user_id as userID,
                           user.facebook_id,
                           DATE_FORMAT(`user`.`create_time`,'%Y%m%d') as cadastro,
                           CONCAT(YEAR(`user`.`create_time`),'',MONTH(`user`.`create_time`),'',DAY(`user`.`create_time`)) as cadastro
                           ")
                ->select("(SELECT FLOOR(DATEDIFF(DATE(" . $dataAtual . "), DATE(cadastro))/7)) as semanas", false)
                ->select("(SELECT STR_TO_DATE((REPLACE((SELECT `user_info`.`info_value` FROM `user_info` WHERE `user_info`.`info_key`='nascimento' AND  `user_info`.`user_id` =userID),'/','-')),'%Y-%m-%d')) as nascimento", false)
                ->select("(SELECT user_info.info_value FROM user_info WHERE user_info.info_key='sobrevoce' AND user_info.user_id =userID) as sobrevoce", false)
                ->select("(SELECT user_info.info_value FROM user_info WHERE user_info.info_key='profissao' AND user_info.user_id =userID) as profissao", false)
                ->select("(SELECT user_info.info_value FROM user_info WHERE user_info.info_key='pratopreferido' AND user_info.user_id =userID) as pratopreferido", false)
                ->select("(SELECT DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(nascimento, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(nascimento, '00-%m-%d'))) as age", false)
                ->select("(SELECT count(*) FROM friends WHERE user_id = " . $user_id . " AND friend_id = userID and status='accepted') as isFriend", false)
                ->select("(SELECT count(*) FROM friends WHERE user_id = " . $user_id . " AND friend_id = userID and status='pendding') as isPending", false)
                ->group_start()
                ->like('name', $this->input->post('q'))
                ->or_like('lastname', $this->input->post('q'))
                ->or_like('email', $this->input->post('q'))
                ->group_end();
        $users = $this->users->get_where(array('status' => 'enable', 'user_id !=' => $user_id))->result();

        $output = array();
        foreach ($users as $item) {
            if (strstr($item->picture, 'user_default.png') and $item->facebook_id) {
                $item->picture = 'https://graph.facebook.com/' . $item->facebook_id . '/picture?type=square';
            }

            $item->totalComum = $this->users->totalComum($user_id, $item->userID);
            $output[] = $item;
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }




    public function atualizar() {
        $this->load->model('user_model', 'user');

        $fields = ($this->input->post('fields'));
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));

        $where = array(
            'user_id' => $user_id
        );
        foreach ($fields as $field) {
            if (isset($field['key']) && isset($field['value'])) {
                $update = array($field['key'] => $field['value']);
                $this->user->update($update, $where);
            }
        }

        $this->output->set_content_type('application/json')
                ->set_output(json_encode('ok'));
    }

    public function adicionarinfo() {
        $this->load->model('user_info_model', 'user_info');

        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $fields = $this->input->post('fields');
        $status = array();

        foreach ($fields as $key => $field) {
            $field['user_id'] = $user_id;
            if (!$this->db->insert('user_info', $field))
                $status[] = "erro";
        }
        if (count($status) > 0)
            $this->output->set_content_type('application/json')
                    ->set_output(json_encode('erro'));
        else
            $this->output->set_content_type('application/json')
                    ->set_output(json_encode('ok'));
    }

    public function editar() {
        $this->load->model('user_model', 'user');
        $this->load->model('user_info_model', 'user_info');
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $user_save['name'] = $this->input->post('name');
        $user_save['lastname'] = $this->input->post('lastname');
        $output['status'] = 'sucesso';

        if ($this->checkEmail($this->input->post('email'), $user_id)) {
            $user_save['email'] = $this->input->post('email');

            if ($this->input->post('new_picture')) {
                $this->load->helper('file');
                $picture_name = date('YmdHis') . uniqid() . '.jpg';
                if (write_file(FCPATH . 'uploads/' . $picture_name, base64_decode(str_replace('data:image/jpeg;base64,', '', $this->input->post('new_picture'))))) {
                    $user_save['picture'] = $picture_name;
                }
            }

            if ($this->input->post('password')) {
                $user_save['password'] = $this->encrypt->encode($this->input->post('password'));
            }

            $this->user->update($user_save, $user_id);

            $extra = $this->input->post('extra');
            unset($extra['formated_address']);
            $extra['nascimento'] = date("Y-m-d", strtotime($extra['nascimento']));
            
            foreach ($extra as $field => $value) {
                $this->user_info->delete(array('info_key' => $field), array('user_id', $user_id));
                $this->user_info->save(array('info_key' => $field, 'info_value' => $value, 'user_id' => $user_id));
            }
            
            $output['status'] = 'sucesso';
            $output['msg'] = 'Dados alterados com sucesso';
        } else {
            $output['status'] = 'erro';
            $output['msg'] = 'Esse email já está sendo usado por outra pessoa';
        }

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    private function checkEmail($email, $user_id) {
        $where['email'] = $email;
        $where['usuarios_id !='] = $user_id;
        $user = $this->user->get_where($where)->row();
        if ($user) {
            return false;
        } else {
            return true;
        }
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
