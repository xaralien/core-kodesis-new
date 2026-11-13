<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('isLogin') == FALSE) {
            $this->session->set_flashdata(
                'msg',
                '<div class="alert rounded-s bg-red-dark" role="alert">
                  Your session has been expired! Please login!
                  <button type="button" class="close color-white opacity-60 font-16" data-bs-dismiss="alert" aria-label="Close">&times;</button>
               </div>'
            );
            redirect('mobile/auth');
        }
        $this->load->model('mobile/M_app', 'M_app');
        $this->load->model('mobile/M_home', 'M_home');
    }

    public function index()
    {
        $data['title'] = 'Home';
        $data['user'] = $this->M_app->getData('users', ['nip' => $this->session->userdata('nip')])->row();
        $data['memo'] = $this->M_app->inbox($this->session->userdata('nip'));
        $data['task'] = $this->M_app->task($this->session->userdata('nip'));
        $data['task_stats'] = $this->M_home->get_task_statistics();

        // var_dump($data['task_stats']);
        $this->load->view('mobile/Layouts/v_header', $data);
        $this->load->view('mobile/v_home', $data);
        $this->load->view('mobile/Layouts/v_footer', $data);
    }
}
