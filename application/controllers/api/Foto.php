<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Foto extends CI_Controller
{
    public function upload() 
    { 
        $config['upload_path'] = FCPATH.'uploads/'; 
        $config['allowed_types'] = 'gif|jpg|jpeg|png'; 
        $config['encrypt_name'] = true;
          
        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('photo')) {
            $output = array('error' => $this->upload->display_errors()); 
        } else { 
            $upload_data = $this->upload->data();
            $output['upload_data']['file_name'] = SITE_URL.'uploads/'.$upload_data['file_name'];
            if($this->input->post('event_id')){
                $this->load->model('event_gallery_model','event_gallery');
                $save['event_id']=$this->input->post('event_id');
                $save['picture'] = $upload_data['file_name'];
                $output['event_gallery_id'] = $this->event_gallery->save($save);
            }
        } 

        $this->output->set_content_type('application/json')
            ->set_content_type('Access-Control-Allow-Origin:*')
            ->set_output(json_encode($output));
    }
}
