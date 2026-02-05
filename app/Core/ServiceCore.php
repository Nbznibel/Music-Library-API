<?php


namespace App\Core;

use App\Core\XApp;
use App\Core\XUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceCore
{
     //:::::::::::::::::::::::::::::::::::::::

     protected $front_type_id = 0;
     protected $needed_right = null;

     protected $user_secured = false;

     protected $time_start     = null;
     protected $time_end       = null;

     protected $sql_debug = true;

     protected $need_search = true;


     protected $lang_def = 'service';

     public function checkUser($request)
     {
          // $user = $request->user;
          // $this->user_id = $user->id;
          // $this->user = $user;
          // $this->role_id = $user->role_id;
     }

     public function ftxn_start()
     {
          DB::beginTransaction();
     }
     //................................
     public function ftxn_commit()
     {
          DB::commit();
     }
     //................................
     public function ftxn_rollback()
     {
          DB::rollBack();
     }
     //................................


     public function fnow()
     {
          return date('Y-m-d H:i:s');
     }
     //.............................................................

     public function fsv_lang($key, $data = [])
     {
          //............................................
          $msg = __($this->lang_def . '.' . $key, $data);
          return $msg;
     }


     public function f_encrypt($plaintext, $_key = null)
     {
          //............................................

          $key = $_key ? $_key : 'ServiceEncryptionKey';

          $method = "AES-256-CBC";
          $iv = openssl_random_pseudo_bytes(16);
          $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
          return base64_encode($iv . $ciphertext);
     }



     /**
      ** Here we scope the service call 
     
      */
     //     public function fs_scope( $front_type_id, $user_id, ){ 
     public function fs_scope($front_type_id)
     {
          //..........................................................................

          $this->front_type_id = $front_type_id;

          return array(
               'success' => true,
               'message' => 'Service initialized.'
          );
     }


     //..............................................................    
     public function fsv_get($id)
     {
          // return $this->fsv_service(XUser::RIGHT_VIEW,  function () use ($id) {
          return $this->fs_get($id);
          // });
     }

     //..............................................................    
     public function fsv_delete($id)
     {
          // return $this->fsv_service(XUser::RIGHT_MANAGE, function () use ($id) {
          // $this->checkUser();
          return $this->fs_delete($id);
          // });
     }
     public function fsv_force_delete($id)
     {
          return $this->fsv_service(XUser::RIGHT_MANAGE, function () use ($id) {
               return $this->fs_force_delete($id);
          });
     }
     public function fsv_restore_delete($id)
     {
          // return $this->fsv_service(XUser::RIGHT_MANAGE, function () use ($id) {
               return $this->fs_restore_delete($id);
          // });
     }

     //..............................................................    
     public function fsv_data_create()
     {
          // return $this->fsv_service(XUser::RIGHT_MANAGE, function () {
          return $this->fs_data_create();
          // });
     }

     //..............................................................    
     public function fsv_data_state()
     {
          // return $this->fsv_service(XUser::RIGHT_MANAGE, function () {
          return $this->fs_data_state();
          // });
     }

     public function fsv_create($sv_data,$status)
     {
          // return $this->fsv_service(XUser::RIGHT_MANAGE, function () use ($sv_data) {
          // $this->checkUser();
          return $this->fs_create($sv_data,$status);
          // });
     }

     public function fsv_state($sv_data)
     {
          // return $this->fsv_service(XUser::RIGHT_MANAGE, function () use ($sv_data) {
          // $this->checkUser();
          return $this->fs_state($sv_data);
          // });
     }

     //..............................................................    
     public function fsv_data_update()
     {
          // return $this->fsv_service(XUser::RIGHT_MANAGE, function () {
          return $this->fs_data_update();
          // });
     }
     public function fsv_update($id, $sv_data)
     {
          // return $this->fsv_service(XUser::RIGHT_MANAGE, function () use ($id, $sv_data) {
          // $this->checkUser();
          return $this->fs_update($id, $sv_data);
          // });
     }


     //..............................................................    
     public function fsv_filter_list()
     {
          // return $this->fsv_service(XUser::RIGHT_VIEW, function () {
               return $this->fs_filter_list();
          // });
     }
     public function fsv_list($filter_data)
     {
          // return $this->fsv_service(XUser::RIGHT_VIEW, function () use ($filter_data) {
          return $this->fs_list($filter_data);
          // });
     }

     //..............................................................    
     public function fsv_filter_datatable()
     {
          // return $this->fsv_service(XUser::RIGHT_VIEW, function () {
               return $this->fs_filter_datatable();
          // });
     }
     public function fsv_datatable($params, $filter)
     {
          // return $this->fsv_service(XUser::RIGHT_VIEW, function () use ($params, $filter) {
               return $this->fs_datatable($params, $filter);
          // });
     }




     public function fsv_service($needed_right, $f_cbk)
     {
          //..............................................................

          // Check Credentials-scope 
          if (! $this->user_secured) {
               return array('success' => false, 'message' => 'User not authenticated and/or not authorized');
          }

          // Check rights
          $has_right = $this->f_check_rights($needed_right);
          if (!$has_right) {
               //return array('success' => false, 'message' => "Not authorized, rights not enought for this operation [$needed_right] [$has_right]");
          }

          // Return error if no callback
          if (! $f_cbk) {
               return $this->fsv_error('not_implemented', 'Service implementation not found', null); //array('success' => false, 'message' => 'Service Callback Not Defined');
          }


          // [0] Start timer
          $this->time_start = microtime(true);



          // [3] Processing Callback
          $sv_res =  $f_cbk();

          // erreur ou pas on retourne la reponse normalisée du service
          return $sv_res;
     } //fsv_service


     /*#####################################
       ##### A u d i t 
       #####################################*/

     /** * Audit Read */ //..........................................................................    
     public function f_audit_read($entity_id, $action_success = 0, $resource = 0)
     {
          $this->f_audit(XApp::AUDIT_ENTITY_READ,          $entity_id, $action_success, $resource);
     }

     /** * Audit Create */ //..........................................................................    
     public function f_audit_create($entity_id, $action_success = 0, $resource = 0, $org_id = 0)
     {
          // $this->f_audit(XApp::AUDIT_ENTITY_CREATE,        $entity_id, $action_success, $resource, $org_id);
     }

     /** * Audit Update */ //..........................................................................    
     public function f_audit_update($entity_id, $action_success = 0, $resource = 0)
     {
          $this->f_audit(XApp::AUDIT_ENTITY_UPDATE,        $entity_id, $action_success, $resource);
     }

     /** * Audit SoftDelete */ //..........................................................................    
     public function f_audit_soft_delete($entity_id, $action_success = 0, $resource = 0)
     {
          $this->f_audit(XApp::AUDIT_ENTITY_SOFT_DELETE,   $entity_id, $action_success, $resource);
     }

     /** * Audit ForceDelete */ //..........................................................................    
     public function f_audit_force_delete($entity_id, $action_success = 0, $resource = 0)
     {
          $this->f_audit(XApp::AUDIT_ENTITY_FORCE_DELETE,  $entity_id, $action_success, $resource);
     }

     /** * Audit RestoreDelete */ //..........................................................................    
     public function f_audit_restore_delete($entity_id, $action_success = 0, $resource = 0)
     {
          $this->f_audit(XApp::AUDIT_ENTITY_RESTORE_DELETE, $entity_id, $action_success, $resource);
     }

     /** * Audit RestoreDelete */ //..........................................................................    
     public function f_audit_list($action_success = 0)
     {
          $this->f_audit(XApp::AUDIT_ENTITY_LIST, 0, $action_success);
     }

     /*** Audit Generic */
     public function f_audit($action_type, $entity_id, $action_success = 0, $resource = 0, $org_id = 0)
     {
          //..........................................................................    

          // $audit_resource = $this::ENTITY_ID;
          // if ($resource) $audit_resource = $resource;

          // if (
          //      !$this->need_audit
          //      ||
          //      $action_success == 0
          // ) return;

          // $xaudit = new CoreAudit();
          // $xaudit->insert([
          //      'front_app'         => $this->front_type_id,
          //      'entity_id'         => $entity_id,
          //      'action_type'       => $action_type,
          //      'action_success'    => $action_success,
          //      //'created_at'        => $this->fnow(),
          //      'created_by'        => $this->user_id,
          //      'org_id'            => ($org_id ? $org_id : $this->org_id),
          //      'domain'            => $this::DOMAIN_ID,
          //      'entity_type'       => $audit_resource
          // ]);
     }



     /**
      * Validate and get safe data from input data
      */
     public function fsv_validate($data, $rules)
     {
          //.....................................    
          $validator = Validator::make($data, $rules);
          if ($validator->fails()) {
               //return [ 'success' => false, 'errors' => $validator->errors() ]; 
               throw new ValidationException($validator);
          }

          // $safe_data = array();
          //       foreach ($rules as $prop => $r) {
          //           if( isset( $data[$prop] )) $safe_data[$prop] = $data[$prop];
          //           else if( $data[$prop] === null ) $safe_data[$prop] = '';
          //           }

          $safe_data = $validator->validated();
          foreach ($safe_data as $prop => $val) {
               if ($val === null) $safe_data[$prop] = '';
               if ($val === '_NULL_') $safe_data[$prop] = '';
          }


          return $safe_data; //['success' => true, 'safe_data' => $safe_data];
     }


     /**
      * Filter for search list
      */
     public function fsv_filter($filter)
     {
          //.....................................    
     }



     /**
      *  Core DataTable 
      * 
      */
     //public function fsv_dt_core( $dtQuery, $start=0, $length=10, $colsToSearch=[], $search='', $orderCol='', $orderDir='', $draw=1  ){
     public function fsv_dt_core($dtQuery, $dt_params, $colsToSearch = [])
     {
          //...............................................................................................................................

          $start    = 0;
          if (isset($dt_params['start']))    $start = $dt_params['start'];
          $length   = 10;
          if (isset($dt_params['length']))   $length = $dt_params['length'];
          $search   = '';
          if (isset($dt_params['search']))   $search = $dt_params['search'];
          $orderCol = '';
          if (isset($dt_params['orderCol'])) $orderCol = $dt_params['orderCol'];
          $orderDir = '';
          if (isset($dt_params['orderDir'])) $orderDir = $dt_params['orderDir'];
          $draw     = 1;
          if (isset($dt_params['draw']))     $draw = $dt_params['draw'];

          // [1] Total records of base query
          $totalCount = $dtQuery->count();


          // [2] Filtering
          $nbColFilter = sizeof($colsToSearch);
          if ($nbColFilter && $search) {
               //$keys = array_keys($colsToSearch);
               for ($i = 0; $i < $nbColFilter; $i++) {
                    $search_col = $colsToSearch[$i];
                    $search_val = $search; //$colsToSearch[ $keys[$i] ];
                    if ($i == 0)
                         $dtQuery->where($search_col, 'LIKE', "%$search_val%");
                    else      $dtQuery->orWhere($search_col, 'LIKE', "%$search_val%");
               }
          }


          // [3] Count filtered data
          $filteredCount = $dtQuery->count();


          // Sorting
          if ($orderCol && $orderDir) {

               $dtQuery->orderBy($orderCol, $orderDir);
          }

          // Pagination
          $dtQuery->offset($start)->limit($length);

          // Fetching the items
          $dt_data = $dtQuery->get();

          return [
               'draw'              => $draw,
               'recordsTotal'      => $totalCount,
               'recordsFiltered'   => $filteredCount,
               'data'              => $dt_data,
               '_q_'               => $this->f_sql_query($dtQuery)
          ];
     }



     //#################################################################################################



     public function fsv_success($_code = '', $_msg = '', $_data = null, $_query = null)
     {
          //..............................................................................


          $dbx = $this->sql_debug;

          $resp = array('success' => true);

          if ($_code)        $resp['code']       = $_code;
          if ($_msg)         $resp['message']    = $_msg;
          if ($_data)        $resp['payload']       = $_data;


          if ($dbx === false) return $resp;

          $this->time_end = microtime(true);
          $resp['_ts_']        = $this->time_end - $this->time_start;
          if ($_query)    $resp['_qm_']        = $this->f_sql_query($_query);

          // return commonApiResponse(false, $_code , $_msg , $_data , 1, 10, 10, 99);
          return commonApiResponse(false, $_msg, $_data);
          // return $resp;
     }

     public function fsv_error($_code, $_msg = '', $_data = null, $_query, $_codeError = 400)
     {
          //.....................................................................

          $dbx = $this->sql_debug;

          $resp = array('success' => false);

          if ($_code)        $resp['code']       = $_code;
          if ($_msg)         $resp['message']    = $_msg;
          if ($_data)        $resp['data']       = $_data;

          if ($dbx === false) return $resp;

          $this->time_end = microtime(true);
          $resp['_t_']        = $this->time_end - $this->time_start;
          if ($_query)    $resp['_q_']        = $this->f_sql_query($_query);

          return commonApiErrorResponse($_code, [], $_data, $_codeError);
     }

     public function f_sql_query($q)
     {
          //....................................
          $_sql = $q->toSql();
          $_bind  =  $q->getBindings();
          $_sql = str_replace(array('%', '?'), array('%%', '%s'), $_sql);
          $_sql = vsprintf($_sql, $_bind);

          return $_sql;
     }



     /** * Audit Read */ //..........................................................................    
     public function f_can_read($entity_id = 0)
     {
          // return $this->f_check_rights(XUser::RIGHT_VIEW, $entity_id);
     }

     /** * Audit Create */ //..........................................................................    
     public function f_can_create($entity_id = 0)
     {
          // return $this->f_check_rights(XUser::RIGHT_MANAGE, $entity_id);
     }

     /** * Audit Update */ //..........................................................................    
     public function f_can_update($entity_id = 0)
     {
          // return $this->f_check_rights(XUser::RIGHT_MANAGE, $entity_id);
     }

     /** * Audit SoftDelete */ //..........................................................................    
     public function f_can_soft_delete($entity_id = 0)
     {
          // return $this->f_check_rights(XUser::RIGHT_MANAGE, $entity_id);
     }

     /** * Audit ForceDelete */ //..........................................................................    
     public function f_can_force_delete($entity_id = 0)
     {
          // return $this->f_check_rights(XUser::RIGHT_MANAGE, $entity_id);
     }

     /** * Audit RestoreDelete */ //..........................................................................    
     public function f_can_restore_delete($entity_id = 0)
     {
          // return $this->f_check_rights(XUser::RIGHT_MANAGE, $entity_id);
     }

     /** * Audit RestoreDelete */ //..........................................................................    
     public function f_can_list()
     {
          // return $this->f_check_rights(XUser::RIGHT_VIEW);
     }


     public function f_check_rights($right_type = '', $entity_id = '')
     {
          //....................................................................

          $R = $this->user_rights;

          $has_right = false;

          if (
               isset($R[$this::DOMAIN])
               &&   isset($R[$this::DOMAIN][$this::SERVICE])
          ) {

               $service_right = $R[$this::DOMAIN][$this::SERVICE];
               if (
                    isset($service_right[$right_type])
                    &&   $service_right[$right_type] == 1
               )
                    $has_right = true;
          }

          return $has_right;
     }
}// Enf-Of-Class
