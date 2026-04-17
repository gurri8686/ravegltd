<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDetailModel extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $table = 'company_details';
    protected $fillable = [
        'company_name', 'custemailomer_id', 'mobile', 'website', 'telephone', 'fax','address1', 'address2','country',
        'state', 'city', 'zipcode', 'vat_no', 'comp_reg_no', 'bank_name','account_no', 'ifsc_code','eirl_no','remarks'
    ];
	
	public static function info(){
		return self::first();
	}
}
