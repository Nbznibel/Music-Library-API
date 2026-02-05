<?php

namespace App\Core;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ApiCore extends Controller
{
    //::::::::::::::::::::::::::::::::::::::::::::

    use AuthorizesRequests, ValidatesRequests;

    protected $front_type_id = 0;

    protected $time_start     = null;
    protected $time_end       = null;

    /**
     * here we started
     */



    public function fc_ajax($needed_right, $f_cbk, $_svc = null)
    {
        //..............................................................

        if (! Auth::check()) {

            response()->json([
                'message' => 'Not Authenticated.',
                'code' => 'not_authenticated'
            ], 401);
        }

        // Return error if no callback
        if (! $f_cbk) {
            return array('success' => false, 'message' => 'Controler Callback Not Defined');
        }


        // [0] Start timer
        $this->time_start = microtime(true);


        $svc =  $_svc ? $_svc : $this->targetService;
        // [2] secure & scope the service call
        $svres = $svc->fs_scope($this->front_type_id);

        // process scoping errors : AuthN & AuthZ
        if ($svres['success'] !== true) {
            $err_code = 500;
            $err_data = '';
            switch ($svres['error']) {
                case XApp::ERR_RES_NOT_FOUND:
                    $err_code = 404;
                    break;
                case XApp::ERR_DATA_NOT_VALID:
                    $err_code = 422;
                    break;
                case XApp::ERR_ADD_CHILD_EXISTS:
                    $err_code = 422;
                    break;
                case XApp::ERR_FAILED_TRANSACTION:
                    $err_code = 422;
                    break;
                case XApp::ERR_UNIQUE_VIOLATION:
                    $err_code = 422;
                    break;

                default:
                    break;
            }

            $err_resp = ['error' => $svres['error'], 'message' => $svres['message'], '_' => 'ApiCore'];
            if ($err_data) $err_resp['details'] = $err_data;

            return response()->json($err_resp, $err_code);
        }

        // [2bis] Check rights for service
        // $has_right = $this->targetService->f_check_rights( $needed_right );
        // if( ! $has_right ){
        //         $err_message = "Not authorized, rights required for this operation [$needed_right] [$has_right]";
        //         abort(403, 'You are not authorized to perform this action.');
        //         //throw new Exception($err_message, 5001);
        //         //return array('success' => false, 'message' => $err_message);
        //         }


        // [3] Processing Callback
        $resp = $f_cbk($this->targetService);
        //echo json_encode( $resp ); exit;

        // process scoping errors : AuthN & AuthZ
        if (isset($resp['success']) && $resp['success'] !== true) {
            $err_code = 500;
            $err_data = '';
            switch ($resp['code']) {
                case XApp::ERR_REFUSED_OPERATION:
                    $err_code = 403;
                    break;
                case XApp::ERR_RES_NOT_FOUND:
                    $err_code = 404;
                    break;
                case XApp::ERR_DATA_NOT_VALID:
                    $err_code = 422;
                    break;
                case XApp::ERR_ADD_CHILD_EXISTS:
                    $err_code = 422;
                    break;

                case XApp::ERR_FAILED_TRANSACTION:
                    $err_code = 422;
                    break;
                case XApp::ERR_UNIQUE_VIOLATION:
                    $err_code = 422;
                    break;
                default:
                    break;
            }

            $err_resp = ['error' => $resp['code'], 'message' => $resp['message'], '_' => 'ApiCore'];
            if ($err_data) $err_resp['details'] = $err_data;

            return response()->json($err_resp, $err_code);
        }




        // [4] time end
        $this->time_end = microtime(true);
        $resp['_tc_'] = $this->time_end - $this->time_start;


        // [5] return response
        return response()->json($resp);
    } //fct_call

    public function fs_scope($front_type_id)
    {
        //..........................................................................

        $this->front_type_id = $front_type_id;
        return array(
            'success' => true,
            'message' => 'Service initialized.'
        );
    }

    public function getPaginationParams($request)
    {
        return array(
            'search'          => $request->search,
            'searchable'      => $request->searchable,
            'order_by'        => $request->order_by ?? "id",
            'order_type'      => $request->order_type ?? "asc",
            'paginated'       => $request->paginated ?? false,
            'page'            => $request->page ?? 0,               // by default $page=0,
            'rows_per_page'   => $request->rows_per_page ?? 10,        // by default $rowsPerPage=10,
        );
    }

    public function getPaginatedData(Request $request)
    {
        //...............................................

        $svc = $this->targetService;

        $dt_params = $this->getPaginationParams($request);
        if (method_exists($svc, 'fs_filter')) {
            $filter_rules = $svc->fs_filter($request);
            $dt_params["filters"] = $svc->fsv_validate($request->all(), $filter_rules);
        }
        $res = $svc->fs_paginatedData($dt_params);


        return $res;
    } // fct_list


    public function fnc_list(Request $request)
    {
        //...............................................

        return $this->getPaginatedData($request);
    } // fct_list

    public function fnc_delete(Request $request, string $id)
    {
        //...............................................

        $svc = $this->targetService;

        // delete with soft delete
        $res = $svc->fsv_delete($id, false);

        // check errors ?
        // . . . 

        return  $res;
    }

    public function fnc_create(Request $request)
    {
        //...............................................

        $svc = $this->targetService;

        // get create data & rule
        $data_rules = $svc->fsv_data_create();

        // Extract data from request and validate
        //$data =  $request->validate( $data_rules );

        $data = $this->fc_ajax_validate($request, $data_rules);

        if (!$data['has_error']) {
            $result = $svc->fsv_create($data['data'], 201);
                    return response()->json($result, 201);
        } else {
            return response()->json($data['data'], 422);
        }
    } // fct_create

    public function fnc_state(Request $request)
    {
        //...............................................

        $svc = $this->targetService;

        // get state data & rule
        $data_rules = $svc->fsv_data_state();

        // Extract data from request and validate
        //$data =  $request->validate( $data_rules );

        $data = $this->fc_ajax_validate($request, $data_rules);
        if (!$data['has_error']) {
            return $svc->fsv_state($data['data']);
        } else {
            return response()->json($data['data'], 422);
        }
    } // fct_state

    public function fnc_get(Request $request, string $id)
    {
        //...............................................

        $svc = $this->targetService;

        $res = $svc->fsv_get($id);

        // check errors ?
        // . . . 

        return  $res;
    }

    public function fnc_update(Request $request, string $id)
    {
        //...............................................

        $svc = $this->targetService;


        // get create data & rule
        $data_rules = $svc->fsv_data_update();

        // Extract data from request and validate
        //$data =  $request->validate( $data_rules );

        $data = $this->fc_ajax_validate($request, $data_rules);


        if (!$data['has_error']) {
            return $svc->fsv_update($id, $data['data']);
        } else {
            return response()->json($data['data'], 422);
        }
    } // fct_update

    //::::::::::::::::::::::::::::::::::::::::::::

    public function fc_ajax_validate($request, $data_rules)
    {
        //...........................................................
        try { // try validate and get data from request
            //echo json_encode($data_rules);exit;
            $safe_data =  $request->validate($data_rules);
            return ["has_error" => false, "data" => $safe_data];
        } catch (ValidationException $e) {

            Log::info("validation exception");
            return ["has_error" => true, "data" => [
                'success' => false,
                'message' => $e->getMessage(), //'Validation Error ',
                'errors' => $e->errors()
            ]];
        }
    }

    public function fc_svc_scope($_svc = null)
    {
        //..............................................................

        $svc->fs_scope($this->front_type_id);
    }

    public function fct_dt_params($request)
    {
        //.......................................

        return array(
            'search'        => $request->search['value'],
            'orderCol'      => $request->columns[$request->order[0]['column']]['data'],
            'orderDir'      => $request->order[0]['dir'],
            'draw'          => $request->draw,
            'start'         => $request->start,         //$start=0,
            'length'        => $request->length,        //$length=10,
        );
    }
} //Class_End
