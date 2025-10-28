<?php
    defined('BASEPATH') OR exit('No direct script access allowed');

    class User_model extends MY_Model {
        public function __construct()
        {
            parent::__construct();
        }

        public function create_user($username, $user_id, $hashed_password) {
            $data = array(
                'username' => $username,
                'user_id' => $user_id,
                'user_password' => $hashed_password
            );

            return $this->db->insert('users', $data);
        }

        public function get_user_by_user_id($user_id) {
            $query = $this->db->get_where('users', array('user_id' => $user_id));
            return $query->row();
        }

        public function verify_user($user_id, $password) {
            $user = $this->get_user_by_user_id($user_id);

            if (!$user) {
                return false;
            }

            if (password_verify($password, $user->user_password)) {
                return $user;
            } else {
                return false;
            }
        }
    }