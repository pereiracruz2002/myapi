<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Upload extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
    }
    
    public function curriculo()
    {
        $config['upload_path'] = FCPATH."uploads/";
        $config['allowed_types'] = "pdf";
        $config['encrypt_name'] = true;
        
        $this->load->library('upload', $config);
        
        if (!$this->upload->do_upload('curriculo')) {
            $output = array('error' => $this->upload->display_errors());
        } else {
            $upload_data = $this->upload->data();
            $output['upload_data']['file_name'] = $upload_data['file_name'];
        }
        
        $output[] = $_FILES;

        $this->output->set_content_type('application/json')
                ->set_content_type('Access-Control-Allow-Origin:*')
                ->set_output(json_encode($output));
    }
    
    public function image() 
    {
        $config['upload_path'] = FCPATH."uploads/";
        $config['allowed_types'] = "jpg|png";
        $config['encrypt_name'] = true;
        
        $this->load->library('upload', $config);
        
        if (!$this->upload->do_upload('image')) {
            $output = array('error' => $this->upload->display_errors());
        } else {
            $upload_data = $this->upload->data();
            $output = array('upload_data' => array('file_name' => $upload_data['file_name']));
        }

        $this->output->set_content_type('application/json')
                ->set_content_type('Access-Control-Allow-Origin:*')
                ->set_output(json_encode($output));
    }

    function base64_to_image() {
        $base64_string = $this->input->post('img');
        $file_name = FCPATH."uploads/".$this->input->post('output');

        $ifp = fopen($file_name, 'wb');
        if ($ifp) {
            $data = explode(",", $base64_string);
            fwrite($ifp, base64_decode($data[1]));
            $output = array('status' => "ok", 'file_name' => $this->input->post('output'));
        } else {
            $output = array('error' => "Erro de permissão");
        }
        fclose($ifp);
        
        $this->output->set_content_type('application/json')
             ->set_content_type('Access-Control-Allow-Origin:*')
             ->set_output(json_encode($output));
    }

    function base64_to_jpeg() {
        $base64_string = $this->input->post('img');
        $output_file = FCPATH."uploads/".$this->input->post('output');

        if(file_exists($output_file)){
            if(unlink(FCPATH."uploads/".$this->input->post('output'))){
                $nomeImagem = explode('.',$output_file);
                $nomeThumb = $nomeImagem[0];
                $nomeFinal = explode('/', $nomeThumb);
                $ifp = fopen( $nomeThumb, 'wb' ); 
                $data = explode( ',', $base64_string );
                fwrite( $ifp, base64_decode( $data[ 1 ] ) );
                fclose( $ifp ); 
                $output['result'] = true;
                $output['file_name'] = end($nomeFinal); 
                $this->session->userdata('user')->picture = $output['file_name'];
            }else{
                $output = array('error' => "Falha ao deletar a imagem original");
            }
        }else{
                $output = array('error' => "Erro de permissão");
        }

        $this->output->set_content_type('application/json')
             ->set_content_type('Access-Control-Allow-Origin:*')
             ->set_output(json_encode($output));
    }
    
    public function picture() 
    {
        $config['upload_path'] = FCPATH."uploads/";
        $config['allowed_types'] = "jpg|png";
        $config['encrypt_name'] = true;
        
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('picture')) {
            $output = array('error' => $this->upload->display_errors());
        } else {
            $upload_data = $this->upload->data();
            $output = array('upload_data' => array('dados'=>$upload_data, "urlbase" => SITE_URL."uploads/",'file_name' => $upload_data['file_name']));
            $output['image'] = $this->CropImagePerfil("{$config['upload_path']}{$output['upload_data']['file_name']}");
        }  

        $this->output->set_content_type('application/json')
                ->set_content_type('Access-Control-Allow-Origin:*')
                ->set_output(json_encode($output));
    }
    
    protected function CropImagePerfil($img_src)
    {
        // $upload_data = getimagesize($img_src);
        // $upload_data['image_width'] = $upload_data[0];
        // $upload_data['image_height'] = $upload_data[1];
        // $image_config["image_library"] = "gd2";
        // $image_config["source_image"] = $img_src;
        // $image_config['create_thumb'] = FALSE;
        // $image_config['maintain_ratio'] = TRUE;
        // $image_config['new_image'] = $img_src;
        // $image_config['quality'] = "100%";
        // $image_config['width'] = 800;
        // $image_config['height'] = 600;
        // $dim = ($image_config['width'] / $image_config['height']);
        // $image_config['master_dim'] = "height";

        // $this->load->library('image_lib');
        // $this->image_lib->initialize($image_config);

        // if(!$this->image_lib->resize()){
        //     $status['resize'] = $this->image_lib->display_errors();
        // } else {
        //     $status['resize'] = "ok";
        // }
        
        // unset($image_config);
        
        // $image_size = getimagesize($img_src);        
        // $image_config['image_library'] = 'gd2';
        // $image_config['source_image'] = $img_src;
        // $image_config['new_image'] = $img_src;
        // $image_config['quality'] = "100%";
        // $image_config['maintain_ratio'] = FALSE;
        // $image_config['width'] = 100;
        // $image_config['height'] = 100;
        // $image_config['x_axis'] = 100 - ($image_size[0] / 2);
        // $image_config['y_axis'] = '0';

        // $this->image_lib->clear();
        // $this->image_lib->initialize($image_config); 

        // if (!$this->image_lib->crop()){
        //     $status['crop'] = $this->image_lib->display_errors();
        // } else {
        //     $status['crop'] = "ok";
        // }

        $status['crop'] = "ok";
        return $status;
    }

    public function cancel_upload($img){
      $upload_path = FCPATH."uploads/";
      if(file_exists($upload_path.$img)){
        unlink($upload_path.$img);
      }
    }


    public function remove()
    {
        $output = array();
        $token = $this->session->userdata('action');
        $compare = $this->encrypt->decode(base64_decode($this->input->post('token')));
        
        if ($token == $compare) {
            $file = $this->input->post('file');
            if (unlink(FCPATH."uploads/{$file}")) {
                $output['status'] = 'ok';
            } else {
                $output['status'] = array('error' => 'o arquivo não foi removido');
            }
        } else {
            $output['status'] = array('error' => "você não tem permissão para executar essa ação");
        }
        
        $this->output->set_content_type('application/json')
                ->set_content_type('Access-Control-Allow-Origin:*')
                ->set_output(json_encode($output));
    }
}


