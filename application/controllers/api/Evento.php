<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Evento extends CI_Controller {

    var $latitude;
    var $longitude;

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
    }

    public function index() {
        $this->load->model('user_model', 'users');
        $where = array('email' => $this->input->post('email'), 'status' => 'enable', 'user_type_id' => 2);
        $user = $this->users->get_where($where)->row();
        if ($user and $this->encrypt->decode($user->password) == $this->input->post('senha')) {
            unset($user->password);
            $this->session->set_userdata('user', $user);
            $output = array('status' => 'ok', 'token' => base64_encode($this->encrypt->encode($user->user_id)));
        } else {
            $output = array('status' => 'erro', 'msg' => 'Usuário não encontrado');
        }

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function getTypes() {
        $this->load->model('event_types_model', 'types');
        //$this->db->where('private', '0');
        $types = $this->types->get_all()->result();
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($types));
    }

    public function getEventsPublic() {
        $this->load->model('events_model', 'events');

        $this->db->select('events.event_id,
                           events.name, 
                           events.city,
                           events.state,
                           DATE_FORMAT(events.start,"%d/%m/%Y %H:%i") as start,
                           DATE_FORMAT(events.end,"%d/%m/%Y %H:%i") as end,
                           events.neighborhood,
                           events.latitude,
                           events.longitude,
                           CONCAT("' . SITE_URL . 'uploads/", events.picture) as picture,event_types.name as category');
        $this->db->join("event_types", "event_types.event_type_id = events.event_type_id");

        $where = array(
            "events.status" => "enable", 
            "event_types.private" => 0, 
            'events.end >=' => date('Y-m-d H:i:s'),
        );
        
        if ($this->input->posts()) {
            $where["events.event_type_id"] = $this->input->post('event_type_id');
            $where["events.city"] = $this->input->post('city');
            $where["events.state"] = $this->input->post('state');
        }
        
        $events = $this->events->get_where($where)->result();

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($events));
    }

    public function getTypesPublic() {
        $where = array(
            'events.end >=' => date('Y-m-d H:i:s'),
            'events.status' => 'enable',
            'events.private' => 0
        );
        if (!empty($this->input->posts())) {

            $estado = $this->input->post('estado');
            $cidade = $this->input->post('cidade');
            if ($estado != "undefined" && $cidade != "undefined") {
                $where['events.state'] = $estado;
                $where['events.city'] = $cidade;
            }
        }
        $this->load->model('event_types_model', 'event_types');
        
        $this->db->select('event_types.event_type_id,'
                . 'event_types.name,'
                . 'CONCAT("'.SITE_URL.'assets/img/eventos/", event_types.image_type) as image_type,'
                . 'COUNT(event_types.event_type_id) as qtd');
        $this->db->join("events", "events.event_type_id = event_types.event_type_id");
        $this->db->group_by('event_types.event_type_id');
        
        $result = $this->event_types->get_where($where)->result();
        
        $output = array();
        foreach ($result as $item) {
            if ($item->qtd > 0) {
                $output[] = $item;
            }
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function getInfoTipoEventos() {
        $this->load->model('event_info_types_model', 'typesInfo');
        $this->data['fieldsOptions'] = $this->typesInfo->get_all()->result();
        //$output['html'] = $this->typesInfo->get_all()->result();
        //$output['html'] = html_compress($this->load->view('fields/layout_fields', $this->data, true));

        $this->db->select("event_info_types.*,
                           CONCAT('event_info_type_id_',event_info_types.event_info_type_id) as namefields");

        $output['html'] = $this->typesInfo->get_all()->result();
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function listaEventos() {
        $this->load->model('events_model', 'events');
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));

        $this->db->select("events.*,
                           events.event_id as ID,
                           CONCAT('" . SITE_URL . "uploads/', events.picture) as picture,
                           DATE_FORMAT(events.start, '%d/%m/%Y %H:%i') as data,
                           events.user_id as owner_id,
                           ")
                ->select("(SELECT CONCAT('" . SITE_URL . "uploads/', user.picture) FROM user WHERE user.user_id = owner_id) as owner_picture", false)
                ->select("(SELECT CONCAT(user.name,' ',user.lastname) FROM user WHERE user.user_id = owner_id) as owner_name", false)
                ->select("(SELECT COUNT(*) FROM event_guests WHERE event_guests.event_id = ID AND status = 'confirmed') as users_confirmed", false)
                ->join("event_types", "event_types.event_type_id=events.event_type_id")
                ->order_by('events.event_id', 'desc');
        $where = array('events.user_id' => $user_id,
            //'events.start >=' => date('Y-m-d H:i:s'),
            'events.status !=' => 'deleted'
        );

        $output = $this->events->get_where($where)->result_array();


        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function novo() 
    {
        $this->load->model('event_types_other_model', 'category_other');
        $this->load->model('event_infos_model', 'event_info');
        $this->load->model('events_model', 'event');
        $this->load->model('event_gallery_model', 'gallery');
        $this->load->model('event_cupom_model', 'cupom');
        $this->load->model('event_guests_model', 'guests');
        $this->load->model('user_model', 'user');
        $this->load->model('rates_model', 'rates');

        $post = $this->input->posts();
        $event_id = $post['event_id'];
        $post['picture'] = "";
        unset($post['event_id']);

        $post['user_id'] = $this->encrypt->decode(base64_decode($this->input->post('user_id')));
        if ($post['user_id'] == "") {
            $output = array('status' => 'erro', 'msg' => 'Falha ao Cadastrar o Evento');
            $this->output->set_content_type('application/json')
                    ->set_output(json_encode($output));
            return;
        }

        if (isset($post['edit_admin'])) {
            unset($post['user_id']);
            unset($post['edit_admin']);
        }

        $gallery = array();
        $gallery_update = array();
        $event_info = array();
        $cupons = array();
        $cupons_save = array();
        $guests = array();
        $guests_update = array();
        $category_other = array();

        if (!empty($post)) {
            if (isset($post['fields'])) {
                foreach ($post['fields'] as $chave => $valor) {
                    $event_info_type_id = explode('_', $chave);
                    $indice = end($event_info_type_id);
                    $event_info[$indice] = $valor;
                    unset($post['fields']);
                }
            }

            if (isset($post['category_other'])) {
                foreach ($post['category_other'] as $valor) {
                    if (isset($valor['value']) && $valor['value'] != "")
                        $category_other['value'] = $valor['value'];
                    if (isset($valor['event_type_other_id']))
                        $category_other['event_type_other_id'] = $valor['event_type_other_id'];
                }
                $category_other['event_type_id'] = $post['event_type_id'];
                unset($post['category_other']);
            }

            if (isset($post['fotos'])) {
                foreach ($post['fotos'] as $fotos) {
                    if (is_array($fotos)) {
                        if ($fotos['principal'] == "sim") {
                            $post['picture'] = $fotos['href'];
                        } else {
                            $gallery[] = $fotos['href'];
                        }
                    }
                }
                unset($post['fotos']);
            }

            if (isset($post['fotos_save'])) {
                foreach ($post['fotos_save'] as $key => $fotos) {
                    if (is_array($fotos)) {
                        if ($fotos['id_imagem'] == "event") {
                            unset($post['fotos_save'][$key]);
                        } else {
                            $gallery_update[] = $fotos;
                        }
                    }
                }
                unset($post['fotos_save']);
            }

            if (isset($post['guests_save'])) {
                if (isset($post['guests'])) {
                    foreach ($post['guests'] as $kg => $guest) {
                        foreach ($post['guests_save'] as $kgs => $guest_save) {
                            $post['guests_save'][$kgs]['event_id'] = $event_id;
                            if ($guest['user_id'] == $guest_save['user_id']) {
                                unset($post['guests'][$kg]);
                                unset($post['guests_save'][$kgs]);
                            }
                        }
                    }
                } else {
                    foreach ($post['guests_save'] as $kgs => $guest_save) {
                        $post['guests_save'][$kgs]['event_id'] = $event_id;
                    }
                }
                $guests_update = $post['guests_save'];
                unset($post['guests_save']);
            }

            if (isset($post['guests'])) {
                foreach ($post['guests'] as $guest) {
                    $guests[] = array(
                        $guest['user_id'],
                        $guest['status']
                    );
                }
                unset($post['guests']);
            }

            if (isset($post['cupons'])) {
                foreach ($post['cupons'] as $c) {
                    $cupons[] = $c;
                }
                unset($post['cupons']);
            }

            if (isset($post['cupons_save'])) {
                foreach ($post['cupons_save'] as $cupom) {
                    if (is_array($cupons_save)) {
                        $cupons_save[] = array(
                            'event_cupom_id' => $cupom['id'],
                            'event_id' => $event_id,
                            'cupom' => $cupom['cupom']
                        );
                    }
                }
                unset($post['cupons_save']);
            }

            $validar = $this->event->validar();
            if ($post['status'] == "incomplete" || $post['status'] == 'update_publish') {
                $op = true;
            } else if ($validar == true) {
                $op = true;
            } else {
                $op = false;
            }

            if ($op) {
                if (isset($post)) {
                    if ($post['status'] != "update_publish") {
                        if (($post['street']) != "" && ($post['neighborhood']) != "" && ($post['city']) != "" && ($post['state'])) {
                            $dadosEndereco = $post['street'] . ' ,' . $post['neighborhood'] . ',' . $post['city'] . ',' . $post['state'];
                            $this->getCoordenada($dadosEndereco);

                            $post['latitude'] = $this->latitude;
                            $post['longitude'] = $this->longitude;
                        }
                    } else {
                        $id_event = $event_id;
                        unset($post['status']);
                    }
                    if ($event_id != "") {

                        $id_event = $event_id;
                        $where = array('event_id' => $id_event);
                        $this->event->update($post, $where);
                    } else {
                        $id_event = $this->event->save($post);
                    }

                    $where = array('event_id' => $id_event);
                    $taxa_evento = $this->event->get_where($where)->row();

                    if (is_null($taxa_evento->rate)) {

                        $where_chef = array('user_id' => $taxa_evento->user_id);
                        $taxa_chef = $this->user->get_where($where_chef)->row();
                        if (is_null($taxa_chef->rate)) {
                            $where_rate = array('rate_id' => 1);
                            $taxa_global = $this->rates->get_where($where_rate)->row();
                            $dados['feeAmountSite'] = $taxa_global->rate_global;
                            $this->event->update($dados, $where);
                        } else {
                            $dados['feeAmountSite'] = $taxa_chef->rate;
                            $this->event->update($dados, $where);
                        }
                    } else {
                        $dados['feeAmountSite'] = $taxa_evento->rate;
                        $this->event->update($dados, $where);
                    }
                }

                if ($gallery_update) {
                    foreach ($gallery_update as $foto_remove) {
                        $removeGallery = array();
                        $removeGallery['event_id'] = $id_event;
                        $removeGallery['picture'] = $foto_remove['href'];
                        $this->gallery->delete($removeGallery);
                    }
                }

                if ($category_other) {
                    $category_other['event_id'] = $id_event;
                    if ($category_other['event_type_id'] == 6) {
                        if (isset($category_other['event_type_other_id'])) {
                            $this->category_other->update($category_other, array('event_type_other_id' => $category_other['event_type_other_id']));
                        } else {
                            $this->category_other->save($category_other);
                        }
                    } else {
                        if (isset($category_other['event_type_other_id']))
                            $this->category_other->delete(array('event_type_other_id' => $category_other['event_type_other_id']));
                    }
                }

                if ($gallery) {
                    foreach ($gallery as $outrasFotos) {
                        $salvaGaleria = array();
                        $salvaGaleria['event_id'] = $id_event;
                        $salvaGaleria['picture'] = $outrasFotos;
                        $this->gallery->save($salvaGaleria);
                    }
                }

                if ($guests_update) {
                    foreach ($guests_update as $guest_remove) {
                        $this->guests->delete($guest_remove);
                    }
                }

                if ($guests) {
                    foreach ($guests as $guest) {
                        $saveGuests = array();
                        $saveGuests['event_id'] = $id_event;
                        $saveGuests['user_id'] = $guest[0];
                        $saveGuests['status'] = $guest[1];
                        $this->guests->save($saveGuests);
                    }
                }

                if ($cupons) {
                    $this->event->genCode($id_event);
                    foreach ($cupons as $cup) {
                        $saveCupom = array();
                        $saveCupom['event_id'] = $id_event;
                        $saveCupom['cupom'] = $cup;
                        $this->cupom->save($saveCupom);
                    }
                }

                if ($cupons_save) {
                    foreach ($cupons_save as $cupom) {
                        $this->cupom->delete($cupom);
                    }
                }

                if ($event_id != "") {
                    foreach ($event_info as $event_info_id => $info_value) {
                        $salvarInEvent = array('info_value' => $info_value);
                        $where_info = array('event_info_id' => $event_info_id, 'event_id' => $id_event);
                        $this->event_info->update($salvarInEvent, $where_info);

                    }
                } else {
                    foreach ($event_info as $event_info_type_id => $info_value) {
                        $salvarInEventinfos = array();
                        $salvarInEventinfos['event_id'] = $id_event;
                        $salvarInEventinfos['event_info_type_id'] = $event_info_type_id;
                        $salvarInEventinfos['info_value'] = $info_value;
                        $event_info_id = $this->event_info->save($salvarInEventinfos);
                    }
                }
                $output = array('status' => 'ok', 'msg' => 'Cadastro Realizado com Sucesso', 'event_id' => $id_event);
            } else {
                $errorMsg = validation_errors();
                $output = array('status' => 'erro', 'msg' => $errorMsg);
            }
        } else {
            $output = array('status' => 'erro', 'msg' => 'Falha ao Cadastrar o Evento');
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function cancel() {
        $this->load->model('events_model', 'event');
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('user_id')));
        $post = $this->input->posts();
        $event_id = $post['event_id'];
        unset($post['event_id']);
        unset($post['user_id']);

        if ($user_id != "") {
            $this->event->update($post, array('event_id' => $event_id));
            $output = array('status' => 'ok', 'msg' => 'Evento cancelado com sucesso', 'event_id' => $event_id);
        } else {
            $output = array('status' => 'erro', 'msg' => 'Falha ao cancelar o evento');
        }

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function setImagemPrincipal() {
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $this->load->model('events_model', 'events');
        $where['event.event_id'] = $this->input->post('event_id');
        $where['event.user_id'] = $user_id;
        $set = array('picture' => str_replace(SITE_URL . 'uploads/', '', $this->input->post('picture')));
        $event = $this->events->update($set, $where);
        $this->output->set_content_type('application/json')
                ->set_output(json_encode(array('status' => 'ok')));
    }

    public function deleteImg() {
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $this->load->model('events_model', 'events');
        $this->load->model('event_gallery_model', 'event_gallery');
        $where['event_gallery.event_gallery_id'] = $this->input->post('event_gallery_id');
        $where['events.user_id'] = $user_id;

        $this->db->select('event_gallery.picture')
                ->join('event_gallery', 'event_gallery.event_id=events.event_id');
        $event = $this->events->get_where($where);
        if ($event) {
            preg_match('/uploads\/.+/', $event->picture, $matches);
            $path = FCPATH . $matches[0];
            if (is_file($path)) {
                unlink($path);
            }
            $this->event_gallery->delete($this->input->post('event_gallery_id'));
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode(array('status' => 'ok')));
    }

    public function getCoordenada($address) {
        $address = str_replace(" ", "+", $address);

        $url = "https://maps.google.com/maps/api/geocode/json?sensor=false&address=$address";

        $response = file_get_contents($url);
        
        $json = json_decode($response, TRUE); //generate array object from the response from the web
        $this->latitude = (isset($json['results'][0]))? $json['results'][0]['geometry']['location']['lat'] : $json['results']['0']['geometry']['location']['lat'];
        $this->longitude = (isset($json['results'][0]))? $json['results'][0]['geometry']['location']['lng'] : $json['results']['0']['geometry']['location']['lng'];

        //return ($json['results'][0]['geometry']['location']['lat'].",".$json['results'][0]['geometry']['location']['lng']);
    }

    public function listaEventosParticipantes() {
        $id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $this->db->select("user.*,event_guests.*,events.private");
        $this->db->from("event_guests");
        $this->db->join("user", "event_guests.user_id = user.user_id");
        $this->db->join("events", "events.event_id = event_guests.event_id");
        $this->db->where("event_guests.user_id", $id);
        $this->db->or_where("events.private", 0);
        $resultado = $this->db->get();

        $output['status'] = "sucesso";
        $output["eventos"] = $resultado->result();

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function usuario($token, $event_type_id) {
        $this->load->model('event_guests_model', 'event_guests');
        $user_id = $this->encrypt->decode(base64_decode($token));
        $this->db->select("events.*,
                           date_format(events.start,'%d-%m-%Y  %H:%i') as start,
                           date_format(events.end,'%d-%m-%Y  %H:%i') as end,
                           events.event_id as eventId,
                           (SELECT COUNT(*) FROM event_guests WHERE event_id = eventId) as total_guests,
                           (SELECT COUNT(*) FROM event_guests WHERE event_id = eventId AND status = 'confirmed') as total_confirmed,
                           DATE_FORMAT(events.start, '%d/%m/%Y %H:%i') as data,
                           CONCAT('" . SITE_URL . "uploads/', events.picture) as picture,
                           event_types.name as event_type
                           ")
                ->from("event_guests")
                ->join("events", "events.event_id =event_guests.event_id", 'right')
                ->join("event_types", "event_types.event_type_id=events.event_type_id")
                ->order_by('events.start', 'asc');
        $where = array("event_types.event_type_id" => $event_type_id);
        //$this->db->where("event_guests.user_id",$user_id);
        $this->db->where($where);
        $this->db->where("(event_guests.user_id = $user_id OR events.private = 0)", null, false);
        $output = $this->db->get()->result();

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function categoriasEventosUsuario($token) {
        $this->load->model('event_guests_model', 'event_guests');

        $user_id = $this->encrypt->decode(base64_decode($token));
        $this->db->select("event_types.event_type_id,
                           event_types.name, 
                           CONCAT('" . SITE_URL . "uploads/', event_types.img) as img,
                           COUNT(event_types.event_type_id) as total,
                           event_types.plural
                           ")
                ->from("event_guests")
                ->join("events", "events.event_id =event_guests.event_id", 'right')
                ->join("event_types", "event_types.event_type_id=events.event_type_id")
                ->group_by("event_types.event_type_id")
                ->order_by('event_types.event_type_id', 'asc');

        $this->db->where("event_guests.user_id", $user_id);

        $this->db->or_where("events.private", 0);

        $result = $this->db->get()->result();
        $output = array();
        foreach ($result as $item) {
            $item->descricao = 'Você foi convidado para ' . $item->total . ' ' . ($item->total > 1 ? $item->plural : $item->name);
            $output[] = $item;
        }

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function convidado($token) {
        $this->load->model('event_guests_model', 'event_guests');

        $user_id = $this->encrypt->decode(base64_decode($token));
        $this->db->select("events.event_id,
                           DATE_FORMAT(events.start, '%d/%m/%Y %H:%i') as data,
                           events.user_id as owner_id,
                           event_types.name as event_type,
                           events.user_id
                           ")
                ->select("(SELECT COUNT(*) FROM events JOIN event_guests ON event_guests.event_id=events.event_id WHERE events.user_id = owner_id AND event_guests.user_id = {$user_id} AND events.status = 'enable' AND events.start >= '" . date('Y-m-d H:i:s') . "') as total_events", false)
                ->select("(SELECT CONCAT('" . SITE_URL . "uploads/', user.picture) FROM user WHERE user.user_id = owner_id) as owner_picture", false)
                ->select("(SELECT CONCAT(user.name,' ',user.lastname) FROM user WHERE user.user_id = owner_id) as owner_name", false)
                ->select("(SELECT facebook_id FROM user WHERE user.user_id = owner_id) as owner_facebook_id", false)
                ->join("user", "event_guests.user_id = user.user_id")
                ->join("events", "events.event_id =event_guests.event_id")
                ->join("event_types", "event_types.event_type_id=events.event_type_id")
                ->where('events.status', 'enable')
                ->where_in('event_guests.status', array('invited', 'confirmed'))
                ->group_by('events.user_id')
                ->having('total_events >', 0);
        $where = array("event_guests.user_id" => $user_id);
        $resultado = $this->event_guests->get_where($where)->result_array();
        foreach ($resultado as $key => $item) {
            if(strstr($item['owner_picture'], 'user_default.png') and $item['owner_facebook_id']){
                $resultado[$key]['owner_picture'] = 'https://graph.facebook.com/'.$item['owner_facebook_id'].'/picture?type=square';
            }
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($resultado));
    }

    public function byUser() {
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $owner_id = $this->input->post('owner_id', $user_id);
        $this->load->model('event_guests_model', 'event_guests');
        $this->db->select("events.*,
                           events.event_id as ID,
                           CONCAT('" . SITE_URL . "uploads/', events.picture) as picture,
                           DATE_FORMAT(events.start, '%d/%m/%Y %H:%i') as data,
                           events.user_id as owner_id,
                           ")
                ->select("(SELECT CONCAT('" . SITE_URL . "uploads/', user.picture) FROM user WHERE user.user_id = owner_id) as owner_picture", false)
                ->select("(SELECT facebook_id FROM user WHERE user.user_id = owner_id) as owner_facebook_id", false)
                ->select("(SELECT CONCAT(user.name,' ',user.lastname) FROM user WHERE user.user_id = owner_id) as owner_name", false)
                ->select("(SELECT COUNT(*) FROM event_guests WHERE event_guests.event_id = ID AND status = 'confirmed') as users_confirmed", false)
                ->select("(SELECT sum(payments.qty_friends) FROM payments WHERE payments.event_id=events.event_id AND payments.status='Pago' group by payments.event_id) as convidados,")
                ->join("events", "events.event_id =event_guests.event_id")
                ->join("event_types", "event_types.event_type_id=events.event_type_id")
                //->where_in('event_guests.status', array('invited', 'confirmed'))
                ->where('events.status', 'enable')
                ->order_by('events.event_id', 'desc');
        $where = array("event_guests.user_id" => $user_id,
            "events.user_id" => $owner_id,
            "events.start >=" => date('Y-m-d H:i:s')
        );

        $resultado = $this->event_guests->get_where($where)->result_array();
        unset($resultado[0]['convidados']);
        foreach ($resultado as $key => $item) {
                if(strstr($item['owner_picture'], 'user_default.png') and $item['owner_facebook_id']){
                    $resultado[$key]['owner_picture'] = 'https://graph.facebook.com/'.$item['owner_facebook_id'].'/picture?type=square';
                }
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($resultado));
    }

    public function curriculum($chef_id) {
        $this->load->model('user_info_model', 'info');

        $this->db->select("user_info.info_key,
                           user_info.info_value,
                           user.email,user.name, 
                           CONCAT('" . SITE_URL . "uploads/', user.picture) as picture,
                           user.lastname");
        $this->db->join("user", "user.user_id=user_info.user_id");
        $where = array('user_info.user_id' => $chef_id);
        $resultado = $this->info->get_where($where);
        $output = $resultado->result();

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function info($event_id) {
        $this->load->model('events_model', 'events');
        $this->load->model('event_types_other_model', 'category_other');
        $output = $this->events->info($event_id);

        $category = $this->category_other->info($event_id);
        if ($output['event_type_id'] == 6) {
            $output['tipo'] = $category->value;
        }

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function getFriends() {
        $this->load->model('events_model', 'events');
        $output = array();
        if ($this->input->post('token') && $this->input->post('event_id')) {
            $where = array(
                'user_id' => $this->encrypt->decode(base64_decode($this->input->post('token'))),
                'event_id' => $this->input->post('event_id')
            );
            $output = $this->events->getFriends($where);
        } else {
            $output = array("status" => "error");
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }
    
    public function inviteFriend()
    {
        $this->load->model('User_model', 'user');
        $this->load->library('lib_onesignal');

        if ($this->input->posts()) {
            $evento = $this->input->post('event_id');
            $convidado = $this->input->post('friend_id');
            $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
            
            $dados_evento = $this->db->where("event_id",$evento)->get("events")->row();
            $dados_convidado = $this->user->getUserInfo(array('user_id' => $convidado));
            $quantidadeConvitePossiveis = $dados_evento->num_users;
            $quantidadeJaConvidadosEvento = $this->db->where("event_id", $evento)->where("status", 'confirmed')->get("event_guests")->num_rows();
            $query_convidados = $this->db->query("SELECT count(event_guest_id) as confirmados FROM event_guests WHERE event_id={$evento} AND status='confirmed'");
            $result_convidados = $query_convidados->result_array();
            $quantidadeJaConvidadosEvento = $result_convidados[0]['confirmados'];

            $data = (object) array();
            $data->convidado = $dados_convidado;
            $data->evento = $dados_evento;

            $output = array();

            if($quantidadeConvitePossiveis > $quantidadeJaConvidadosEvento){
                $dadosParaInsert = array(
                    'event_id' => $evento,
                    'user_id' => $convidado,
                    'status' => "invited",
                    'updated_at' => date("Y-m-d H:i:s")
                );

                $this->db->insert("event_guests",$dadosParaInsert);

                $this->db->select("user.*");
                $this->db->from("user");
                $this->db->join("friends","friends.friend_id = user.user_id");
                $this->db->where("friends.friend_id not in (select user_id from event_guests where event_id = {$evento})",null,false);
                $this->db->where("friends.user_id",$user_id);
                $resultadoNaoConvidados = $this->db->get()->result();

                $this->db->select("user.*");
                $this->db->from("user");
                $this->db->join("friends","friends.friend_id = user.user_id");
                $this->db->where("friends.friend_id in (select user_id from event_guests where event_id = {$evento})",null,false);
                $this->db->where("friends.user_id",$user_id);
                $resultadoConvidados = $this->db->get()->result();

                $output["status"] = "success";
                $output["listaConvidados"] = $resultadoConvidados;
                $output["listaNaoConvidados"] = $resultadoNaoConvidados;

                $this->sendEmailFromGuest($data);

                $msg = 'Você foi convidado para o evento '.$data->evento->name;
                $this->lib_onesignal->send(array($data->convidado->onesignal_userid), $msg);

            }else{
                $output["status"] = "error";
                $output["msg"] = "Evento já alcançou o maximo de convites possiveis";
            }
        }
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($output));
    }
    
    protected function sendEmailFromGuest($data)
    {
        $this->load->library('email');
        
        $this->email->from(EMAIL_FROM, "Dinner for Friends");
        $this->email->to($data->convidado->email);
        $this->email->subject("Convite para participar do evento {$data->evento->name}");
        
        $header = $this->load->view("emails/templates/header", $data, TRUE);
        $body = $this->load->view("emails/templates/convite_evento", $data, TRUE);
        $footer = $this->load->view("emails/templates/footer", $data, TRUE);

        $this->email->message("{$header}{$body}{$footer}");
        if ($this->email->send()) {
            return true;
        }
        return false;
    }
    
    public function listaEventosDetalhes($evento) {
        $this->load->model('event_infos_model', 'event_info');
        $this->load->model('events_model', 'event');

        $this->db->select("event_info_types.name,event_infos.info_value");
        $this->db->join("events", "events.event_id=event_infos.event_id");
        $this->db->join("event_info_types", "event_info_types.event_info_type_id=event_infos.event_info_type_id");
        $where = array("event_infos.event_id" => $evento);
        $resultado = $this->event_info->get_where($where);
        $output = $resultado->result();

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function getEventDetailPublic($evento) {
        $this->load->model('events_model', 'events');
        $this->load->model('event_cupom_user_model', 'event_cupom_user');

        $events = $this->events->info($evento);

        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        if ($user_id and $events['cupons']) {
            foreach ($events['cupons'] as $key => $item) {
                $where_cupom = array(
                    'event_cupom_id' => $key,
                    'user_id' => $user_id
                );
                $used = $this->event_cupom_user->get_where($where_cupom)->row();
                $events['cupons'][$key]['used'] = ($used ? 1 : 0);
            }
        }
        if($events['cupons'])
            $events['cupons'] = array_values($events['cupons']);

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($events));
    }

    public function paymentStatus() 
    {
        $this->load->model('event_guests_model', 'event_guests');
        $this->load->model('payments_model','payments');
        $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
        $where['payments.event_id'] = $this->input->post('event_id');

        $this->db->select('payments.status')
                ->join('payments_guests', 'payments.payment_id=payments_guests.payment_id', 'left')
                ->join('event_guests', 'payments_guests.event_guest_id=event_guests.event_guest_id', 'left')
                ->group_start()
                   ->where('event_guests.user_id', $user_id)
                   ->or_where('payments.user_id', $user_id)
                 ->group_end();

        $guest = $this->payments->get_where($where)->row();
        $output = array('status' => 'pendding');
        if ($guest) {
            if (in_array($guest->status, array('Pago', 'Completo'))) {
                $output = array('status' => 'confirmed');
            }
            if (in_array($guest->status, array('Em Análise', 'Aguardando Pagto.'))) {
                $output = array('status' => 'waitting');
            }
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function checkInviteFriendsForEvent() {
        $evento_id = $this->input->post("event_id");
        $user_id = $this->encrypt->decode(base64_decode($this->input->post("token")));
        $evento = $this->db->where("event_id", $evento_id)->get("events")->row();
        $quantidadeDeConvidadosPossiveisPorPessoa = $evento->invite_limit;
        $limiteMaximoDePessoasNoEvento = $evento->num_users;
        $quantidadeJaConvidada = $this->db->where("event_id", $evento_id)->get("event_guests")->num_rows();

        if ($limiteMaximoDePessoasNoEvento > $quantidadeJaConvidada) {
            $this->db->select("user.*");
            $this->db->from("user");
            $this->db->join("friends", "friends.friend_id = user.user_id");
            $this->db->where("friends.friend_id not in (select user_id from event_guests where event_id = '" . $evento_id . "')", null, false);
            $this->db->where("friends.user_id", $user_id);
            $resultadoNaoConvidados = $this->db->get();

            $this->db->select("user.*");
            $this->db->from("user");
            $this->db->join("friends", "friends.friend_id = user.user_id");
            $this->db->where("friends.friend_id in (select user_id from event_guests where event_id = '" . $evento_id . "')", null, false);
            $this->db->where("friends.user_id", $user_id);
            $resultadoConvidados = $this->db->get()->num_rows();

            if ($quantidadeDeConvidadosPossiveisPorPessoa > $resultadoConvidados) {
                $quantidadeDisponiveisNoMomento = $quantidadeDeConvidadosPossiveisPorPessoa - $resultadoConvidados;
                $output = array(
                    'status' => "success",
                    'ConvitesDisponiveis' => $quantidadeDisponiveisNoMomento,
                    'ListagemDeAmigosNaoConvidados' => $resultadoNaoConvidados->result()
                );
            } else {

                $output = array(
                    'status' => "error",
                    'msg' => "É permitido somente {$quantidadeDeConvidadosPossiveisPorPessoa} por participante"
                );
            }
        } else {
            $output = array(
                'status' => "error",
                'msg' => "Este evento já alcançou o máximo de participantes"
            );
        }

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function insertInvitedInEvent() {
        $amigos = $this->input->post("amigos");
        $evento = $this->db->where("event_id", $this->input->post("evento"))->get("events")->row();
        $limiteMaximoDePessoasNoEvento = $evento->num_users;
        $errors = 0;

        foreach ($amigos as $amigo) {
            $quantidadeJaConvidada = $this->db->where("event_id", $this->input->post("evento"))->get("event_guests")->num_rows();
            if ($limiteMaximoDePessoasNoEvento > $quantidadeJaConvidada) {
                $usuario = $this->db->where("user_id", $amigo)->get("user")->row();
                $this->db->insert("event_guests", array('event_id' => $this->input->post("evento"), 'user_id' => $amigo));
                $output['inscrito'][] = $usuario;
            } else {
                $usuario = $this->db->where("user_id", $amigo)->get("user")->row();
                $output["errors"][] = $usuario;
                $errors = $errors + 1;
            }
        }

        if ($errors == 0) {
            $output["status"] = "sucesso";
        } else {
            $output["status"] = "erro";
            $output["msg"] = "evento Esgotado";
        }

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function invitedEmail() {
        $email = $this->input->post("email");
        $event_id = $this->input->post("event_id");
        $separaEmail = explode(",", $email);
        $user_id = $this->encrypt->decode(base64_decode($this->input->post("token")));
        $evento = $this->db->where("event_id", $event_id)->get("events")->row();
        //print_r($evento);
        $quantidadeDeConvidadosPossiveisPorPessoa = $evento->invite_limit;
        $limiteMaximoDePessoasNoEvento = $evento->num_users;
        $erro = 0;
        foreach ($separaEmail as $item) {
            $quantidadeJaConvidada = $this->db->where("event_id", $event_id)->get("event_guests")->num_rows();
            if ($quantidadeDeConvidadosPossiveisPorPessoa > $quantidadeJaConvidada) {
                $codigo = rand(11111111, 99999999);
                $dados = array(
                    'code' => $codigo,
                    'email' => $item,
                    'event_id' => $event_id
                );
                $this->db->insert("invite_codes", $dados);
                $de = "atendimento@dinnerforfriends.com.br";
                $para = $email;

                $msg = "<h1>Convite de partição Chef Amigo</h1>";
                $msg .= "<p><strong>Codigo:</strong>" . $codigo . "</p>";

                $this->load->library('email');
                $this->email->from($de, 'Convite de partição Chef Amigo');
                $this->email->to($para);
                $this->email->subject('Convite de partição Chef Amigo');

                $this->email->message($msg);

                if ($this->email->send()) {
                    $output["enviado"][] = $item;
                } else {
                    echo $this->email->print_debugger();
                    exit();
                    //$output["status"]="error";
                    //$output["msg"] = "Não foi possivel enviar e-mail no momento";
                }
            } else {
                $erro = $erro + 1;
                $output["naoenvidados"][] = $item;
            }
        }

        if ($erro == 0) {
            $output["status"] = "success";
            $output["msg"] = "convites enviados com successo";
        } else {
            $output["status"] = "error";
            $output["msg"] = "Não foi possivel enviar todos os convites, quantidade de convites não dispoível no momento";
        }

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function sendEmailFromGuests() {
        $this->load->model('events_model', 'event');
        $this->load->model('event_guests_model', 'guests');
        $this->load->library('email');

        $post = $this->input->posts();
        $event_id = $post['event_id'];

        $evento = $this->event->getInfoEvent(array('event_id' => $event_id));
        $guests = $this->guests->get_guests($event_id);

        $output = array();
        foreach ($guests['guests'] as $guest) {
            $data = (object) array();
            $data->convidado = (object) $guest;
            $data->evento = (object) $evento;

            $this->email->from(EMAIL_FROM, "Dinner for Friends");
            $this->email->to($data->convidado->email);
            $this->email->subject("Convite para participar do evento {$data->evento->name}");

            $header = $this->load->view("emails/templates/header", $data, TRUE);
            $body = $this->load->view("emails/templates/convite_evento", $data, TRUE);
            $footer = $this->load->view("emails/templates/footer", $data, TRUE);

            $this->email->message("{$header}{$body}{$footer}");
            if ($this->email->send()) {
                $output['email']['success'][] = $data->convidado->email;
                $updateGuest = array('status' => 'invited');
                $where = array('event_id' => $event_id, 'user_id' => $data->convidado->user_id);
                $this->guests->update($updateGuest, $where);
            } else {
                $output['email']['error'][] = array('Err' => $data->convidado->email, "Msg" => $this->email->print_debugger());
            }
            $this->email->clear(TRUE);
        }
        if (count($guests['guests']) != 0) {
            if (isset($output['email']) && count($output['email']['success']) == count($guests['guests'])) {
                $output['send'] = array('status' => 'ok', 'msg' => 'Convites enviados com sucesso');
            } else {
                $output['send'] = array('status' => 'incomplete', 'msg' => 'Alguns convites podem não ter sido enviados');
            }
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($output));
    }

    protected function sendEmailInvitation($data) {

        echo "{$header}{$body}{$footer}\n\n\n";
    }

    public function insertCommentForEvent() {

        $user_id = $this->encrypt->decode(base64_decode($this->input->post("token")));
        $event_id = $this->input->post("event_id");
        $comment = $this->input->post("comment");

        $datetime = date("Y-m-d H:i:s");
        $dadosInsert = array(
            'user_id' => $user_id,
            'event_id' => $event_id,
            'datetime' => $datetime,
            'comment' => $comment,
            'status' => "enable"
        );

        $this->db->insert("event_comments", $dadosInsert);
        $listagem = $this->db->select("user.name, user.lastname, event_comments.comment, DATE_FORMAT(event_comments.datetime, '%d/%m/%Y %H:%i') as date")
                        ->from("event_comments")
                        ->join("user", "event_comments.user_id=user.user_id")
                        ->where("event_id", $event_id)
                        ->get()->result();

        $output["status"] = "success";
        $output["comentarios"] = $listagem;

        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }

    public function addEventGuest() {
        $this->load->model('user_model', 'user');
        $this->load->model('invite_codes_model', 'invite_codes');
        $this->load->model('event_guests_model', 'event_guests');

        $this->db->join('events', 'events.event_id=invite_codes.event_id');
        $code = $this->invite_codes->get_where(array('invite_codes.code' => $this->input->post('code'), 'invite_codes.status' => 'pending'))->row();
        if ($code) {
            $user_id = $this->encrypt->decode(base64_decode($this->input->post('token')));
            $save_guest = array('event_id' => $code->event_id, 'user_id' => $user_id);
            if ($this->event_guests->get_where($save_guest)->row()) {
                $output = array('status' => 'error', 'title' => 'Atenção', 'msg' => 'Você já está na lista de convidados desse evento.');
            } else {
                $this->user->beFriends($user_id, $code->user_id);
                $this->invite_codes->update(array('status' => 'registered'), array('code' => $this->input->post('code')));
                $save_guest['status'] = 'invited';
                $this->event_guests->save($save_guest);
                $output = array('status' => 'success', 'title' => 'Parabéns', 'msg' => 'Você entrou na lista de convidados');
            }
        } else {
            $output = array('status' => 'error', 'title' => 'Atenção', 'msg' => 'Código não reconhecido.');
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }
    
    public function like() {
        $this->load->model('events_model', 'events');
        $output = array();
        if ($this->input->post('token') && $this->input->post('event_id')) {
            $dados = array(
                'user_id' => $this->encrypt->decode(base64_decode($this->input->post('token'))),
                'event_id' => $this->input->post('event_id')
            );
            if ($this->events->like($dados) > 0) 
                $output = array("status" => "success");
            else
                $output = array("status" => "error");
        } else {
            $output = array("status" => "error");
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }
    
    public function getLikes()
    {
        $this->load->model('events_model', 'events');
        $output = array();
        if ($this->input->post('token') && $this->input->post('event_id')) {
            $dados = array(
                'event_like.user_id' => $this->encrypt->decode(base64_decode($this->input->post('token'))),
                'event_like.event_id' => $this->input->post('event_id')
            );
            $output = $this->events->getLikes($dados);
        }
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }
    
    
    public function updatePaymentStatus()
    {
        $output = array();
        $this->load->model("Payments_model", "payments");
        $this->load->model("Event_guests_model", "guests");
        
        $where = array(
            'status' => 'Pago'
        );
        $events = $this->payments->get_where($where)->result();
        foreach ($events as $item) {
            $where_confirmed = array(
                'event_id' => $item->event_id,
                'user_id' => $item->user_id
            );
            $this->guests->update(array('status' => 'confirmed'), $where_confirmed);
        }
        
        $this->output->set_content_type('application/json')
                ->set_output(json_encode($output));
    }


    public function notificacao()
    {
        $this->load->library("lib_apipagseguroevento");
        $this->load->model('payments_events_model','payments_events');
        $this->load->model('events_model','events');

        if($this->input->post('notificationCode') and $this->lib_apipagseguroevento->notificationPost()){

            $this->db->select('event_id,status');
            $event_item = $this->payments_events->get_where(array('payment_id'=>$this->lib_apipagseguroevento->ipn_data->reference))->row();

            $save['payment_id'] = $this->lib_apipagseguroevento->ipn_data->reference;
            $save['discountAmount'] = $this->lib_apipagseguroevento->ipn_data->discountAmount;
            $save['feeAmountPagseguro'] = $this->lib_apipagseguroevento->ipn_data->feeAmount;
            $save['netAmount'] = $this->lib_apipagseguroevento->ipn_data->netAmount;
            $save['extraAmount'] = $this->lib_apipagseguroevento->ipn_data->extraAmount;
            $save['status'] = pagseguro_status($this->lib_apipagseguroevento->ipn_data->status);
            $this->payments_events->save($save);
            if($event_item ->status != "Pago" ){
                $update_status['event_id'] = $event_item->event_id;
                $update_status['status_payment'] = pagseguro_status($this->lib_apipagseguroevento->ipn_data->status);
                $this->events->save($update_status);
            }

        }
    }
}
