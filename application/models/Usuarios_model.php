<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Usuarios_model extends MY_Model {

    var $id_col = 'usuarios_id';
    var $fields = array(
        'nome' => array(
            'type' => 'text',
            'class' => '',
            'label' => 'Nome',
            'rules' => 'required|min_length[3]',
            'extra' => array('required' => 'required'),
			'label_class' => 'col-md-2',
            'prepend' => '<div class="col-md-3">',
            'append' => '</div>',
        ),
        'sobrenome' => array(
            'type' => 'text',
            'class' => '',
            'label' => 'Sobrenome',
            'rules' => 'required|min_length[3]',
            'extra' => array('required' => 'required'),
        ),
        'email' => array(
            'type' => 'text',
            'label' => 'Email',
            'class' => '',
            'rules' => 'required|valid_email|callback_uniqEmail',
            'extra' => array('required' => 'required')
        ),
        // 'senha' => array(
        //     'type' => 'password',
        //     'label' => 'Senha',
        //     'rules' => 'required|min_length[4]',
        //     'class' => 'medium vObrigatorio',
        //     'extra' => array('required' => 'required'),
        // ),
        'sexo' => array(
            'type' => 'select',
            'label' => 'Sexo',
            'class' => '',
            'rules' => 'required',
            'values' => array(
                "" => "Selecione um sexo",
                "masculino" => "Masculino",
                "feminino" => "Feminino",
            ),
            'empty' => '--Selecine um sexo--',
        ),
        'status' => array(
            'type' => 'select',
            'label' => 'Status',
            'class' => '',
            'rules' => 'required',
            'values' => array(
                "" => "Selecione um status",
                "enable" => "Ativo",
                "disabled" => "Inativo",
            ),
            'empty' => '--Selecine um status--',
        ),
        'tipo_id' => array(
            'type' => 'select',
            'label' => 'Perfil',
            'rules' => 'required',
            'class' => '',
            'values' => array(
                "" => "Selecione um status",
                "profissional" => "Profissional",
                "aluno" => "Aluno",
            ),
            'empty' => '--Selecine um perfil--',
        ),
        'estado' => array(
            'type' => 'text',
            'label' => 'Estado',
            'rules' => 'required|min_length[2]',
            'class' => 'medium vObrigatorio',
            'extra' => array('required' => 'required'),
        ),
        'cidade' => array(
            'type' => 'text',
            'label' => 'Cidade',
            'rules' => 'required|min_length[4]',
            'class' => 'medium vObrigatorio',
            'extra' => array('required' => 'required'),
        ),
        'nascimento' => array(
            'type' => 'text',
            'label' => 'Nascimento',
            'rules' => 'required|min_length[4]',
            'class' => 'medium vObrigatorio',
            'extra' => array('required' => 'required'),
        ),

        'id' => array(
            'type' => 'text',
            'label' => 'Firebase ID',
            'rules' => 'required|min_length[4]',
            'class' => 'medium vObrigatorio',
            'extra' => array('required' => 'required'),
        ),
    );
}
