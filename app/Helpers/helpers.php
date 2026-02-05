    <?php

    use App\Core\XReferenceModules;
    use Illuminate\Support\Facades\Route;

    if (!function_exists('fh_can_page')) {
        function fh_can_page($_rights, $_front, $_page)
        {
            //....................................................

            if ($_rights == '*') return true;

            require base_path("resources/views/fronts/pg-map-$_front.php");

            $pg_services = $_pg_map_[$_page];

            $sz = sizeof($pg_services);

            $has_right = true;
            for ($i = 0; $i < $sz; $i++) {
                $svc = $pg_services[$i];
                $_domain    = $svc['domain'];
                $_service   = $svc['service'];

                if (
                    !isset($_rights[$_domain])
                    ||  !isset($_rights[$_domain][$_service])
                    ||  (1 != $_rights[$_domain][$_service]['V'] && 1 != $_rights[$_domain][$_service]['M'])
                ) {

                    echo "Page not authz: $_front, $_page, \n" . json_encode($_rights);
                    exit;

                    // has rights ok !
                    $has_right = false;
                }
            }

            return $has_right;
        } //fh_has_right
    }


    if (!function_exists('fh_can_service')) {
        function fh_can_service($_rights, $_domain, $_service)
        {
            //....................................................

            $has_right = false;
            if (
                isset($_rights[$_domain])
                &&  isset($_rights[$_domain][$_service])
                &&  (1 == $_rights[$_domain][$_service])
            ) {
                // has rights ok !
                $has_right = true;
            }


            return $has_right;
        } //fh_has_right
    }


    if (!function_exists('fh_escape')) {
        function fh_escape($_s, $_c = "'")
        {
            //....................................................

            return str_replace($_c, ('\\' . $_c), $_s);
        } //fh_escape
    }


    if (!function_exists('fh_lb_js')) {
        function fh_lb_js($_klb)
        {
            //....................................................

            return fh_escape(__($_klb));
        } //fh_lb_js
    }




    if (!function_exists('fh_page_app_uri')) {
        function fh_page_app_uri($pg)
        {
            //....................................................

            preg_match('/^([^.]+\.[^.]+)/', Route::currentRouteName(), $matches);
            $pg_route = $matches[1];

            return $pg_route . '.' . $pg;
        } //fh_page_app_uri
    }

    use App\Core\XUser;
    use App\Models\RefCounter;
    use App\Models\User;
    use App\Src\organization\entity\Model\BusinessEntity;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Log;

    use function PHPUnit\Framework\isArray;



    if (!function_exists('commonApiResponse')) {
        function commonApiResponse(
            $_error = false,
            $_msg = '',
            $_data = null,
            $_currentPage = null,
            $_lastPage = null,
            $_perPage = null,
            $_total = null
        ) {
            $resp = array('success' => true);

            if (isset($_data['updated_by']) && isset($_data['updated_at'])) {
                $user = User::where('id', $_data['updated_by'])->first();
                if ($user != null) {
                    $_data['update'] = ["by" => $user->name, "at" => $_data['updated_at']];
                }
            }

            if ($_error)             $resp['error']              = $_error;
            if ($_msg)              $resp['message']           = $_msg;
            $resp['data']              = $_data != null ? $_data : [];
            if ($_currentPage)      $resp['currentPage']       = $_currentPage;
            if ($_lastPage)         $resp['lastPage']          = $_lastPage;
            if ($_perPage)          $resp['perPage']           = $_perPage;
            if ($_total)            $resp['total']             = $_total;

            return $resp;
        } //commonApiResponse
    }

    if (!function_exists('commonApiErrorResponse')) {
        function commonApiErrorResponse(
            $_msg = '',
            $_data = null,
            $_errors = null,
            $_code = 400,
        ) {
            $resp = array('success' => false);

            $resp['error']              = true;
            if ($_msg)                  $resp['message']           = $_msg;
            $resp['data']               = $_data != null ? $_data : [];
            $resp['errors']             = $_errors != null ? $_errors : [];

            return response()->json($resp, $_code);
        } //commonApiErrorResponse
    }

    function generateReference($refCounter, $item)
    {
        if ($item->reference === null && $refCounter != null) {
            $counter = $refCounter->next;
            $reference = $refCounter->format;

            $reference = str_replace([
                '{YY}',
                '{YYYY}',
                '{PREFIX}',
                '{ID}',
                '{NUMBER}',
                '{TEXT}'
            ], [
                now()->format('y'),
                now()->format('Y'),
                $refCounter->prefix,
                $item->id,
                str_pad($counter, $refCounter->digitals, '0', STR_PAD_LEFT),
                $refCounter->text ?? ''
            ], $reference);

            $item->reference = $reference;
            $item->save();
            $counter++;
            $refCounter->update(['next' => $counter]);
        }
    }



    if (!function_exists('modelHasAnyRelationData')) {
        function modelHasAnyRelationData($model)
        {
            if (!$model) return false;

            $class = new \ReflectionClass($model);

            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                // Only methods declared directly on this model
                if ($method->class !== $class->getName()) {
                    continue;
                }

                // Skip methods that expect arguments
                if ($method->getNumberOfParameters() > 0) {
                    continue;
                }

                try {
                    $return = $method->invoke($model);

                    // Only check HasOne or HasMany relations
                    if (
                        // $return instanceof \Illuminate\Database\Eloquent\Relations\HasOne ||
                        // $return instanceof \Illuminate\Database\Eloquent\Relations\HasMany ||

                        $return instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo ||
                        $return instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany
                    ) {

                        if ($return->exists()) {
                            return true;
                        }
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            return false;
        }
    }


    if (!function_exists('encryptValue')) {
        function encryptValue($plaintext, $key)
        {
            $key = substr(hash('sha256', $key, true), 0, 32); // ensure 32-byte key
            $iv = openssl_random_pseudo_bytes(16);           // 16-byte IV
            $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            return base64_encode($iv . $ciphertext);        // store IV + ciphertext together
        }
    }

    if (!function_exists('decryptValues')) {
        function decryptValues($ciphertext, $key)
        {
            try {
                $key = substr(hash('sha256', $key, true), 0, 32);
                $data = base64_decode($ciphertext);

                // Check if the decoded data is long enough to contain an IV and ciphertext
                if ($data === false || strlen($data) < 16) {
                    // Log::error('Invalid ciphertext or too short data.');
                    return null;
                }

                $iv = substr($data, 0, 16);
                $ciphertext_raw = substr($data, 16);

                $decrypted = openssl_decrypt($ciphertext_raw, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

                if ($decrypted === false || !is_string($decrypted)) {
                    Log::error('Decryption failed or result is not a valid string.');
                    return null;
                }

                // Convert to UTF-8 safely
                $decrypted_utf8 = mb_convert_encoding($decrypted, 'UTF-8', 'UTF-8');

                if (!mb_check_encoding($decrypted_utf8, 'UTF-8')) {
                    Log::error('Decrypted string is not valid UTF-8.');
                    return null;
                }

                return $decrypted_utf8;
            } catch (Exception $e) {
                Log::error('Error decrypting: ' . $e->getMessage());
                return null;
            }
        }
    }

    use Illuminate\Support\Facades\Http;
    use Illuminate\Http\Request;

    if (!function_exists('errorLog')) {
        function errorLog($data)
        {
            // if (env('LOG_URL')) {

            //     $response = Http::post(env('LOG_URL'), $data);

            //     return $response->json();
            // }

            // return response()->json(['error' => 'No log created'], 400);
            return response()->json(['error' => 'passed'], 200);
        }
    }

    if (!function_exists('lockModel')) {
        function lockModel($model, $user)
        {
            // if ($model->locked_by && $model->locked_by !== $user->id) {

            //     $model->load('locker');
            //     $locker = BusinessUser::where('id', $model->locked_by)->first();
            //     return [
            //         'locked'   => true,
            //         'by'       => $locker->firstname,
            //         'editable' => false,
            //         'message'  => "{$locker->firstname} est en train de modifier cette page."
            //     ];
            // }

            // $model->locked_by = $user->id;
            // $model->locked_at = now();
            // $model->save();

            return [
                'locked'   => true,
                'by'       => $user->firstname,
                'editable' => true,
                'message'  => "{$user->firstname} est en train de modifier cette page."
            ];
        }
    }

    if (!function_exists('unlockModel')) {
        function unlockModel($model)
        {
            // $model::unguard();
            // $model->locked_by = null;
            // $model->locked_at = null;
            // $model->save();
            // $model::reguard();
            // Log::info(["locker", $model->locked_by]);

            return [
                'locked'   => false,
                'message'  => "Le verrou a été libéré"
            ];
        }
    }
