<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Notificacoes extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->set_header('Access-Control-Allow-Origin: *');
    }
   
    public function index() 
    {
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $this->load->model('notification_model', 'notifications');
        $where = array('user_id' => $user_id);
        $this->db->select("notification_id, text, read, DATE_FORMAT(data, '%d/%m/%Y') as data, action")
                 ->order_by('notification_id', 'desc');
        $output = $this->notifications->get_where($where)->result();
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($output));
    }
}
