<?php
namespace Bageur\Auth\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class perkakasController extends Controller
{

    public function p_upload(Request $request)
    {
        // base64
        $u              = new \Bageur\Auth\model\upload;
        $u->uuid        = $request->uuid;
        $u->group       = $request->group;
        $u->folder      = $request->info['folder'];
        $u->type        = $request->info['type'];
        $u->file        = \Bageur::base64_v2($request->info);
        $u->save();

        return ['status' => true];
    }

    public function p_upload_blob(Request $request)
    {
        // base64
        $u              = new \Bageur\Auth\model\upload;
        $u->uuid        = (string) Str::uuid();
        $u->folder      = $request->folder;
        $u->type        = $request->file('image')->getClientMimeType();
        $u->file        = \Bageur::blob($request->file('image'),$request->folder)['up'];
        $u->save();
        
        return ['status' => true,'data' => $u];
    }
    public function getgroup_p_upload($folder,$id)
    {
        $u              = \Bageur\Auth\model\upload::where('group',$id)->where('folder',$folder)->get();
        return $u;
    }
    public function delete_p_upload($id)
    {
        $u              = \Bageur\Auth\model\upload::where('uuid',$id)->first();
        \Storage::disk('public')->delete($u->folder.'/'.$u->file);
        $u              = \Bageur\Auth\model\upload::where('uuid',$id)->delete();
        return ['status' => true];
    }
}