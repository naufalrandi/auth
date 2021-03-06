<?php
namespace Bageur\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Bageur\Auth\Model\user;
use Bageur\Auth\Model\bageur_akses;
use Bageur\Auth\Model\deviceregister;
use Bageur\Company\Model\company;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $input = $request->all();
            $fieldType = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            if(! $token = Auth::attempt([$fieldType => $input['email'], 'password' => $input['password']])){
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        try {

            if (! $user = Auth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['token_expired'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent'], $e->getStatusCode());

        }

        return response()->json(compact('user'));
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {   
        $user = Auth::refresh();
        return $this->respondWithToken($user);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
     $id_level = Auth::user()->id_level;
     $data = bageur_akses::with(['sub_menu','action'])->whereNull('sub_id')->where('id_level',$id_level)->get();
      $listing = [];
      foreach ($data as $d => $rd) {
        $listing[$rd->link] = $rd->granted == 0 ? false : true;
        foreach ($rd->sub_menu as $a => $rs) {
         $listing[$rs->link] = $rs->granted == 0 ? false : true;
          foreach ($rs->action as $aa => $raa) {
            if($raa->nama == 'delete'){
              $listing[$rs->link.'-delete'] = $raa->granted == 0 ? false : true;
            }else{
              $listing[$raa->route] = $raa->granted == 0 ? false : true;
            }
          }
        }

        foreach ($rd->action as $a => $ra) {
         if($ra->nama == 'delete'){
              $listing[$rd->link.'-delete'] = $ra->granted == 0 ? false : true;
            }else{
              $listing[$ra->route] = $ra->granted == 0 ? false : true;
            }
        }

      }

      $menu        = bageur_akses::with(['sub_menu' => function($query){
                            $query->where('granted','1');
                     }])->whereNull('sub_id')->where('granted','1')
                        ->where('id_level',$id_level)
                        ->orderBy('urutan','asc')
                        ->get();

      $perusahaan = company::find(1);

        return response()->json([
            'access_token'  => $token,
            'token_type'    => 'bearer',
            'level_akses'   => $listing,
            'menu'          => $menu,
            'perusahaan'    => $perusahaan,
            'expires_in'    => auth()->factory()->getTTL() * 60
        ]);
    }

    public function device_add(Request $request){
        $rules    = [
            'fcmtoken'         => 'required|unique:\Bageur\Auth\Model\deviceregister,token',
        ];

        $messages = [
        ];

        $attributes = [
        ];
        $validator = \Validator::make($request->all(), $rules,$messages,$attributes);
        if (!$validator->fails()) {
            $new            = new deviceregister;
            $new->id_user   = @$request->user_id;  
            $new->token     = @$request->fcmtoken;  
            $new->topic     = @$request->topic;
            $new->save();
            return ['status' => true]; 
        }
    }
    
    public function getuserinfo()
    {
        // $db = user::where('id', Auth::user()->id)->first();
         return user::superadmin()->findOrFail(Auth::user()->id);
        // return $db;
    }

    public function edituser(Request $request)
    {
         $rules     = [
                        'name'                  => 'required|min:3',
                        'email'                 => 'required|unique:bgr_user,id,|email',
                        'username'              => 'required|unique:bgr_user,id,',
                        'userkode'              => 'required',
                        'password'              => 'nullable|min:3|confirmed',
                        'password_confirmation' => 'nullable',
                      ];

        $messages   = [];
        $attributes = [];

        $validator = Validator::make($request->all(), $rules,$messages,$attributes);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response(['status' => false ,'error'    =>  $errors->all()], 200);
        }else{
            // $users                     = user::get('id');
            // dd($users);
            // $user                      = user::find($users);
            $user                      = user::where('id', Auth::user()->id)->first();
            $user->username            = $request->username;
            $user->name                = $request->name;
            $user->email               = $request->email;
            if(!empty($request->file)){
                $upload                           = avatarbase64($request->file,'admin');
                $user->foto                      = $upload['up'];
                $user->foto_path                 = $upload['path'];
            }

            if(!empty($request->file2)){
                $upload                           = avatarbase64($request->file2,'photos');
                $user->foto                      = $upload['up'];
            }else{
                $upload['up']                     = $request->digital_signature;
            }
            $user->addons              = json_encode(['userkode' => $request->userkode,'digital_signature' => @$upload['up']]);
            $user->password            = Hash::make($request->password);
            $user->save();
            return response(['status' => true ,'text'    => 'has input'], 200);
        }
    }

}