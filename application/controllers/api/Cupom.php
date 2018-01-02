<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Cupom extends CI_Controller
{
    public function __construct() 
    {
        parent::__construct();
        $this->output->set_header('Access-Control-Allow-Origin: *');
    }

    public function validar()
    {
        $this->user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));

        $this->load->model('event_cupom_user_model','event_cupom_user');
        $this->load->model('event_cupom_model','event_cupom');
        $where_cupom = array(
            'event_cupom.event_cupom_id' => $this->input->post('event_cupom_id'),
            'events.event_id' => $this->input->post('event_id'),
            'events.code' => $this->input->post('code')
        );
        $this->db->join('events', 'events.event_id=event_cupom.event_id');
        $cupom = $this->event_cupom->get_where($where_cupom)->row();
        $output = array();
        if($cupom){
            $save_cupom = array(
                'user_id' => $this->user_id,
                'event_cupom_id' => $this->input->post('event_cupom_id')
            );
            $validate = $this->event_cupom_user->get_where($save_cupom)->row();
            if($validate){
                $output = array(
                    'status' => 0,
                    'msg' => 'Você já utilizou esse cupom'
                );
            } else {
                $where_last_cupom = array(
                    'event_cupom.event_id' => $this->input->post('event_id'),
                    'event_cupom_user.user_id' => $this->user_id,
                    'event_cupom_user.data >= ' => date('Y-m-d H:i:s', strtotime('-20 hours'))
                );
                $this->db->select('data')
                         ->join('event_cupom', 'event_cupom.event_cupom_id=event_cupom_user.event_cupom_id');
                $last_cupom = $this->event_cupom_user->get_where($where_last_cupom)->row();
                if($last_cupom){
                    $output = array(
                        'status' => 0,
                        'msg' => 'Desculpe você não pode utilizar 2 cupons no mesmo dia'
                    );

                } else {
                    $save_cupom['data'] = date('Y-m-d H:i:s');
                    $this->event_cupom_user->save($save_cupom);
                    $output = array('status' => 1, 'msg' => 'Cupom utilizado com sucesso');
                }
            }
        } else {
            $output = array('status' => 0, 'msg' => 'Código de ativação inválido.');
        }
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output));
    }
}
