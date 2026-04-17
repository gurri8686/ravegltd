    @extends('layouts.main')

    @section('sidebar')
    @include('layouts.sidebars.admin')
    @endsection

    @push('stylesheets')
    <style>
    .content-header { display: none !important; }
    .form-btn-cancel {
        display: inline-flex; align-items: center; gap: 7px;
        height: 42px; padding: 0 22px; border-radius: 10px;
        border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b;
        font-weight: 600; font-size: 14px; text-decoration: none;
        transition: background 0.18s, border-color 0.18s, color 0.18s;
        cursor: pointer;
    }
    .form-btn-cancel:hover, .form-btn-cancel:active { background: #fff8f3; border-color: #f97316; color: #f97316; text-decoration: none; }
    .form-btn-save {
        display: inline-flex; align-items: center; gap: 7px;
        height: 42px; padding: 0 26px; border-radius: 10px;
        background: linear-gradient(135deg, #f97316, #ea580c); color: #fff;
        font-weight: 700; font-size: 14px; border: none;
        box-shadow: 0 4px 14px rgba(249,115,22,0.35); cursor: pointer;
        transition: box-shadow 0.18s, transform 0.18s; outline: none;
    }
    .form-btn-save:hover { box-shadow: 0 6px 20px rgba(249,115,22,0.45); transform: translateY(-1px); }
    .form-btn-save:active { transform: translateY(0); }
    </style>
    @endpush

    <?php
    //echo '<pre>';print_r($group); die;
    // echo $search_array['permission_module_id'];
    // die;


    ?>

    @push('scripts')
    <x-ajax-form-data
        formId='saveRoleForm'
        :route="route('management.roles.role.create.store')"
        method='POST'
    />
    @endpush

    @section('content')
    <section>
    <div style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;border-radius:14px;background:#F27420;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(242,116,32,0.25);">
                <i class="fa fa-users" style="color:#fff;font-size:20px;"></i>
            </div>
            <div>
                <h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Add New Role</h1>
                <p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Create a new role</p>
            </div>
        </div>
    </div>
    <div class="row match-height">
        <div class="col-md-12">
            <div class="card" style="height: 988.725px;">
                <!--<div class="card-header">
                    <h4 class="card-title" id="basic-layout-form">Add Role</h4>
                    <a class="heading-elements-toggle"><i class="fa fa-ellipsis-v font-medium-3"></i></a>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i class="feather icon-minus"></i></a></li>
                            <li><a data-action="reload"><i class="feather icon-rotate-cw"></i></a></li>
                            <li><a data-action="expand"><i class="feather icon-maximize"></i></a></li>
                            <li><a data-action="close"><i class="feather icon-x"></i></a></li>
                        </ul>
                    </div>
                </div>-->
                <div class="card-content collapse show">
                    <div class="card-body">

                        <form class="form" id="saveRoleForm" name="saveRoleForm">
                        @csrf
                            <div class="form-body">
                                <h4 class="form-section"><i class="feather icon-user"></i> Role Information</h4>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="name">Name<span class="text-danger">*</span></label>
                                            <input type="text" id="name" class="form-control" placeholder="Role Name" name="name">
                                            <div class="row"><div class="col-sm-12" data-validate="name"></div></div>
                                        </div>
                                    </div>

                                </div>

    </div>


                            <div style="display:flex;justify-content:flex-end;align-items:center;gap:12px;padding-top:20px;border-top:1px solid #f1f5f9;">
                                <a href="{{ route('management.settings.index') }}?tab=roles" class="form-btn-cancel">
                                    <i class="fa fa-times" style="font-size:11px;"></i> Cancel
                                </a>
                                <button type="submit" class="form-btn-save">
                                    <i class="fa fa-check"></i> Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </section>
    @endsection
