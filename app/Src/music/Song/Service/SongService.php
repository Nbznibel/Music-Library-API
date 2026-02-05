<?php
/* Service <SongService> - Application Logic & Services Implementation
** Version 2024-06-10
*/

namespace App\Src\music\song\Service;

use App\Core\ServiceCore;
use App\Core\XApp;
use App\Src\music\song\Model\Song;

class SongService extends ServiceCore
{
    //:::::::::::::::::::::::::::::::::::::::

    public const DOMAIN_ID  = 1001;
    public const SERVICE_ID = 1001;
    public const ENTITY_ID  = 1001;

    public const DOMAIN     = 'music';
    public const SERVICE    = 'song';
    public const ENTITY     = 'Song';
    public const TABLE      = 'songs';
    public const COMPONENT  = 'S';

    //==============================
    // Get Song
    //==============================
    public function fs_get($id)
    {
        $query = Song::query();


        $sv_obj = $query->where('id', $id)->first();

        if (!$sv_obj) {
            $msg = $this->fsv_lang('get_ko', ['entity' => 'Song', 'id' => $id]);
            return $this->fsv_error(XApp::ERR_RES_NOT_FOUND, $msg, null, $query);
        }


        $msg = $this->fsv_lang('get_ok', ['entity' => 'Song']);
        return $this->fsv_success('get_success', $msg, $sv_obj->toArray(), $query);
    }

    //==============================
    // Delete Song (Soft)
    //==============================
    public function fs_delete($id)
    {
        $query = Song::query();


        $sv_obj = $query->where('id', $id)->first();

        if (!$sv_obj) {
            $msg = $this->fsv_lang('delete_ko', ['entity' => 'Song', 'id' => $id]);
            return $this->fsv_error(XApp::ERR_RES_NOT_FOUND, $msg, null, $query);
        }

        $sv_obj->delete();

        $msg = $this->fsv_lang('delete_ok', ['entity' => 'Song']);
        return $this->fsv_success('delete_success', $msg, '', $query);
    }

    //==============================
    // Create validation rules
    //==============================
    public function fs_data_create()
    {
        return [
            'title'        => 'required|string|max:255',
            'artist'       => 'required|string|max:255',
            'duration'     => 'required|integer|min:1',
            'release_date' => 'nullable|date',
        ];
    }

    //==============================
    // Create Song
    //==============================
    public function fs_create($sv_data, $status)
    {
        $status = 201;
        $this->ftxn_start();

        $safe_data = $this->fsv_validate($sv_data, $this->fs_data_create());

        $safe_data['created_at'] = $this->fnow();
        // $safe_data['created_by'] = $this->user_id;
        $safe_data['updated_at'] = $this->fnow();
        // $safe_data['updated_by'] = $this->user_id;

        Song::unguard();
        $sv_obj = Song::create($safe_data);
        Song::reguard();

        $this->ftxn_commit();

        $this->f_audit_create($sv_obj->id, 1);

        $msg = $this->fsv_lang('create_ok', ['entity' => 'Song']);
        return $this->fsv_success('create_success', $msg, $sv_obj->toArray(), null, $status);
    }

    //==============================
    // Update validation rules
    //==============================
    public function fs_data_update()
    {
        return [
            'title'        => 'required|string|max:255',
            'artist'       => 'required|string|max:255',
            'duration'     => 'required|integer|min:1',
            'release_date' => 'nullable|date',
        ];
    }

    //==============================
    // Update Song
    //==============================
    public function fs_update($id, $sv_data)
    {
        $safe_data = $this->fsv_validate($sv_data, $this->fs_data_update());

        $safe_data['updated_at'] = $this->fnow();
        // $safe_data['updated_by'] = $this->user_id;

        $query = Song::query();


        $sv_obj = $query->where('id', $id)->first();

        if (!$sv_obj) {
            $msg = $this->fsv_lang('update_ko', ['entity' => 'Song', 'id' => $id]);
            return $this->fsv_error(XApp::ERR_RES_NOT_FOUND, $msg, null, $query);
        }

        Song::unguard();
        $sv_obj->update($safe_data);
        Song::reguard();

        $this->f_audit_update($id, 1);

        $msg = $this->fsv_lang('update_ok', ['entity' => 'Song', 'id' => $id]);
        return $this->fsv_success('update_success', $msg, $sv_obj->toArray(), $query);
    }

    //==============================
    // List Songs
    //==============================
    public function fs_list($filter_data = null)
    {
        $query = Song::query();


        if (!empty($filter_data['filter'])) {
            $filter = $filter_data['filter'];
            $query->where('title', 'like', "%{$filter}%")
                ->orWhere('artist', 'like', "%{$filter}%");
        }

        $data = $query->get();

        $this->f_audit_list(1);

        $msg = $this->fsv_lang('list_ok', ['entity' => 'Song']);
        return $this->fsv_success('list_success', $msg, $data, $query);
    }
    public function fs_paginatedData($dt_params, $filter = null)
    {
        //............................................................

        // Manage filter
        if (!$dt_params['paginated']) {
            $query = Song::query();
            $data = $query->get();
            return \commonApiResponse(false, 'dt_success', $data);
        } else {
            $query = Song::query();
            // Sorting
            if ($dt_params['order_by'] && $dt_params['order_type']) {

                $query->orderBy($dt_params['order_by'], $dt_params['order_type']);
            }
            // Searching
            if (!empty($dt_params['search'])) {
                $searchTerm = $dt_params['search'];

                $query->where(function ($q) use ($searchTerm, $dt_params) {
                    // Determine searchable fields
                    $searchableFields = !empty($dt_params['searchable'])
                        ? explode(',', $dt_params['searchable'])
                        : (new Song())->getFillable(); // fallback

                    foreach ($searchableFields as $field) {
                        $field = trim($field);

                        if (str_contains($field, '.')) {
                            $parts = explode('.', $field);
                            $finalField = array_pop($parts);
                            $relations = implode('.', $parts);

                            $q->orWhereHas($relations, function ($relQ) use ($finalField, $searchTerm) {
                                $relQ->where($finalField, 'LIKE', '%' . $searchTerm . '%');
                            });
                        } else {
                            // Simple field on the main model
                            $q->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                        }
                    }
                });
            }


            // Pagination
            $qr_result = $query->paginate($dt_params['rows_per_page']);
            // return $qr_result->currentPage();
            return \commonApiResponse(false, 'dt_success', $qr_result->items(), $qr_result->currentPage(), $qr_result->lastPage(), $qr_result->perPage(), $qr_result->total());
        }
    } //fs_datatable
}
