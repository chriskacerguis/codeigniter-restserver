<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . '/libraries/REST_Controller.php';

class Endpoint extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('eventos_model');
        //$this->load->library('form_validation');
    }

    /*
    |
    |    SELECT
    |
     */
    public function method_get()
    {
        $result = $this->the_model->function();

        if ($result['code'] == 0) {
            $this- > response([
                'code'   => 2000,
                'title'  => 'FILA',
                'detail' => $result['row'],
            ], $this::HTTP_OK);
        } else {
            $this- > response(
                [
                    'code'   => 2004,
                    'title'  => 'CONSULTA VACÍA',
                    'detail' => 'No hubo resultados',
                ],
                $this::HTTP_NO_CONTENT
            );
        }
    }

    /*
    |
    |    INSERT
    |
     */
    public function method_post()
    {
        if (!$_POST) {
            $this- > response(
                [
                    'code'   => 4009,
                    'title'  => 'FORMULARIO VACÍO',
                    'detail' => 'No se enviaron datos.',
                ],
                $this::HTTP_BAD_REQUEST
            );
        }

        $data = [
            'field_name' => $this->input->post('field_name'),
        ];

        $result = $this->the_model->insert('table', $data);

        if ($result['code'] == 0) {
            $this- > response(
                [
                    'code'   => 2001,
                    'title'  => 'DATOS REGISTRADOS',
                    'detail' => 'ID: ' . $result['insert_id'],
                ],
                $this::HTTP_CREATED
            );
        } else {
            $this- > response(
                [
                    'code'   => $result['code'], // mySQL error code
                    'title'  => 'DATOS NO REGISTRADOS',
                    'detail' => $result['message'], // mySQL error message
                ],
                $this::HTTP_CONFLICT
            );
        }
    }

    /*
    |
    |    UPDATE
    |
     */
    public function method_put()
    {
        if (!$_POST) {
            $this- > response(
                [
                    'code'   => 4009,
                    'title'  => 'FORMULARIO VACIO',
                    'detail' => 'No se enviaron datos.',
                ],
                $this::HTTP_BAD_REQUEST
            );
        }

        $data = [
            'field_name' => $this->input->post('field_name'),
        ];

        $where = [
            'field_name' => $this->input->post('field_name'),
        ];

        $result = $this->the_model->update('table', $where, $data);

        if ($result['code'] == 0) {
            $this- > response(
                [
                    'code'   => 2000,
                    'title'  => 'DATOS ACTUALIZADOS',
                    'detail' => 'datos actualizados',
                ],
                $this::HTTP_OK// or HTTP_NO_CONTENT?
            );
        } elseif ($result['code'] == 3004) {
            $this- > response(
                [
                    'code'   => 3004,
                    'title'  => 'DATOS NO MODIFICADOS',
                    'detail' => 'datos no modificados', // mySQL error message
                ],
                $this::HTTP_NOT_MODIFIED
            );
        } else {
            $this- > response(
                [
                    'code'   => $result['code'], // mySQL error code
                    'title'  => 'DATOS NO MODIFICADOS',
                    'detail' => $result['message'], // mySQL error message
                ],
                $this::HTTP_CONFLICT
            );
        }
    }
    /*
    |
    |    DELETE
    |
     */
    public function method_delete()
    {
        if (!$_POST) {
            $this- > response(
                [
                    'code'   => 4009,
                    'title'  => 'FORMULARIO VACIO',
                    'detail' => 'No se enviaron datos.',
                ],
                $this::HTTP_BAD_REQUEST
            );
        }

        $where = [
            'field_name' => $this->input->post('field_name'),
        ];

        $result = $this->the_model->delete('table', $where);

        if ($result['code'] == 0) {
            $this- > response(
                [
                    'code'   => 2000,
                    'title'  => 'DATOS ELIMINADOS',
                    'detail' => 'datos eliminados',
                ],
                $this::HTTP_OK// or HTTP_NO_CONTENT?
            );
        } else {
            $this- > response(
                [
                    'code'   => $result['code'], // mySQL error code
                    'title'  => 'DATOS NO ELIMINADOS',
                    'detail' => $result['message'], // mySQL error message
                ],
                $this::HTTP_CONFLICT
            );
        }
    }
}
