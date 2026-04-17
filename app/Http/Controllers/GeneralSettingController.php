<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;

class GeneralSettingController extends Controller
{
    
	public function index()
    {
        return view('general-setting.list');
    }
	
	public function list()
    {
        return response()->json(GeneralSetting::where('is_archive', 0)->get());
    }

    public function update(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            GeneralSetting::where('setting', $key)->update([
                'status' => $value ? 1 : 0,
            ]);
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }
}
